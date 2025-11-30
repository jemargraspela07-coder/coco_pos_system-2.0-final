<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['customer'])) {
    echo json_encode(['success'=>false,'message'=>'Please log in first.']);
    exit;
}

if (!isset($_POST['id'], $_POST['action'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid request.']);
    exit;
}

$id = $_POST['id'];
$action = $_POST['action'];
$cart = &$_SESSION['cart'];

if (!isset($cart[$id])) {
    echo json_encode(['success'=>false,'message'=>'Item not found in cart.']);
    exit;
}

$stmt = $conn->prepare("SELECT stock, price FROM products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success'=>false,'message'=>'Product not found.']);
    exit;
}
$product = $res->fetch_assoc();
$current_stock = (int)$product['stock'];
$stmt->close();

switch ($action) {
    case 'increase':
        if ($cart[$id]['quantity'] + 1 > $current_stock) {
            echo json_encode(['success'=>false,'message'=>'Not enough stock available.']);
            exit;
        }
        $cart[$id]['quantity']++;
        break;

    case 'decrease':
        $cart[$id]['quantity']--;
        if ($cart[$id]['quantity'] <= 0) {
            unset($cart[$id]);
        }
        break;

    case 'remove':
        unset($cart[$id]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
        exit;
}

$grand_total = 0;
foreach ($cart as $item) {
    $grand_total += $item['price'] * $item['quantity'];
}

$response = [
    'success'=>true,
    'new_quantity'=> isset($cart[$id]) ? $cart[$id]['quantity'] : 0,
    'subtotal'=> isset($cart[$id]) ? $cart[$id]['price'] * $cart[$id]['quantity'] : 0,
    'grand_total'=> $grand_total
];

echo json_encode($response);
exit;
?>
