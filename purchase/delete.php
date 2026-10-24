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

    $conn->begin_transaction();
    $started = true;

    purchase_lock($conn, $id);

    // A purchase that has returns cannot be deleted. Delete its returns first.
    if (!empty(purchase_returned_quantities($conn, $id))) {
        throw new RuntimeException('This purchase has returns. Delete its returns first');
    }

    // Take the purchased quantities back out of stock.
    $old = purchase_old_quantities($conn, $id);
    foreach ($old as $part_id => $quantity) {
        purchase_adjust_stock($conn, $part_id, -$quantity);
    }

    $stmt = $conn->prepare("DELETE FROM purchase_items WHERE purchase_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    purchase_refresh_last_price($conn, array_keys($old));

    $conn->commit();

    $res = [
        'status' => true,
        'data' => [],
        'message' => 'Purchase deleted and stock adjusted'
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
