<?php
session_start();
include '../db.php';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch user by email (customer role only)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND role='customer'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();

        // Check if account is active
        if ($user['status'] !== 'active') {
            $login_error = "Your account is inactive. Please contact support.";
        } else {
            // Password check (assuming plaintext for now, you can replace with password_verify if hashed)
            if ($user['password'] === $password) {
                $_SESSION['customer'] = $user['id'];
                $_SESSION['customer_name'] = $user['username'];

                $login_success = "Login successful! Redirecting to homepage...";
            } else {
                $login_error = "Incorrect password.";
            }
        }
    } else {
        $login_error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Login | Coco POS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: rgba(0,0,0,0.6) url('../images/Cococoir.jpg') no-repeat center center/cover;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}
.login-container {
    background: #fff;
    color: #333;
    padding: 35px 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    width: 360px;
    max-width: 90%;
    text-align: center;
}
.login-container h2 {
    font-size: 26px;
    margin-bottom: 20px;
    color: #222;
}
.login-container input {
    width: 100%;
    padding: 10px 12px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}
.password-container { position: relative; }
.password-container input { padding-right: 11px; }
.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #777;
    font-size: 16px;
}
.toggle-password:hover { color: #000; }
button {
    width: 100%;
    background: #00b4d8;
    color: #fff;
    border: none;
    padding: 12px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 10px;
}
button:hover { background: #00b4d8; }
.footer-link {
    display: block;
    margin-top: 12px;
    color: #00b4d8;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}
.footer-link:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="login-container">
    <h2>Customer Login</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>

        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
        </div>

        <button type="submit" name="login">Login</button>
    </form>

    <a href="signup.php" class="footer-link">Don't have an account? Sign Up</a>
    <a href="index.php" class="footer-link">Back to Homepage</a>
</div>

<script>
const togglePassword = document.getElementById('togglePassword');
const passwordField = document.getElementById('password');
togglePassword.addEventListener('click', () => {
    const type = passwordField.type === 'password' ? 'text' : 'password';
    passwordField.type = type;
    togglePassword.classList.toggle('fa-eye');
    togglePassword.classList.toggle('fa-eye-slash');
});

<?php if(isset($login_success)) : ?>
Swal.fire({
    icon: 'success',
    title: 'Login Successful!',
    text: '<?php echo $login_success; ?>',
    showConfirmButton: false,
    timer: 1500
}).then(() => {
    window.location.href = "index.php";
});
<?php elseif(isset($login_error)) : ?>
Swal.fire({
    icon: 'error',
    title: 'Login Failed',
    text: '<?php echo $login_error; ?>',
    confirmButtonColor: '#d33'
});
<?php endif; ?>
</script>

</body>
</html>
