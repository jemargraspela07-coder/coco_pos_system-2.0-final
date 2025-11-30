<?php 
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['order_id'])) {
    header("Location: order_history.php");
    exit;
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['customer'];

$order_sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = $conn->query($order_sql);
if ($order_result->num_rows == 0) {
    echo "Order not found.";
    exit;
}
$order = $order_result->fetch_assoc();

$items_sql = "
    SELECT oi.*, p.name AS product_name, p.image AS product_image, p.price AS product_price
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
";
$items_result = $conn->query($items_sql);

$feedback_submitted = false;
if (isset($_POST['submit_feedback'])) {
    if($order['status'] === 'Completed') {
        $product_id = intval($_POST['product_id']);
        $rating = intval($_POST['rating']);
        $comment = trim($_POST['comment']);

        $check = $conn->query("SELECT * FROM feedback WHERE user_id=$user_id AND product_id=$product_id AND order_id=$order_id");
        if ($check->num_rows == 0 && $comment != '') {
            $stmt = $conn->prepare("INSERT INTO feedback (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $user_id, $product_id, $order_id, $rating, $comment);
            $stmt->execute();
            $feedback_submitted = true;
        }
    } else {
        echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'You can submit feedback only after your order is completed.'
        });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
.container { max-width: 1000px; margin: 50px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 30px 40px; }
h1 { text-align: center; font-weight: 600; font-size: 28px; color: #333; margin-bottom: 25px; }
.back-btn { display: inline-block; text-decoration: none; color: white; background: #2b3a55; padding: 10px 18px; border-radius: 8px; transition: 0.3s; }
.back-btn:hover { background-color: #2b3a55; }
.order-summary { display: flex; justify-content: space-between; flex-wrap: wrap; background: #f9fafc; border-radius: 12px; padding: 15px 20px; margin: 20px 0 30px 0; }
.order-summary p { margin: 5px 0; font-weight: 500; color: #333; }
.product-list { display: flex; flex-direction: column; gap: 15px; }
.product-item { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 12px; padding: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); flex-wrap: wrap; }
.product-info { display: flex; align-items: center; gap: 15px; flex: 1 1 60%; }
.product-info img { width: 90px; height: 90px; border-radius: 10px; object-fit: cover; border: 1px solid #ddd; }
.product-name { font-weight: 600; color: #222; font-size: 16px; }
.price, .qty, .subtotal { text-align: right; color: #444; min-width: 70px; }
.feedback { width: 100%; margin-top: 10px; }
.feedback form { margin-top: 5px; }
.feedback textarea { width: 100%; border-radius: 8px; border: 1px solid #ccc; padding: 8px; margin-top: 5px; }
.feedback button { background-color: #28a745; color: #fff; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; transition: 0.3s; margin-top: 5px; }
.feedback button:hover { background-color: #218838; }
.total { text-align: right; margin-top: 25px; font-weight: bold; font-size: 20px; }
.table img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
.star-rating { direction: rtl; display: inline-block; padding: 5px 0; }
.star-rating input[type=radio] { display: none; }
.star-rating label { font-size: 24px; color: #ccc; cursor: pointer; margin: 0 2px; }
.star-rating input[type=radio]:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label { color: #f39c12; }
@media (max-width: 768px) {
    .container { width: 95%; padding: 20px; margin: 20px auto; }
    .order-summary { flex-direction: column; gap: 8px; }
    .product-item { flex-direction: column; align-items: flex-start; }
    .product-info { width: 100%; }
    .price, .qty, .subtotal { width: auto; text-align: left; margin-top: 5px; }
}
</style>
</head>
<body>

<div class="container">
    <a href="order_history.php" class="back-btn mb-3">← Back</a>

    <?php
    $items_result->data_seek(0);
    $first_item = $items_result->fetch_assoc();

    $img = trim($first_item['product_image']);
    $possible_paths = ["../uploads/$img", "../images/$img", "../product_images/$img", "../$img"];
    $logo_path = "../assets/no-image.png"; 
    foreach ($possible_paths as $path) {
        if (!empty($img) && file_exists($path)) {
            $logo_path = $path;
            break;
        }
    }
    ?>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" 
             alt="Logo" 
             style="width: 140px; height: 140px; object-fit: cover; border-radius: 12px;">
    </div>

    <!-- Invoice Section -->
    <h2>Order Details</h2>
    <div class="order-summary">
        <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
        <p><strong>Pick-up Locations:</strong> Bliss, Monbon, Irosin, Sorsogon</p>
        <p><strong>Contact Number:</strong> 0950 081 1801</p>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product Image</th>
                <th>Product Name</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $items_result->data_seek(0);
            $grand_total = 0;
            while ($item = $items_result->fetch_assoc()):
                $subtotal = $item['price'] * $item['quantity'];
                $grand_total += $subtotal;

                $img = trim($item['product_image']);
                $possible_paths = ["../uploads/$img", "../images/$img", "../product_images/$img", "../$img"];
                $image_path = "../assets/no-image.png";
                foreach ($possible_paths as $path) {
                    if (!empty($img) && file_exists($path)) {
                        $image_path = $path;
                        break;
                    }
                }
            ?>
            <tr>
                <td><img src="<?php echo htmlspecialchars($image_path); ?>" alt="Product"></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₱<?php echo number_format($item['price'],2); ?></td>
                <td>₱<?php echo number_format($subtotal,2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p class="total"><strong>Grand Total: ₱<?php echo number_format($grand_total,2); ?></strong></p>

    <!-- Feedback Section -->
    <?php if($order['status'] === 'Completed'): ?>
        <h3>Leave Feedback</h3>
        <?php
        $items_result->data_seek(0);
        while($item = $items_result->fetch_assoc()):
            $pid = $item['product_id'];
            $fb = $conn->query("SELECT * FROM feedback WHERE user_id=$user_id AND product_id=$pid AND order_id=$order_id");
            if($fb->num_rows == 0):
        ?>
            <div class="feedback">
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                    <div class="star-rating">
                        <input type="radio" id="5-star-<?php echo $pid; ?>" name="rating" value="5"><label for="5-star-<?php echo $pid; ?>">★</label>
                        <input type="radio" id="4-star-<?php echo $pid; ?>" name="rating" value="4"><label for="4-star-<?php echo $pid; ?>">★</label>
                        <input type="radio" id="3-star-<?php echo $pid; ?>" name="rating" value="3"><label for="3-star-<?php echo $pid; ?>">★</label>
                        <input type="radio" id="2-star-<?php echo $pid; ?>" name="rating" value="2"><label for="2-star-<?php echo $pid; ?>">★</label>
                        <input type="radio" id="1-star-<?php echo $pid; ?>" name="rating" value="1"><label for="1-star-<?php echo $pid; ?>">★</label>
                    </div>
                    <textarea name="comment" rows="3" placeholder="Write your feedback..." required></textarea>
                    <button type="submit" name="submit_feedback">Submit</button>
                </form>
            </div>
        <?php else:
            $row = $fb->fetch_assoc();
            echo "<p>⭐ {$row['rating']}<br>" . htmlspecialchars($row['comment']) . "</p>";
        endif;
        endwhile;
        ?>
    <?php else: ?>
        <p>Feedback will be available once your order is completed.</p>
    <?php endif; ?>

</div>

<?php if($feedback_submitted): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Thank you!',
    text: 'Your feedback has been submitted.',
    timer: 2000,
    showConfirmButton: false
});
</script>
<?php endif; ?>

</body>
</html>
