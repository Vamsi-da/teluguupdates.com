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

global $pdo;

// Handle category add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $message = "✅ Category added successfully.";
    } else {
        $message = "⚠️ Please enter a category name.";
    }
}

// Handle category delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    header("Location: categories.php?deleted=1");
    exit;
}

// Fetch categories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Manage Categories - Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: #f7f9fc;
  margin: 0;
  padding: 0;
  color: #111;
}
header {
  background: linear-gradient(90deg,#0074b7,#005fa3);
  color: #fff;
  padding: 14px 20px;
  font-size: 20px;
  font-weight: 700;
}
.container {
  max-width: 800px;
  margin: 30px auto;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  padding: 20px;
}
h1 {
  font-size: 22px;
  margin-bottom: 10px;
  color: #0b3d91;
}
form {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}
input[type=text] {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 15px;
}
button {
  background: #0074b7;
  color: #fff;
  border: none;
  padding: 10px 18px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}
button:hover {
  background: #005fa3;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
th, td {
  border: 1px solid #e2e8f0;
  padding: 10px;
  text-align: left;
}
th {
  background: #f1f5f9;
}
tr:nth-child(even) {
  background: #fafafa;
}
a.delete-btn {
  color: #d93025;
  text-decoration: none;
  font-weight: bold;
}
a.delete-btn:hover {
  text-decoration: underline;
}
.message {
  background: #e0f7e9;
  color: #0b7223;
  padding: 10px;
  border-radius: 6px;
  margin-bottom: 15px;
  font-weight: 600;
}
</style>
</head>
<body>
<header>🗂️ Manage Categories</header>
<div class="container">
  <h1>Add New Category</h1>

  <?php if (!empty($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="message" style="background:#fee2e2;color:#b91c1c;">🗑️ Category deleted.</div>
  <?php endif; ?>

  <form method="post">
    <input type="text" name="name" placeholder="Enter category name..." required>
    <button type="submit">Add</button>
  </form>

  <h1>Existing Categories</h1>
  <?php if (empty($categories)): ?>
    <p>No categories yet. Add your first one above.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Action</th>
      </tr>
      <?php foreach ($categories as $cat): ?>
      <tr>
        <td><?= (int)$cat['id']; ?></td>
        <td><?= htmlspecialchars($cat['name']); ?></td>
        <td><a class="delete-btn" href="?delete=<?= (int)$cat['id']; ?>" onclick="return confirm('Delete this category?')">Delete</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
</body>
</html>