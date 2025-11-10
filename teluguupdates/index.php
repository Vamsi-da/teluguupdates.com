<?php
// -------------------------
// CONFIG & INITIALIZATION
// -------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';

// Initialize defaults
$settings = get_settings();
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$cat    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where  = "WHERE p.published = 1";
$params = [];

// -------------------------
// FILTER CONDITIONS
// -------------------------
if ($cat) {
    $where .= " AND p.category_id = ?";
    $params[] = $cat;
}

if (!empty($search)) {
    $where .= " AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content_html LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// -------------------------
// MAIN QUERY
// -------------------------
$queryBase = "
  SELECT 
    p.id, p.title, p.slug, p.excerpt, p.thumb, 
    p.created_at, p.views, c.name AS category
  FROM posts p
  LEFT JOIN categories c ON p.category_id = c.id
  $where
  ORDER BY p.created_at DESC
";

try {
    list($posts, $totalPages, $page) = paginate($queryBase, $params ?? [], $page, 10);
} catch (PDOException $e) {
    echo "<p style='color:red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    $posts = [];
    $totalPages = 1;
}
?>
<!doctype html>
<html lang="te">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($settings['site_title']); ?></title>
  <meta name="description" content="<?php echo h($settings['site_tagline']); ?>">
  <meta name="keywords" content="Telugu updates, Teluguupdates, Telugu, schemes, government schemes, govt, ap, ts, andhra pradesh, telangana, India, services">
  <meta name="robots" content="index, follow">

  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f6f8fb;
      margin: 0;
      color: #333;
      transition: background 0.3s, color 0.3s;
      line-height: 1.5;
    }
    header {
      background: linear-gradient(90deg, #0074b7, #00b4d8);
      color: white;
      padding: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    header .container {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    header strong {
      font-size: 1.4rem;
    }
    .toggle-dark {
      cursor: pointer;
      background: #ffffff33;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 1.2rem;
    }

    /* Search bar */
    .search-box {
      background: #fff;
      padding: 15px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      border-radius: 8px;
      margin: 10px auto;
      max-width: 800px;
    }
    .search-box form {
      display: flex;
      gap: 10px;
    }
    .search-box input[type=text] {
      flex: 1;
      padding: 10px 14px;
      border: 2px solid #0074b7;
      border-radius: 8px;
      font-size: 1rem;
      outline: none;
    }
    .search-box button {
      background: #0074b7;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
    }

    /* Category ribbon */
    .category-ribbon {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      gap: 8px;
      padding: 10px 12px;
      background: #fff;
      border-radius: 8px;
      margin: 12px auto;
      max-width: 1100px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .cat-chip {
      display: inline-block;
      padding: 6px 14px;
      background: #eef2ff;
      border-radius: 20px;
      text-decoration: none;
      color: #0b3d91;
      font-weight: 600;
      white-space: nowrap;
      transition: 0.3s;
    }
    .cat-chip:hover {
      background: #0074b7;
      color: #fff;
    }

    /* Layout grid */
    .layout {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
      max-width: 1200px;
      margin: 20px auto;
      padding: 0 10px;
    }
    .main-container {
      flex: 1 1 720px;
      min-width: 300px;
      background: #fff;
      border-radius: 12px;
      padding: 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    aside {
      flex: 1 1 320px;
      min-width: 250px;
    }

    /* Posts */
    .post-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 18px;
    }
    .post-card {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 3px 8px rgba(0,0,0,0.08);
      transition: transform 0.2s;
    }
    .post-card:hover {
      transform: translateY(-5px);
    }
    .post-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }
    .post-card-content {
      padding: 14px;
    }
    .post-card h2 {
      font-size: 1rem;
      color: #0074b7;
      margin: 5px 0;
    }
    .read-more {
      display: inline-block;
      color: #ff007f;
      font-weight: 600;
      text-decoration: none;
      margin-top: 8px;
    }
    .views-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(0,0,0,0.6);
      color: #fff;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.75rem;
    }

    /* Pagination */
    .pagination {
      text-align: center;
      margin-top: 25px;
    }
    .pagination a, .pagination span {
      display: inline-block;
      padding: 8px 14px;
      margin: 2px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .pagination a {
      background: #0074b7;
      color: white;
    }
    .pagination span {
      background: #ddd;
      color: #444;
    }

    /* Sidebar */
    .sidebar-card {
      background: #fff;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    footer {
      background: #222;
      color: #bbb;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
      font-size: 0.9rem;
    }

    /* Dark mode */
    .dark-mode {
      background: #121212;
      color: #ddd;
    }
    .dark-mode header { background: linear-gradient(90deg, #222, #333); }
    .dark-mode .main-container, .dark-mode .sidebar-card, .dark-mode .search-box, .dark-mode .category-ribbon {
      background: #1e1e1e;
      color: #ddd;
      box-shadow: 0 2px 8px rgba(255,255,255,0.05);
    }

    @media (max-width: 768px) {
      header strong { font-size: 1.2rem; }
      .post-card img { height: 140px; }
      .layout { flex-direction: column; }
      aside { order: -1; }
    }
  </style>
</head>

<body>
<header>
  <div class="container">
    <div>
      <strong><?php echo h($settings['site_title']); ?></strong><br>
      <span style="font-size:0.85rem;"><?php echo h($settings['site_tagline']); ?></span>
    </div>
    <div><span class="toggle-dark" onclick="toggleDarkMode()">🌙 / ☀️</span></div>
  </div>
</header>

<!-- 🔍 Search Bar -->
<div class="search-box">
  <form method="get" action="index.php">
    <?php if(isset($_GET['cat']) && $_GET['cat']): ?>
      <input type="hidden" name="cat" value="<?php echo (int)$_GET['cat']; ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="🔍 శోధించండి... (Search for topics, posts, or updates)">
    <button type="submit">Search</button>
  </form>
</div>

<!-- Category Ribbon -->
<div class="category-ribbon">
  <?php
  try {
      $currentCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
      $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
      $allActive = ($currentCat === 0) ? 'style="background:#0074b7;color:#fff;"' : '';
      echo '<a class="cat-chip" '.$allActive.' href="index.php">All</a>';
      foreach ($cats as $c) {
          $active = ($currentCat === (int)$c['id']) ? 'style="background:#0074b7;color:#fff;"' : '';
          echo '<a class="cat-chip" '.$active.' href="index.php?cat='.h($c['id']).'">'.h($c['name']).'</a>';
      }
  } catch (Exception $e) {
      echo '<span style="color:red;">Error loading categories</span>';
  }
  ?>
</div>

<!-- Layout -->
<div class="layout">
  <main class="main-container">
    <div class="post-grid">
      <?php if (!empty($posts)): ?>
        <?php foreach($posts as $p): ?>
          <div class="post-card" style="position:relative;">
            <a href="post.php?id=<?php echo h($p['id']); ?>">
              <?php if (!empty($p['thumb'])): ?>
                <img src="<?php echo htmlspecialchars($p['thumb']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
              <?php endif; ?>
              <div class="views-badge">👁️ <?php echo h($p['views']); ?></div>
            </a>
            <div class="post-card-content">
              <div style="font-size:0.8rem;color:#888;">
                <?php echo h($p['category']); ?> • <?php echo h(date('M j, Y', strtotime($p['created_at']))); ?>
              </div>
              <h2><a href="post.php?id=<?php echo h($p['id']); ?>"><?php echo h($p['title']); ?></a></h2>
              <a class="read-more" href="post.php?id=<?php echo h($p['id']); ?>">పూర్తిగా చదవండి →</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="text-align:center;">ఏ పథకాలు కనుగొనబడలేదు.</p>
      <?php endif; ?>
    </div>

    <div class="pagination">
      <?php if($page>1): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page-1])); ?>">&laquo; Prev</a>
      <?php endif; ?>
      <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
      <?php if($page<$totalPages): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET,['page'=>$page+1])); ?>">Next &raquo;</a>
      <?php endif; ?>
    </div>
  </main>
<main class="main-container">
  <aside>
    <div class="sidebar-card">
      <h4>Trending Posts 🔥</h4>
      <ul style="list-style:none;padding:0;margin:0;">
      <?php
      $trend = $pdo->query("SELECT id,title,views FROM posts WHERE published=1 ORDER BY views DESC LIMIT 5");
      foreach($trend->fetchAll() as $t){
        echo '<li style="margin-bottom:10px;">
                <a href="post.php?id='.$t['id'].'" style="text-decoration:none;color:#0074b7;font-weight:500;display:flex;justify-content:space-between;">
                  <span>'.h($t['title']).'</span>
                  <span style="color:#888;">👁️ '.h($t['views']).'</span>
                </a>
              </li>';
      }
      ?>
      </ul>
    </div>
  </aside></main>
</div>

<footer>
  &copy; <?php echo date('Y') . ' ' . h($settings['site_title']); ?> | Designed with ❤️ by Telugu Updates
</footer>

<script>
function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}
if (localStorage.getItem('darkMode') === 'true') document.body.classList.add('dark-mode');
</script>
</body>
</html>
