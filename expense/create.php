<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

try {
    $e = expense_read_input();

    $stmt = $conn->prepare("INSERT INTO expenses (expense_date, category, amount, method, note) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssdss', $e['expense_date'], $e['category'], $e['amount'], $e['method'], $e['note']);
    $stmt->execute();
    $expense_id = $conn->insert_id;
    $stmt->close();

    $res = ['status' => true, 'data' => $expense_id, 'message' => 'Expense saved'];
} catch (Throwable $ex) {
    $res = ['status' => false, 'data' => [], 'message' => $ex->getMessage()];
}

echo json_encode($res);
