<?php
// Shared helpers for sale create / update / delete.

// Reads and validates the JSON body sent by the React sale form.
function sale_read_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request data');
    }

    $invoice_no = trim((string)($input['invoice_no'] ?? ''));
    $customer_id = (int)($input['customer_id'] ?? 0);
    $sale_date = trim((string)($input['sale_date'] ?? ''));
    $note = trim((string)($input['note'] ?? ''));

    if ($invoice_no === '' || $customer_id < 1 || $sale_date === '') {
        throw new InvalidArgumentException('Invoice number, customer and sale date are required');
    }

    $date = DateTime::createFromFormat('Y-m-d', $sale_date);
    if (!$date || $date->format('Y-m-d') !== $sale_date) {
        throw new InvalidArgumentException('Sale date is not valid');
    }

    $items = [];
    $seen = [];
    $total = 0;
    foreach ((array)($input['items'] ?? []) as $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Invalid item data');
        }
        $part_id = (int)($row['part_id'] ?? 0);
        $quantity = (int)($row['quantity'] ?? 0);
        $unit_price = (isset($row['unit_price']) && is_numeric($row['unit_price'])) ? (float)$row['unit_price'] : -1;

        if ($part_id < 1 || $quantity < 1 || $unit_price < 0) {
            throw new InvalidArgumentException('Each item needs a part, a quantity of at least 1 and a sale price');
        }
        if (isset($seen[$part_id])) {
            throw new InvalidArgumentException('The same part is added more than once. Please combine it into one row');
        }
        $seen[$part_id] = true;

        $items[] = [
            'part_id' => $part_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
        ];
        $total += $quantity * $unit_price;
    }

    if (empty($items)) {
        throw new InvalidArgumentException('Add at least one item');
    }

    return [
        'invoice_no' => $invoice_no,
        'customer_id' => $customer_id,
        'sale_date' => $sale_date,
        'note' => $note,
        'total_amount' => round($total, 2),
        'items' => $items,
    ];
}

function sale_check_customer($conn, $customer_id)
{
    $stmt = $conn->prepare("SELECT id FROM customers WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new InvalidArgumentException('Selected customer was not found');
    }
}

// Locks the sale row so two requests cannot change it at the same time.
function sale_lock($conn, $sale_id)
{
    $stmt = $conn->prepare("SELECT id FROM sales WHERE id = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new RuntimeException('Sale not found');
    }
}

// Returns [part_id => quantity] for the items already saved on a sale.
function sale_old_quantities($conn, $sale_id)
{
    $stmt = $conn->prepare("SELECT part_id, SUM(quantity) AS qty FROM sale_items WHERE sale_id = ? GROUP BY part_id");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $old = [];
    while ($row = $rows->fetch_assoc()) {
        $old[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $old;
}

// $old_costs = [part_id => cost] of the sale before it was edited, so an edit keeps the old cost.
function sale_insert_items($conn, $sale_id, $items, $old_costs = [])
{
    $costStmt = $conn->prepare("SELECT purchase_price FROM parts WHERE id = ?");
    $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, part_id, quantity, unit_price, cost_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $part_id = $item['part_id'];
        $quantity = $item['quantity'];
        $unit_price = $item['unit_price'];
        $line_total = round($quantity * $unit_price, 2);

        // Cost of this part: the old saved cost, or else the part's current purchase price.
        if (isset($old_costs[$part_id])) {
            $cost_price = $old_costs[$part_id];
        } else {
            $costStmt->bind_param('i', $part_id);
            $costStmt->execute();
            $part = $costStmt->get_result()->fetch_assoc();
            $cost_price = $part ? (float)$part['purchase_price'] : 0;
        }

        $stmt->bind_param('iiiddd', $sale_id, $part_id, $quantity, $unit_price, $cost_price, $line_total);
        $stmt->execute();
    }
    $stmt->close();
    $costStmt->close();
}

// Returns [part_id => cost_price] saved on a sale (only costs above 0).
function sale_old_costs($conn, $sale_id)
{
    $stmt = $conn->prepare("SELECT part_id, MAX(cost_price) AS cost FROM sale_items WHERE sale_id = ? GROUP BY part_id");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $costs = [];
    while ($row = $rows->fetch_assoc()) {
        if ((float)$row['cost'] > 0) {
            $costs[(int)$row['part_id']] = (float)$row['cost'];
        }
    }
    $stmt->close();
    return $costs;
}

// Changes stock for one part. A sale uses a negative number, a returned sale a positive one.
// Stock can never go below zero, so you cannot sell more than you have.
function sale_adjust_stock($conn, $part_id, $delta)
{
    if ($delta == 0) {
        return;
    }
    // Only parts that still exist can be sold.
    $active = $delta < 0 ? " AND deleted_at IS NULL" : "";
    $stmt = $conn->prepare("UPDATE parts SET stock_quantity = stock_quantity + ? WHERE id = ?" . $active . " AND stock_quantity + ? >= 0");
    $stmt->bind_param('iii', $delta, $part_id, $delta);
    $stmt->execute();
    $done = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$done) {
        $stmt = $conn->prepare("SELECT name, stock_quantity FROM parts WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param('i', $part_id);
        $stmt->execute();
        $part = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($part) {
            throw new RuntimeException('Not enough stock for "' . $part['name'] . '". Available: ' . $part['stock_quantity']);
        }
        throw new RuntimeException("Part #$part_id was not found");
    }
}

// [part_id => quantity] of this sale that was already returned (sale return module).
function sale_returned_quantities($conn, $sale_id)
{
    $stmt = $conn->prepare(
        "SELECT ri.part_id, SUM(ri.quantity) AS qty
         FROM sale_return_items ri
         JOIN sale_returns r ON r.id = ri.sale_return_id
         WHERE r.sale_id = ? AND r.deleted_at IS NULL
         GROUP BY ri.part_id"
    );
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $map = [];
    while ($row = $rows->fetch_assoc()) {
        $map[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $map;
}
