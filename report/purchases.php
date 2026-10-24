<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    list($from, $to) = report_dates();

    $stmt = $cc->conn->prepare(
        "SELECT p.id, p.invoice_no, p.purchase_date AS doc_date, s.name AS party_name, p.total_amount,
                COALESCE(r.total, 0) AS returned
         FROM purchases p
         LEFT JOIN suppliers s ON s.id = p.supplier_id
         LEFT JOIN (SELECT purchase_id, SUM(total_amount) AS total FROM purchase_returns WHERE deleted_at IS NULL GROUP BY purchase_id) r ON r.purchase_id = p.id
         WHERE p.deleted_at IS NULL AND p.purchase_date BETWEEN ? AND ?
         ORDER BY p.purchase_date ASC, p.id ASC");
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
