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

$cart = $_SESSION['cart'];
$ids = implode(',', array_keys($cart));
$result = $conn->query("SELECT id, name, price, image FROM products WHERE id IN ($ids)");

$cart_items = [];
$total_price = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $qty = $cart[$id]['quantity'];
    $subtotal = $row['price'] * $qty;
    $row['quantity'] = $qty;
    $row['subtotal'] = $subtotal;
    $cart_items[] = $row;
    $total_price += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Checkout Summary</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f5f5f5;color:#000;min-height:100vh;}
header{
  background:#435b7e;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  padding:15px 20px;position:fixed;width:100%;top:0;left:0;z-index:1000;
  flex-wrap:wrap;
}
header a{color:#fff;text-decoration:none;margin:0 8px;font-weight:500;transition:0.3s;}
header a:hover{color:#00b4d8;}
.container{
  max-width:1000px;margin:120px auto 60px auto;background:#fff;
  border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);
  padding:25px;
}
h2{text-align:center;margin-bottom:25px;color:#000;}
.table{
  width:100%;border-collapse:collapse;margin-bottom:20px;
}
.table th, .table td{
  border-bottom:1px solid #ddd;padding:12px 10px;text-align:left;vertical-align:middle;
}
.table th{background:#f0f0f0;color:#000;}
.product-img{
  width:60px;height:60px;border-radius:8px;object-fit:cover;margin-right:10px;border:1px solid #ddd;vertical-align:middle;
}
.total{
  text-align:right;font-size:20px;font-weight:600;margin-top:15px;color:#000;
}
.buttons{
  display:flex;justify-content:flex-end;gap:15px;margin-top:25px;flex-wrap:wrap;
}
.btn{
  display:inline-block;padding:12px 25px;border-radius:8px;
  font-weight:600;text-decoration:none;text-align:center;transition:0.3s;flex:1;
}
.btn-back{background:#ccc;color:#000;}
.btn-back:hover{background:#999;}
.btn-confirm{background:#00b4d8;color:#fff;}
.btn-confirm:hover{background:#009ecf;}

@media (max-width:768px){
  .table, .table thead, .table tbody, .table th, .table td, .table tr{display:block;width:100%;}
  .table thead{display:none;}
  .table tr{margin-bottom:15px;border-bottom:2px solid #eee;}
  .table td{
    text-align:right;
    padding-left:50%;
    position:relative;
    font-size:14px;
  }
  .table td::before{
    content: attr(data-label);
    position:absolute;
    left:10px;
    width:45%;
    text-align:left;
    font-weight:600;
  }
  .product-img{width:50px;height:50px;margin-right:8px;}
  .total{text-align:left;}
  .buttons .btn{flex:100%;width:100%;}
}
</style>
</head>
<body>

<header>
  <div>
    <a href="index.php">Home</a>
    <a href="products_customer.php">Products</a>
    <a href="order_history.php">Orders</a>
  </div>
  <div>
    <a href="profile.php">Profile</a>
    <a href="index.php?logout=1">Logout</a>
  </div>
</header>

<div class="container">
  <h2>Checkout Summary</h2>

  <table class="table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($cart_items as $item): ?>
      <tr>
        <td data-label="Product">
          <img src="../<?php echo htmlspecialchars($item['image']); ?>" class="product-img" alt="">
          <?php echo htmlspecialchars($item['name']); ?>
        </td>
        <td data-label="Price">₱<?php echo number_format($item['price'], 2); ?></td>
        <td data-label="Quantity"><?php echo $item['quantity']; ?></td>
        <td data-label="Subtotal">₱<?php echo number_format($item['subtotal'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="total">Total Amount: ₱<?php echo number_format($total_price, 2); ?></div>

  <div class="buttons">
    <a href="cart.php" class="btn btn-back">← Back to Cart</a>
    <a href="confirm_order.php" class="btn btn-confirm">✅ Confirm Order</a>
  </div>
</div>

</body>
</html>
