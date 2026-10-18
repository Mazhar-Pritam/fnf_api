<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$started = false;

try {
    $p = purchase_read_input();

    $conn->begin_transaction();
    $started = true;

    purchase_check_supplier($conn, $p['supplier_id']);

    $stmt = $conn->prepare("INSERT INTO purchases (invoice_no, supplier_id, purchase_date, total_amount, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sisds', $p['invoice_no'], $p['supplier_id'], $p['purchase_date'], $p['total_amount'], $p['note']);
    $stmt->execute();
    $purchase_id = $conn->insert_id;
    $stmt->close();

    purchase_insert_items($conn, $purchase_id, $p['items']);

    foreach ($p['items'] as $item) {
        purchase_adjust_stock($conn, $item['part_id'], $item['quantity']);
    }

    $conn->commit();

    $res = [
        'status' => true,
        'data' => $purchase_id,
        'message' => 'Purchase saved and stock updated'
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
