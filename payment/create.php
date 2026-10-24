<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

try {
    $p = payment_read_input();
    payment_check_party($conn, $p['type'], $p['party_id']);

    $stmt = $conn->prepare("INSERT INTO payments (payment_no, type, party_id, amount, method, payment_date, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssidsss', $p['payment_no'], $p['type'], $p['party_id'], $p['amount'], $p['method'], $p['payment_date'], $p['note']);
    $stmt->execute();
    $payment_id = $conn->insert_id;
    $stmt->close();

    $res = ['status' => true, 'data' => $payment_id, 'message' => 'Payment saved'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
