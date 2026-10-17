<?php
include '../connection.php';
include '../auth_check.php';

$res=[];
if(!empty($_POST['name'])){
    $res=$cc->common_insert('suppliers', $_POST);
}else{
    $res=[
        'status' => false,
        'message' => 'Supplier name is required'
    ];
}
echo json_encode($res);
