<?php
include '../connection.php';
include '../auth_check.php';

$res=[];
if(!empty($_POST['name'])){
    $res=$cc->common_insert('customers', $_POST);
}else{
    $res=[
        'status' => false,
        'message' => 'Customer name is required'
    ];
}
echo json_encode($res);
