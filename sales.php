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

$chartLabels = [];
$chartTotals = [];

// -------------------- Daily / Weekly Sales --------------------
if ($view === 'daily') {
    $startDate = strtotime("$filterYear-$filterMonth-01");
    $endDate = strtotime(date("Y-m-t", $startDate));

    $dailyTotals = [];
    for ($d = $startDate; $d <= $endDate; $d = strtotime("+1 day", $d)) {
        $dateStr = date("Y-m-d", $d);
        $chartLabels[] = $dateStr;
        $dailyTotals[$dateStr] = 0;
    }

    $salesQuery = $conn->prepare("
        SELECT DATE(o.created_at) as date, SUM(s.total_price) as total
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
        GROUP BY DATE(o.created_at)
        ORDER BY DATE(o.created_at) ASC
    ");
    $salesQuery->bind_param("ii", $filterMonth, $filterYear);
    $salesQuery->execute();
    $result = $salesQuery->get_result();

    while ($row = $result->fetch_assoc()) {
        $dailyTotals[$row['date']] = floatval($row['total']);
    }
    $chartTotals = array_values($dailyTotals);

} elseif ($view === 'weekly') {
    // Fixed weekly mapping: Week 1-4
    $weekTotals = [1=>0,2=>0,3=>0,4=>0];

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
        if ($day <= 7) $week = 1;
        elseif ($day <= 14) $week = 2;
        elseif ($day <= 21) $week = 3;
        else $week = 4;

        $weekTotals[$week] += floatval($row['total']);
    }

    $chartLabels = ["Week 1","Week 2","Week 3","Week 4"];
    $chartTotals = array_values($weekTotals);
}

// -------------------- Total Revenue & Avg Order --------------------
$revenueQuery = $conn->prepare("
    SELECT SUM(s.total_price) AS total_revenue
    FROM sales s
    JOIN orders o ON s.order_id = o.id
    WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=?
");
$revenueQuery->bind_param("ii", $filterMonth, $filterYear);
$revenueQuery->execute();
$totalRevenue = $revenueQuery->get_result()->fetch_assoc()['total_revenue'] ?? 0;

$avgQuery = $conn->prepare("
    SELECT AVG(o.total) AS avg_order_value
    FROM orders o
    WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=? AND o.status='Completed'
");
$avgQuery->bind_param("ii", $filterMonth, $filterYear);
$avgQuery->execute();
$avgOrderValue = $avgQuery->get_result()->fetch_assoc()['avg_order_value'] ?? 0;

// -------------------- Top 10 Products --------------------
$productQuery = $conn->prepare("
    SELECT 
        p.name AS product_name,
        SUM(s.quantity) AS total_qty,
        SUM(s.total_price) AS total_sales
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN orders o ON s.order_id = o.id
    WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=?
    GROUP BY p.id
    ORDER BY total_sales DESC
    LIMIT 10
");
$productQuery->bind_param("ii", $filterMonth, $filterYear);
$productQuery->execute();
$productSales = $productQuery->get_result();
$productArray = [];
while($p=$productSales->fetch_assoc()){ $productArray[] = $p; }

// -------------------- Sales by Hour --------------------
$hourlyQuery = $conn->prepare("
    SELECT HOUR(o.created_at) AS hour, SUM(s.total_price) AS total_sales
    FROM sales s
    JOIN orders o ON s.order_id = o.id
    WHERE MONTH(o.created_at)=? AND YEAR(o.created_at)=?
    GROUP BY hour
    ORDER BY hour ASC
");
$hourlyQuery->bind_param("ii", $filterMonth, $filterYear);
$hourlyQuery->execute();
$hourlyResult = $hourlyQuery->get_result();

$hourLabels = [];
$hourTotals = [];
for ($h=0; $h<24; $h++){ $hourLabels[] = sprintf("%02d:00",$h); $hourTotals[$h]=0; }
while($row = $hourlyResult->fetch_assoc()){ $hourTotals[intval($row['hour'])] = floatval($row['total_sales']); }
$hourTotals = array_values($hourTotals);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Generated - Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
body{display:flex;background:#f8f9fb;color:#333;}
.sidebar{width:240px;background:#0a2342;color:white;height:100vh;position:fixed;top:0;left:0;padding-top:20px;}
.sidebar h2{text-align:center;margin-bottom:30px;font-size:22px;}
.sidebar a{display:block;color:white;text-decoration:none;padding:15px 20px;margin:5px 0;font-size:16px;transition:0.3s;}
.sidebar a:hover,.sidebar a.active{background:#1d3557;border-left:5px solid #00b4d8;}
.main{margin-left:240px;width:calc(100% - 240px);padding:20px 40px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.filter{margin-bottom:20px;display:flex;gap:10px;align-items:center;}
.filter select{padding:5px 10px;border-radius:5px;border:1px solid #ccc;}
button{padding:6px 12px;border:none;background:#1d3557;color:white;border-radius:5px;cursor:pointer;}
.total-box{background:#00b4d8;color:white;padding:12px 20px;border-radius:10px;display:inline-block;font-weight:bold;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
table{width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.08);margin-bottom:30px;}
th,td{padding:14px 18px;text-align:center;border-bottom:1px solid #f1f1f1;}
th{background:#1d3557;color:white;text-transform:uppercase;font-size:14px;}
tr:hover{background:#f0f8ff;}
.summary-cards{display:flex;gap:20px;margin-bottom:30px;}
.card{flex:1;background:white;padding:20px;border-radius:10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.card h3{color:#1d3557;font-size:18px;margin-bottom:8px;}
.card p{font-size:22px;font-weight:bold;color:#00b4d8;}
.section-title{font-size:20px;color:#1d3557;margin:25px 0 10px;font-weight:600;}
.no-data{text-align:center;color:#777;padding:20px;}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php')?'active':'' ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php')?'active':'' ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php')?'active':'' ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php')?'active':'' ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php')?'active':'' ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php') ? 'active' : ''; ?>"><i class="fa fa-comments"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar"><h2>Sales Generated Report</h2></div>

    <form class="filter" method="GET">
        <label>Month:
            <select name="month">
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==$filterMonth?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>Year:
            <select name="year">
                <?php for($y=date('Y')-5;$y<=date('Y');$y++): ?>
                <option value="<?= $y ?>" <?= $y==$filterYear?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>View:
            <select name="view">
                <option value="daily" <?= $view=='daily'?'selected':'' ?>>Daily</option>
                <option value="weekly" <?= $view=='weekly'?'selected':'' ?>>Weekly</option>
            </select>
        </label>

        <button type="submit">Filter</button>

        <a href="export_sales_csv.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>&view=<?= $view ?>" 
           style="background:#40916c;padding:6px 12px;border-radius:5px;color:white;text-decoration:none;">
           Export CSV
        </a>

        <a href="export_sales_excel.php?month=<?= $filterMonth ?>&year=<?= $filterYear ?>&view=<?= $view ?>" 
           style="background:#1d3557;padding:6px 12px;border-radius:5px;color:white;text-decoration:none;">
           Export Excel
        </a>
    </form>

    <div class="summary-cards">
        <div class="card">
            <h3>Total Revenue</h3>
            <p>₱<?= number_format($totalRevenue,2) ?></p>
        </div>
        <div class="card">
            <h3>Average per Order</h3>
            <p>₱<?= number_format($avgOrderValue,2) ?></p>
        </div>
    </div>

    <h3 class="section-title">Sales Trend (<?= ucfirst($view) ?>)</h3>
    <canvas id="salesChart" height="100"></canvas>

    <h3 class="section-title">Product Sales Breakdown (Top 10 by Revenue)</h3>
    <table>
        <tr><th>Product Name</th><th>Quantity Sold</th><th>Total Sales (₱)</th></tr>
        <?php if(count($productArray) > 0): ?>
            <?php foreach($productArray as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['product_name']) ?></td>
                <td><?= number_format($p['total_qty']) ?></td>
                <td>₱<?= number_format($p['total_sales'],2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="no-data">No product sales for selected month.</td></tr>
        <?php endif; ?>
    </table>

    <h3 class="section-title">Product Sales Chart (Top 10 by Revenue)</h3>
    <canvas id="productChart" height="100"></canvas>

    <h3 class="section-title">Sales by Hour (Peak Hours)</h3>
    <canvas id="hourlyChart" height="100"></canvas>

</div>

<script>
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type:'line',
    data:{
        labels: <?= json_encode($chartLabels) ?>,
        datasets:[{
            label:'Sales Generated (₱)',
            data: <?= json_encode($chartTotals) ?>,
            borderColor:'#00b4d8',
            backgroundColor:'rgba(0,180,216,0.2)',
            borderWidth:2,
            fill:true,
            tension:0.3
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:true}},
        scales:{y:{beginAtZero:true}, x:{ticks:{maxRotation:90,minRotation:45}}}
    }
});

const productData = {
    labels: <?= json_encode(array_map(fn($p)=>$p['product_name'], $productArray)) ?>,
    datasets: [{
        label: 'Total Sales (₱)',
        data: <?= json_encode(array_map(fn($p)=>floatval($p['total_sales']), $productArray)) ?>,
        backgroundColor: 'rgba(0,180,216,0.6)',
        borderColor: '#00b4d8',
        borderWidth: 1
    }]
};
new Chart(document.getElementById('productChart'), {
    type: 'bar',
    data: productData,
    options: {responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}, x:{ticks:{maxRotation:90,minRotation:45}}}}
});

const peakValue = Math.max(...<?= json_encode($hourTotals) ?>);
const barColors = <?= json_encode($hourTotals) ?>.map(v => v === peakValue ? 'rgba(255,0,0,0.8)' : 'rgba(255,165,0,0.6)');

const hourlyData = {
    labels: <?= json_encode($hourLabels) ?>,
    datasets: [{
        label: 'Sales (₱)',
        data: <?= json_encode($hourTotals) ?>,
        backgroundColor: barColors,
        borderColor: barColors.map(c => c === 'rgba(255,0,0,0.8)' ? 'red' : 'orange'),
        borderWidth: 1
    }]
};

new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: hourlyData,
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true }, x: { ticks: { maxRotation: 90, minRotation: 45 } } }
    }
});
</script>

</body>
</html>
