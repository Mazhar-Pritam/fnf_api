<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT r.id, r.return_no, r.sale_id, pa.invoice_no, r.customer_id, pt.name AS party_name,
                   r.return_date, r.total_amount, r.note
            FROM sale_returns r
            LEFT JOIN sales pa ON pa.id = r.sale_id
            LEFT JOIN customers pt ON pt.id = r.customer_id
            WHERE r.deleted_at IS NULL
            ORDER BY r.return_date DESC, r.id DESC";

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
