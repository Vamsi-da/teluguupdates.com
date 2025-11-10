<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            $slug = slugify($name);
            $pdo->prepare('INSERT INTO categories (name,slug,created_at) VALUES (?, ?, NOW())')->execute([$name,$slug]);
            header('Location: categories.php'); exit;
        }
    }
}

$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$posts = $pdo->query('SELECT id,title,category_id FROM posts ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/inc/header.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

?>
<div class="container">
  <h2>Categories Management</h2>
  <div class="card">
    <h4>Add Category</h4>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <input name="name" placeholder="Category name" required>
      <button type="submit">Add</button>
    </form>
  </div>

  <div class="card" style="margin-top:12px;">
    <h4>Existing Categories</h4>
    <table>
      <thead><tr><th>Name</th><th>Slug</th><th>Posts</th></tr></thead>
      <tbody>
        <?php foreach($cats as $c): 
            $pc = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE category_id=?');
            $pc->execute([$c['id']]);
            $count = $pc->fetchColumn();
        ?>
        <tr>
          <td><?= h($c['name']); ?></td>
          <td><?= h($c['slug']); ?></td>
          <td><?= (int)$count; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="card" style="margin-top:12px;">
    <h4>Organize Posts</h4>
    <form method="post" action="/admin/post_edit.php">
      <p>Select a post to edit and change its category in the post edit screen.</p>
      <table>
        <thead><tr><th>Title</th><th>Category</th></tr></thead>
        <tbody>
        <?php foreach($posts as $p): ?>
          <tr>
            <td><a href="/admin/post_edit.php?id=<?= h($p['id']); ?>"><?= h($p['title']); ?></a></td>
            <td><?= h($p['category_id'] ? ($pdo->query('SELECT name FROM categories WHERE id='.(int)$p['category_id'])->fetchColumn()) : '—'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </form>
  </div>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>