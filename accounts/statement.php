<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Statement of one customer or supplier: every bill, return and payment in date order,
// with a running balance.
//   charge = makes the balance go UP   (a sale to the customer / a purchase from the supplier)
//   credit = makes the balance go DOWN (a return, or money received / paid)
$type = $_GET['type'] ?? '';
$party_id = filter_input(INPUT_GET, 'party_id', FILTER_VALIDATE_INT);

try {
    if (!$party_id || $party_id < 1) {
        throw new InvalidArgumentException('A valid party is required');
    }

    // Every query has an "ord" column: 0 = a bill (goes first on the same day), 1 = money / returns.
    if ($type === 'customer') {
        $table = 'customers';
        $sql = "SELECT sale_date AS d, 'Sale' AS kind, invoice_no AS ref, total_amount AS charge, 0 AS credit, id, 0 AS ord
                FROM sales WHERE customer_id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT sale_date, 'Paid at Sale', invoice_no, 0, paid_amount, id, 1
                FROM sales WHERE customer_id = ? AND deleted_at IS NULL AND paid_amount > 0
                UNION ALL
                SELECT return_date, 'Sale Return', return_no, 0, total_amount, id, 1
                FROM sale_returns WHERE customer_id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT payment_date, 'Payment Received', payment_no, 0, amount, id, 1
                FROM payments WHERE type = 'receipt' AND party_id = ? AND deleted_at IS NULL
                ORDER BY d ASC, ord ASC, id ASC";
    } elseif ($type === 'supplier') {
        $table = 'suppliers';
        $sql = "SELECT purchase_date AS d, 'Purchase' AS kind, invoice_no AS ref, total_amount AS charge, 0 AS credit, id, 0 AS ord
                FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT purchase_date, 'Paid at Purchase', invoice_no, 0, paid_amount, id, 1
                FROM purchases WHERE supplier_id = ? AND deleted_at IS NULL AND paid_amount > 0
                UNION ALL
                SELECT return_date, 'Purchase Return', return_no, 0, total_amount, id, 1
                FROM purchase_returns WHERE supplier_id = ? AND deleted_at IS NULL
                UNION ALL
                SELECT payment_date, 'Payment Made', payment_no, 0, amount, id, 1
                FROM payments WHERE type = 'payment' AND party_id = ? AND deleted_at IS NULL
                ORDER BY d ASC, ord ASC, id ASC";
    } else {
        throw new InvalidArgumentException('Type must be customer or supplier');
    }

    $conn = $cc->conn;

    $stmt = $conn->prepare("SELECT id, name, phone, address FROM $table WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param('i', $party_id);
    $stmt->execute();
    $party = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$party) {
        throw new InvalidArgumentException('Party not found');
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $party_id, $party_id, $party_id, $party_id);
    $stmt->execute();
    $rows = $stmt->get_result();

    $balance = 0;
    $totalCharge = 0;
    $totalCredit = 0;
    $items = [];
    while ($row = $rows->fetch_assoc()) {
        $balance += (float)$row['charge'] - (float)$row['credit'];
        $totalCharge += (float)$row['charge'];
        $totalCredit += (float)$row['credit'];
        $row['balance'] = round($balance, 2);
        $items[] = $row;
    }
    $stmt->close();

    $res = [
        'status' => true,
        'data' => [
            'party' => $party,
            'rows' => $items,
            'total_charge' => round($totalCharge, 2),
            'total_credit' => round($totalCredit, 2),
            'balance' => round($balance, 2),
        ],
        'message' => 'OK'
    ];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
