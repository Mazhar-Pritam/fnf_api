<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

try {
    // The sales module is not built yet. Count sold quantity only if sale_items exists.
    $hasSales = $conn->query("SHOW TABLES LIKE 'sale_items'")->num_rows > 0;
    $soldJoin = $hasSales
        ? "LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM sale_items GROUP BY part_id) sa ON sa.part_id = p.id"
        : "";
    $soldColumn = $hasSales ? "COALESCE(sa.qty, 0)" : "0";

    $sql = "SELECT p.id, p.name, p.part_number, p.category_id, c.name AS category_name,
                   p.purchase_price, p.sale_price, p.stock_quantity,
                   COALESCE(pu.qty, 0) AS purchased_qty,
                   $soldColumn AS sold_qty
            FROM parts p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM purchase_items GROUP BY part_id) pu ON pu.part_id = p.id
            $soldJoin
            WHERE p.deleted_at IS NULL
            ORDER BY p.name ASC";

    $rs = $conn->query($sql);
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
