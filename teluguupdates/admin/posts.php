<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
admin_require_login();

$posts = $pdo->query('SELECT p.id, p.title, p.published, p.created_at, c.name as category FROM posts p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC')->fetchAll();

require __DIR__ . '/../inc/header.php';
?>
<h2>Posts <a href="post_edit.php" style="float:right">+ New Post</a></h2>
<div class="card">
  <table>
    <thead><tr><th>Title</th><th>Category</th><th>Published</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($posts as $p): ?>
      <tr>
        <td><?php echo h($p['title']); ?></td>
        <td><?php echo h($p['category']); ?></td>
        <td><?php echo $p['published'] ? 'Yes' : 'No'; ?></td>
        <td><?php echo h($p['created_at']); ?></td>
        <td>
          <a href="post_edit.php?id=<?php echo h($p['id']); ?>">Edit</a> |
          <a href="post_delete.php?id=<?php echo h($p['id']); ?>&csrf=<?php echo h(csrf_token()); ?>" onclick="return confirm('Delete?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];
