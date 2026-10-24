<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('A valid return ID is required');
    }

    $result = $cc->common_select('purchase_returns', '*', ['id' => $id]);

    if ($result['status']) {
        $conn = $cc->conn;

        $stmt = $conn->prepare(
            "SELECT ri.part_id, ri.quantity, ri.unit_price, p.name AS part_name, p.part_number
             FROM purchase_return_items ri
             LEFT JOIN parts p ON p.id = ri.part_id
             WHERE ri.purchase_return_id = ?
             ORDER BY ri.id"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rows = $stmt->get_result();
        $items = [];
        while ($row = $rows->fetch_object()) {
            $items[] = $row;
        }
        $stmt->close();

        $parent_id = (int)$result['data'][0]->purchase_id;
        $stmt = $conn->prepare("SELECT invoice_no FROM purchases WHERE id = ?");
        $stmt->bind_param('i', $parent_id);
        $stmt->execute();
        $parent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $result['data'][0]->invoice_no = $parent ? $parent['invoice_no'] : '';
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
