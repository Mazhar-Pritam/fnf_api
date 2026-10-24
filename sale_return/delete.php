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

    $conn->begin_transaction();
    $started = true;

    $saved = sreturn_lock($conn, $id);
    sreturn_lock_parent($conn, (int)$saved['sale_id']);

    // Undo the stock change of this return.
    $old = sreturn_old_quantities($conn, $id);
    foreach ($old as $part_id => $quantity) {
        sreturn_adjust_stock($conn, $part_id, -$quantity);
    }

    $stmt = $conn->prepare("DELETE FROM sale_return_items WHERE sale_return_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM sale_returns WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $res = [
        'status' => true,
        'data' => [],
        'message' => 'Sale return deleted and stock adjusted'
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
