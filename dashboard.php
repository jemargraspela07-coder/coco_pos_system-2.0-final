<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit(); 
}

$users = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'] ?? 0;
$orders = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'] ?? 0;

$pending_orders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE LOWER(status)='pending'")->fetch_assoc()['total'] ?? 0;
$ready_orders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE LOWER(status)='ready for pick up'")->fetch_assoc()['total'] ?? 0;
$completed_orders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE LOWER(status)='completed'")->fetch_assoc()['total'] ?? 0;

$sales = $conn->query("
    SELECT COALESCE(SUM(s.total_price), 0) AS total 
    FROM sales s 
    JOIN orders o ON s.order_id = o.id 
    WHERE LOWER(o.status)='completed'
")->fetch_assoc()['total'] ?? 0;

$daily_total = $conn->query("
    SELECT COALESCE(SUM(s.total_price), 0) AS total 
    FROM sales s 
    JOIN orders o ON s.order_id=o.id 
    WHERE DATE(o.created_at) = CURDATE() 
    AND LOWER(o.status)='completed'
")->fetch_assoc()['total'] ?? 0;

$weekly_total = $conn->query("
    SELECT COALESCE(SUM(s.total_price), 0) AS total 
    FROM sales s 
    JOIN orders o ON s.order_id=o.id 
    WHERE YEARWEEK(o.created_at,1) = YEARWEEK(CURDATE(),1) 
    AND LOWER(o.status)='completed'
")->fetch_assoc()['total'] ?? 0;

$monthly_total = $conn->query("
    SELECT COALESCE(SUM(s.total_price), 0) AS total 
    FROM sales s 
    JOIN orders o ON s.order_id=o.id 
    WHERE MONTH(o.created_at) = MONTH(CURDATE()) 
    AND YEAR(o.created_at) = YEAR(CURDATE()) 
    AND LOWER(o.status)='completed'
")->fetch_assoc()['total'] ?? 0;

$currentYear = date('Y');
$months = [];
$monthlyOrders = [];
$monthlyRevenue = [];

for ($m = 1; $m <= 12; $m++) {
    $monthStart = date("$currentYear-$m-01");
    $monthEnd = date("Y-m-t", strtotime($monthStart));

    $sales_query = $conn->query("
        SELECT 
            COUNT(*) AS orders_count,
            COALESCE(SUM(s.total_price),0) AS revenue
        FROM sales s
        JOIN orders o ON s.order_id = o.id
        WHERE o.created_at BETWEEN '$monthStart 00:00:00' AND '$monthEnd 23:59:59'
        AND LOWER(o.status)='completed'
    ")->fetch_assoc();

    $months[] = date('F', strtotime($monthStart));
    $monthlyOrders[] = (int)$sales_query['orders_count'];
    $monthlyRevenue[] = (float)$sales_query['revenue'];
}

$monthsJSON = json_encode($months);
$monthlyOrdersJSON = json_encode($monthlyOrders);
$monthlyRevenueJSON = json_encode($monthlyRevenue);

$topProducts = $conn->query("
    SELECT 
        p.id,
        p.name,
        p.stock,
        COALESCE(SUM(s.quantity),0) AS total_sales,
        COALESCE(SUM(s.total_price),0) AS revenue
    FROM products p
    LEFT JOIN sales s ON s.product_id = p.id
    LEFT JOIN orders o ON s.order_id = o.id
    WHERE LOWER(o.status)='completed'
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 10
");

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins", sans-serif; }
body { display:flex; background:#f8f9fb; color:#333; }
.sidebar { width:240px; background:#0a2342; color:white; height:100vh; position:fixed; top:0; left:0; padding-top:20px; }
.sidebar h2 { text-align:center; margin-bottom:30px; font-size:22px; }
.sidebar a { display:block; color:white; text-decoration:none; padding:15px 20px; margin:5px 0; font-size:16px; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:#1d3557; border-left:5px solid #00b4d8; }
.main { margin-left:240px; width:calc(100% - 240px); padding:20px 40px; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.topbar h2 { color:#0a2342; }
.admin-info { display:flex; align-items:center; gap:10px; background:#1d3557; color:white; padding:8px 14px; border-radius:8px; font-size:14px; }
.admin-info a { color:white; text-decoration:none; display:flex; align-items:center; gap:6px; }
.admin-info a:hover { color:#00b4d8; }
.cards { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-bottom:20px; }
.card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.08); transition: transform 0.2s ease, box-shadow 0.2s ease; text-align:center; }
.card:hover { transform:translateY(-5px); box-shadow:0 4px 15px rgba(0,0,0,0.1); }
.card h3 { color:#1d3557; font-size:18px; margin-bottom:10px; }
.card p { font-size:22px; font-weight:600; color:#00b4d8; }
.pie-card canvas, #salesLineChart { width:100% !important; height:250px !important; }
.feedback-section { margin-top:40px; }
.feedback-section h3 { color:#1d3557; margin-bottom:10px; }
.feedback-list { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); max-height:420px; overflow-y:auto; }
.feedback-item { padding:12px 15px; border-bottom:1px solid #eee; border-radius:8px; margin-bottom:10px; background:#f9f9f9; }
.feedback-item:last-child { margin-bottom:0; border-bottom:none; }
.feedback-item strong { color:#0a2342; font-size:15px; }
.star-rating { color:#FFD700; margin:5px 0; font-size:14px; display:block; }
.top-products table { width:100%; border-collapse:collapse; }
.top-products th, .top-products td { padding:10px; text-align:left; }
.top-products th { background:#0a2342; color:white; }
.top-products tr { border-bottom:1px solid #eee; }
.top-products tr:last-child { border-bottom:none; }
@media (max-width:768px) { .sidebar { width:180px; } .main { margin-left:180px; padding:15px; } .card p { font-size:20px; } .sales-overview { flex-direction:column; } }
</style>
</head>
<body>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php') ? 'active' : ''; ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php') ? 'active' : ''; ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php') ? 'active' : ''; ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php') ? 'active' : ''; ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php') ? 'active' : ''; ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php') ? 'active' : ''; ?>"><i class="fa fa-comments"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>Dashboard Overview</h2>
        <div class="admin-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['admin']); ?></span>
            <a href="profile.php"><i class="fa fa-user-circle"></i> Profile</a>
        </div>
    </div>

    <div class="cards">
        <div class="card"><h3><i class="fa fa-users"></i> Users</h3><p><?= $users; ?></p></div>
        <div class="card"><h3><i class="fa fa-box"></i> Products</h3><p><?= $products; ?></p></div>
        <div class="card"><h3><i class="fa fa-shopping-cart"></i> Orders</h3><p><?= $orders; ?></p></div>
        <div class="card"><h3><i class="fa fa-coins"></i> Sales</h3><p>₱<?= number_format($sales,2); ?></p></div>
    </div>

    <div class="cards">
        <div class="card"><h3>Pending</h3><p><?= $pending_orders; ?></p></div>
        <div class="card"><h3>Ready for Pick Up</h3><p><?= $ready_orders; ?></p></div>
        <div class="card"><h3>Completed</h3><p><?= $completed_orders; ?></p></div>
    </div>

    <div class="sales-overview" style="display:flex; flex-wrap:wrap; gap:20px; margin-top:30px;">
        <div style="flex:0 0 300px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
            <h3 style="text-align:center; color:#1d3557;">Sales Overview</h3>
            <canvas id="salesPieChart"></canvas>
        </div>

        <div style="flex:1; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08); min-width:300px;">
            <h3 style="text-align:center; color:#1d3557;">Monthly Sales & Revenue</h3>
            <canvas id="salesLineChart"></canvas>
        </div>
    </div>

    <div class="top-products" style="margin-top:40px;">
        <h3 style="color:#1d3557;"><i class="fa fa-box"></i> Top Products</h3>
        <div style="overflow-x:auto; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align:center;">Sales</th>
                        <th style="text-align:center;">Revenue (₱)</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($topProducts && $topProducts->num_rows > 0): ?>
                    <?php while($prod = $topProducts->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($prod['name']); ?></td>
                            <td style="text-align:center;"><?= (int)$prod['total_sales']; ?></td>
                            <td style="text-align:center;">₱<?= number_format($prod['revenue'],2); ?></td>
                            <td style="text-align:center;">
                                <?php
                                    if($prod['stock'] > 5){
                                        echo '<span style="color:green;font-weight:600;">In Stock</span>';
                                    } elseif($prod['stock'] > 0){
                                        echo '<span style="color:orange;font-weight:600;">Low Stock</span>';
                                    } else{
                                        echo '<span style="color:red;font-weight:600;">Out of Stock</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">No products found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="feedback-section">
        <h3><i class="fa fa-comments"></i> Recent Customer Feedback</h3>
        <div class="feedback-list">
            <?php
            $feedback_sql = "
                SELECT f.*, u.username AS customer_name
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC
                LIMIT 5
            ";
            $feedbacks = $conn->query($feedback_sql);

            if ($feedbacks && $feedbacks->num_rows > 0):
                while ($fb = $feedbacks->fetch_assoc()):
                    $stars = isset($fb['rating']) ? (int)$fb['rating'] : 0;
                    $stars = max(1, min(5, $stars)); // 1-5 range
                    $statusStars = str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
            ?>
                <div class="feedback-item">
                    <strong><?= htmlspecialchars($fb['customer_name'] ?? 'Unknown'); ?></strong>
                    <div class="star-rating"><?= $statusStars ?></div>
                    <small style="color:#666;"><?= date("F d, Y h:i A", strtotime($fb['created_at'])); ?></small>
                    <p style="margin-top:6px; font-size:14px; color:#333;"><?= htmlspecialchars($fb['comment']); ?></p>
                </div>
            <?php
                endwhile;
            else:
                echo "<p style='text-align:center; color:#666;'>No feedbacks yet.</p>";
            endif;
            ?>
            <div style="text-align:right; margin-top:10px;">
                <a href="feedbacks.php" style="color:#00b4d8; text-decoration:none; font-weight:500;">View All Feedbacks →</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxPie = document.getElementById('salesPieChart').getContext('2d');
new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: ['Daily', 'Weekly', 'Monthly'],
        datasets: [{
            data: [<?= $daily_total ?>, <?= $weekly_total ?>, <?= $monthly_total ?>],
            backgroundColor: ['#00b4d8','#0077b6','#90e0ef'],
            borderWidth:1
        }]
    },
    options: {
        responsive:true,
        plugins:{
            legend:{position:'bottom', labels:{font:{size:14}}},
            tooltip:{callbacks:{label:function(ctx){return ctx.label+': ₱'+ctx.raw.toLocaleString();}}}
        }
    }
});

// Combo Chart: Orders (bar) + Revenue (line)
const ctxCombo = document.getElementById('salesLineChart').getContext('2d');
new Chart(ctxCombo, {
    data: {
        labels: <?= $monthsJSON ?>,
        datasets: [
            {
                type: 'bar',
                label: 'Orders',
                data: <?= $monthlyOrdersJSON ?>,
                backgroundColor: '#00b4d8',
                yAxisID: 'y1'
            },
            {
                type: 'line',
                label: 'Revenue (₱)',
                data: <?= $monthlyRevenueJSON ?>,
                borderColor: '#0077b6',
                backgroundColor: '#0077b6',
                yAxisID: 'y2',
                tension:0.3,
                fill:false,
                pointRadius:5,
                pointStyle:'circle'
            }
        ]
    },
    options: {
        responsive:true,
        interaction:{mode:'index', intersect:false},
        plugins:{
            legend:{position:'bottom', labels:{font:{size:14}}},
            tooltip:{callbacks:{
                label:function(ctx){
                    if(ctx.dataset.label==='Revenue (₱)'){
                        return ctx.dataset.label+': ₱'+ctx.raw.toLocaleString();
                    }
                    return ctx.dataset.label+': '+ctx.raw;
                }
            }}
        },
        scales:{
            y1:{
                type:'linear',
                position:'left',
                title:{display:true, text:'Orders'},
                beginAtZero:true
            },
            y2:{
                type:'linear',
                position:'right',
                title:{display:true, text:'Revenue (₱)'},
                beginAtZero:true,
                grid:{drawOnChartArea:false}
            }
        }
    }
});
</script>
</body>
</html>
