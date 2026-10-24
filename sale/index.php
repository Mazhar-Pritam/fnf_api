<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT s.id, s.invoice_no, s.customer_id, c.name AS customer_name,
                   s.sale_date, s.total_amount, s.paid_amount, s.note
            FROM sales s
            LEFT JOIN customers c ON c.id = s.customer_id
            WHERE s.deleted_at IS NULL
            ORDER BY s.sale_date DESC, s.id DESC";

    $rs = $cc->conn->query($sql);
    $data = [];
    while ($row = $rs->fetch_object()) {
        $data[] = $row;
    }

    $res = [
        'status' => true,
        'data' => $data,
        'message' => count($data) > 0 ? 'Records found' : 'No records found'
    ];
} catch (Throwable $e) {
    $res = [
        'status' => false,
        'data' => [],
        'message' => $e->getMessage()
    ];
}

echo json_encode($res);
