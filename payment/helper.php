<?php
// Shared helpers for payment create / update.
// type 'receipt' = money received from a customer, type 'payment' = money paid to a supplier.

function payment_methods()
{
    return ['Cash', 'Bank', 'Mobile Banking'];
}

function payment_read_input()
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid request data');
    }

    $payment_no = trim((string)($input['payment_no'] ?? ''));
    $type = (string)($input['type'] ?? '');
    $party_id = (int)($input['party_id'] ?? 0);
    $amount = (isset($input['amount']) && is_numeric($input['amount'])) ? round((float)$input['amount'], 2) : 0;
    $method = (string)($input['method'] ?? '');
    $payment_date = trim((string)($input['payment_date'] ?? ''));
    $note = trim((string)($input['note'] ?? ''));

    if ($payment_no === '' || $party_id < 1 || $payment_date === '') {
        throw new InvalidArgumentException('Payment number, party and date are required');
    }
    if ($type !== 'receipt' && $type !== 'payment') {
        throw new InvalidArgumentException('Payment type is not valid');
    }
    if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be more than 0');
    }
    if (!in_array($method, payment_methods(), true)) {
        throw new InvalidArgumentException('Payment method is not valid');
    }
    $date = DateTime::createFromFormat('Y-m-d', $payment_date);
    if (!$date || $date->format('Y-m-d') !== $payment_date) {
        throw new InvalidArgumentException('Payment date is not valid');
    }

    return [
        'payment_no' => $payment_no,
        'type' => $type,
        'party_id' => $party_id,
        'amount' => $amount,
        'method' => $method,
        'payment_date' => $payment_date,
        'note' => $note,
    ];
}

// A receipt must belong to a customer, a payment to a supplier.
function payment_check_party($conn, $type, $party_id)
{
    $table = ($type === 'receipt') ? 'customers' : 'suppliers';
    $stmt = $conn->prepare("SELECT id FROM $table WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $party_id);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$found) {
        throw new InvalidArgumentException($type === 'receipt' ? 'Selected customer was not found' : 'Selected supplier was not found');
    }
}
