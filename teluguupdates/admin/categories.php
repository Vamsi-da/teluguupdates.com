<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

admin_require_login();

// ✅ Add new category manually
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));

    // Check if exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
    $check->execute([$slug]);
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
    }
}

// ✅ Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories</title>
<style>
body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 30px; }
.container { max-width: 700px; margin: auto; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
input[type="text"] { width: 75%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
button { padding: 8px 14px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #0056b3; }
.category-item { display:inline-block; background:#e8f4ff; color:#007bff; padding:6px 12px; border-radius:6px; margin:5px 6px; }
h2 { margin-top: 0; }
</style>
</head>
<body>
<div class="container">
  <h2>🗂 Manage Categories</h2>

  <form method="POST" style="margin-bottom:20px;">
    <input type="text" name="category_name" placeholder="Enter new category" required>
    <button type="submit">Add Category</button>
  </form>

  <h3>Existing Categories</h3>
  <div>
    <?php foreach ($categories as $cat): ?>
      <span class="category-item"><?= htmlspecialchars($cat['name']) ?></span>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>