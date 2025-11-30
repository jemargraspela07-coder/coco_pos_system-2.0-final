<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0
];

if (!isset($_SESSION['customer'])) {
    $response['message'] = 'Please login first.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['product_id'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

$stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id=? AND is_archived=0 LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

$current_stock = (int)$product['stock'];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$current_qty_in_cart = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
$new_quantity = $current_qty_in_cart + $quantity;

if ($new_quantity > $current_stock) {
    $response['message'] = 'Only ' . $current_stock . ' left in stock.';
    echo json_encode($response);
    exit;
}

$_SESSION['cart'][$product_id] = [
    'id' => $product['id'],
    'name' => $product['name'],
    'price' => $product['price'],
    'image' => $product['image'],
    'quantity' => $new_quantity
];

$response['success'] = true;
$response['message'] = $product['name'] . ' added to cart!';
$response['cart_count'] = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode($response);
exit;
?>
