<?php
include '../connection.php';
include '../auth_check.php';

$id=$_GET['id'];
$get_res=$cc->common_select('suppliers','*',['id'=>$id]);
if($get_res['status'] && count($get_res['data'])>0){
    $supplier=$get_res['data'][0];
    if(file_exists('../'.$supplier->image)){
        unlink('../'.$supplier->image);
    }
}
$res=$cc->common_delete('suppliers',['id'=>$id]);

echo json_encode($res);