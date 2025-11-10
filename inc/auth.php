<?php
// inc/auth.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


function admin_is_logged_in() {
    return !empty($_SESSION['admin_id']);
}

function admin_require_login() {
    if (!admin_is_logged_in()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit();
    }
}

function admin_login($username, $password) {
    global $pdo;

    // Change "admins" table & columns as per your DB structure
    $stmt = $pdo->prepare('SELECT id, username, password FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }

    return false;
}

function admin_logout() {
    session_unset();
    session_destroy();
}
?>