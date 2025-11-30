<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// =========================
// HANDLE RESTOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'], $_POST['product_id'], $_POST['quantity'])) {
    $productId = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0) {
        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $productId);
        $stmt->execute();

        // Insert into restock_logs
        $stmtLog = $conn->prepare("INSERT INTO restock_logs (product_id, quantity_added, restocked_at) VALUES (?, ?, NOW())");
        $stmtLog->bind_param("ii", $productId, $quantity);
        $stmtLog->execute();

        // Success alert
        echo "<script>
        Swal.fire({
            icon:'success',
            title:'Stock Added!',
            text:'$quantity units added to the product.',
            timer:1500,
            showConfirmButton:false
        }).then(()=>{ window.location='inventory.php'; });
        </script>";
    }
}

// =========================
// HANDLE ARCHIVE / UNARCHIVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'], $_POST['product_id'])) {
    $productId = intval($_POST['product_id']);
    $action = $_POST['action_type'];

    if ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE products SET is_archived = 1 WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $alertMessage = 'Product Archived!';
    } elseif ($action === 'unarchive') {
        $stmt = $conn->prepare("UPDATE products SET is_archived = 0 WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $alertMessage = 'Product Restored!';
    }

    // Pass a flag for JS to show SweetAlert
    $showArchiveAlert = true;
}

// =========================
// DETERMINE VIEW MODE
$view_archive = isset($_GET['view']) && $_GET['view'] === 'archive';

// =========================
// FETCH PRODUCTS
$result = $conn->query(
    $view_archive
    ? "SELECT * FROM products WHERE is_archived = 1 ORDER BY id DESC"
    : "SELECT * FROM products WHERE is_archived = 0 ORDER BY id DESC"
);

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    // Check for empty fields
    if ($name === '' || $price <= 0 || $stock < 0 || !isset($_FILES['image'])) {
        echo "<script>
        Swal.fire({icon:'error', title:'Invalid Input', text:'Fill all fields and select a JPG image.'});
        </script>";
        exit;
    }

    // Check duplicate name
    $stmtCheck = $conn->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
    $stmtCheck->bind_param("s", $name);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    if ($stmtCheck->num_rows > 0) {
        echo "<script>
        Swal.fire({icon:'error', title:'Duplicate Product', text:'A product with this name already exists.'});
        </script>";
        exit;
    }

    // Image upload
    $image = $_FILES['image'];
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg'])) {
        echo "<script>
        Swal.fire({icon:'error', title:'Invalid Image', text:'Only JPG images are allowed.'});
        </script>";
        exit;
    }
    $image_name = uniqid('prod_') . '.' . $ext;
    $upload_dir = '../images/'; // folder for images
    if (!move_uploaded_file($image['tmp_name'], $upload_dir.$image_name)) {
        echo "<script>
        Swal.fire({icon:'error', title:'Upload Failed', text:'Failed to upload image.'});
        </script>";
        exit;
    }

    // Insert product
    $stmt = $conn->prepare("INSERT INTO products (name, price, stock, image, is_archived) VALUES (?,?,?,?,0)");
    $stmt->bind_param("sdis", $name, $price, $stock, $image_name);
    if ($stmt->execute()) {
        echo "<script>
        Swal.fire({icon:'success', title:'Product Added!', text:'New product added successfully.', timer:1500, showConfirmButton:false})
        .then(()=>{ window.location='inventory.php'; });
        </script>";
    }
}

// =========================
// DETERMINE VIEW MODE
$view_archive = isset($_GET['view']) && $_GET['view'] === 'archive';

// =========================
// FETCH PRODUCTS
$result = $conn->query(
    $view_archive
    ? "SELECT * FROM products WHERE is_archived = 1 ORDER BY id DESC"
    : "SELECT * FROM products WHERE is_archived = 0 ORDER BY id DESC"
);

// =========================
// FETCH GRAPH DATA
$graphQuery = $conn->query("
    SELECT p.name, 
        SUM(r.quantity_added) AS total_restocked,
        (SELECT COALESCE(SUM(quantity),0) FROM order_items oi 
         JOIN orders o ON o.id = oi.order_id 
         WHERE oi.product_id = p.id AND o.status='Completed') AS total_sold
    FROM products p
    LEFT JOIN restock_logs r ON p.id = r.product_id
    GROUP BY p.id
");
$graphLabels = [];
$graphRestocked = [];
$graphSold = [];
while ($row = $graphQuery->fetch_assoc()) {
    $graphLabels[] = $row['name'];
    $graphRestocked[] = intval($row['total_restocked']);
    $graphSold[] = intval($row['total_sold']);
}

// =========================
// FETCH STOCK USAGE & MOVEMENT (Adjusted)
$stockChartQuery = $conn->query("
    SELECT 
        p.name,
        p.stock AS current_stock,
        COALESCE(SUM(r.quantity_added),0) AS total_restocked,
        (SELECT COALESCE(SUM(oi.quantity),0) 
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE oi.product_id = p.id AND o.status='Completed') AS total_sold
    FROM products p
    LEFT JOIN restock_logs r ON p.id = r.product_id
    WHERE p.is_archived = 0
    GROUP BY p.id
    ORDER BY p.id DESC
");

$stockChartLabels = [];
$stockChartStock = [];
$stockChartRestocked = [];
$stockChartSold = [];

while($row = $stockChartQuery->fetch_assoc()){
    $stockChartLabels[] = $row['name'];
    $stockChartStock[] = intval($row['current_stock']);
    $stockChartRestocked[] = intval($row['total_restocked']);
    $stockChartSold[] = intval($row['total_sold']);
}

// FILTER OUT TEST PRODUCTS (Optional)
$filteredStockLabels = [];
$filteredStockStock = [];
$filteredStockRestocked = [];
$filteredStockSold = [];

for($i=0; $i<count($stockChartLabels); $i++){
    $name = strtolower($stockChartLabels[$i]);
    if($name !== 'test coco coconut' && $name !== 'fg'){
        $filteredStockLabels[] = $stockChartLabels[$i];
        $filteredStockStock[] = $stockChartStock[$i];
        $filteredStockRestocked[] = $stockChartRestocked[$i];
        $filteredStockSold[] = $stockChartSold[$i];
    }
}

// =========================
// CURRENT INVENTORY SUMMARY
$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products WHERE is_archived=0")->fetch_assoc()['total'];
$lowStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock <= 10 AND is_archived=0")->fetch_assoc()['total'];
$outOfStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock = 0 AND is_archived=0")->fetch_assoc()['total'];
$totalStockQty = $conn->query("SELECT COALESCE(SUM(stock),0) AS total_qty FROM products WHERE is_archived=0")->fetch_assoc()['total_qty'];
$totalInventoryValue = $conn->query("SELECT COALESCE(SUM(stock*price),0) AS total_value FROM products WHERE is_archived=0")->fetch_assoc()['total_value'];

// =========================
// TOP SELLING PRODUCTS
$topProductsQuery = $conn->query("
    SELECT p.name, COALESCE(SUM(oi.quantity),0) AS total_sold
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.status='Completed'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$topLabels = [];
$topData = [];
while($row = $topProductsQuery->fetch_assoc()){
    $topLabels[] = $row['name'];
    $topData[] = intval($row['total_sold']);
}

// =========================
// FETCH RESTOCK HISTORY (ACTIVE PRODUCTS ONLY)
if (!$view_archive) {
    $restocks = $conn->query("
        SELECT r.id, p.name AS product_name, r.quantity_added, r.restocked_at
        FROM restock_logs r
        JOIN products p ON r.product_id = p.id
        WHERE p.is_archived=0
        ORDER BY r.restocked_at DESC
    ");
}

// =========================
// SALES VELOCITY & PROJECTED DEPLETION (LAST 30 DAYS)
$thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));

$salesVelocityQuery = $conn->query("
    SELECT p.id, p.name, p.stock,
        COALESCE(SUM(oi.quantity),0) AS total_sold_last30
    FROM products p
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON o.id = oi.order_id AND o.status='Completed' AND o.created_at >= '$thirtyDaysAgo'
    GROUP BY p.id
");

$salesVelocityLabels = [];
$salesVelocityData = [];
$projectedDepletionData = [];
$projectedDepletionColors = [];

while($row = $salesVelocityQuery->fetch_assoc()) {
    $productName = $row['name'];
    $totalSold = intval($row['total_sold_last30']);
    $currentStock = intval($row['stock']);
    $daysForSales = 30;

    $salesPerDay = $totalSold / $daysForSales;

    if($salesPerDay > 0) {
        $depletionDays = round($currentStock / $salesPerDay, 1);
        if ($depletionDays <= 3) $color = 'rgba(255,99,132,0.7)';
        elseif ($depletionDays <= 7) $color = 'rgba(255,206,86,0.7)';
        else $color = 'rgba(54,162,235,0.7)';
    } else {
        $depletionDays = null;
        $color = 'rgba(200,200,200,0.7)';
    }

    $salesVelocityLabels[] = $productName;
    $salesVelocityData[] = round($salesPerDay,2);
    $projectedDepletionData[] = $depletionDays;
    $projectedDepletionColors[] = $color;
}

// =========================
// FILTER OUT TEST PRODUCTS FOR CHARTS
$filteredGraphLabels = [];
$filteredGraphRestocked = [];
$filteredGraphSold = [];
for($i=0; $i<count($graphLabels); $i++){
    $name = strtolower($graphLabels[$i]);
    if($name !== 'test coco coconut' && $name !== 'fg'){
        $filteredGraphLabels[] = $graphLabels[$i];
        $filteredGraphRestocked[] = $graphRestocked[$i];
        $filteredGraphSold[] = $graphSold[$i];
    }
}

$filteredSalesLabels = [];
$filteredSalesData = [];
$filteredDepletionData = [];
$filteredDepletionColors = [];
for($i=0; $i<count($salesVelocityLabels); $i++){
    $name = strtolower($salesVelocityLabels[$i]);
    if($name !== 'test coco coconut' && $name !== 'fg'){
        $filteredSalesLabels[] = $salesVelocityLabels[$i];
        $filteredSalesData[] = $salesVelocityData[$i];
        $filteredDepletionData[] = $projectedDepletionData[$i];
        $filteredDepletionColors[] = $projectedDepletionColors[$i];
    }
}

// =========================
// CURRENT PAGE
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory - Coco POS</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* EXISTING CSS UNCHANGED */
* {margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
body {display:flex;background:#f8f9fb;color:#333;}
.sidebar {width:240px;background:#0a2342;color:white;height:100vh;position:fixed;top:0;left:0;padding-top:20px;}
.sidebar h2 {text-align:center;margin-bottom:30px;font-size:22px;}
.sidebar a {display:block;color:white;text-decoration:none;padding:15px 20px;margin:5px 0;font-size:16px;transition:0.3s;}
.sidebar a:hover, .sidebar a.active {background:#1d3557;border-left:5px solid #00b4d8;}
.main {margin-left:240px;width:calc(100% - 240px);padding:20px 40px;}
.topbar {display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.topbar h2 {color:#0a2342;font-size:24px;}
.summary-cards {display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap;}
.card {flex:1;background:white;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.1);padding:20px;text-align:center;}
.card h3 {font-size:16px;color:#666;margin-bottom:8px;}
.card p {font-size:22px;font-weight:600;color:#0a2342;}
.low {background:#fff3cd;}
.out {background:#f8d7da;}
.chart-container {background:white;padding:20px;border-radius:12px;margin-bottom:25px;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.add-product-form {display:none;}
.add-product-form input {padding:8px 10px;border-radius:6px;border:1px solid #ccc;margin-right:8px;font-size:14px;}
.add-product-form button {background:#00b4d8;color:white;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;transition:0.3s;}
.add-product-form button:hover {background:#0077b6;}
table {width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
th,td {padding:14px 18px;text-align:center;border-bottom:1px solid #f1f1f1;}
th {background:#1d3557;color:white;text-transform:uppercase;font-size:14px;letter-spacing:0.5px;}
tr:hover {background:#f0f8ff;}
.low-stock {background:#fff3cd !important;}
.out-stock {background:#f8d7da !important;}
.archive-btn,.unarchive-btn {background:#dc3545;color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;}
.unarchive-btn {background:#198754;}
.restock-history {margin-top:40px;}
.restock-history h3 {margin-bottom:15px;}
.no-data {text-align:center; padding:40px 0; background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); color:#777; font-size:16px;}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php') ? 'active':''; ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php') ? 'active':''; ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php') ? 'active':''; ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php') ? 'active':''; ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php') ? 'active':''; ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php') ? 'active' : ''; ?>"><i class="fa fa-comments"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar" style="display:flex; justify-content:space-between; align-items:center;">
    <h2><?= $view_archive ? 'Archived Products' : 'Inventory Management'; ?></h2>
    <div>
        <?php if(!$view_archive): ?>
            <button onclick="openAddProductModal()" style="background:#00b4d8;color:white;border:none;padding:8px 12px;border-radius:6px;cursor:pointer; margin-right:10px;">
                <i class="fa fa-plus"></i> Add Product
            </button>
        <?php endif; ?>
        <a href="inventory.php<?= $view_archive ? '' : '?view=archive'; ?>" class="toggle-btn" style="text-decoration:none; color:#0a2342; font-weight:500;">
            <i class="fa <?= $view_archive ? 'fa-box' : 'fa-archive'; ?>"></i>
            <?= $view_archive ? 'Back to Active Products' : 'View Archive'; ?>
        </a>
    </div>
</div>

<?php if (!$view_archive): ?>

    <div class="summary-cards">
        <div class="card"><h3>Total Products</h3><p><?= $totalProducts; ?></p></div>
        <div class="card"><h3>Total Stock Qty</h3><p><?= $totalStockQty; ?></p></div>
        <div class="card"><h3>Total Inventory Value</h3><p>₱<?= number_format($totalInventoryValue,2); ?></p></div>
        <div class="card low"><h3>Low Stock</h3><p><?= $lowStock; ?></p></div>
        <div class="card out"><h3>Out of Stock</h3><p><?= $outOfStock; ?></p></div>
    </div>

    <div class="chart-container" style="height:300px; max-width:600px; margin-bottom:25px;">
        <h3>Top Selling Products</h3>
        <canvas id="topDonutChart"></canvas>
    </div>

    <div class="chart-container">
        <h3>Stock Usage & Movement Trends</h3>
        <canvas id="stockChart" height="100"></canvas>
    </div>

    <div class="chart-container" style="height:300px;">
        <h3>Sales Velocity (Units Sold per Day - Last 30 Days)</h3>
        <canvas id="salesVelocityChart"></canvas>
    </div>

    <div class="chart-container" style="height:300px;">
        <h3>Projected Stock Depletion (Days Remaining)</h3>
        <canvas id="projectedDepletionChart"></canvas>
    </div>

<?php endif; ?>

<table>
    <tr>
        <th>NAME</th>
        <th>PRICE</th>
        <th>STOCK</th>
        <th>ACTIONS</th>
    </tr>

    <?php 
    $lowStockAlerts = [];
    $outStockAlerts = [];
    while($row = $result->fetch_assoc()): 
        $stockClass = ($row['stock'] == 0) ? 'out-stock' : (($row['stock'] <= 10) ? 'low-stock' : '');
        if($row['stock'] <= 10 && $row['stock'] > 0) $lowStockAlerts[] = $row['name'].' ('.$row['stock'].' left)';
        if($row['stock'] == 0) $outStockAlerts[] = $row['name'];
    ?>
    <tr class="<?= $stockClass; ?>">
        <td><?= htmlspecialchars($row['name']); ?></td>
        <td>₱<?= number_format($row['price'],2); ?></td>
        <td><?= intval($row['stock']); ?></td>
        <td>
            <?php if ($view_archive): ?>
                <form method="POST" onsubmit="return confirmArchive(this, 'unarchive');" style="display:inline-block;">
                    <input type="hidden" name="product_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="action_type" value="unarchive">
                    <button type="submit" class="unarchive-btn"><i class="fa fa-undo"></i> Unarchive</button>
                </form>
            <?php else: ?>
                <form method="POST" class="restock-form" style="display:inline-flex; align-items:center;">
                    <input type="hidden" name="product_id" value="<?= $row['id']; ?>">
                    <input type="number" name="quantity" min="1" step="1" placeholder="Qty" required>
                    <button type="submit" name="restock" class="add-btn"><i class="fa fa-plus"></i> Add</button>
                </form>
                <form method="POST" onsubmit="return confirmArchive(this, 'archive');" style="display:inline-block;">
                    <input type="hidden" name="product_id" value="<?= $row['id']; ?>">
                    <input type="hidden" name="action_type" value="archive">
                    <button type="submit" class="archive-btn"><i class="fa fa-archive"></i> Archive</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>

<?php if (!$view_archive && isset($restocks) && $restocks->num_rows>0): ?>
<div class="restock-history">
    <h3>Recent Restocks</h3>
    <table>
        <tr><th>Product</th><th>Quantity Added</th><th>Restocked At</th></tr>
        <?php while($r = $restocks->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['product_name']); ?></td>
                <td><?= intval($r['quantity_added']); ?></td>
                <td><?= date('M d, Y H:i', strtotime($r['restocked_at'])); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php elseif (!$view_archive): ?>
    <div class="no-data">No restock records found.</div>
<?php endif; ?>

<script>
function confirmArchive(form, type){
    Swal.fire({
        title: type==='archive' ? 'Archive this product?' : 'Unarchive this product?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: type==='archive' ? 'Yes, archive it!' : 'Yes, restore it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed){
            form.submit();
        }
    });
    return false; // prevent normal form submission
}

// =========================
// ADD PRODUCT MODAL (with image upload)
function openAddProductModal() {
    Swal.fire({
        title: 'Add New Product',
        html:
            '<input id="swal-name" class="swal2-input" placeholder="Product Name">' +
            '<input id="swal-price" type="number" step="0.01" min="0.01" class="swal2-input" placeholder="Price">' +
            '<input id="swal-stock" type="number" min="0" step="1" class="swal2-input" placeholder="Initial Stock">' +
            '<input id="swal-image" type="file" accept=".jpg,.jpeg" class="swal2-file">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Add Product',
        preConfirm: () => {
            const name = document.getElementById('swal-name').value;
            const price = document.getElementById('swal-price').value;
            const stock = document.getElementById('swal-stock').value;
            const image = document.getElementById('swal-image').files[0];

            if (!name || !price || !stock || !image) {
                Swal.showValidationMessage('Please fill all fields and select an image.');
            } else if(!['image/jpeg','image/jpg'].includes(image.type)) {
                Swal.showValidationMessage('Only JPG images are allowed.');
            }
            return { name: name, price: price, stock: stock, image: image };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = new FormData();
            form.append('name', result.value.name);
            form.append('price', result.value.price);
            form.append('stock', result.value.stock);
            form.append('image', result.value.image);
            form.append('add_product', '1');

            fetch('inventory.php', { method: 'POST', body: form })
            .then(res => res.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
            });
        }
    });
}


// CHART.JS DATA
const topDonutCtx = document.getElementById('topDonutChart').getContext('2d');
new Chart(topDonutCtx, {
    type:'doughnut',
    data:{
        labels: <?= json_encode($topLabels); ?>,
        datasets:[{data: <?= json_encode($topData); ?>, backgroundColor:['#00b4d8','#0077b6','#ff6b6b','#f9c74f','#90be6d']}]
    }
});

const stockCtx = document.getElementById('stockChart')?.getContext('2d');
if(stockCtx){
    new Chart(stockCtx, {
        type:'bar',
        data:{
            labels: <?= json_encode($filteredStockLabels); ?>,
            datasets:[
                {label:'Current Stock', data: <?= json_encode($filteredStockStock); ?>, backgroundColor:'#00b4d8'},
                {label:'Restocked', data: <?= json_encode($filteredStockRestocked); ?>, backgroundColor:'#0077b6'},
                {label:'Sold', data: <?= json_encode($filteredStockSold); ?>, backgroundColor:'#ff6b6b'}
            ]
        },
        options:{
            responsive:true,
            plugins:{legend:{position:'top'}},
            scales:{
                y:{beginAtZero:true}
            }
        }
    });
}

const salesVelocityCtx = document.getElementById('salesVelocityChart')?.getContext('2d');
if(salesVelocityCtx){
    new Chart(salesVelocityCtx, {
        type:'bar',
        data:{
            labels: <?= json_encode($filteredSalesLabels); ?>,
            datasets:[{label:'Units Sold per Day', data: <?= json_encode($filteredSalesData); ?>, backgroundColor:'#0077b6'}]
        }
    });
}

const projectedDepletionCtx = document.getElementById('projectedDepletionChart')?.getContext('2d');
if(projectedDepletionCtx){
    new Chart(projectedDepletionCtx, {
        type:'bar',
        data:{
            labels: <?= json_encode($filteredSalesLabels); ?>,
            datasets:[{
                label:'Days Remaining',
                data: <?= json_encode($filteredDepletionData); ?>,
                backgroundColor: <?= json_encode($filteredDepletionColors); ?>
            }]
        },
        options:{indexAxis:'y', responsive:true, plugins:{legend:{display:false}}}
    });
}

// Low/Out stock alert using SweetAlert2
<?php if(count($lowStockAlerts)>0): ?>
Swal.fire({
    icon: 'warning',
    title: 'Low Stock Alert!',
    html: 'The following products are low on stock:<br><b><?= implode('<br>', $lowStockAlerts); ?></b>'
});
<?php endif; ?>
<?php if(count($outStockAlerts)>0): ?>
Swal.fire({
    icon: 'error',
    title: 'Out of Stock!',
    html: 'The following products are out of stock:<br><b><?= implode('<br>', $outStockAlerts); ?></b>'
});
<?php endif; ?>
</script>

</div>
</body>
</html>