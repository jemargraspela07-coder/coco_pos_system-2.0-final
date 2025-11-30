<?php
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    header("Location: ../login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$customer_id = $_SESSION['customer'];
$cart = $_SESSION['cart'];

$total_price = 0;
$product_ids = implode(',', array_keys($cart));
$result = $conn->query("SELECT id, price, stock FROM products WHERE id IN ($product_ids)");

while ($row = $result->fetch_assoc()) {
    $pid = $row['id'];
    $qty = $cart[$pid]['quantity'];
    $total_price += $row['price'] * $qty;
}

$order_sql = "INSERT INTO orders (user_id, total_amount, order_date, status) 
              VALUES ($customer_id, $total_price, NOW(), 'Pending')";
if ($conn->query($order_sql)) {
    $order_id = $conn->insert_id;

    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $pid = $row['id'];
        $qty = $cart[$pid]['quantity'];
        $price = $row['price'];

        $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price)
                      VALUES ($order_id, $pid, $qty, $price)");

        $conn->query("UPDATE products SET stock = stock - $qty WHERE id = $pid");
    }

    $_SESSION['cart'] = [];

    header("Location: order_history.php?success=1");
    exit;
} else {
    echo "❌ Error placing order: " . $conn->error;
}
?>
