<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    list($from, $to) = report_dates();

    $stmt = $cc->conn->prepare(
        "SELECT s.id, s.invoice_no, s.sale_date AS doc_date, c.name AS party_name, s.total_amount,
                COALESCE(r.total, 0) AS returned
         FROM sales s
         LEFT JOIN customers c ON c.id = s.customer_id
         LEFT JOIN (SELECT sale_id, SUM(total_amount) AS total FROM sale_returns WHERE deleted_at IS NULL GROUP BY sale_id) r ON r.sale_id = s.id
         WHERE s.deleted_at IS NULL AND s.sale_date BETWEEN ? AND ?
         ORDER BY s.sale_date ASC, s.id ASC");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result();
    $data = [];
    while ($row = $rows->fetch_object()) {
        $data[] = $row;
    }
    $stmt->close();

    $res = ['status' => true, 'data' => $data, 'message' => count($data) > 0 ? 'Records found' : 'No records found'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
