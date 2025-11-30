<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// ===== Handle Mark as Ready =====
if (isset($_POST['mark_ready'])) {
    $order_id = intval($_POST['order_id']);
    $update = $conn->query("UPDATE orders SET status='Ready for Pick Up' WHERE id = $order_id");
    if (!$update) die("Error updating order: " . $conn->error);
    echo "<script>window.location.href='orders.php';</script>";
}

// ===== Handle Mark as Completed =====
if (isset($_POST['mark_completed'])) {
    $order_id = intval($_POST['order_id']);
    $update = $conn->query("UPDATE orders SET status='Completed' WHERE id = $order_id");
    if (!$update) die("Error updating order: " . $conn->error);

    $items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");
    if ($items && $items->num_rows > 0) {
        while ($item = $items->fetch_assoc()) {
            $product_id = intval($item['product_id']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $total_price = $quantity * $price;

            $exists = $conn->query("SELECT COUNT(*) AS cnt FROM sales WHERE order_id = $order_id AND product_id = $product_id")->fetch_assoc()['cnt'];
            if ($exists == 0) {
                $insert = $conn->query("INSERT INTO sales (order_id, product_id, quantity, total_price, sale_date) VALUES ($order_id, $product_id, $quantity, $total_price, NOW())");
                if (!$insert) die("Error inserting into sales: " . $conn->error);
            }
        }
    }

    echo "<script>window.location.href='orders.php';</script>";
}

// ===== Pagination Setup =====
$limit = 10;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ===== Filter Setup =====
$status_filter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$date_filter = isset($_GET['order_date']) ? $conn->real_escape_string($_GET['order_date']) : '';

$where = "WHERE 1=1";
if ($status_filter != '') $where .= " AND o.status='$status_filter'";
if ($date_filter != '') $where .= " AND DATE(o.created_at)='$date_filter'";

// ===== Count total orders with filter =====
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM orders o $where");
$totalOrders = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $limit);

// ===== Fetch orders with filter =====
$sql = "
    SELECT 
        o.id AS order_id,
        u.username,
        IF(o.total_amount IS NOT NULL AND o.total_amount > 0, o.total_amount, o.total) AS total_display,
        o.status,
        o.created_at AS order_date,
        IFNULL(GROUP_CONCAT(p.name SEPARATOR ', '), 'No products') AS product_names
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    $where
    GROUP BY o.id
    ORDER BY o.id DESC
    LIMIT $limit OFFSET $offset
";
$orders = $conn->query($sql);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Orders - Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
body { display:flex; background:#f8f9fb; color:#333; }
.sidebar { width:240px; background:#0a2342; color:white; height:100vh; position:fixed; top:0; left:0; padding-top:20px; }
.sidebar h2 { text-align:center; margin-bottom:30px; font-size:22px; }
.sidebar a { display:block; color:white; text-decoration:none; padding:15px 20px; margin:5px 0; font-size:16px; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:#1d3557; border-left:5px solid #00b4d8; }
.main { margin-left:240px; width:calc(100% - 240px); padding:20px 40px; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.topbar h2 { color:#0a2342; font-size:24px; }
table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:15px; table-layout: fixed; }
th, td { padding:14px 18px; text-align:center; border-bottom:1px solid #f1f1f1; word-wrap: break-word; height:70px; }
th { background:#1d3557; color:white; text-transform:uppercase; font-size:14px; letter-spacing:0.5px; }
tr:hover { background:#f0f8ff; }
td { font-size:15px; overflow: hidden; text-overflow: ellipsis; }
.btn { background:#00b4d8; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; transition:0.2s; margin:2px; text-decoration:none; }
.btn:hover { background:#0077b6; }
.btn-green { background:#28a745; }
.btn-green:hover { background:#218838; }
.section-title { margin:20px 0 10px; font-size:20px; color:#0a2342; border-left:5px solid #00b4d8; padding-left:10px; }
.pagination { text-align:center; margin-top:10px; }
.pagination a { display:inline-block; margin:0 5px; padding:6px 12px; background:#00b4d8; color:#fff; border-radius:6px; text-decoration:none; transition:0.2s; }
.pagination a:hover { background:#0077b6; }
.pagination span { display:inline-block; margin:0 5px; padding:6px 12px; }
@media (max-width:768px) { 
    .sidebar { width:180px; } 
    .main { margin-left:180px; padding:15px; } 
    table { font-size:13px; } 
    .btn, .pagination a { font-size:12px; padding:4px 8px; } 
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php') ? 'active':'' ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php') ? 'active':'' ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php') ? 'active':'' ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php') ? 'active':'' ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php') ? 'active':'' ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php') ? 'active' : ''; ?>"><i class="fa fa-comments"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>Customers Order</h2>
    </div>

    <h3 class="section-title">Orders List</h3>

    <!-- Filter Form -->
    <form method="GET" style="margin-bottom:15px;">
        <label>Status:</label>
        <select name="status">
            <option value="">All</option>
            <option value="Pending" <?= ($status_filter=='Pending') ? 'selected':'' ?>>Pending</option>
            <option value="Ready for Pick Up" <?= ($status_filter=='Ready for Pick Up') ? 'selected':'' ?>>Ready for Pick Up</option>
            <option value="Completed" <?= ($status_filter=='Completed') ? 'selected':'' ?>>Completed</option>
        </select>

        <label>Order Date:</label>
        <input type="date" name="order_date" value="<?= $date_filter ?>">

        <button type="submit">Filter</button>
        <button type="button" style="margin-left:10px;" onclick="resetFilters()">Reset</button>
    </form>

    <table>
        <tr>
            <th>Order Date</th>
            <th>Product Name</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $orders->fetch_assoc()): ?>
        <tr>
            <td><?= date('M d, Y H:i', strtotime($row['order_date'])) ?></td>
            <td><?= htmlspecialchars($row['product_names']) ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td>₱<?= number_format($row['total_display'], 2) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <button class="btn" onclick="viewOrder(<?= $row['order_id'] ?>)">
                    <i class="fa fa-eye"></i> View
                </button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="orders.php?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&order_date=<?= urlencode($date_filter) ?>"><i class="fa fa-arrow-left"></i> Prev</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="orders.php?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&order_date=<?= urlencode($date_filter) ?>">Next <i class="fa fa-arrow-right"></i></a>
        <?php endif; ?>
    </div>
</div>

<script>
function viewOrder(orderId) {
    fetch('orders_view.php?order_id=' + orderId)
    .then(response => response.text())
    .then(data => {
        Swal.fire({
            title: 'Order Details',
            html: data,
            width: '800px',
            showCloseButton: true,
            showCancelButton: false,
            showConfirmButton: false
        });
    });
}

function changeStatus(orderId, actionType) {
    Swal.fire({
        title: 'Are you sure?',
        text: actionType === 'mark_ready' ? 'Mark this order as Ready for Pick Up?' : 'Mark this order as Completed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: actionType === 'mark_ready' ? '#3085d6' : '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = 'orders.php';

            let idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'order_id';
            idField.value = orderId;

            let actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = actionType;
            actionField.value = true;

            form.appendChild(idField);
            form.appendChild(actionField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Reset button function
function resetFilters() {
    document.querySelector('select[name="status"]').value = '';
    document.querySelector('input[name="order_date"]').value = '';
    document.querySelector('form').submit();
}
</script>

</body>
</html>
