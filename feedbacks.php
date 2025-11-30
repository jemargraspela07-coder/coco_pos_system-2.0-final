<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// ===== Pagination Setup =====
$limit = 10; // feedbacks per page
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ===== Rating Filter =====
$filterRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Total feedback count with filter
$totalQuery = "SELECT COUNT(*) AS total FROM feedback" . ($filterRating > 0 ? " WHERE rating = $filterRating" : "");
$totalResult = $conn->query($totalQuery);
$totalFeedbacks = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalFeedbacks / $limit);

// Fetch feedbacks for current page with filter
$sql = "
    SELECT f.*, 
           u.username AS customer_name, 
           p.name AS product_name
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.id
    LEFT JOIN products p ON f.product_id = p.id
    " . ($filterRating > 0 ? "WHERE f.rating = $filterRating" : "") . "
    ORDER BY f.created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Feedbacks - Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
body {display:flex;background:#f8f9fb;color:#333;}
.sidebar {width:240px;background:#0a2342;color:white;height:100vh;position:fixed;top:0;left:0;padding-top:20px;}
.sidebar h2 {text-align:center;margin-bottom:30px;font-size:22px;}
.sidebar a {display:block;color:white;text-decoration:none;padding:15px 20px;margin:5px 0;font-size:16px;transition:0.3s;}
.sidebar a:hover, .sidebar a.active {background:#1d3557;border-left:5px solid #00b4d8;}
.main {margin-left:240px;width:calc(100% - 240px);padding:20px 40px;}
.topbar {display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;}
.topbar h2 {color:#0a2342;}
.admin-info {display:flex;align-items:center;gap:10px;background:#1d3557;color:white;padding:8px 14px;border-radius:8px;font-size:14px;}
.admin-info a {color:white;text-decoration:none;display:flex;align-items:center;gap:6px;}
.admin-info a:hover {color:#00b4d8;}
.table-container {background:white;padding:25px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
table {width:100%;border-collapse:collapse;margin-top:10px;}
th, td {padding:12px 15px;text-align:left;border-bottom:1px solid #ddd;}
th {background:#1d3557;color:white;}
tr:hover {background:#f1f1f1;}
.star-rating {color:#FFD700;}
.comment-box {max-width:350px;white-space:normal;word-wrap:break-word;}
.pagination {text-align:center; margin-top:15px;}
.pagination a {display:inline-block; margin:0 5px; padding:6px 12px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none; transition:0.2s;}
.pagination a:hover {background:#0056b3;}
.pagination span {margin:0 5px; padding:6px 12px; display:inline-block;}
@media (max-width:768px){
    .sidebar{width:180px;}
    .main{margin-left:180px;padding:15px;}
    table{font-size:14px;}
}
.filter-form {margin-bottom:15px;}
.filter-form label {margin-right:8px; font-weight:bold;}
.filter-form select {padding:4px 8px;}
</style>
</head>
<body>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php')?'active':''; ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php')?'active':''; ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php')?'active':''; ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php')?'active':''; ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php')?'active':''; ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php')?'active':''; ?>"><i class="fa fa-star"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>Customer Feedbacks</h2>
        <div class="admin-info">
            <span>Welcome, <?= htmlspecialchars($_SESSION['admin']); ?></span>
            <a href="profile.php"><i class="fa fa-user-circle"></i> Profile</a>
        </div>
    </div>

    <div class="table-container">
        <h3 style="color:#1d3557;margin-bottom:10px;"><i class="fa fa-comments"></i> All Customer Feedback</h3>

        <!-- Rating Filter Form -->
        <form method="get" class="filter-form">
            <label for="rating">Filter by Rating:</label>
            <select name="rating" id="rating" onchange="this.form.submit()">
                <option value="0" <?= $filterRating === 0 ? 'selected' : ''; ?>>All Ratings</option>
                <option value="1" <?= $filterRating === 1 ? 'selected' : ''; ?>>1 Star</option>
                <option value="2" <?= $filterRating === 2 ? 'selected' : ''; ?>>2 Stars</option>
                <option value="3" <?= $filterRating === 3 ? 'selected' : ''; ?>>3 Stars</option>
                <option value="4" <?= $filterRating === 4 ? 'selected' : ''; ?>>4 Stars</option>
                <option value="5" <?= $filterRating === 5 ? 'selected' : ''; ?>>5 Stars</option>
            </select>
        </form>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Order ID</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $count = $offset + 1; while ($fb = $result->fetch_assoc()): ?>
                        <?php 
                            $stars = isset($fb['rating']) ? (int)$fb['rating'] : 0;
                            $stars = max(0, min(5, $stars)); // siguraduhin 0-5 range
                        ?>
                        <tr>
                            <td><?= $count++; ?></td>
                            <td><?= htmlspecialchars($fb['customer_name'] ?? 'Unknown'); ?></td>
                            <td><?= htmlspecialchars($fb['product_name'] ?? 'Unknown'); ?></td>
                            <td><?= htmlspecialchars($fb['order_id']); ?></td>
                            <td>
                                <span class="star-rating">
                                    <?= str_repeat('★', $stars); ?>
                                    <?= str_repeat('☆', 5 - $stars); ?>
                                </span>
                            </td>
                            <td class="comment-box"><?= htmlspecialchars($fb['comment']); ?></td>
                            <td><?= date("F d, Y h:i A", strtotime($fb['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No feedbacks found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
            <?php
            // Build base URL for pagination while preserving rating filter
            $baseUrl = '?';
            if ($filterRating > 0) $baseUrl .= "rating=$filterRating&";
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?>page=<?= $page - 1 ?>"><i class="fa fa-arrow-left"></i> Prev</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $baseUrl ?>page=<?= $page + 1 ?>">Next <i class="fa fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>

    </div>
</div>
</body>
</html>
