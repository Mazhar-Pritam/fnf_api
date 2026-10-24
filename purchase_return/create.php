<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$started = false;

try {
    $r = preturn_read_input();

    $conn->begin_transaction();
    $started = true;

    $parent = preturn_lock_parent($conn, $r['purchase_id']);
    $built = preturn_build_items($conn, $r['purchase_id'], $r['items'], 0);

    $party_id = (int)$parent['supplier_id'];
    $stmt = $conn->prepare("INSERT INTO purchase_returns (return_no, purchase_id, supplier_id, return_date, total_amount, note) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('siisds', $r['return_no'], $r['purchase_id'], $party_id, $r['return_date'], $built['total'], $r['note']);
    $stmt->execute();
    $return_id = $conn->insert_id;
    $stmt->close();

    preturn_insert_items($conn, $return_id, $built['items']);

    // Goods go back to the supplier, so stock goes down.
    foreach ($built['items'] as $item) {
        preturn_adjust_stock($conn, $item['part_id'], -$item['quantity']);
    }

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $return_id,
        'message' => 'Purchase return saved and stock updated'
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
