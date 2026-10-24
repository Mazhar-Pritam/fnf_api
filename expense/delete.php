<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Expense ID is required');
    }

    $stmt = $cc->conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $res = ['status' => true, 'data' => [], 'message' => 'Expense deleted'];
} catch (Throwable $ex) {
    $res = ['status' => false, 'data' => [], 'message' => $ex->getMessage()];
}

echo json_encode($res);
