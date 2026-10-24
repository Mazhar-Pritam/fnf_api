<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$started = false;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Purchase ID is required');
    }

    $p = purchase_read_input();

    $conn->begin_transaction();
    $started = true;

    purchase_lock($conn, $id);
    purchase_check_supplier($conn, $p['supplier_id']);

    // Change stock only by the difference between the old and the new quantities.
    $old = purchase_old_quantities($conn, $id);
    $new = [];
    foreach ($p['items'] as $item) {
        $new[$item['part_id']] = $item['quantity'];
    }
    // A part that already has a return cannot be reduced below the returned quantity.
    foreach (purchase_returned_quantities($conn, $id) as $part_id => $returned) {
        if (($new[$part_id] ?? 0) < $returned) {
            throw new RuntimeException('Part #' . $part_id . ' already has a return of ' . $returned . '. The quantity cannot be lower than that. Delete or edit the return first');
        }
    }
    foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $part_id) {
        $delta = ($new[$part_id] ?? 0) - ($old[$part_id] ?? 0);
        purchase_adjust_stock($conn, $part_id, $delta);
    }

    $stmt = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    purchase_insert_items($conn, $id, $p['items']);

    $stmt = $conn->prepare("UPDATE purchases SET invoice_no = ?, supplier_id = ?, purchase_date = ?, total_amount = ?, paid_amount = ?, note = ? WHERE id = ?");
    $stmt->bind_param('sisddsi', $p['invoice_no'], $p['supplier_id'], $p['purchase_date'], $p['total_amount'], $p['paid_amount'], $p['note'], $id);
    $stmt->execute();
    $stmt->close();

    purchase_refresh_last_price($conn, array_merge(array_keys($old), array_keys($new)));

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $id,
        'message' => 'Purchase updated and stock adjusted'
    ];
} catch (Throwable $e) {
    if ($started) {
        $conn->rollback();
    }
    $res = [
        'status' => false,
        'data' => [],
        'message' => $e->getMessage()
    ];
}

echo json_encode($res);
