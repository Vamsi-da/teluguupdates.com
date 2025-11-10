<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/auth.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

admin_require_login();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$id = (int)$_GET['id'];

global $pdo;
$stmt = $pdo->prepare("DELETE FROM posts WHERE id=?");
$stmt->execute([$id]);

header('Location: dashboard.php');
exit;
?>
