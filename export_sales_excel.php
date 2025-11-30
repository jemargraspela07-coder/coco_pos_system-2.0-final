<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$filterMonth = $_GET['month'] ?? date('m');
$filterYear = $_GET['year'] ?? date('Y');
$view = $_GET['view'] ?? 'daily';

// Filename
$filename = "sales_report_{$filterYear}-{$filterMonth}.csv";

// Headers for download
header("Content-Type: application/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename");

// Open output
$output = fopen("php://output", "w");

// CSV Header
fputcsv($output, ["Date / Week", "Total Sales (₱)"]);

// -------------------- Fetch Sales Data --------------------
if ($view === 'daily') {
    $salesQuery = $conn->prepare("
        SELECT DATE(o.created_at) AS date, SUM(s.total_price) AS total
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=?
        GROUP BY DATE(o.created_at)
        ORDER BY DATE(o.created_at) ASC
    ");
    $salesQuery->bind_param("ii", $filterMonth, $filterYear);
    $salesQuery->execute();
    $result = $salesQuery->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['date'], number_format($row['total'], 2)]);
    }

} elseif ($view === 'weekly') {
    // Fixed 4 weeks per month
    $weekTotals = [1=>0,2=>0,3=>0,4=>0];
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $filterMonth, $filterYear);
    $daysPerWeek = ceil($daysInMonth / 4);

    $salesQuery = $conn->prepare("
        SELECT DATE(o.created_at) AS date, SUM(s.total_price) AS total
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=?
        GROUP BY DATE(o.created_at)
        ORDER BY DATE(o.created_at) ASC
    ");
    $salesQuery->bind_param("ii", $filterMonth, $filterYear);
    $salesQuery->execute();
    $result = $salesQuery->get_result();

    while ($row = $result->fetch_assoc()) {
        $day = intval(date('j', strtotime($row['date'])));
        $week = min(4, ceil($day / $daysPerWeek));
        $weekTotals[$week] += floatval($row['total']);
    }

    foreach ($weekTotals as $week => $total) {
        fputcsv($output, ["Week $week", number_format($total, 2)]);
    }
}

fclose($output);
exit();
