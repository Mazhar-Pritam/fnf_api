<?php
include '../connection.php';
include '../auth_check.php';
$result=$cc->common_select('customers');
echo json_encode($result);
