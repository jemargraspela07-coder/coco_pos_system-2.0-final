<?php 
session_start();
include '../db.php';

if(isset($_POST['login'])){
    $email = trim($_POST['email']); 
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='customer' AND status='active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows == 1){
        $user = $res->fetch_assoc();
        if($user['password'] === $password){
            $_SESSION['customer'] = $user['id'];
            $_SESSION['customer_name'] = $user['username'];
            $login_success = "Login successful! Redirecting...";
        } else {
            $login_error = "Incorrect password.";
        }
    } else {
        $login_error = "Email not found or account inactive.";
    }
}

if(isset($_POST['signup'])){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $cp_number = trim($_POST['cp_number']);
    $address = trim($_POST['address']);

    if(empty($username) || empty($email) || empty($password) || empty($cp_number) || empty($address)){
        $signup_error = "All fields are required!";
    } elseif(!preg_match('/^[0-9]{11}$/', $cp_number)){
        $signup_error = "Contact number must be 11 digits!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=? OR cp_number=?");
        $stmt->bind_param("sss", $username, $email, $cp_number);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows > 0){
            $signup_error = "Full name, email, or contact number is already registered!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username,email,password,cp_number,address,role,status) VALUES(?,?,?,?,?, 'customer','active')");
            $stmt->bind_param("sssss",$username,$email,$password,$cp_number,$address);
            if($stmt->execute()){
                $signup_success = "Account created successfully! You can now login.";
            } else {
                $signup_error = "Error creating account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cocobind | Home</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
    background: #fff;
    color: #000;
    min-height:100vh;
    overflow-x:hidden;
}

/* Header / Navbar */
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
.logo{
    color:#fff;
    text-decoration:none;
    font-size:20px;
    font-weight:600;
}
nav ul{
    list-style:none;
    display:flex;
    gap:20px;
}
nav ul li a{
    color:#fff;
    text-decoration:none;
    font-weight:500;
    transition:color 0.3s;
}
nav ul li a:hover{
    color:#00b4d8;
}

/* Hero Section with background image */
.hero{
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
    height:100vh;
    padding:0 20px;
    background: url('../images/Cococoir.jpg') no-repeat center center/cover;
    position: relative;
}
.hero::before {
    content: "";
    position: absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.4);
    z-index:0;
}
.hero h1,
.hero p,
.hero .btn {
    position: relative;
    z-index:1;
}
.hero h1{
    font-size:50px;
    color:#fff;
    margin-bottom:20px;
}
.hero p{
    font-size:18px;
    color:#f5f5f5;
    max-width:600px;
    line-height:1.6;
    margin-bottom:40px;
}
.hero .btn{
    background:#00b4d8;
    color:#fff;
    padding:12px 25px;
    border:none;
    border-radius:6px;
    font-weight:600;
    text-decoration:none;
    transition:0.3s;
}
.hero .btn:hover{
    opacity:0.9;
}

/* Info Section with logo on left, content on right */
.info-section{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#fff;
    color:#333;
    padding:80px 40px;
    gap:40px;
    flex-wrap:wrap;
}
.info-logo{
    flex:1;
    min-width:250px;
    display:flex;
    justify-content:center;
    align-items:center;
}
.info-logo img{
    max-width:100%;
    height:auto;
}
.info-content{
    flex:2;
    min-width:300px;
}
.info-box{
    margin-bottom:40px;
}
.info-box h2{
    font-size:28px;
    margin-bottom:15px;
    color:#435b7e;
}
.info-box p{
    font-size:16px;
    line-height:1.7;
    color:#444;
}
.info-box a{
    color:#00b4d8;
    text-decoration:none;
}
.info-box a:hover{
    text-decoration:underline;
}

/* Footer */
footer{
    text-align:center;
    padding:20px;
    background:#435b7e;
    color:#fff;
    font-size:14px;
}

/* Modal */
.modal{
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.7);
    justify-content:center;
    align-items:center;
    z-index:2000;
    padding:15px;
}
.modal-content{
    background:#fff;
    color:#333;
    padding:30px 25px;
    border-radius:12px;
    width:360px;
    max-width:90%;
    position:relative;
    text-align:center;
}
.modal-content h2{
    margin-bottom:20px;
    color:#435b7e;
}
.modal-content input, .modal-content textarea{
    width:100%;
    padding:10px 12px;
    margin:8px 0;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}
.modal-content button{
    width:100%;
    background:#00b4d8;
    color:#fff;
    border:none;
    padding:12px;
    border-radius:8px;
    font-weight:600;
    margin-top:10px;
    cursor:pointer;
    transition:0.3s;
}
.modal-content button:hover{
    opacity:0.9;
}
.modal-content .close{
    position:absolute;
    top:10px;right:12px;
    font-size:20px;
    font-weight:bold;
    color:#555;
    cursor:pointer;
}
.modal-content .footer-link{
    display:block;
    margin-top:10px;
    color:#00b4d8;
    text-decoration:none;
}
.modal-content .footer-link:hover{
    text-decoration:underline;
}

/* Responsive */
@media(max-width:768px){
    header{
        flex-direction:column;
        text-align:center;
        gap:10px;
        padding:12px 10px;
    }
    nav ul{
        flex-direction:column;
        gap:10px;
    }
    .hero h1{font-size:34px;}
    .hero p{font-size:16px;}
    .info-section{
        flex-direction:column;
        text-align:center;
        padding:50px 20px;
    }
    .info-logo, .info-content{
        flex:1;
    }
    .info-box h2{font-size:22px;}
    .info-box p{font-size:15px;}
}
@media(max-width:480px){
    .hero h1{font-size:28px;}
    .hero p{font-size:14px;}
    .modal-content{width:95%;padding:20px;}
}
</style>
</head>
<body>

<header>
  <a href="#" class="logo">Cocobind</a>
  <nav>
    <ul>
      <li><a href="#" style="color:#00b4d8;">Home</a></li>
      <li><a href="products_customer.php">Products</a></li>
      <li><a href="#" id="openLogin">Login</a></li>
      <li><a href="#" id="openSignup">Sign Up</a></li>
    </ul>
  </nav>
</header>

<section class="hero">
  <h1>Welcome to Cocobind</h1>
  <p>Discover the essence of nature with Coco Products — pure, natural, and crafted for your lifestyle.</p>
  <a href="products_customer.php" class="btn">Shop Now</a>
</section>

<section class="info-section">
  <div class="info-logo">
    <img src="../images/cocobind_logo.jpg" alt="Cocobind Logo">
  </div>
  <div class="info-content">
    <div class="info-box">
      <h2>About Cocobind</h2>
      <p>Coco POS is an integrated point-of-sale and ordering system built to make business operations efficient and customer-friendly.</p>
    </div>
    <div class="info-box">
      <h2>Contact Us</h2>
      <p>📞 09500811801<br>
         ✉️ <a href="mailto:cocobindinc@gmail.com">cocobindinc@gmail.com</a><br>
         📍 Bliss Monbon, Irosin, Sorsogon</p>
    </div>
  </div>
</section>

<footer>&copy; <?php echo date('Y'); ?> Cocobind. All Rights Reserved.</footer>

<div class="modal" id="loginModal">
  <div class="modal-content">
    <span class="close" id="closeLogin">&times;</span>
    <h2>Customer Login</h2>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Login</button>
      <a href="#" id="switchToSignup" class="footer-link">Don't have an account? Sign Up</a>
    </form>
  </div>
</div>

<div class="modal" id="signupModal">
  <div class="modal-content">
    <span class="close" id="closeSignup">&times;</span>
    <h2>Customer Signup</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="text" name="cp_number" placeholder="Contact Number (11 digits)" required>
      <textarea name="address" placeholder="Address" required></textarea>
      <button type="submit" name="signup">Sign Up</button>
      <a href="#" id="switchToLogin" class="footer-link">Already have an account? Login</a>
    </form>
  </div>
</div>

<script>
const loginModal=document.getElementById('loginModal');
const signupModal=document.getElementById('signupModal');

document.getElementById('openLogin').onclick=()=>loginModal.style.display='flex';
document.getElementById('openSignup').onclick=()=>signupModal.style.display='flex';
document.getElementById('closeLogin').onclick=()=>loginModal.style.display='none';
document.getElementById('closeSignup').onclick=()=>signupModal.style.display='none';
document.getElementById('switchToSignup').onclick=()=>{loginModal.style.display='none';signupModal.style.display='flex';};
document.getElementById('switchToLogin').onclick=()=>{signupModal.style.display='none';loginModal.style.display='flex';};

<?php if(isset($signup_success)) : ?>
Swal.fire({icon:'success',title:'Success!',text:"<?php echo $signup_success; ?>",confirmButtonColor:'#00b4d8'});
<?php elseif(isset($signup_error)) : ?>
Swal.fire({icon:'error',title:'Signup Failed',text:"<?php echo $signup_error; ?>",confirmButtonColor:'#00b4d8'});
<?php endif; ?>

<?php if(isset($login_success)) : ?>
Swal.fire({icon:'success',title:'Welcome!',text:"<?php echo $login_success; ?>",confirmButtonColor:'#00b4d8'}).then(()=>{window.location.href="products_customer.php";});
<?php elseif(isset($login_error)) : ?>
Swal.fire({icon:'error',title:'Login Failed',text:"<?php echo $login_error; ?>",confirmButtonColor:'#00b4d8'});
<?php endif; ?>
</script>
</body>
</html>
