<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin']) || !isset($_GET['order_id'])) exit;

$order_id = intval($_GET['order_id']);

// Correct absolute URL for images
$baseURL = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/coco_pos_system/images/';

// Fetch order info with cp_number
$orderQuery = $conn->query("
    SELECT o.*, u.username, u.cp_number 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = $order_id
");

if ($orderQuery->num_rows === 0) {
    echo "<p>Order not found.</p>";
    exit;
}

$order = $orderQuery->fetch_assoc();

// Fetch order items
$items = $conn->query("
    SELECT oi.*, p.name, p.image, p.price 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = $order_id
");
?>

<style>
/* Modern card layout */
.order-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    max-width: 800px;
    margin-left:auto;
    margin-right:auto;
}
.order-card h3 {
    margin: 0 0 5px;
    font-size: 1.4em;
}
.order-card p {
    margin: 4px 0;
    color: #555;
    font-size: 0.95em;
}

/* Modern table */
.order-table {
    width: 100%;
    border-collapse: collapse;
    max-width: 800px;
    margin-left:auto;
    margin-right:auto;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.order-table th, .order-table td {
    padding: 12px 15px;
    text-align: center;
}
.order-table th {
    background: #2b3a55;
    color: #fff;
    font-weight: 500;
}
.order-table tr {
    background: #fff;
    transition: background 0.2s;
}
.order-table tr:hover {
    background: #f5f5f5;
}
.order-table img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

/* Buttons */
.btn {
    padding: 8px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: 0.2s;
}
.btn:hover {
    opacity: 0.9;
}
.btn-primary {
    background: #007BFF;
    color: #fff;
}
.btn-success {
    background: #28a745;
    color: #fff;
}
.button-container {
    max-width: 800px;
    margin:auto;
    margin-top: 15px;
    display:flex;
    justify-content:flex-end;
    gap: 10px;
}
</style>

<div class="order-card">
    <h3><?= htmlspecialchars($order['username']); ?></h3>
    <p><strong>Contact Number:</strong> <?= htmlspecialchars($order['cp_number']); ?></p>
    <p><strong>Status:</strong> <?= $order['status']; ?></p>
</div>

<table class="order-table">
    <tr>
        <th>Image</th>
        <th>Product</th>
        <th>Quantity</th>
        <th>Price</th>
        <th>Total</th>
    </tr>
<?php
$total_order = 0;
while ($item = $items->fetch_assoc()) {
    $total_price = $item['quantity'] * $item['price'];
    $total_order += $total_price;

    $imageFile = basename($item['image']);
    $imagePath = $baseURL . $imageFile;
    ?>
    <tr>
        <td><img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($item['name']); ?>"></td>
        <td><?= htmlspecialchars($item['name']); ?></td>
        <td><?= $item['quantity']; ?></td>
        <td>₱<?= number_format($item['price'],2); ?></td>
        <td>₱<?= number_format($total_price,2); ?></td>
    </tr>
<?php } ?>
    <tr style="font-weight:bold; background:#f0f0f0;">
        <td colspan="4">Total Order</td>
        <td>₱<?= number_format($total_order,2); ?></td>
    </tr>
</table>

<div class="button-container">
<?php if (strtolower($order['status']) === 'pending'): ?>
    <button class="btn btn-primary" onclick="changeStatus(<?= $order_id ?>, 'mark_ready')">Mark as Ready</button>
<?php endif; ?>
<?php if (strtolower($order['status']) === 'ready for pick up'): ?>
    <button class="btn btn-success" onclick="changeStatus(<?= $order_id ?>, 'mark_completed')">Mark as Completed</button>
<?php endif; ?>
</div>
