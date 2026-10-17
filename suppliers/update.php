<?php
<?php
include '../connection.php';
include '../auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$data = $_POST;

if (empty($data)) {
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (is_array($jsonData)) {
        $data = $jsonData;
    }
}

if (!$id || $id < 1) {
    echo json_encode([
        'status' => false,
        'message' => 'A valid supplier ID is required'
    ]);
    exit;
}

if (empty($data)) {
    echo json_encode([
        'status' => false,
        'message' => 'No supplier data was provided'
    ]);
    exit;
}

if ((empty($data['supplier_name'] ?? '') || trim((string) $data['supplier_name']) === '') || (empty($data['phone'] ?? '') || trim((string) $data['phone']) === '')) {
    echo json_encode([
        'status' => false,
        'message' => 'Supplier name and phone are required'
    ]);
    exit;
}

$existing = $cc->common_select('suppliers', '*', ['id' => $id]);

if (!$existing['status']) {
    echo json_encode($existing);
    exit;
}

if (isset($data['supplier_name'])) {
    $data['supplier_name'] = trim((string) $data['supplier_name']);
}
if (isset($data['company_name'])) {
    $data['company_name'] = trim((string) $data['company_name']);
}
if (isset($data['phone'])) {
    $data['phone'] = trim((string) $data['phone']);
}
if (isset($data['email'])) {
    $data['email'] = trim((string) $data['email']);
}
if (isset($data['address'])) {
    $data['address'] = trim((string) $data['address']);
}
if (isset($data['city'])) {
    $data['city'] = trim((string) $data['city']);
}

$res = $cc->common_update('suppliers', $data, ['id' => $id]);

echo json_encode($res);
