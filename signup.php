<?php
include '../db.php';

if (isset($_POST['signup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $cp_number = trim($_POST['cp_number']);
    $address = trim($_POST['address']);

    if (empty($username) || empty($email) || empty($password) || empty($cp_number) || empty($address)) {
        echo "<script>alert('All fields are required!');</script>";
    } elseif (!preg_match('/^[0-9]{11}$/', $cp_number)) {
        echo "<script>alert('Contact number must be 11 digits!');</script>";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR cp_number = ?");
        $stmt->bind_param("sss", $username, $email, $cp_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Full name, email, or contact number is already registered!');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, cp_number, address, role, status) VALUES (?, ?, ?, ?, ?, 'customer', 'active')");
            $stmt->bind_param("sssss", $username, $email, $password, $cp_number, $address);

            if ($stmt->execute()) {
                echo "<script>alert('Account created successfully! You can now log in.'); window.location.href='login.php';</script>";
            } else {
                echo "<script>alert('Error creating account. Please try again.');</script>";
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
<title>Cocobind | Signup</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family:'Poppins',sans-serif;
    background:#fff;
    margin:0;
}

header {
    background:#435b7e;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo {
    color:#fff;
    font-size:20px;
    font-weight:600;
    text-decoration:none;
}

nav ul {
    list-style:none;
    display:flex;
    gap:20px;
    margin:0;
}

nav ul li a {
    text-decoration:none;
    color:#fff;
    font-weight:500;
}

nav ul li a:hover {
    color:#00b4d8;
}

.signup-container {
    max-width:400px;
    margin:100px auto 50px;
    background:#fff;
    padding:30px 25px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}

.signup-container h2 {
    text-align:center;
    margin-bottom:20px;
    color:#435b7e;
}

input, textarea {
    width:100%;
    padding:12px 1px;
    margin:10px 0;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:14px;
    display:block;
}

textarea {
    resize:none;
    height:60px;
}

.password-container {
    position:relative;
}

.password-container input {
    padding-right:1px;
}

.toggle-password {
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    color:#435b7e;
    font-size:16px;
}

button {
    width:100%;
    padding:12px;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    margin-top:12px;
    transition:0.3s;
}

button.signup-btn { background:#00b4d8; color:#fff; }
button.signup-btn:hover { background:#0092ad; }

button.back-btn { background:#435b7e; color:#fff; }
button.back-btn:hover { background:#334763; }

@media(max-width:500px){
    .signup-container { margin:80px 20px; }
}
</style>
</head>
<body>

<header>
    <a href="index.php" class="logo">Cocobind</a>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="signup.php" style="color:#00b4d8;">Sign Up</a></li>
        </ul>
    </nav>
</header>

<div class="signup-container">
    <h2>Customer Signup</h2>

    <form method="POST">
        <input type="text" name="username" placeholder="Full Name (e.g. Juan Dela Cruz)" required>
        <input type="email" name="email" placeholder="Email" required>

        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
        </div>

        <input type="text" name="cp_number" placeholder="Contact Number (11 digits)" required>
        <textarea name="address" placeholder="Address" required></textarea>

        <button type="submit" name="signup" class="signup-btn">Sign Up</button>
        <button type="button" class="back-btn" onclick="window.location.href='login.php';">Back to Login</button>
    </form>
</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordField = document.getElementById('password');

togglePassword.addEventListener('click', () => {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    togglePassword.classList.toggle('fa-eye');
    togglePassword.classList.toggle('fa-eye-slash');
});
</script>

</body>
</html>
