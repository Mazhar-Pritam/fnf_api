<?php
include '../connection.php';
include '../auth_check.php';

$res=[];
if(!empty($_POST['invoice_no']) && !empty($_POST['supplier_id'])){
    $res=$cc->common_insert('purchases', $_POST);
}else{
    $res=[
        'status' => false,
        'message' => 'Invoice number and supplier are required'
    ];
}
echo json_encode($res);
