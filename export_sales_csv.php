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

$filename = "sales_report_{$filterYear}-{$filterMonth}.csv";

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename");

$output = fopen("php://output", "w");

if($view === 'daily') {
    fputcsv($output, ["Date", "Total Sales (₱)"]);

    $query = $conn->prepare("
        SELECT DATE(o.created_at) AS date, SUM(s.total_price) AS total
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
        GROUP BY DATE(o.created_at)
        ORDER BY date ASC
    ");
    $query->bind_param("ii", $filterMonth, $filterYear);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['date'],
            number_format($row['total'], 2)
        ]);
    }
} else { // weekly
    fputcsv($output, ["Week", "Total Sales (₱)"]);

    $startDate = strtotime("$filterYear-$filterMonth-01");
    $endDate = strtotime(date("Y-m-t", $startDate));
    $weekTotals = [1=>0,2=>0,3=>0,4=>0];

    // Calculate which day belongs to which week (4 weeks fixed)
    $daysInMonth = date('t', $startDate);
    $daysPerWeek = ceil($daysInMonth / 4);

    $query = $conn->prepare("
        SELECT DATE(o.created_at) AS date, SUM(s.total_price) AS total
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
        GROUP BY DATE(o.created_at)
        ORDER BY date ASC
    ");
    $query->bind_param("ii", $filterMonth, $filterYear);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $day = intval(date('j', strtotime($row['date'])));
        $week = min(4, ceil($day / $daysPerWeek)); // Week 1-4
        $weekTotals[$week] += floatval($row['total']);
    }

    for($i=1;$i<=4;$i++){
        fputcsv($output, ["Week $i", number_format($weekTotals[$i],2)]);
    }
}

fclose($output);
exit();
