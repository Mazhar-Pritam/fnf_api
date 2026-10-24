<?php
include '../connection.php';
include '../auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $res = ['status' => false, 'data' => [], 'message' => 'A valid expense ID is required'];
} else {
    $res = $cc->common_select('expenses', '*', ['id' => $id]);
}

echo json_encode($res);
