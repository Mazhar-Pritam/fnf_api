<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$started = false;

try {
    $r = sreturn_read_input();

    $conn->begin_transaction();
    $started = true;

    $parent = sreturn_lock_parent($conn, $r['sale_id']);
    $built = sreturn_build_items($conn, $r['sale_id'], $r['items'], 0);

    $party_id = (int)$parent['customer_id'];
    $stmt = $conn->prepare("INSERT INTO sale_returns (return_no, sale_id, customer_id, return_date, total_amount, note) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('siisds', $r['return_no'], $r['sale_id'], $party_id, $r['return_date'], $built['total'], $r['note']);
    $stmt->execute();
    $return_id = $conn->insert_id;
    $stmt->close();

    sreturn_insert_items($conn, $return_id, $built['items']);

    // Goods come back from the customer, so stock goes up.
    foreach ($built['items'] as $item) {
        sreturn_adjust_stock($conn, $item['part_id'], $item['quantity']);
    }

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $return_id,
        'message' => 'Sale return saved and stock updated'
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
