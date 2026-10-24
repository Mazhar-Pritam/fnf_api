<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Payment ID is required');
    }

    $p = payment_read_input();
    payment_check_party($conn, $p['type'], $p['party_id']);

    $stmt = $conn->prepare("SELECT id FROM payments WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new RuntimeException('Payment not found');
    }

    $stmt = $conn->prepare("UPDATE payments SET payment_no = ?, type = ?, party_id = ?, amount = ?, method = ?, payment_date = ?, note = ? WHERE id = ?");
    $stmt->bind_param('ssidsssi', $p['payment_no'], $p['type'], $p['party_id'], $p['amount'], $p['method'], $p['payment_date'], $p['note'], $id);
    $stmt->execute();
    $stmt->close();

    $res = ['status' => true, 'data' => $id, 'message' => 'Payment updated'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
