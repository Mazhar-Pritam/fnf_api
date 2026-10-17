<?php
include '../connection.php';
include '../auth_check.php';

$uploadDir = '../';
if (!empty($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $name = rand().basename($_FILES['image']['name']);
    $targetPath = 'part/'.$name;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $targetPath)) {
        $_POST['image']=$targetPath;
    }
}
$res=[];
if(!empty($_POST['name']) && !empty($_POST['category_id'])){
    $res=$cc->common_insert('parts', $_POST);
}else{
    $res=[
        'status' => false,
        'message' => 'Part name and category are required'
    ];
}
echo json_encode($res);
