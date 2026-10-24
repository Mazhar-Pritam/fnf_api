<?php
// Shared helpers for purchase create / update / delete / single.

// Reads and validates the JSON body sent by the React purchase form.
function purchase_read_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request data');
    }

    $invoice_no = trim((string)($input['invoice_no'] ?? ''));
    $supplier_id = (int)($input['supplier_id'] ?? 0);
    $purchase_date = trim((string)($input['purchase_date'] ?? ''));
    $note = trim((string)($input['note'] ?? ''));

    if ($invoice_no === '' || $supplier_id < 1 || $purchase_date === '') {
        throw new InvalidArgumentException('Invoice number, supplier and purchase date are required');
    }

    $date = DateTime::createFromFormat('Y-m-d', $purchase_date);
    if (!$date || $date->format('Y-m-d') !== $purchase_date) {
        throw new InvalidArgumentException('Purchase date is not valid');
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
        $sale_price = (isset($row['sale_price']) && is_numeric($row['sale_price'])) ? (float)$row['sale_price'] : -1;

        if ($part_id < 1 || $quantity < 1 || $unit_price < 0 || $sale_price < 0) {
            throw new InvalidArgumentException('Each item needs a part, a quantity of at least 1, a purchase price and a sale price');
        }
        if (isset($seen[$part_id])) {
            throw new InvalidArgumentException('The same part is added more than once. Please combine it into one row');
        }
        $seen[$part_id] = true;

        $items[] = [
            'part_id' => $part_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'sale_price' => $sale_price,
        ];
        $total += $quantity * $unit_price;
    }

    if (empty($items)) {
        throw new InvalidArgumentException('Add at least one item');
    }

    // Paid amount: money paid at the time of the purchase. Empty means 0 (everything is due).
    $paid = (isset($input['paid_amount']) && $input['paid_amount'] !== '' && is_numeric($input['paid_amount']))
        ? round((float)$input['paid_amount'], 2) : 0;
    if ($paid < 0) {
        throw new InvalidArgumentException('Paid amount cannot be below 0');
    }
    if ($paid > round($total, 2)) {
        throw new InvalidArgumentException('Paid amount cannot be more than the total');
    }

    return [
        'invoice_no' => $invoice_no,
        'supplier_id' => $supplier_id,
        'purchase_date' => $purchase_date,
        'note' => $note,
        'total_amount' => round($total, 2),
        'paid_amount' => $paid,
        'items' => $items,
    ];
}

function purchase_check_supplier($conn, $supplier_id)
{
    $stmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $supplier_id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new InvalidArgumentException('Selected supplier was not found');
    }
}

// Locks the purchase row so two requests cannot change it at the same time.
function purchase_lock($conn, $purchase_id)
{
    $stmt = $conn->prepare("SELECT id FROM purchases WHERE id = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->bind_param('i', $purchase_id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new RuntimeException('Purchase not found');
    }
}

// Returns [part_id => quantity] for the items already saved on a purchase.
function purchase_old_quantities($conn, $purchase_id)
{
    $stmt = $conn->prepare("SELECT part_id, SUM(quantity) AS qty FROM purchase_items WHERE purchase_id = ? GROUP BY part_id");
    $stmt->bind_param('i', $purchase_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $old = [];
    while ($row = $rows->fetch_assoc()) {
        $old[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $old;
}

function purchase_insert_items($conn, $purchase_id, $items)
{
    $stmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, part_id, quantity, unit_price, sale_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $part_id = $item['part_id'];
        $quantity = $item['quantity'];
        $unit_price = $item['unit_price'];
        $sale_price = $item['sale_price'];
        $line_total = round($quantity * $unit_price, 2);
        $stmt->bind_param('iiiddd', $purchase_id, $part_id, $quantity, $unit_price, $sale_price, $line_total);
        $stmt->execute();
    }
    $stmt->close();
}

// Adds (or removes, if negative) stock for one part. Stock can never go below zero.
function purchase_adjust_stock($conn, $part_id, $delta)
{
    if ($delta == 0) {
        return;
    }
    // A new purchase may only add stock to parts that still exist.
    $active = $delta > 0 ? " AND deleted_at IS NULL" : "";
    $stmt = $conn->prepare("UPDATE parts SET stock_quantity = stock_quantity + ? WHERE id = ?" . $active . " AND stock_quantity + ? >= 0");
    $stmt->bind_param('iii', $delta, $part_id, $delta);
    $stmt->execute();
    $done = $stmt->affected_rows === 1;
    $stmt->close();
    if (!$done) {
        throw new RuntimeException("Stock could not be updated for part #$part_id. The part was not found, or its stock would go below zero (it may already be sold).");
    }
}


// Copies the prices of the part's latest purchase (latest purchase_date, then latest id)
// to the part: parts.purchase_price and parts.sale_price.
// A price of 0 is ignored, so old purchases without a sale price never wipe a part's price.
// If a part has no purchase left, its prices are not changed.
function purchase_refresh_last_price($conn, $part_ids)
{
    $find = $conn->prepare(
        "SELECT pi.unit_price, pi.sale_price
         FROM purchase_items pi
         JOIN purchases pu ON pu.id = pi.purchase_id
         WHERE pi.part_id = ?
         ORDER BY pu.purchase_date DESC, pu.id DESC
         LIMIT 1"
    );
    $set = $conn->prepare(
        "UPDATE parts
         SET purchase_price = IF(? > 0, ?, purchase_price),
             sale_price = IF(? > 0, ?, sale_price)
         WHERE id = ?"
    );

    foreach (array_unique($part_ids) as $part_id) {
        $part_id = (int)$part_id;
        $find->bind_param('i', $part_id);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        if ($row) {
            $buy = (float)$row['unit_price'];
            $sell = (float)$row['sale_price'];
            $set->bind_param('ddddi', $buy, $buy, $sell, $sell, $part_id);
            $set->execute();
        }
    }

    $find->close();
    $set->close();
}

// [part_id => quantity] of this purchase that was already returned (purchase return module).
function purchase_returned_quantities($conn, $purchase_id)
{
    $stmt = $conn->prepare(
        "SELECT ri.part_id, SUM(ri.quantity) AS qty
         FROM purchase_return_items ri
         JOIN purchase_returns r ON r.id = ri.purchase_return_id
         WHERE r.purchase_id = ? AND r.deleted_at IS NULL
         GROUP BY ri.part_id"
    );
    $stmt->bind_param('i', $purchase_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $map = [];
    while ($row = $rows->fetch_assoc()) {
        $map[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $map;
}
