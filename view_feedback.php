<?php
include '../db.php';
session_start();

if (!isset($_GET['product_id'])) {
    header("Location: ../customer/products_customer.php");
    exit;
}

$product_id = intval($_GET['product_id']);
$product = $conn->query("SELECT * FROM products WHERE id=$product_id")->fetch_assoc();
$feedbacks = $conn->query("SELECT f.*, u.username FROM feedback f 
                           JOIN users u ON f.user_id=u.id 
                           WHERE product_id=$product_id ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Feedback</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    max-width: 850px;
    margin: 60px auto;
    background: #fff;
    border-radius: 15px;
    padding: 30px 40px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    position: relative;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
    color: #222;
}

.back-btn {
    display: inline-block;
    text-decoration: none;
    color: #fff;
    background: #007bff;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    transition: 0.3s;
}
.back-btn:hover {
    background: #0056b3;
}

.feedback {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #fafafa;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}
.feedback:hover {
    transform: scale(1.01);
}

.avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
}

.feedback-content {
    flex: 1;
}

.feedback .stars {
    color: #FFD700;
    font-size: 18px;
    margin-bottom: 8px;
}

.feedback p {
    margin: 0;
    font-size: 15px;
    color: #555;
    line-height: 1.5;
}

.feedback small {
    display: block;
    margin-top: 8px;
    font-size: 13px;
    color: #888;
}

.no-feedback {
    text-align: center;
    padding: 30px 0;
    color: #666;
    background: #f8f8f8;
    border-radius: 10px;
}

.product-header {
    text-align: center;
    margin-bottom: 30px;
}
.product-header h3 {
    font-size: 20px;
    color: #444;
    font-weight: 500;
}
</style>
</head>
<body>

<div class="container">
    <a href="../customer/products_customer.php" class="back-btn">← Back</a>
    
    <div class="product-header">
        <h2>Customer Feedback</h2>
        <h3>"<?php echo htmlspecialchars($product['name']); ?>"</h3>
    </div>

    <?php if ($feedbacks->num_rows == 0): ?>
        <div class="no-feedback">
            <p>No feedback yet for this product.</p>
        </div>
    <?php else: ?>
        <?php while ($fb = $feedbacks->fetch_assoc()): ?>
            <div class="feedback">
                <div class="avatar">
                    <?php echo strtoupper(substr($fb['username'], 0, 1)); ?>
                </div>
                <div class="feedback-content">
                    <div class="stars"><?php echo str_repeat('⭐', $fb['rating']); ?></div>
                    <p><?php echo htmlspecialchars($fb['comment']); ?></p>
                    <small>By <strong><?php echo htmlspecialchars($fb['username']); ?></strong> on <?php echo date("F j, Y g:i A", strtotime($fb['created_at'])); ?></small>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

</body>
</html>
