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

    $conn->begin_transaction();
    $started = true;

    sale_lock($conn, $id);

    // A sale that has returns cannot be deleted. Delete its returns first.
    if (!empty(sale_returned_quantities($conn, $id))) {
        throw new RuntimeException('This sale has returns. Delete its returns first');
    }

    // Put the sold quantities back into stock.
    $old = sale_old_quantities($conn, $id);
    foreach ($old as $part_id => $quantity) {
        sale_adjust_stock($conn, $part_id, $quantity);
    }

    $stmt = $conn->prepare("DELETE FROM sale_items WHERE sale_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $res = [
        'status' => true,
        'data' => [],
        'message' => 'Sale deleted and stock returned'
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
