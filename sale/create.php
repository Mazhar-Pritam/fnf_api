<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$started = false;

try {
    $s = sale_read_input();

    $conn->begin_transaction();
    $started = true;

    sale_check_customer($conn, $s['customer_id']);

    $stmt = $conn->prepare("INSERT INTO sales (invoice_no, customer_id, sale_date, total_amount, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sisds', $s['invoice_no'], $s['customer_id'], $s['sale_date'], $s['total_amount'], $s['note']);
    $stmt->execute();
    $sale_id = $conn->insert_id;
    $stmt->close();

    sale_insert_items($conn, $sale_id, $s['items']);

    // Take the sold quantities out of stock.
    foreach ($s['items'] as $item) {
        sale_adjust_stock($conn, $item['part_id'], -$item['quantity']);
    }

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $sale_id,
        'message' => 'Sale saved and stock updated'
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
