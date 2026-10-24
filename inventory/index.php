<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

try {
    // Quantities sold, and quantities returned by purchase / sale returns.
    // A table that does not exist yet is simply counted as 0.
    function table_exists($conn, $name)
    {
        return $conn->query("SHOW TABLES LIKE '$name'")->num_rows > 0;
    }

    $joins = "";
    $soldColumn = "0";
    $purchaseReturnColumn = "0";
    $saleReturnColumn = "0";

    if (table_exists($conn, 'sale_items')) {
        $joins .= " LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM sale_items GROUP BY part_id) sa ON sa.part_id = p.id";
        $soldColumn = "COALESCE(sa.qty, 0)";
    }
    if (table_exists($conn, 'purchase_return_items')) {
        $joins .= " LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM purchase_return_items GROUP BY part_id) pr ON pr.part_id = p.id";
        $purchaseReturnColumn = "COALESCE(pr.qty, 0)";
    }
    if (table_exists($conn, 'sale_return_items')) {
        $joins .= " LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM sale_return_items GROUP BY part_id) sr ON sr.part_id = p.id";
        $saleReturnColumn = "COALESCE(sr.qty, 0)";
    }

    $sql = "SELECT p.id, p.name, p.part_number, p.category_id, c.name AS category_name,
                   p.purchase_price, p.sale_price, p.stock_quantity,
                   COALESCE(pu.qty, 0) AS purchased_qty,
                   $purchaseReturnColumn AS purchase_return_qty,
                   $soldColumn AS sold_qty,
                   $saleReturnColumn AS sale_return_qty
            FROM parts p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN (SELECT part_id, SUM(quantity) AS qty FROM purchase_items GROUP BY part_id) pu ON pu.part_id = p.id
            $joins
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
