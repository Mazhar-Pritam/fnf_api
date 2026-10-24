<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// type=customer : what every customer still owes us
// type=supplier : what we still owe every supplier
// balance = billed - returned - paid
$type = $_GET['type'] ?? '';

try {
    if ($type === 'customer') {
        $party = 'customers';
        $bills = 'sales';
        $returns = 'sale_returns';
        $key = 'customer_id';
        $payType = 'receipt';
    } elseif ($type === 'supplier') {
        $party = 'suppliers';
        $bills = 'purchases';
        $returns = 'purchase_returns';
        $key = 'supplier_id';
        $payType = 'payment';
    } else {
        throw new InvalidArgumentException('Type must be customer or supplier');
    }

    $sql = "SELECT t.id, t.name, t.phone,
                   COALESCE(b.total, 0) AS billed,
                   COALESCE(r.total, 0) AS returned,
                   COALESCE(p.total, 0) AS paid,
                   COALESCE(b.total, 0) - COALESCE(r.total, 0) - COALESCE(p.total, 0) AS balance
            FROM $party t
            LEFT JOIN (SELECT $key AS pid, SUM(total_amount) AS total FROM $bills WHERE deleted_at IS NULL GROUP BY $key) b ON b.pid = t.id
            LEFT JOIN (SELECT $key AS pid, SUM(total_amount) AS total FROM $returns WHERE deleted_at IS NULL GROUP BY $key) r ON r.pid = t.id
            LEFT JOIN (SELECT party_id AS pid, SUM(amount) AS total FROM payments WHERE type = '$payType' AND deleted_at IS NULL GROUP BY party_id) p ON p.pid = t.id
            WHERE t.deleted_at IS NULL
            ORDER BY t.name ASC";

    $rs = $cc->conn->query($sql);
    $data = [];
    while ($row = $rs->fetch_object()) {
        $data[] = $row;
    }

    $res = ['status' => true, 'data' => $data, 'message' => count($data) > 0 ? 'Records found' : 'No records found'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
