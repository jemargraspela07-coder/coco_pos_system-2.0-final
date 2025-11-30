<?php
session_start();
header('Content-Type: application/json');
include '../db.php';

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$updatedCart = [];
foreach($cart as $item){
    $product_id = intval($item['id']);
    $qty = intval($item['quantity']);

    $stmt = $conn->prepare("SELECT name, price, stock FROM products WHERE id=? AND is_archived=0");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows > 0){
        $product = $res->fetch_assoc();

        if($qty > $product['stock']){
            $qty = $product['stock'];
        }

        $updatedCart[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => floatval($product['price']),
            'quantity' => $qty
        ];
    }
}

$_SESSION['cart'] = $updatedCart;

echo json_encode([
    'items' => $updatedCart
]);
