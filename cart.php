<?php
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    header("Location: login.php");
    exit;
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

$grand_total = 0;
foreach ($cart as $item) {
    $grand_total += isset($item['price'],$item['quantity']) ? $item['price'] * $item['quantity'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Cart</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{font-family:'Poppins',sans-serif;background:#f5f5f5;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
h2{color:#435b7e;margin-bottom:20px;text-align:center;}
.cart-header{display:flex;font-weight:bold;padding-bottom:10px;border-bottom:2px solid #00b4d8;margin-bottom:15px;color:#435b7e;}
.cart-header div{flex:1;text-align:center;}
.cart-item{display:flex;align-items:center;margin-bottom:15px;padding:12px;border-radius:10px;background:#f9f9f9;flex-wrap:wrap;box-shadow:0 2px 6px rgba(0,0,0,0.05);}
.cart-item img{width:80px;height:80px;object-fit:cover;border-radius:6px;margin-right:15px;border:2px solid #00b4d8;}
.item-info{flex:2;}
.item-info strong{display:block;margin-bottom:5px;color:#435b7e;}
.item-info .price{color:##000;font-weight:bold;}
.item-qty{flex:1;display:flex;justify-content:center;align-items:center;margin-top:5px;}
.qty-btn{cursor:pointer;padding:6px 12px;margin:0 5px;background:#00b4d8;color:#fff;border:none;border-radius:6px;transition:0.3s;}
.qty-btn:hover{opacity:0.85;}
.qty{min-width:30px;text-align:center;font-weight:bold;color:#435b7e;}
.remove-btn{flex:0.2;cursor:pointer;color:#e63946;font-size:20px;border:none;background:none;margin-top:5px;transition:0.3s;}
.remove-btn:hover{color:#d90429;}
.grand-total{text-align:right;font-size:20px;font-weight:bold;margin-top:20px;color:#435b7e;}
.empty{text-align:center;margin-top:50px;font-size:18px;color:#435b7e;}
.empty button{padding:10px 20px;background:#00b4d8;color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:0.3s;}
.empty button:hover{opacity:0.9;}
.cart-footer{display:flex;justify-content:space-between;margin-top:25px;flex-wrap:wrap;gap:10px;}
.cart-footer button{padding:12px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:600;flex:1;transition:0.3s;}
.back-btn{background:#ccc;color:#000;}
.place-btn{background:#00b4d8;color:#fff;}
.cart-footer button:hover{opacity:0.9;}

/* Mobile Responsive */
@media (max-width: 768px) {
    .cart-header {display:none;}
    .cart-item {flex-direction: column;align-items: flex-start;padding:15px 10px;}
    .cart-item img {width:100%;max-width:150px;margin-bottom:10px;border:2px solid #00b4d8;}
    .item-info {width:100%;margin-bottom:10px;}
    .item-info strong {font-size:16px;margin-bottom:5px;word-break:break-word;}
    .item-info .price {font-size:15px;}
    .item-qty {width:100%;display:flex;justify-content:flex-start;align-items:center;margin-bottom:10px;gap:10px;}
    .qty-btn {padding:8px 12px;font-size:16px;}
    .qty {min-width:30px;text-align:center;font-size:16px;}
    .remove-btn {align-self:flex-end;font-size:22px;margin-top:0;}
    .cart-footer {flex-direction:column;gap:10px;}
    .cart-footer button {width:100%;padding:12px 0;font-size:16px;}
    .grand-total {text-align:left;font-size:18px;margin-top:15px;}
}
</style>
</head>
<body>
<div class="container">
<h2>My Cart</h2>

<?php if(count($cart) == 0): ?>
    <p class="empty">Your cart is empty.<br>
    <button onclick="window.location.href='products_customer.php'">Shop Now</button></p>
<?php else: ?>
<div class="cart-header">
    <div>Product</div>
    <div>Quantity</div>
    <div>Remove</div>
</div>

<?php foreach($cart as $id => $item): ?>
<div class="cart-item" data-id="<?php echo htmlspecialchars($id); ?>">
    <img src="../<?php echo !empty($item['image']) ? $item['image'] : 'images/default.png'; ?>" 
         alt="<?php echo isset($item['name']) ? htmlspecialchars($item['name']) : 'Product'; ?>">
    <div class="item-info">
        <strong><?php echo isset($item['name']) ? htmlspecialchars($item['name']) : 'Unnamed Product'; ?></strong>
        <span class="price">₱<?php echo isset($item['price']) ? number_format($item['price'],2) : '0.00'; ?></span>
    </div>
    <div class="item-qty">
        <button class="qty-btn decrease">-</button>
        <span class="qty"><?php echo isset($item['quantity']) ? $item['quantity'] : 0; ?></span>
        <button class="qty-btn increase">+</button>
    </div>
    <button class="remove-btn">×</button>
</div>
<?php endforeach; ?>

<div class="grand-total">
    Grand Total: ₱<span id="grand_total"><?php echo number_format($grand_total,2); ?></span>
</div>

<div class="cart-footer">
    <button class="back-btn" onclick="window.location.href='products_customer.php'">Back to Products</button>
    <button class="place-btn" id="place_order_btn">Place Order</button>
</div>
<?php endif; ?>
</div>

<script>
document.querySelectorAll('.increase, .decrease').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.cart-item');
        const id = item.dataset.id;
        const action = btn.classList.contains('increase') ? 'increase' : 'decrease';

        fetch('update_cart_quantity.php', {
            method: 'POST',
            body: new URLSearchParams({ id, action })
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success){ Swal.fire('⚠️', data.message, 'warning'); return; }
            const qtyElem = item.querySelector('.qty');
            qtyElem.innerText = data.new_quantity;
            document.getElementById('grand_total').innerText = Number(data.grand_total).toLocaleString(undefined,{minimumFractionDigits:2});
            if(data.new_quantity === 0){ item.remove();
                if(document.querySelectorAll('.cart-item').length === 0){
                    document.querySelector('.container').innerHTML = '<p class="empty">Your cart is empty.<br><button onclick="window.location.href=\'products_customer.php\'">Shop Now</button></p>';
                }
            }
        }).catch(err => console.error(err));
    });
});

document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.cart-item');
        const id = item.dataset.id;

        fetch('update_cart_quantity.php', {
            method: 'POST',
            body: new URLSearchParams({ id, action: 'remove' })
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success){ Swal.fire('⚠️', data.message, 'warning'); return; }
            item.remove();
            document.getElementById('grand_total').innerText = Number(data.grand_total).toLocaleString(undefined,{minimumFractionDigits:2});
            if(document.querySelectorAll('.cart-item').length === 0){
                document.querySelector('.container').innerHTML = '<p class="empty">Your cart is empty.<br><button onclick="window.location.href=\'products_customer.php\'">Shop Now</button></p>';
            }
            Swal.fire('Removed!','Item has been removed from your cart.','success');
        }).catch(err => console.error(err));
    });
});

document.getElementById('place_order_btn')?.addEventListener('click', () => {
    Swal.fire({
        title: 'Confirm Order',
        text: "Proceed to checkout?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#00b4d8',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes'
    }).then((result) => {
        if(result.isConfirmed){
            window.location.href = 'check_out.php';
        }
    });
});
</script>
</body>
</html>
