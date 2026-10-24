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
        throw new InvalidArgumentException('Return ID is required');
    }

    $r = preturn_read_input();

    $conn->begin_transaction();
    $started = true;

    // The purchase of a return cannot be changed, so it is always taken from the saved return.
    $saved = preturn_lock($conn, $id);
    $parent_id = (int)$saved['purchase_id'];
    preturn_lock_parent($conn, $parent_id);

    $built = preturn_build_items($conn, $parent_id, $r['items'], $id);

    // Change stock only by the difference between the old and the new return quantities.
    $old = preturn_old_quantities($conn, $id);
    $new = [];
    foreach ($built['items'] as $item) {
        $new[$item['part_id']] = $item['quantity'];
    }
    foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $part_id) {
        $diff = ($new[$part_id] ?? 0) - ($old[$part_id] ?? 0);
        preturn_adjust_stock($conn, $part_id, -$diff);
    }

    $stmt = $conn->prepare("DELETE FROM purchase_return_items WHERE purchase_return_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    preturn_insert_items($conn, $id, $built['items']);

    $stmt = $conn->prepare("UPDATE purchase_returns SET return_no = ?, return_date = ?, total_amount = ?, note = ? WHERE id = ?");
    $stmt->bind_param('ssdsi', $r['return_no'], $r['return_date'], $built['total'], $r['note'], $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $id,
        'message' => 'Purchase return updated and stock adjusted'
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
