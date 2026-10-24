<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('A valid sale ID is required');
    }

    $result = $cc->common_select('sales', '*', ['id' => $id]);

    if ($result['status']) {
        $stmt = $cc->conn->prepare(
            "SELECT si.part_id, si.quantity, si.unit_price, p.category_id, p.name AS part_name
             FROM sale_items si
             LEFT JOIN parts p ON p.id = si.part_id
             WHERE si.sale_id = ?
             ORDER BY si.id"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rows = $stmt->get_result();
        $items = [];
        while ($row = $rows->fetch_object()) {
            $items[] = $row;
        }
        $stmt->close();

        $result['data'][0]->items = $items;
    }
} catch (Throwable $e) {
    $result = [
        'status' => false,
        'data' => [],
        'message' => $e->getMessage()
    ];
}

echo json_encode($result);
