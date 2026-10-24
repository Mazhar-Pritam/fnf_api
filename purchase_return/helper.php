<?php
// Shared helpers for purchase return create / update / delete.
// Rule: a return can only give back what was bought on the purchase, minus what was returned before.
// Stock goes DOWN when goods are returned to the supplier.

// Reads and validates the JSON body sent by the React return form.
function preturn_read_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request data');
    }

    $return_no = trim((string)($input['return_no'] ?? ''));
    $parent_id = (int)($input['purchase_id'] ?? 0);
    $return_date = trim((string)($input['return_date'] ?? ''));
    $note = trim((string)($input['note'] ?? ''));

    if ($return_no === '' || $parent_id < 1 || $return_date === '') {
        throw new InvalidArgumentException('Return number, purchase and return date are required');
    }

    $date = DateTime::createFromFormat('Y-m-d', $return_date);
    if (!$date || $date->format('Y-m-d') !== $return_date) {
        throw new InvalidArgumentException('Return date is not valid');
    }

    $items = [];
    $seen = [];
    foreach ((array)($input['items'] ?? []) as $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Invalid item data');
        }
        $part_id = (int)($row['part_id'] ?? 0);
        $quantity = (int)($row['quantity'] ?? 0);

        if ($part_id < 1 || $quantity < 1) {
            throw new InvalidArgumentException('Each item needs a part and a return quantity of at least 1');
        }
        if (isset($seen[$part_id])) {
            throw new InvalidArgumentException('The same part is added more than once');
        }
        $seen[$part_id] = true;
        $items[] = ['part_id' => $part_id, 'quantity' => $quantity];
    }

    if (empty($items)) {
        throw new InvalidArgumentException('Enter a return quantity for at least one part');
    }

    return [
        'return_no' => $return_no,
        'purchase_id' => $parent_id,
        'return_date' => $return_date,
        'note' => $note,
        'items' => $items,
    ];
}

// Locks the purchase row so it cannot be edited or returned twice at the same moment.
function preturn_lock_parent($conn, $parent_id)
{
    $stmt = $conn->prepare("SELECT id, supplier_id FROM purchases WHERE id = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        throw new InvalidArgumentException('The selected purchase was not found');
    }
    return $row;
}

// Locks the return row and gives back its purchase id.
function preturn_lock($conn, $return_id)
{
    $stmt = $conn->prepare("SELECT id, purchase_id FROM purchase_returns WHERE id = ? AND deleted_at IS NULL FOR UPDATE");
    $stmt->bind_param('i', $return_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        throw new RuntimeException('Return not found');
    }
    return $row;
}

// [part_id => ['quantity' => ..., 'unit_price' => ..., 'name' => ...]] of the original purchase.
function preturn_parent_items($conn, $parent_id)
{
    $stmt = $conn->prepare(
        "SELECT i.part_id, SUM(i.quantity) AS qty, MAX(i.unit_price) AS unit_price, MAX(p.name) AS name
         FROM purchase_items i
         LEFT JOIN parts p ON p.id = i.part_id
         WHERE i.purchase_id = ?
         GROUP BY i.part_id"
    );
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $map = [];
    while ($row = $rows->fetch_assoc()) {
        $map[(int)$row['part_id']] = [
            'quantity' => (int)$row['qty'],
            'unit_price' => (float)$row['unit_price'],
            'name' => $row['name'],
        ];
    }
    $stmt->close();
    return $map;
}

// [part_id => quantity] already returned on this purchase (leaving out one return, used when editing it).
function preturn_returned_quantities($conn, $parent_id, $exclude_return_id = 0)
{
    $stmt = $conn->prepare(
        "SELECT ri.part_id, SUM(ri.quantity) AS qty
         FROM purchase_return_items ri
         JOIN purchase_returns r ON r.id = ri.purchase_return_id
         WHERE r.purchase_id = ? AND r.deleted_at IS NULL AND r.id <> ?
         GROUP BY ri.part_id"
    );
    $stmt->bind_param('ii', $parent_id, $exclude_return_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $map = [];
    while ($row = $rows->fetch_assoc()) {
        $map[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $map;
}

// Checks every requested quantity against what can still be returned.
// The price of each line is always taken from the original purchase, never from the browser.
function preturn_build_items($conn, $parent_id, $requested, $exclude_return_id)
{
    $original = preturn_parent_items($conn, $parent_id);
    $returned = preturn_returned_quantities($conn, $parent_id, $exclude_return_id);

    $items = [];
    $total = 0;
    foreach ($requested as $row) {
        $part_id = $row['part_id'];
        $quantity = $row['quantity'];

        if (!isset($original[$part_id])) {
            throw new InvalidArgumentException("Part #$part_id is not on this purchase");
        }
        $left = $original[$part_id]['quantity'] - ($returned[$part_id] ?? 0);
        if ($quantity > $left) {
            throw new InvalidArgumentException('You can return at most ' . $left . ' of "' . $original[$part_id]['name'] . '"');
        }

        $unit_price = $original[$part_id]['unit_price'];
        $items[] = ['part_id' => $part_id, 'quantity' => $quantity, 'unit_price' => $unit_price];
        $total += $quantity * $unit_price;
    }

    return ['items' => $items, 'total' => round($total, 2)];
}

// Returns [part_id => quantity] for the items already saved on a return.
function preturn_old_quantities($conn, $return_id)
{
    $stmt = $conn->prepare("SELECT part_id, SUM(quantity) AS qty FROM purchase_return_items WHERE purchase_return_id = ? GROUP BY part_id");
    $stmt->bind_param('i', $return_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $old = [];
    while ($row = $rows->fetch_assoc()) {
        $old[(int)$row['part_id']] = (int)$row['qty'];
    }
    $stmt->close();
    return $old;
}

function preturn_insert_items($conn, $return_id, $items)
{
    $stmt = $conn->prepare("INSERT INTO purchase_return_items (purchase_return_id, part_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $part_id = $item['part_id'];
        $quantity = $item['quantity'];
        $unit_price = $item['unit_price'];
        $line_total = round($quantity * $unit_price, 2);
        $stmt->bind_param('iiidd', $return_id, $part_id, $quantity, $unit_price, $line_total);
        $stmt->execute();
    }
    $stmt->close();
}

// Changes stock for one part. Stock can never go below zero.
function preturn_adjust_stock($conn, $part_id, $delta)
{
    if ($delta == 0) {
        return;
    }
    $stmt = $conn->prepare("UPDATE parts SET stock_quantity = stock_quantity + ? WHERE id = ? AND stock_quantity + ? >= 0");
    $stmt->bind_param('iii', $delta, $part_id, $delta);
    $stmt->execute();
    $done = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$done) {
        $stmt = $conn->prepare("SELECT name, stock_quantity FROM parts WHERE id = ?");
        $stmt->bind_param('i', $part_id);
        $stmt->execute();
        $part = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($part) {
            throw new RuntimeException('Not enough stock of "' . $part['name'] . '" (in stock: ' . $part['stock_quantity'] . '). Stock cannot go below zero, the goods may already be sold');
        }
        throw new RuntimeException("Part #$part_id was not found");
    }
}
