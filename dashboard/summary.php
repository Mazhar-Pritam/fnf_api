<?php
include '../connection.php';
include '../auth_check.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = $cc->conn;

// Runs a query and gives back the first number (0 when there is nothing).
function one_total($conn, $sql)
{
    $row = $conn->query($sql)->fetch_row();
    return round((float)($row[0] ?? 0), 2);
}

// Gives back all rows of a query as a list.
function all_rows($conn, $sql)
{
    $list = [];
    $rs = $conn->query($sql);
    while ($row = $rs->fetch_assoc()) {
        $list[] = $row;
    }
    return $list;
}

function has_column($conn, $table, $column)
{
    return $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->num_rows > 0;
}

try {
    $today = date('Y-m-d');
    $monthStart = date('Y-m-01');

    // ---- Today and this month ----
    $todaySales = one_total($conn, "SELECT SUM(total_amount) FROM sales WHERE deleted_at IS NULL AND sale_date = '$today'");
    $todayCount = (int)one_total($conn, "SELECT COUNT(*) FROM sales WHERE deleted_at IS NULL AND sale_date = '$today'");
    $monthSales = one_total($conn, "SELECT SUM(total_amount) FROM sales WHERE deleted_at IS NULL AND sale_date BETWEEN '$monthStart' AND '$today'");
    $monthReturns = one_total($conn, "SELECT SUM(total_amount) FROM sale_returns WHERE deleted_at IS NULL AND return_date BETWEEN '$monthStart' AND '$today'");

    // Profit of this month (same rule as the Profit & Loss report)
    $cost = "IF(si.cost_price > 0, si.cost_price, COALESCE(p.purchase_price, 0))";
    $costSold = one_total($conn,
        "SELECT SUM(si.quantity * $cost) FROM sale_items si
         JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
         LEFT JOIN parts p ON p.id = si.part_id
         WHERE s.sale_date BETWEEN '$monthStart' AND '$today'");
    $costReturned = one_total($conn,
        "SELECT SUM(sri.quantity * $cost) FROM sale_return_items sri
         JOIN sale_returns sr ON sr.id = sri.sale_return_id AND sr.deleted_at IS NULL
         JOIN sale_items si ON si.sale_id = sr.sale_id AND si.part_id = sri.part_id
         LEFT JOIN parts p ON p.id = sri.part_id
         WHERE sr.return_date BETWEEN '$monthStart' AND '$today'");
    $monthExpenses = one_total($conn, "SELECT SUM(amount) FROM expenses WHERE deleted_at IS NULL AND expense_date BETWEEN '$monthStart' AND '$today'");
    $grossProfit = ($monthSales - $monthReturns) - ($costSold - $costReturned);
    $netProfit = round($grossProfit - $monthExpenses, 2);

    // ---- Money owed (same rule as Accounts) ----
    $sales = one_total($conn, "SELECT SUM(total_amount) FROM sales WHERE deleted_at IS NULL");
    $saleReturns = one_total($conn, "SELECT SUM(total_amount) FROM sale_returns WHERE deleted_at IS NULL");
    $purchases = one_total($conn, "SELECT SUM(total_amount) FROM purchases WHERE deleted_at IS NULL");
    $purchaseReturns = one_total($conn, "SELECT SUM(total_amount) FROM purchase_returns WHERE deleted_at IS NULL");
    $received = one_total($conn, "SELECT SUM(amount) FROM payments WHERE type = 'receipt' AND deleted_at IS NULL")
        + one_total($conn, "SELECT SUM(paid_amount) FROM sales WHERE deleted_at IS NULL");
    $paid = one_total($conn, "SELECT SUM(amount) FROM payments WHERE type = 'payment' AND deleted_at IS NULL")
        + one_total($conn, "SELECT SUM(paid_amount) FROM purchases WHERE deleted_at IS NULL");
    $allExpenses = one_total($conn, "SELECT SUM(amount) FROM expenses WHERE deleted_at IS NULL");

    // ---- Stock ----
    $partsCount = (int)one_total($conn, "SELECT COUNT(*) FROM parts WHERE deleted_at IS NULL");
    $stockValue = one_total($conn, "SELECT SUM(stock_quantity * purchase_price) FROM parts WHERE deleted_at IS NULL");

    // Low stock = stock is at or below the minimum stock of the part.
    // If the parts table has no minimum_stock column yet, parts with 5 or less are shown.
    $limit = has_column($conn, 'parts', 'minimum_stock')
        ? "CASE WHEN minimum_stock > 0 THEN minimum_stock ELSE 5 END"
        : "5";
    $lowStock = all_rows($conn,
        "SELECT id, name, part_number, stock_quantity, $limit AS minimum_stock
         FROM parts
         WHERE deleted_at IS NULL AND stock_quantity <= $limit
         ORDER BY stock_quantity ASC, name ASC
         LIMIT 8");
    $lowStockCount = (int)one_total($conn,
        "SELECT COUNT(*) FROM parts WHERE deleted_at IS NULL AND stock_quantity <= $limit");

    // ---- Sales of the last 7 days (for the small chart) ----
    $byDay = [];
    foreach (all_rows($conn,
        "SELECT sale_date, SUM(total_amount) AS total FROM sales
         WHERE deleted_at IS NULL AND sale_date >= DATE_SUB('$today', INTERVAL 6 DAY) AND sale_date <= '$today'
         GROUP BY sale_date") as $row) {
        $byDay[$row['sale_date']] = (float)$row['total'];
    }
    $last7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("$today -$i day"));
        $last7[] = ['date' => $day, 'label' => date('D', strtotime($day)), 'total' => round($byDay[$day] ?? 0, 2)];
    }

    // ---- Recent sales and best selling parts of this month ----
    $recentSales = all_rows($conn,
        "SELECT s.id, s.invoice_no, s.sale_date, s.total_amount, s.paid_amount, c.name AS customer_name
         FROM sales s LEFT JOIN customers c ON c.id = s.customer_id
         WHERE s.deleted_at IS NULL
         ORDER BY s.id DESC LIMIT 5");
    $topParts = all_rows($conn,
        "SELECT MAX(p.name) AS name, SUM(si.quantity) AS qty, SUM(si.line_total) AS revenue
         FROM sale_items si
         JOIN sales s ON s.id = si.sale_id AND s.deleted_at IS NULL
         LEFT JOIN parts p ON p.id = si.part_id
         WHERE s.sale_date BETWEEN '$monthStart' AND '$today'
         GROUP BY si.part_id
         ORDER BY qty DESC LIMIT 5");

    $data = [
        'today_sales' => $todaySales,
        'today_count' => $todayCount,
        'month_sales' => round($monthSales - $monthReturns, 2),
        'month_profit' => $netProfit,
        'receivable' => round($sales - $saleReturns - $received, 2),
        'payable' => round($purchases - $purchaseReturns - $paid, 2),
        'cash_balance' => round($received - $paid - $allExpenses, 2),
        'parts_count' => $partsCount,
        'stock_value' => $stockValue,
        'low_stock_count' => $lowStockCount,
        'low_stock' => $lowStock,
        'last7' => $last7,
        'recent_sales' => $recentSales,
        'top_parts' => $topParts,
    ];

    $res = ['status' => true, 'data' => $data, 'message' => 'OK'];
} catch (Throwable $e) {
    $res = ['status' => false, 'data' => [], 'message' => $e->getMessage()];
}

echo json_encode($res);
