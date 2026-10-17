<?php
include '../connection.php';
include '../auth_check.php';

$id=$_GET['id'];
$res=$cc->common_delete('purchases',['id'=>$id]);

echo json_encode($res);
