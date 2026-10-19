<?php
include '../connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$uploadedPath = null;

try {
    include '../auth_check.php';

    $data = $_POST;
    if (empty($data)) {
        $rawBody = file_get_contents('php://input');
        if ($rawBody !== '') {
            $jsonData = json_decode($rawBody, true);
            if (!is_array($jsonData)) {
                throw new InvalidArgumentException('Invalid request data');
            }
            $data = $jsonData;
        }
    }

    $allowedFields = [
        'name',
        'part_number',
        'category_id',
        'purchase_price',
        'sale_price',
        'description',
        'status',
        'image'
    ];
    $unknownFields = array_diff(array_keys($data), $allowedFields, ['stock_quantity']);
    if (!empty($unknownFields)) {
        throw new InvalidArgumentException('Unsupported field: ' . reset($unknownFields));
    }

    // Stock is changed only by purchases and sales, never by hand.
    unset($data['stock_quantity']);

    $name = isset($data['name']) && is_scalar($data['name']) ? trim((string)$data['name']) : '';
    if ($name === '') {
        throw new InvalidArgumentException('Part name is required');
    }
    if (strlen($name) > 255) {
        throw new InvalidArgumentException('Part name cannot exceed 255 characters');
    }

    $categoryValue = $data['category_id'] ?? null;
    if (!is_scalar($categoryValue)) {
        throw new InvalidArgumentException('A valid category is required');
    }
    $categoryId = filter_var($categoryValue, FILTER_VALIDATE_INT);
    if ($categoryId === false || $categoryId < 1) {
        throw new InvalidArgumentException('A valid category is required');
    }

    $categoryResult = $cc->common_select('categories', 'id', ['id' => $categoryId]);
    if (!$categoryResult['status']) {
        throw new InvalidArgumentException('Selected category was not found');
    }

    $insertData = [
        'name' => $name,
        'category_id' => $categoryId
    ];

    if (array_key_exists('part_number', $data)) {
        if (!is_scalar($data['part_number'])) {
            throw new InvalidArgumentException('Part number must be a string');
        }
        $partNumber = trim((string)$data['part_number']);
        if (strlen($partNumber) > 100) {
            throw new InvalidArgumentException('Part number cannot exceed 100 characters');
        }
        $insertData['part_number'] = $partNumber;
    }

    foreach (['purchase_price', 'sale_price'] as $priceField) {
        if (!array_key_exists($priceField, $data) || $data[$priceField] === '') {
            continue;
        }
        if (!is_scalar($data[$priceField]) || !is_numeric($data[$priceField])) {
            throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $priceField)) . ' must be a valid amount');
        }
        $price = (float)$data[$priceField];
        if (!is_finite($price) || $price < 0 || $price > 9999999999.99) {
            throw new InvalidArgumentException(ucfirst(str_replace('_', ' ', $priceField)) . ' must be between 0 and 9999999999.99');
        }
        $insertData[$priceField] = $price;
    }

    if (array_key_exists('description', $data)) {
        if (!is_scalar($data['description'])) {
            throw new InvalidArgumentException('Description must be text');
        }
        $insertData['description'] = trim((string)$data['description']);
    }

    if (array_key_exists('status', $data) && $data['status'] !== '') {
        if (!is_scalar($data['status'])) {
            throw new InvalidArgumentException('Status must be 0 or 1');
        }
        $status = filter_var($data['status'], FILTER_VALIDATE_INT);
        if ($status === false || !in_array($status, [0, 1], true)) {
            throw new InvalidArgumentException('Status must be 0 or 1');
        }
        $insertData['status'] = $status;
    }

    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed with code ' . $_FILES['image']['error']);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('Unable to validate the uploaded image');
        }
        $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);

        $imageExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        if (!isset($imageExtensions[$mimeType])) {
            throw new InvalidArgumentException('Image must be a JPEG, PNG, GIF or WebP file');
        }

        $fileName = bin2hex(random_bytes(12)) . '.' . $imageExtensions[$mimeType];
        $uploadedPath = 'part/' . $fileName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . DIRECTORY_SEPARATOR . $fileName)) {
            throw new RuntimeException('Unable to save the uploaded image');
        }
        $insertData['image'] = $uploadedPath;
    } elseif (isset($data['image'])) {
        if (!is_scalar($data['image'])) {
            throw new InvalidArgumentException('Image must be uploaded as a file');
        }
        if (trim((string)$data['image']) !== '') {
            throw new InvalidArgumentException('Image must be uploaded as a file');
        }
    }

    $res = $cc->common_insert('parts', $insertData);
    if (!$res['status'] && $uploadedPath !== null) {
        unlink(__DIR__ . DIRECTORY_SEPARATOR . basename($uploadedPath));
    }
} catch (Throwable $e) {
    if ($uploadedPath !== null) {
        $imageFile = __DIR__ . DIRECTORY_SEPARATOR . basename($uploadedPath);
        if (is_file($imageFile)) {
            unlink($imageFile);
        }
    }
    $res = [
        'status' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($res);
