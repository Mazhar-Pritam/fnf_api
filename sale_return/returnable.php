<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$parent_id = filter_input(INPUT_GET, 'sale_id', FILTER_VALIDATE_INT);
$return_id = (int)filter_input(INPUT_GET, 'return_id', FILTER_VALIDATE_INT);

try {
    if (!$parent_id || $parent_id < 1) {
        throw new InvalidArgumentException('A valid sale is required');
    }

    $returned = sreturn_returned_quantities($conn, $parent_id, $return_id);

    $stmt = $conn->prepare(
        "SELECT i.part_id, SUM(i.quantity) AS bought_qty, MAX(i.unit_price) AS unit_price,
                MAX(p.name) AS part_name, MAX(p.part_number) AS part_number, MAX(p.stock_quantity) AS stock_quantity
         FROM sale_items i
         LEFT JOIN parts p ON p.id = i.part_id
         WHERE i.sale_id = ?
         GROUP BY i.part_id
         ORDER BY MIN(i.id)"
    );
    $stmt->bind_param('i', $parent_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    $data = [];
    while ($row = $rows->fetch_object()) {
        $row->returned_qty = $returned[(int)$row->part_id] ?? 0;
        $row->available_qty = (int)$row->bought_qty - (int)$row->returned_qty;
        $data[] = $row;
    }
    $stmt->close();

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
