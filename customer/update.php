<?php
include '../connection.php';
include '../auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$data = $_POST;

if (empty($data)) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $data = $jsonData;
    }
}

if (!$id || $id < 1) {
    $res = [
        'status' => false,
        'message' => 'Customer ID is required'
    ];
} elseif (empty($data)) {
    $res = [
        'status' => false,
        'message' => 'No customer data was provided'
    ];
} else {
    $res = $cc->common_update('customers', $data, ['id' => $id]);
}

echo json_encode($res);
