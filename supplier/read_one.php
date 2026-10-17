<?php

include '../connection.php';
include '../auth_check.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (!ctype_digit((string) $id)) {
    echo json_encode([
        'status' => false,
        'data' => [],
        'message' => 'A valid supplier ID is required'
    ]);
    exit;
}

$result = $cc->common_select('suppliers', '*', ['supplier_id' => $id]);

echo json_encode($result);
