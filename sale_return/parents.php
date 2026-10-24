<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT pa.id, pa.invoice_no, pa.sale_date AS doc_date, pa.total_amount, pt.name AS party_name
            FROM sales pa
            LEFT JOIN customers pt ON pt.id = pa.customer_id
            WHERE pa.deleted_at IS NULL
            ORDER BY pa.sale_date DESC, pa.id DESC";

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
