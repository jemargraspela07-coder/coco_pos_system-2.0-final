<?php
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['customer'];

// ===== Pagination Setup =====
$limit = 5; // orders per page (you can adjust)
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Total orders count
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE user_id=$user_id");
$totalOrders = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalOrders / $limit);

// Fetch orders for current page
$sql = "SELECT * FROM orders WHERE user_id=$user_id ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order History</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f7f9fb; margin: 0; padding: 0; }
.container { max-width: 950px; margin-top: 80px; }
.card { border: none; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden; }
.card-header { background: linear-gradient(135deg, #2b3a55, #2b3a55); color: white; font-size: 1.4rem; font-weight: 600; text-align: center; padding: 20px; }
.back-btn { display: inline-block; background-color: #2b3a55; color: white; padding: 10px 10px; border-radius: 8px; text-decoration: none; transition: 0.3s; }
.back-btn:hover { background-color: #5a6268; }
.alert-success { background-color: #d4edda; color: #155724; border-left: 6px solid #28a745; border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; }
.table { margin-bottom: 0; }
.table th { background-color: #f8f9fa; color: #333; font-weight: 600; text-transform: uppercase; font-size: 14px; }
.table td { vertical-align: middle; font-size: 15px; }
.status { display: inline-block; padding: 6px 14px; border-radius: 25px; color: #fff; font-weight: 600; font-size: 13px; }
.status.pending { background-color: #ffc107; }
.status.ready { background-color: #17a2b8; }
.status.completed { background-color: #28a745; }
.btn-view { background: #007bff; color: white; text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 14px; transition: 0.3s; }
.btn-view:hover { background: #0056b3; }
.no-orders { text-align: center; color: #777; margin: 30px 0; font-size: 16px; }
.pagination { text-align:center; margin-top:20px; }
.pagination a { display:inline-block; margin:0 5px; padding:6px 12px; background:#007bff; color:#fff; border-radius:6px; text-decoration:none; transition:0.2s; }
.pagination a:hover { background:#0056b3; }
.pagination span { margin:0 5px; padding:6px 12px; display:inline-block; }
@media (max-width: 768px) {
    .container { width: 95%; margin-top: 40px; }
    .table { display: none; }
    .order-card { background: white; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); padding: 15px; margin-bottom: 15px; }
    .order-card .top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .order-card .product-name { font-weight: 600; color: #333; font-size: 16px; }
    .order-card .price { font-weight: 500; color: #000; font-size: 15px; }
    .order-card .details { font-size: 13px; color: #555; margin-bottom: 6px; }
    .order-card .btn-view { display: inline-block; width: 100%; text-align: center; margin-top: 8px; padding: 8px 0; font-size: 14px; }
}
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">Order History</div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <a href="products_customer.php" class="back-btn mb-2">← Back to Shop</a>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert-success w-100 mt-2">✅ Your order was successfully placed!</div>
                <?php endif; ?>
            </div>

            <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                            $product_name = '';
                            $order_id = $row['id'];
                            $prod_query = $conn->query("SELECT p.name FROM order_items oi 
                                                        JOIN products p ON oi.product_id = p.id 
                                                        WHERE oi.order_id = $order_id LIMIT 1");
                            if ($prod_query && $prod_query->num_rows > 0) {
                                $product_name = $prod_query->fetch_assoc()['name'];
                            } else {
                                $product_name = 'N/A';
                            }

                            $statusClass = '';
                            switch (strtolower($row['status'])) {
                                case 'pending': $statusClass = 'pending'; break;
                                case 'ready for pick up': $statusClass = 'ready'; break;
                                case 'completed': $statusClass = 'completed'; break;
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($product_name) ?></td>
                            <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                            <td><span class="status <?= $statusClass ?>"><?= ucfirst($row['status']); ?></span></td>
                            <td><?= $row['created_at']; ?></td>
                            <td><a href="order_details.php?order_id=<?= $row['id']; ?>" class="btn-view">View</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none">
                <?php 
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()):
                    $order_id = $row['id'];
                    $prod_query = $conn->query("SELECT p.name FROM order_items oi 
                                                JOIN products p ON oi.product_id = p.id 
                                                WHERE oi.order_id = $order_id LIMIT 1");
                    $product_name = ($prod_query && $prod_query->num_rows > 0)
                        ? $prod_query->fetch_assoc()['name']
                        : 'N/A';
                    $statusClass = '';
                    switch (strtolower($row['status'])) {
                        case 'pending': $statusClass = 'pending'; break;
                        case 'ready for pick up': $statusClass = 'ready'; break;
                        case 'completed': $statusClass = 'completed'; break;
                    }
                ?>
                <div class="order-card">
                    <div class="top">
                        <div class="product-name"><?= htmlspecialchars($product_name) ?></div>
                        <div class="price">₱<?= number_format($row['total_amount'], 2) ?></div>
                    </div>
                    <div class="details"><b>Status:</b> <span class="status <?= $statusClass ?>"><?= ucfirst($row['status']); ?></span></div>
                    <div class="details"><b>Date:</b> <?= $row['created_at']; ?></div>
                    <a href="order_details.php?order_id=<?= $row['id']; ?>" class="btn-view">View Details</a>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>"><i class="fa fa-arrow-left"></i> Prev</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>">Next <i class="fa fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>

            <?php else: ?>
                <p class="no-orders">You have no orders yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
