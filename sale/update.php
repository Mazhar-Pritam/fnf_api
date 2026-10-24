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
        throw new InvalidArgumentException('Sale ID is required');
    }

    $s = sale_read_input();

    $conn->begin_transaction();
    $started = true;

    sale_lock($conn, $id);
    sale_check_customer($conn, $s['customer_id']);

    // Change stock only by the difference between the old and the new quantities.
    // Selling more takes more stock out; selling less puts stock back.
    $old = sale_old_quantities($conn, $id);
    $new = [];
    foreach ($s['items'] as $item) {
        $new[$item['part_id']] = $item['quantity'];
    }
    // A part that already has a return cannot be reduced below the returned quantity.
    foreach (sale_returned_quantities($conn, $id) as $part_id => $returned) {
        if (($new[$part_id] ?? 0) < $returned) {
            throw new RuntimeException('Part #' . $part_id . ' already has a return of ' . $returned . '. The quantity cannot be lower than that. Delete or edit the return first');
        }
    }
    foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $part_id) {
        $delta = ($old[$part_id] ?? 0) - ($new[$part_id] ?? 0);
        sale_adjust_stock($conn, $part_id, $delta);
    }

    $old_costs = sale_old_costs($conn, $id);

    $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    sale_insert_items($conn, $id, $s['items'], $old_costs);

    $stmt = $conn->prepare("UPDATE sales SET invoice_no = ?, customer_id = ?, sale_date = ?, total_amount = ?, note = ? WHERE id = ?");
    $stmt->bind_param('sisdsi', $s['invoice_no'], $s['customer_id'], $s['sale_date'], $s['total_amount'], $s['note'], $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $id,
        'message' => 'Sale updated and stock adjusted'
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
