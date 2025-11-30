<?php
session_start();
include '../db.php';

// Function to generate random password
function generateTempPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

if (isset($_POST['ajax_request'])) {

    // generate new password
    $temp_pass = generateTempPassword();

    // update admin password
    $conn->query("UPDATE users SET password='$temp_pass' WHERE role='admin' LIMIT 1");

    echo $temp_pass; // return password to AJAX
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password - Admin</title>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #435b7e;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .box {
        background: #fff;
        padding: 40px 50px;
        width: 360px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    h2 {
        margin-bottom: 20px;
        color: #333;
        font-size: 22px;
    }

    p {
        color: #555;
        margin-bottom: 20px;
        font-size: 14px;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #000;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background-color: #222;
    }

    a {
        margin-top: 15px;
        display: block;
        color: #435b7e;
        text-decoration: none;
        font-size: 14px;
    }

    a:hover {
        text-decoration: underline;
    }

    @media (max-width: 480px) {
        .box {
            width: 90%;
            padding: 30px;
        }
    }
</style>

</head>
<body>

    <div class="box">
        <h2>Forgot Password</h2>

        <p>Click the button below to generate a temporary admin password.</p>

        <button onclick="generatePassword()">Generate Temporary Password</button>

        <a href="login.php">Back to Login</a>
    </div>

<script>
function generatePassword() {
    // AJAX request using fetch()
    fetch("", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ajax_request=1"
    })
    .then(response => response.text())
    .then(tempPassword => {
        Swal.fire({
            title: "Temporary Password Generated!",
            html: "<b style='color:red; font-size:20px;'>" + tempPassword + "</b>",
            icon: "success",
            confirmButtonText: "Copy & Close"
        }).then(() => {
            navigator.clipboard.writeText(tempPassword);
        });
    });
}
</script>

</body>
</html>
