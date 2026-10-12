<?php

include '../connection.php';
include '../auth_check.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (!ctype_digit((string) $id)) {
    echo json_encode([
        'status' => false,
        'message' => 'A valid supplier ID is required'
    ]);
    exit;
}

if (empty($_POST['supplier_name']) || empty($_POST['phone'])) {
    echo json_encode([
        'status' => false,
        'message' => 'Supplier name and phone are required'
    ]);
    exit;
}

$existing = $cc->common_select('suppliers', '*', ['supplier_id' => $id]);

if (!$existing['status']) {
    echo json_encode($existing);
    exit;
}

$data = [
    'supplier_name' => trim($_POST['supplier_name']),
    'company_name' => trim($_POST['company_name'] ?? ''),
    'phone' => $_POST['phone'],
    'email' => $_POST['email'] ?? '',
    'address' => $_POST['address'] ?? '',
    'city' => $_POST['city'] ?? '',
    'opening_balance' => $_POST['opening_balance'] ?? '0'
];

$result = $cc->common_update('suppliers', $data, ['supplier_id' => $id]);

echo json_encode($result);
