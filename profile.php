<?php
session_start();
include '../db.php';

if (!isset($_SESSION['customer'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['customer'];
$res = $conn->prepare("SELECT * FROM users WHERE id=?");
$res->bind_param("i", $user_id);
$res->execute();
$result = $res->get_result();
$user = $result->fetch_assoc();

$update_success = $update_error = "";

if (isset($_POST['update_confirmed'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $cp = trim($_POST['cp_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);

    try {
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, cp_number=?, address=?, password=? WHERE id=?");
            $stmt->bind_param("sssssi", $username, $email, $cp, $address, $password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username=?, email=?, cp_number=?, address=? WHERE id=?");
            $stmt->bind_param("ssssi", $username, $email, $cp, $address, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['customer_name'] = $username;
            $update_success = "Your profile has been successfully updated!";
        } else {
            $update_error = "Something went wrong while saving your changes.";
        }
    } catch (Exception $e) {
        $update_error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f3f3f3 0%, #ffffff 100%);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.container {
    width: 360px;
    background: #fff;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}
label {
    display: block;
    margin-bottom: 5px;
    color: #444;
    font-weight: 500;
    font-size: 14px;
}
input[type="text"], input[type="password"], input[type="email"] {
    width: 100%;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-bottom: 14px;
    font-size: 14px;
    transition: 0.3s;
    box-sizing: border-box;
}
input:focus {
    border-color: #d4af37;
    box-shadow: 0 0 5px rgba(212,175,55,0.5);
    outline: none;
}
.password-container {
    position: relative;
    width: 100%;
}
.password-container input {
    width: 100%;
    padding-right: 35px;
}
.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #777;
    font-size: 15px;
    transition: color 0.3s;
}
.toggle-password:hover { color: #000; }
.btn {
    width: 100%;
    background: #d4af37;
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 8px;
    font-size: 15px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}
.btn:hover {
    background: #b89128;
}
</style>
</head>
<body>

<div class="container">
    <h2>My Profile</h2>

    <form method="post" id="profileForm">
        <label>Full Name</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label>Password (leave blank if unchanged)</label>
        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Enter new password">
            <i class="fa-solid fa-eye toggle-password" id="togglePassword"></i>
        </div>

        <label>Contact Number</label>
        <input type="text" name="cp_number" value="<?php echo htmlspecialchars($user['cp_number']); ?>">

        <label>Address</label>
        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

        <input type="hidden" name="update_confirmed" value="1">
        <button type="button" id="updateBtn" class="btn">Update Profile</button>
    </form>
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

document.getElementById('updateBtn').addEventListener('click', (e) => {
    e.preventDefault();
    Swal.fire({
        title: "Confirm Update",
        text: "Are you sure you want to update your profile information?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#d4af37",
        cancelButtonColor: "#888",
        confirmButtonText: "Yes, Update it",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('profileForm').submit();
        }
    });
});

<?php if (!empty($update_success)): ?>
Swal.fire({
    title: "Profile Updated!",
    text: "<?php echo $update_success; ?>",
    icon: "success",
    confirmButtonColor: "#d4af37",
    confirmButtonText: "OK"
}).then(() => {
    window.location.href = "profile.php";
});
<?php elseif (!empty($update_error)): ?>
Swal.fire({
    title: "Error",
    text: "<?php echo $update_error; ?>",
    icon: "error",
    confirmButtonColor: "#d4af37",
    confirmButtonText: "Try Again"
});
<?php endif; ?>
</script>

</body>
</html>
