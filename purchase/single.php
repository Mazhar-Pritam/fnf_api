<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('A valid purchase ID is required');
    }

    $result = $cc->common_select('purchases', '*', ['id' => $id]);

    if ($result['status']) {
        $stmt = $cc->conn->prepare(
            "SELECT pi.part_id, pi.quantity, pi.unit_price, p.category_id, p.name AS part_name
             FROM purchase_items pi
             LEFT JOIN parts p ON p.id = pi.part_id
             WHERE pi.purchase_id = ?
             ORDER BY pi.id"
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
