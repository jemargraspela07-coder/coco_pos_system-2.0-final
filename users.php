<?php
session_start();
include '../db.php';
if (!isset($_SESSION['admin'])) { 
    header("Location: login.php"); 
    exit(); 
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $conn->query("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=$id");
    header("Location: users.php");
    exit();
}

// Handle search
$search = '';
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
}

// Fetch users
$sql = "SELECT * FROM users WHERE role='customer'";
if ($search !== '') {
    $sql .= " AND username LIKE '%$search%'";
}
$users = $conn->query($sql);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users - Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
body { display:flex; background:#f8f9fb; color:#333; }

.sidebar {
    width:240px; background:#0a2342; color:white; height:100vh; position:fixed; top:0; left:0; padding-top:20px;
}
.sidebar h2 { text-align:center; margin-bottom:30px; font-size:22px; }
.sidebar a { display:block; color:white; text-decoration:none; padding:15px 20px; margin:5px 0; font-size:16px; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:#1d3557; border-left:5px solid #00b4d8; }

.main { margin-left:240px; width:calc(100% - 240px); padding:20px 40px; }
.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; }
.topbar h2 { color:#0a2342; font-size:24px; }

/* Search bar */
.search-bar { margin-bottom:20px; display:flex; justify-content:flex-end; }
.search-bar input[type="text"] { padding:8px 12px; border:1px solid #ccc; border-radius:6px 0 0 6px; outline:none; width:250px; }
.search-bar button { padding:8px 12px; border:none; background:#1d3557; color:white; border-radius:0 6px 6px 0; cursor:pointer; transition:0.3s; }
.search-bar button:hover { background:#00b4d8; }

table { width:100%; border-collapse:collapse; background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08); }
th, td { padding:14px 18px; text-align:center; border-bottom:1px solid #f1f1f1; }
th { background:#1d3557; color:white; text-transform:uppercase; font-size:14px; letter-spacing:0.5px; }
tr:hover { background:#f0f8ff; }
td { font-size:15px; }

.btn-inactive { background: #e63946; color:white; padding:7px 15px; border-radius:6px; text-decoration:none; transition:0.3s; }
.btn-inactive:hover { background:#a4161a; }
.btn-active { background: #06d6a0; color:white; padding:7px 15px; border-radius:6px; text-decoration:none; transition:0.3s; }
.btn-active:hover { background:#118ab2; }

@media (max-width:768px) {
    .sidebar { width:180px; }
    .main { margin-left:180px; padding:15px; }
    table { font-size:13px; }
    .btn-inactive, .btn-active { font-size:12px; padding:5px 10px; }
    .search-bar input[type="text"] { width:150px; }
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="dashboard.php" class="<?= ($currentPage=='dashboard.php')?'active':'' ?>"><i class="fa fa-chart-line"></i> Dashboard</a>
    <a href="inventory.php" class="<?= ($currentPage=='inventory.php')?'active':'' ?>"><i class="fa fa-box"></i> Inventory</a>
    <a href="orders.php" class="<?= ($currentPage=='orders.php')?'active':'' ?>"><i class="fa fa-shopping-cart"></i> Orders</a>
    <a href="users.php" class="<?= ($currentPage=='users.php')?'active':'' ?>"><i class="fa fa-users"></i> Users</a>
    <a href="sales.php" class="<?= ($currentPage=='sales.php')?'active':'' ?>"><i class="fa fa-coins"></i> Sales</a>
    <a href="feedbacks.php" class="<?= ($currentPage=='feedbacks.php') ? 'active' : ''; ?>"><i class="fa fa-comments"></i> Feedbacks</a>
    <a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h2>User Management</h2>
    </div>

    <div class="search-bar">
        <form method="get" action="users.php">
            <input type="text" name="search" placeholder="Search by username..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit"><i class="fa fa-search"></i> Search</button>
        </form>
    </div>

    <table>
        <tr>
            <th>Username</th>
            <th>Contact</th>
            <th>Address</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php if($users->num_rows > 0): ?>
            <?php while($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['contact'] ?? $row['cp_number']) ?></td>
                <td><?= htmlspecialchars($row['address'] ?? '-') ?></td>
                <td style="color: <?= $row['status']=='active' ? '#06d6a0' : '#e63946' ?>;">
                    <?= ucfirst($row['status']) ?>
                </td>
                <td>
                    <a href="?toggle=<?= $row['id'] ?>" 
                       class="<?= $row['status']=='active' ? 'btn-inactive' : 'btn-active' ?>">
                       <i class="fa <?= $row['status']=='active' ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                       <?= $row['status']=='active' ? 'Set Inactive' : 'Set Active' ?>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No users found.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
