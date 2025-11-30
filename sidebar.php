<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <h2>Admin Panel</h2>
    
    <a href="dashboard.php" class="<?php echo ($currentPage=='dashboard.php') ? 'active' : ''; ?>">
        <i class="fa fa-chart-line"></i> Dashboard
    </a>
    
    <a href="inventory.php" class="<?php echo ($currentPage=='inventory.php') ? 'active' : ''; ?>">
        <i class="fa fa-box"></i> Inventory
    </a>
    
    <a href="orders.php" class="<?php echo ($currentPage=='orders.php') ? 'active' : ''; ?>">
        <i class="fa fa-shopping-cart"></i> Orders
    </a>
    
    <a href="users.php" class="<?php echo ($currentPage=='users.php') ? 'active' : ''; ?>">
        <i class="fa fa-users"></i> Users
    </a>
    
    <a href="sales.php" class="<?php echo ($currentPage=='sales.php') ? 'active' : ''; ?>">
        <i class="fa fa-coins"></i> Sales
    </a>

    <a href="feedbacks.php" class="<?php echo ($currentPage=='feedbacks.php') ? 'active' : ''; ?>">
        <i class="fa fa-comment-dots"></i> Feedbacks
    </a>
    
    <a href="logout.php">
        <i class="fa fa-sign-out-alt"></i> Logout
    </a>
</div>
