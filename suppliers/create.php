
<?php

include '../connection.php';
include '../auth_check.php';

$res = [];

if (!empty($_POST['supplier_name']) && !empty($_POST['phone'])) {

    $data = [
        'supplier_name' => trim($_POST['supplier_name']),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'phone' => $_POST['phone'],
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'opening_balance' => $_POST['opening_balance'] ?? '0'
    ];

    $res = $cc->common_insert('suppliers', $data);

} else {

    $res = [
        'status' => false,
        'message' => 'Supplier name and phone are required'
    ];

}

echo json_encode($res);