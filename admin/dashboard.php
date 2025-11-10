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

global $pdo;

// ==== FILTERS ====
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Determine date filtering condition
$condition = "";
$params = [];

switch ($filter) {
    case 'today':
        $condition = "WHERE DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $condition = "WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $condition = "WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        break;
    case 'year':
        $condition = "WHERE YEAR(created_at) = YEAR(CURDATE())";
        break;
    default:
        $condition = ""; // no filter
}

// ==== PAGINATION ====
$countStmt = $pdo->query("SELECT COUNT(*) FROM posts $condition");
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalPosts / $perPage));
$offset = ($page - 1) * $perPage;

// ==== FETCH POSTS ====
$sql = "SELECT id, title, slug, created_at, published, views
        FROM posts
        $condition
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==== SUMMARY STATS ====
$stats = [
    'total'     => $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM posts WHERE published = 1")->fetchColumn(),
    'views'     => $pdo->query("SELECT SUM(views) FROM posts")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f6f8; margin:0; padding:0; }
        header { background:#007bff; color:white; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; }
        header a { color:white; text-decoration:none; margin-left:15px; }
        .container { padding:25px; }
        .card { background:white; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { border-bottom:1px solid #ddd; text-align:left; padding:10px; }
        th { background:#007bff; color:white; }
        tr:hover { background:#f1f1f1; }
        .btn { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; text-decoration:none; }
        .btn-add { background:#28a745; color:white; }
        .btn-edit { background:#007bff; color:white; }
        .btn-del { background:#dc3545; color:white; }
        .stats { display:flex; gap:20px; margin-top:20px; flex-wrap:wrap; }
        .stat { background:white; padding:15px; flex:1; border-radius:8px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
        .filters { margin-top:20px; }
        .filters a { margin-right:10px; text-decoration:none; color:#007bff; font-weight:500; }
        .filters a.active { text-decoration:underline; }
        .pagination { margin-top:20px; text-align:center; }
        .pagination a { padding:6px 10px; margin:0 3px; background:#007bff; color:white; text-decoration:none; border-radius:4px; }
        .pagination a.disabled { background:#ccc; pointer-events:none; }
    </style>
</head>
<body>
<header>
  <h2>🧭 Admin Dashboard</h2>
  <div>
    <a href="categories.php" class="btn btn-add">+ Add New Category</a>
    <a href="post_edit.php" class="btn btn-add">+ Add New Post</a>
    <a href="logout.php" class="btn" style="background:#6c757d;">Logout</a>
  </div>
</header>

<div class="container">
  <!-- Stats -->
  
<?php
// === STATS QUERIES ===
try {
    // Total posts
    $totalPosts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

    // Total views
    $totalViews = $pdo->query("SELECT SUM(views) FROM posts")->fetchColumn() ?: 0;

    // Posts published this month
    $monthlyPosts = $pdo->query("SELECT COUNT(*) FROM posts WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetchColumn();

    // Average views per post
    $avgViews = $totalPosts > 0 ? round($totalViews / $totalPosts, 1) : 0;
} catch (Exception $e) {
    $totalPosts = $totalViews = $monthlyPosts = $avgViews = 0;
}
?>
<div class="stats">
  <div class="stat"><strong>Total Posts</strong><br><?php echo $totalPosts; ?></div>
  <div class="stat"><strong>Posts This Month</strong><br><?php echo $monthlyPosts; ?></div>
  <div class="stat"><strong>Total Views</strong><br><?php echo $totalViews; ?></div>
  <div class="stat"><strong>Avg. Views/Post</strong><br><?php echo $avgViews; ?></div>
</div>

  <!-- Filters -->
  <div class="filters">
    <strong>Filter by:</strong>
    <?php
    $filters = ['all'=>'All','today'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'];
    foreach ($filters as $key=>$label): ?>
      <a href="?filter=<?php echo $key; ?>" class="<?php echo $filter === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
  </div>

  <!-- Posts Table -->
  <div class="card">
    <h3>📋 Posts List (<?php echo ucfirst($filter); ?>)</h3>
    <table>
      <thead>
        <tr><th>ID</th><th>Title</th><th>Slug</th><th>Views</th><th>Published</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if(empty($posts)): ?>
          <tr><td colspan="7">No posts found.</td></tr>
        <?php else: foreach($posts as $p): ?>
          <tr>
            <td><?php echo (int)$p['id']; ?></td>
            <td><?php echo htmlspecialchars($p['title']); ?></td>
            <td><?php echo htmlspecialchars($p['slug']); ?></td>
            <td><?php echo (int)$p['views']; ?></td>
            <td><?php echo $p['published'] ? '✅' : '❌'; ?></td>
            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
            <td>
              <a href="post_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-edit">Edit</a>
              <a href="post_delete.php?id=<?php echo $p['id']; ?>" class="btn btn-del" onclick="return confirm('Delete this post?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page-1; ?>">&laquo; Prev</a>
      <?php else: ?>
        <a class="disabled">&laquo; Prev</a>
      <?php endif; ?>

      <span> Page <?php echo $page; ?> of <?php echo $totalPages; ?> </span>

      <?php if ($page < $totalPages): ?>
        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page+1; ?>">Next &raquo;</a>
      <?php else: ?>
        <a class="disabled">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </div>
  <!-- Top Viewed Posts Widget -->
<div class="card" style="margin-top:30px;">
  <h3>🔥 Top 10 Most Viewed Posts</h3>
  <?php
  try {
      $stmt = $pdo->query("SELECT id, title, views, created_at FROM posts WHERE published = 1 ORDER BY views DESC LIMIT 10");
      $topViewed = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
      $topViewed = [];
  }
  ?>
  <table>
    <thead>
      <tr><th>#</th><th>Title</th><th>Views</th><th>Created</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php if (empty($topViewed)): ?>
        <tr><td colspan="5">No data available</td></tr>
      <?php else: $i = 1; foreach ($topViewed as $t): ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><?php echo htmlspecialchars($t['title']); ?></td>
          <td><strong><?php echo (int)$t['views']; ?></strong></td>
          <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($t['created_at']))); ?></td>
          <td><a class="btn btn-edit" href="post_edit.php?id=<?php echo $t['id']; ?>">Edit</a></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Views Chart -->
<div class="card" style="margin-top:30px;">
  <h3>📊 Daily Views (Last 14 Days)</h3>
  <canvas id="viewsChart" height="100"></canvas>
</div>

<?php
// Fetch daily aggregated views for last 14 days
try {
    $chartStmt = $pdo->query("
        SELECT DATE(created_at) AS day, SUM(views) AS total_views
        FROM posts
        WHERE created_at >= CURDATE() - INTERVAL 14 DAY
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");
    $chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $chartData = [];
}
$chartLabels = json_encode(array_column($chartData, 'day'));
$chartValues = json_encode(array_map('intval', array_column($chartData, 'total_views')));
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('viewsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo $chartLabels; ?>,
        datasets: [{
            label: 'Total Views',
            data: <?php echo $chartValues; ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>


</div>
</body>
</html>

<?php require __DIR__ . '/../inc/footer.php'; ?>