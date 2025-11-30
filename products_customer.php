<?php
session_start();
include '../db.php';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php?logout_success=1");
    exit;
}

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['customer'];

// Fetch user data
$userRes = $conn->prepare("SELECT * FROM users WHERE id=?");
$userRes->bind_param("i", $user_id);
$userRes->execute();
$userResult = $userRes->get_result();
$user = $userResult->fetch_assoc();

// Check if account is active
if ($user['status'] !== 'active') {
    session_destroy();
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Account Inactive</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
    <script>
    Swal.fire({
        icon: "error",
        title: "Account Inactive",
        text: "Your account is inactive. You cannot access this page.",
        confirmButtonColor: "#d33"
    }).then(() => {
        window.location.href = "login.php";
    });
    </script>
    </body>
    </html>';
    exit;
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM products WHERE is_archived = 0";
if ($search != '') {
    $search_safe = $conn->real_escape_string($search);
    $sql .= " AND name LIKE '%$search_safe%'";
}
$result = $conn->query($sql);

// Count ready orders
$readyOrdersRes = $conn->query("SELECT COUNT(*) as ready_count FROM orders WHERE user_id=$user_id AND status='Ready for Pick Up'");
$readyOrders = $readyOrdersRes->fetch_assoc();
$readyCount = intval($readyOrders['ready_count']);

$profile_updated = false;

// Profile update
if(isset($_POST['update_profile'])){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $cp = trim($_POST['cp_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);

    if(!empty($password)){
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, cp_number=?, address=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $username, $email, $cp, $address, $password, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, cp_number=?, address=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $cp, $address, $user_id);
    }

    $stmt->execute();
    $_SESSION['customer_name'] = $username;
    $user['username'] = $username;
    $user['email'] = $email;
    $user['cp_number'] = $cp;
    $user['address'] = $address;
    $profile_updated = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cocobind | Products</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#fff;color:#333;min-height:100vh;}

/* Header */
header{
    background:#435b7e;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:fixed;
    top:0;left:0;width:100%;
    z-index:1000;
    color:#fff;
}
.logo{color:#fff;text-decoration:none;font-size:20px;font-weight:600;}
nav ul{list-style:none;display:flex;gap:20px;}
nav ul li a{color:#fff;text-decoration:none;font-weight:500;transition:0.3s;}
nav ul li a:hover{color:#00b4d8;}
.menu-toggle{display:none;color:#fff;font-size:24px;cursor:pointer;}
nav{display:flex;align-items:center;}
nav.active ul{display:flex;flex-direction:column;background:#435b7e;position:absolute;top:60px;left:0;width:100%;padding:15px 0;gap:15px;z-index:999;}
nav.active ul li{text-align:center;}
nav.active ul li a{font-size:17px;}

/* Search bar */
.search-bar{
    display:flex;
    margin:100px auto 30px auto;
    max-width:600px;
    background:#fff;
    border-radius:8px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}
.search-bar input{flex:1;border:none;padding:10px 15px;font-size:15px;outline:none;}
.search-bar button{
    background:#00b4d8;
    border:none;color:#fff;font-weight:600;padding:0 20px;cursor:pointer;transition:0.3s;
}
.search-bar button:hover{opacity:0.9;}

/* Container & products */
.container{max-width:1200px;margin:0 auto;padding:0 20px 60px 20px;}
.products{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:25px;}
.product{background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.08);transition:transform 0.3s;}
.product:hover{transform:translateY(-5px);}
.product img{width:100%;height:180px;object-fit:cover;cursor:pointer;border-bottom:2px solid #00b4d8;}
.product-content{text-align:center;padding:15px;}
.product-content h3{font-size:16px;margin-bottom:8px;color:#435b7e;}
.price{color:#d4af37;font-weight:600;}
.rating{color:#f4c150;font-size:14px;margin-top:5px;}
.stock{font-size:13px;color:#666;margin-top:5px;}
.product button{
    background:#00b4d8;
    border:none;color:#fff;padding:8px 15px;border-radius:6px;cursor:pointer;transition:0.3s;font-weight:600;
}
.product button:hover{opacity:0.9;}
.feedback-btn{
    display:inline-block;margin-top:10px;background:#00b4d8;color:#fff;padding:6px 12px;border-radius:6px;text-decoration:none;font-size:13px;
}
.feedback-btn:hover{opacity:0.9;}
.out-stock{color:#e63946;font-weight:600;margin-top:10px;}

/* Profile modal */
#profileModal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:25px 20px;border-radius:12px;box-shadow:0 6px 25px rgba(0,0,0,0.3);z-index:5000;width:400px;max-width:90%;}
#modalOverlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:4000;}
#profileModalClose{position:absolute;top:10px;right:15px;font-size:22px;cursor:pointer;color:#333;}
#profileModal h2{color:#435b7e;text-align:center;margin-bottom:20px;}
#profileModal input{width:100%;padding:10px 12px;margin:8px 0;border:1px solid #ccc;border-radius:6px;font-size:14px;}
#profileModal button{width:100%;background:#00b4d8;color:#fff;border:none;padding:12px;border-radius:8px;cursor:pointer;font-weight:600;margin-top:10px;transition:0.3s;}
#profileModal button:hover{opacity:0.9;}
#profileModal div{position:relative;}
#profileModal i{position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:#435b7e;}

/* Image modal */
#imageModal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);justify-content:center;align-items:center;z-index:6000;}
#imageModal img{max-width:90%;max-height:80%;border-radius:10px;}
#closeImageModal{position:absolute;top:20px;right:30px;font-size:35px;color:#fff;cursor:pointer;}

/* Responsive */
@media(max-width:768px){
    .menu-toggle{display:block;}
    nav ul{display:none;}
    .search-bar{margin-top:90px;width:90%;}
    .products{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:15px;}
    .product img{height:140px;}
    .product-content h3{font-size:14px;}
}
</style>
</head>
<body>

<header>
<a href="index.php" class="logo">Cocobind</a>
<span class="menu-toggle"><i class="fa-solid fa-bars"></i></span>
<nav>
<ul>
<li><a href="index.php">Home</a></li>
<li><a href="products_customer.php" style="color:#00b4d8;">Products</a></li>
<li><a href="cart.php">Cart <span class="badge" style="background:red;border-radius:50%;padding:2px 6px;"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span></a></li>
<li><a href="order_history.php">Orders <?php if($readyCount>0):?><span class="badge" style="background:red;border-radius:50%;padding:2px 6px;"><?php echo $readyCount;?></span><?php endif;?></a></li>
<li><a href="#" id="profileBtn">Profile</a></li>
<li><a href="#" id="logoutBtn">Logout</a></li>
</ul>
</nav>
</header>

<form class="search-bar" method="get" action="products_customer.php">
<input type="text" name="search" placeholder="Search product..." value="<?php echo htmlspecialchars($search); ?>">
<button type="submit">Search</button>
</form>

<div class="container">
<div class="products">
<?php while($row = $result->fetch_assoc()): ?>
<?php
$imageFile = $row['image'];
$imagePath = '../images/' . $imageFile;
if (!file_exists($imagePath)) $imagePath = '../' . $imageFile;
if (!file_exists($imagePath) || empty($imageFile)) $imagePath = '../images/default.png';
?>
<div class="product">
<img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="zoomable">
<div class="product-content">
<h3><?php echo $row['name']; ?></h3>
<p class="price">₱<?php echo number_format($row['price'],2); ?></p>

<?php
$pid = $row['id'];
$ratingQuery = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM feedback WHERE product_id=$pid");
$ratingData = $ratingQuery->fetch_assoc();
$avg = $ratingData['avg_rating'] ?? 0;
$count = $ratingData['total_reviews'] ?? 0;
$stars = str_repeat('⭐', round($avg));
?>
<div class="rating"><?php echo ($avg>0)?$stars:'No rating yet';?></div>
<?php if($count>0):?><div class="review-count">(<?php echo $count;?> reviews)</div><?php endif;?>

<div class="stock">Stock: <?php echo $row['stock'];?></div>
<?php if($row['stock']>0): ?>
<form class="add-to-cart-form">
<input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
<input type="number" name="quantity" value="1" min="1" max="<?php echo $row['stock'];?>" style="width:55px;">
<button type="submit">Add</button>
</form>
<?php else: ?><p class="out-stock">Out of stock</p><?php endif;?>

<a href="view_feedback.php?product_id=<?php echo $row['id']; ?>" class="feedback-btn">View Feedback</a>
</div>
</div>
<?php endwhile; ?>
</div>
</div>

<!-- Profile Modal -->
<div id="profileModal">
<span id="profileModalClose">&times;</span>
<h2>My Profile</h2>
<form method="post">
<label>Full Name</label>
<input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
<label>Email</label>
<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
<label>Password (leave blank if unchanged)</label>
<div>
<input type="password" name="password" id="modalPassword" placeholder="Enter new password">
<i class="fa-solid fa-eye" id="toggleModalPassword"></i>
</div>
<label>Contact Number</label>
<input type="text" name="cp_number" value="<?php echo htmlspecialchars($user['cp_number']); ?>">
<label>Address</label>
<input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
<button type="submit" name="update_profile">Update Profile</button>
</form>
</div>
<div id="modalOverlay"></div>

<!-- Image Modal -->
<div id="imageModal">
<span id="closeImageModal">&times;</span>
<img id="modalImage" src="" alt="Zoomed Product">
</div>

<script>
const menuToggle=document.querySelector('.menu-toggle');
const nav=document.querySelector('nav');
menuToggle.addEventListener('click',()=>nav.classList.toggle('active'));

document.querySelectorAll('.add-to-cart-form').forEach(form=>{
form.addEventListener('submit',e=>{
e.preventDefault();
fetch('ajax_add_to_cart.php',{method:'POST',body:new FormData(form)})
.then(res=>res.json())
.then(data=>{
Swal.fire({icon:data.success?'success':'warning',title:data.success?'Added to Cart!':'Oops!',text:data.message,confirmButtonColor:'#00b4d8',timer:data.success?2000:null,showConfirmButton:!data.success});
if(data.success)setTimeout(()=>location.reload(),1500);
});
});
});

document.getElementById('logoutBtn').addEventListener('click',e=>{
e.preventDefault();
Swal.fire({title:"Are you sure?",text:"You will be logged out.",icon:"warning",showCancelButton:true,confirmButtonText:"Yes",cancelButtonText:"Cancel"}).then(res=>{if(res.isConfirmed)window.location.href="products_customer.php?logout=1";});
});

const profileBtn=document.getElementById('profileBtn');
const profileModal=document.getElementById('profileModal');
const modalOverlay=document.getElementById('modalOverlay');
const closeProfileModal=document.getElementById('profileModalClose');

profileBtn.addEventListener('click',e=>{
e.preventDefault();
profileModal.style.display='block';
modalOverlay.style.display='block';
});
closeProfileModal.onclick=()=>{profileModal.style.display='none';modalOverlay.style.display='none';};
modalOverlay.onclick=()=>{profileModal.style.display='none';modalOverlay.style.display='none';};

document.getElementById('toggleModalPassword').onclick=()=>{const pass=document.getElementById('modalPassword');pass.type=pass.type==='password'?'text':'password';};

const zoomImgs=document.querySelectorAll('.zoomable');
const imgModal=document.getElementById('imageModal');
const modalImg=document.getElementById('modalImage');
const closeImg=document.getElementById('closeImageModal');
zoomImgs.forEach(img=>img.addEventListener('click',()=>{modalImg.src=img.src;imgModal.style.display='flex';}));
closeImg.onclick=()=>imgModal.style.display='none';
imgModal.onclick=e=>{if(e.target===imgModal)imgModal.style.display='none';};

<?php if($profile_updated): ?>
Swal.fire({icon:'success',title:'Profile Updated!',text:'Your details were updated.',timer:2000,showConfirmButton:false});
<?php endif; ?>
</script>
</body>
</html>
