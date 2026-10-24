<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Payment ID is required');
    }

    $stmt = $cc->conn->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $res = ['status' => true, 'data' => [], 'message' => 'Payment deleted'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
