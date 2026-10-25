<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

try {
    if (!$id || $id < 1) {
        throw new InvalidArgumentException('Part ID is required');
    }

    // A part that is already used in a purchase, sale or return cannot be deleted,
    // because those records and the stock would be left pointing at a part that is gone.
    $usedIn = [
        'purchase_items' => 'a purchase',
        'sale_items' => 'a sale',
        'purchase_return_items' => 'a purchase return',
        'sale_return_items' => 'a sale return',
    ];
    foreach ($usedIn as $table => $label) {
        // Skip a table that does not exist (yet).
        if ($conn->query("SHOW TABLES LIKE '$table'")->num_rows === 0) {
            continue;
        }
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE part_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
        if ($count > 0) {
            throw new RuntimeException("This part is used in $label, so it cannot be deleted. You can make the part Inactive instead");
        }
    }

    // Delete the image file, then the part.
    $get_res = $cc->common_select('parts', '*', ['id' => $id]);
    if ($get_res['status'] && count($get_res['data']) > 0) {
        $part = $get_res['data'][0];
        if (!empty($part->image) && is_file('../' . $part->image)) {
            unlink('../' . $part->image);
        }
    }
    $res = $cc->common_delete('parts', ['id' => $id]);
} catch (Throwable $e) {
    $res = [
        'status' => false,
        'data' => [],
        'message' => $e->getMessage()
    ];
}

echo json_encode($res);
