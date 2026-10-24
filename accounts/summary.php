<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

// Runs a "SELECT SUM(...)" query and gives back the number (0 when there is nothing).
function one_total($conn, $sql)
{
    $row = $conn->query($sql)->fetch_row();
    return round((float)($row[0] ?? 0), 2);
}

try {
    $sales = one_total($conn, "SELECT SUM(total_amount) FROM sales WHERE deleted_at IS NULL");
    $saleReturns = one_total($conn, "SELECT SUM(total_amount) FROM sale_returns WHERE deleted_at IS NULL");
    $purchases = one_total($conn, "SELECT SUM(total_amount) FROM purchases WHERE deleted_at IS NULL");
    $purchaseReturns = one_total($conn, "SELECT SUM(total_amount) FROM purchase_returns WHERE deleted_at IS NULL");
    // Money received = separate receipts + money paid on the sale bills themselves.
    $received = one_total($conn, "SELECT SUM(amount) FROM payments WHERE type = 'receipt' AND deleted_at IS NULL")
        + one_total($conn, "SELECT SUM(paid_amount) FROM sales WHERE deleted_at IS NULL");
    // Money paid out = separate payments + money paid on the purchase bills themselves.
    $paid = one_total($conn, "SELECT SUM(amount) FROM payments WHERE type = 'payment' AND deleted_at IS NULL")
        + one_total($conn, "SELECT SUM(paid_amount) FROM purchases WHERE deleted_at IS NULL");
    $received = round($received, 2);
    $paid = round($paid, 2);
    $expenses = one_total($conn, "SELECT SUM(amount) FROM expenses WHERE deleted_at IS NULL");

    $data = [
        // money customers still have to pay us
        'receivable' => round($sales - $saleReturns - $received, 2),
        // money we still have to pay suppliers
        'payable' => round($purchases - $purchaseReturns - $paid, 2),
        'total_received' => $received,
        'total_paid' => $paid,
        'total_expenses' => $expenses,
        // money in hand = received - paid to suppliers - expenses
        'cash_balance' => round($received - $paid - $expenses, 2),
    ];

    $res = ['status' => true, 'data' => $data, 'message' => 'OK'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
