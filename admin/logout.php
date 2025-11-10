<?php
require_once __DIR__ . '/../inc/auth.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

admin_logout();
header('Location: login.php');
exit;