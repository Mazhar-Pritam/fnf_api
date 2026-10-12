<?php

include 'connection.php';
$data = json_decode(file_get_contents("php://input"), true);

if (
	!is_array($data) ||
	!isset($data['email'], $data['password']) ||
	!is_string($data['email']) ||
	!is_string($data['password']) ||
	trim($data['email']) === '' ||
	trim($data['password']) === ''
) {
	echo json_encode(array(
		"status" => false,
		"message" => "Email and password are required."
	));
	exit;
}

$result = $cc->common_select("users", '*', array(
	"email" => trim($data['email']),
	"password" => sha1(trim($data['password']))
));

if ($result['status'] === true && count($result['data']) > 0) {
	$rdata = $result['data'][0];
	$remember_token = bin2hex(random_bytes(32));

	$update = $cc->common_update(
		"users",
		array("remember_token" => $remember_token),
		array("id" => $rdata->id)
	);
	if ($update['status'] !== true) {
		http_response_code(500);
		echo json_encode(array(
			"status" => false,
			"message" => "Unable to complete sign in. Please try again."
		));
		exit;
	}

	unset($rdata->password, $rdata->remember_token);
	echo json_encode(array(
		"status" => true,
		"message" => "Successful login.",
		"jwt" => $remember_token,
		"data" => json_encode($rdata)
	));
} else {
	echo json_encode(array(
		"status" => false,
		"message" => "Invalid email or password."
	));
}
