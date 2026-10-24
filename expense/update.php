<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Expense ID is required');
    }

    $e = expense_read_input();

    $stmt = $conn->prepare("SELECT id FROM expenses WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new RuntimeException('Expense not found');
    }

    $stmt = $conn->prepare("UPDATE expenses SET expense_date = ?, category = ?, amount = ?, method = ?, note = ? WHERE id = ?");
    $stmt->bind_param('ssdssi', $e['expense_date'], $e['category'], $e['amount'], $e['method'], $e['note'], $id);
    $stmt->execute();
    $stmt->close();

    $res = ['status' => true, 'data' => $id, 'message' => 'Expense updated'];
} catch (Throwable $ex) {
    $res = ['status' => false, 'data' => [], 'message' => $ex->getMessage()];
}

echo json_encode($res);
