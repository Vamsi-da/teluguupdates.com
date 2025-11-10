<?php
require_once __DIR__ . '/inc/functions.php'; // auto-inserted
// inc/header.php - used by admin pages
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

?>
<!doctype html>
<html lang="te">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h(site_title()); ?> — Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
  <header style="padding:12px;background:#0074b7;color:white">
    <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between">
      <div><strong><?php echo h(site_title()); ?> (Admin)</strong></div>
      <div><a href="/" style="color:white;text-decoration:none">Visit Site</a></div>
    </div>
  </header>
  <main style="max-width:1100px;margin:18px auto;display:flex;gap:18px">
    <nav style="width:220px">
      <div class="card">
        <h3>Admin</h3>
        <ul>
          <li><a href="/admin/dashboard.php">Dashboard</a></li>
          <li><a href="/admin/posts.php">Posts</a></li>
          <li><a href="/admin/post_edit.php">New Post</a></li>
          <li><a href="/admin/categories.php">Categories</a></li>
          <li><a href="/admin/users.php">Users</a></li>
          <li><a href="/admin/settings.php">Settings</a></li>
          <li><a href="/admin/logout.php">Logout</a></li>
        </ul>
      </div>
    </nav>
    <section style="flex:1">
    <?php if($msg = flash_get('success')): ?><div style="color:green;margin-bottom:8px"><?php echo h($msg); ?></div><?php endif; ?>
    <?php if($err = flash_get('error')): ?><div style="color:red;margin-bottom:8px"><?php echo h($err); ?></div><?php endif; ?>