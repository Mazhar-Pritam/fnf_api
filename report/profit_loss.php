<?php
include '../connection.php';
include '../auth_check.php';
include 'helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

// Runs a query with two dates (from, to) and gives back the first number.
function total_between($conn, $sql, $from, $to)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return round((float)($row[0] ?? 0), 2);
}

try {
    list($from, $to) = report_dates();

    // Cost of one sold piece = the cost saved on the sale item.
    // Old sales have 0 saved, so the part's current purchase price is used for them.
    $cost = "IF(si.cost_price > 0, si.cost_price, COALESCE(p.purchase_price, 0))";

    // ---- Sales and returns ----
    $sales = total_between($conn,
        "SELECT SUM(total_amount) FROM sales WHERE deleted_at IS NULL AND sale_date BETWEEN ? AND ?", $from, $to);
    $saleReturns = total_between($conn,
        "SELECT SUM(total_amount) FROM sale_returns WHERE deleted_at IS NULL AND return_date BETWEEN ? AND ?", $from, $to);

    // ---- Cost of goods sold (what the sold parts cost us) ----
    $costSold = total_between($conn,
        "SELECT SUM(si.quantity * $cost)
         FROM sale_items si
         JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
         LEFT JOIN parts p ON p.id = si.part_id
         WHERE s.sale_date BETWEEN ? AND ?", $from, $to);
    // Returned parts come back into stock, so their cost is taken off again.
    $costReturned = total_between($conn,
        "SELECT SUM(sri.quantity * $cost)
         FROM sale_return_items sri
         JOIN sale_returns sr ON sr.id = sri.sale_return_id AND sr.deleted_at IS NULL
         JOIN sale_items si ON si.sale_id = sr.sale_id AND si.part_id = sri.part_id
         LEFT JOIN parts p ON p.id = sri.part_id
         WHERE sr.return_date BETWEEN ? AND ?", $from, $to);

    $revenue = round($sales - $saleReturns, 2);
    $cogs = round($costSold - $costReturned, 2);
    $grossProfit = round($revenue - $cogs, 2);

    // ---- Expenses by category ----
    $stmt = $conn->prepare("SELECT category, SUM(amount) AS total FROM expenses WHERE deleted_at IS NULL AND expense_date BETWEEN ? AND ? GROUP BY category ORDER BY category");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result();
    $expenses = [];
    $totalExpenses = 0;
    while ($row = $rows->fetch_assoc()) {
        $row['total'] = round((float)$row['total'], 2);
        $totalExpenses += $row['total'];
        $expenses[] = $row;
    }
    $stmt->close();
    $totalExpenses = round($totalExpenses, 2);

    // ---- Profit of every product ----
    $products = [];

    $stmt = $conn->prepare(
        "SELECT si.part_id, MAX(p.name) AS name, SUM(si.quantity) AS qty, SUM(si.quantity * si.unit_price) AS revenue,
                SUM(si.quantity * $cost) AS cost
         FROM sale_items si
         JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
         LEFT JOIN parts p ON p.id = si.part_id
         WHERE s.sale_date BETWEEN ? AND ?
         GROUP BY si.part_id");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        $products[(int)$row['part_id']] = [
            'part_id' => (int)$row['part_id'],
            'name' => $row['name'],
            'sold_qty' => (int)$row['qty'],
            'returned_qty' => 0,
            'revenue' => (float)$row['revenue'],
            'cost' => (float)$row['cost'],
        ];
    }
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT sri.part_id, MAX(p.name) AS name, SUM(sri.quantity) AS qty, SUM(sri.quantity * sri.unit_price) AS revenue,
                SUM(sri.quantity * $cost) AS cost
         FROM sale_return_items sri
         JOIN sale_returns sr ON sr.id = sri.sale_return_id AND sr.deleted_at IS NULL
         JOIN sale_items si ON si.sale_id = sr.sale_id AND si.part_id = sri.part_id
         LEFT JOIN parts p ON p.id = sri.part_id
         WHERE sr.return_date BETWEEN ? AND ?
         GROUP BY sri.part_id");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        $id = (int)$row['part_id'];
        if (!isset($products[$id])) {
            $products[$id] = ['part_id' => $id, 'name' => $row['name'], 'sold_qty' => 0, 'returned_qty' => 0, 'revenue' => 0, 'cost' => 0];
        }
        $products[$id]['returned_qty'] = (int)$row['qty'];
        $products[$id]['revenue'] -= (float)$row['revenue'];
        $products[$id]['cost'] -= (float)$row['cost'];
    }
    $stmt->close();

    $productRows = [];
    foreach ($products as $p) {
        $p['revenue'] = round($p['revenue'], 2);
        $p['cost'] = round($p['cost'], 2);
        $p['profit'] = round($p['revenue'] - $p['cost'], 2);
        $productRows[] = $p;
    }
    usort($productRows, function ($a, $b) {
        return $b['profit'] <=> $a['profit'];
    });

    $res = [
        'status' => true,
        'data' => [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'sale_returns' => $saleReturns,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_profit' => round($grossProfit - $totalExpenses, 2),
            'products' => $productRows,
        ],
        'message' => 'OK'
    ];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
