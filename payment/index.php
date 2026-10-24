<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "SELECT p.id, p.payment_no, p.type, p.party_id, COALESCE(c.name, s.name) AS party_name,
                   p.amount, p.method, p.payment_date, p.note
            FROM payments p
            LEFT JOIN customers c ON p.type = 'receipt' AND c.id = p.party_id
            LEFT JOIN suppliers s ON p.type = 'payment' AND s.id = p.party_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.payment_date DESC, p.id DESC";

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
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
