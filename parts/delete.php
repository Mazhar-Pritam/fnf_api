<?php
include '../connection.php';
include '../auth_check.php';

$id=$_GET['id'];
$get_res=$cc->common_select('parts','*',['id'=>$id]);
if($get_res['status'] && count($get_res['data'])>0){
    $part=$get_res['data'][0];
    if(!empty($part->image) && is_file('../'.$part->image)){
        unlink('../'.$part->image);
    }
}
$res=$cc->common_delete('parts',['id'=>$id]);

echo json_encode($res);
