<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

$q = trim($_GET['q'] ?? '');
$date = trim($_GET['date'] ?? '');

$sql = "
  SELECT p.id, p.title, p.slug, p.thumb, p.content, p.created_at, p.views, c.name AS category
  FROM posts p
  LEFT JOIN categories c ON p.category_id = c.id
  WHERE p.published = 1
";
$params = [];

// Keyword filter
if ($q !== '') {
  $sql .= " AND (p.title LIKE ? OR p.content LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}

// Date filter
if ($date !== '') {
  $sql .= " AND DATE(p.created_at) = ?";
  $params[] = $date;
}

$sql .= " ORDER BY p.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$posts) {
  echo "<div class='alert alert-warning text-center'>No posts found.</div>";
  exit;
}

$colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary'];
foreach ($posts as $p):
  $color = $colors[array_rand($colors)];
  $excerpt = strip_tags($p['content']);
  $preview = mb_strimwidth($excerpt, 0, 150, '...');
?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100 border-<?php echo $color; ?> border-3 shadow-sm post-link" style="cursor:pointer;"
         onclick="window.location='post.php?id=<?php echo (int)$p['id']; ?>'">
      <?php if (!empty($p['thumb'])): ?>
        <img src="<?php echo h($p['thumb']); ?>" class="card-img-top" style="height:160px;object-fit:cover;">
      <?php endif; ?>
      <div class="card-body">
        <h5 class="card-title text-<?php echo $color; ?>"><?php echo h($p['title']); ?></h5>
        <p class="card-text text-muted small mb-2">
          <?php echo h($p['category']); ?> • <?php echo date('M j, Y', strtotime($p['created_at'])); ?>
        </p>
        <p class="card-text"><?php echo h($preview); ?></p>
      </div>
      <div class="card-footer bg-transparent border-0 text-end small text-muted">
        👁️ <?php echo (int)$p['views']; ?> views
      </div>
    </div>
  </div>
<?php endforeach; ?>
