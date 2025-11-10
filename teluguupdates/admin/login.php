<?php
session_start();
require_once __DIR__ . '/../inc/config.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


// ✅ Direct PDO connection check
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];

            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Please fill all fields!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial; background: #f8f9fa; }
        .login-box {
            width: 350px; margin: 100px auto; background: white;
            padding: 20px; border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; }
        input {
            width: 100%; padding: 10px; margin-top: 10px;
            border: 1px solid #ccc; border-radius: 5px;
        }
        input[type=submit] {
            background: #007bff; color: white; border: none;
            cursor: pointer; transition: 0.3s;
        }
        input[type=submit]:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
<div class="login-box">
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username (ex: admin@teluguschemes.com)" required>
        <input type="password" name="password" placeholder="Password (ex: admin123)" required>
        <input type="submit" name="login" value="Login">
    </form>
</div>
</body>
</html>