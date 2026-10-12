
<?php

include '../connection.php';
include '../auth_check.php';

$res = [];

if (!empty($_POST['name']) && !empty($_POST['phone'])) {

    $data = [
        'name' => $_POST['name'],
        'contact_person' => $_POST['contact_person'] ?? '',
        'phone' => $_POST['phone'],
        'email' => $_POST['email'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];

    $res = $cc->common_insert('suppliers', $data);

} else {

    $res = [
        'status' => false,
        'message' => 'Supplier name and phone are required'
    ];

}

echo json_encode($res);