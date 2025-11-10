<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


$settings = get_settings();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = "WHERE p.published = 1";
$params = [];

if ($cat) {
    $where .= " AND p.category_id = ?";
    $params[] = $cat;
}

if (!empty($search)) {
    $where .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}


$queryBase = "
  SELECT p.id, p.title, p.slug, p.excerpt, p.thumb, p.created_at, p.views, c.name AS category
  FROM posts p LEFT JOIN categories c ON p.category_id = c.id
  $where
  ORDER BY p.created_at DESC
";



list($posts, $totalPages, $page) = paginate($queryBase, $params, $page, 10);
?>
<!doctype html>
<html lang="te">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($settings['site_title']); ?></title>
  <meta name="description" content="<?php echo h($settings['site_tagline']); ?>">
  <meta name="keywords" content="Telugu updates, Teluguupdates, Telugu, schemes, government schemes, government, govt, ap, ts, apndhra pradesh, telangana, India, services" >
  <meta name="robots" content="index, follow">

  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #f0f3f8;
      margin: 0;
      color: #333;
      transition: background 0.3s, color 0.3s;
    }

    header {
      background: linear-gradient(90deg, #0074b7, #00b4d8);
      color: white;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    header .container {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    header a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    /* Layout */
    .layout {
      max-width: 1300px;
      margin: 30px auto;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
      padding: 0 10px;
    }

    /* MAIN CONTAINER */
    .main-container {
      border: 5px solid transparent;
      border-image: linear-gradient(45deg, #ff007f, #ffae00, #00c3ff, #7cff00);
      border-image-slice: 1;
      border-radius: 16px;
      background: white;
      padding: 20px;
      animation: borderColor 6s linear infinite;
      transition: background 0.3s, color 0.3s;
    }
    @keyframes borderColor {
      0% { border-image-source: linear-gradient(45deg, #ff007f, #ffae00, #00c3ff, #7cff00); }
      50% { border-image-source: linear-gradient(45deg, #7cff00, #00c3ff, #ffae00, #ff007f); }
      100% { border-image-source: linear-gradient(45deg, #ff007f, #ffae00, #00c3ff, #7cff00); }
    }

   
    /* Posts */
    .post-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    .post-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .post-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .post-card img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }
    .post-card-content { padding: 15px; }
    .post-card h2 { margin: 0; font-size: 1.1rem; color: #0074b7; }
    .post-card p { font-size: 0.9rem; color: #444; margin-top: 6px; line-height: 1.4em; }
    .post-card a.read-more { display: inline-block; margin-top: 8px; color: #ff007f; font-weight: bold; text-decoration: none; }

    /* Pagination */
    .pagination { text-align: center; margin-top: 25px; }
    .pagination a, .pagination span {
      display: inline-block;
      padding: 8px 14px;
      margin: 2px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .pagination a { background: #0074b7; color: white; }
    .pagination a:hover { background: #005f93; }
    .pagination span { background: #ddd; color: #444; }

    /* SIDEBAR */
    aside {
      max-width: 350px;
      width: 100%;
    }
    .sidebar-card {
      background: #fff;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: background 0.3s, color 0.3s;
    }
    .sidebar-card h4 {
      border-bottom: 3px solid #0074b7;
      padding-bottom: 6px;
      margin-bottom: 10px;
    }

    footer {
      background: #222;
      color: #bbb;
      text-align: center;
      padding: 20px;
      margin-top: 40px;
    }

    /* DARK MODE */
    .dark-mode {
      background: #121212;
      color: #ddd;
    }
    .dark-mode header {
      background: linear-gradient(90deg, #222, #333);
    }
    .dark-mode .main-container,
    .dark-mode .sidebar-card {
      background: #1e1e1e;
      color: #ddd;
      box-shadow: 0 2px 8px rgba(255,255,255,0.05);
    }
    .toggle-dark {
      cursor: pointer;
      background: #fff3;
      padding: 6px 12px;
      border-radius: 6px;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 900px) {
      .layout {
        grid-template-columns: 1fr;
        max-width: 100%;
      }
      aside {
        max-width: 100%;
      }
      .post-grid {
        grid-template-columns: 1fr;
      }
    }
	.views-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(0,0,0,0.7);
  color: #fff;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: bold;
}
.categories-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.category-btn {
  display: inline-block;
  padding: 6px 12px;
  background: #0074b7;
  color: white;
  border-radius: 20px;
  text-decoration: none;
  font-size: 0.85rem;
  transition: all 0.2s ease;
}

.category-btn:hover {
  background: #00b4d8;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}


.trending-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.trending-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  margin-bottom: 10px;
  background: #f5f7fa;
  border-radius: 8px;
  text-decoration: none;
  color: #0074b7;
  font-weight: 500;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.trending-item:hover {
  background: #0074b7;
  color: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.trending-title {
  flex: 1;
  margin-right: 10px;
  font-size: 0.95rem;
}

.trending-views {
  background: rgba(0,0,0,0.1);
  color: #0074b7;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.8rem;
}

.trending-item:hover .trending-views {
  background: rgba(255,255,255,0.3);
  color: #fff;
}




  </style>
</head>
<body>

<header>
  <div class="container">
    <div>
      <strong style="font-size:1.5rem;"><?php echo h($settings['site_title']); ?></strong>
      <div style="font-size:0.9rem;"><?php echo h($settings['site_tagline']); ?></div>
    </div>
    <div>
      <a href="admin/login.php">Admin</a> |
      <span class="toggle-dark" onclick="toggleDarkMode()">🌙 / ☀️</span>
    </div>
  </div>
</header>

<div class="layout">
  <!-- MAIN CONTENT -->
  <main class="main-container">
    

    <div class="post-grid">
      <?php if (!empty($posts)): ?>
        <?php foreach($posts as $p): ?>
          <div class="post-card" style="border-top:4px solid <?php echo ['#ff007f','#ffae00','#0074b7','#00b4d8','#7cff00'][array_rand([0,1,2,3,4])]; ?>;">
  <a href="post.php?id=<?php echo h($p['id']); ?>" style="position:relative; display:block;">
    <?php if(!empty($p['thumb'])): ?>
      <img src="<?php echo h($p['thumb']); ?>" alt="<?php echo h($p['title']); ?>">
    <?php endif; ?>
    <!-- Views Badge -->
    <div class="views-badge">
      👁️ <?php echo h($p['views']); ?>
    </div>
  </a>
  <div class="post-card-content">
    <div style="font-size:0.8rem;color:#888;">
      <?php echo h($p['category']); ?> • <?php echo h(date('M j, Y', strtotime($p['created_at']))); ?>
    </div>
    <h2><a href="post.php?id=<?php echo h($p['id']); ?>"><?php echo h($p['title']); ?></a></h2>
    <p><?php echo h(mb_substr(strip_tags($p['excerpt']),0,120)); ?>...</p>
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

  <!-- SIDEBAR -->
  <aside>
    <div class="sidebar-card">
  <h4>Categories</h4>
  <div class="categories-list">
    <?php
    $cstmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
    foreach($cstmt->fetchAll() as $c) {
      echo '<a class="category-btn" href="?cat=' . h($c['id']) . '">' . h($c['name']) . '</a>';
	  
    }
    ?>
  </div>
</div>

    <div class="sidebar-card">
  <h4>Trending Posts 🔥</h4>
  <ul class="trending-list">
    <?php
    $trend = $pdo->query("SELECT id,title,views FROM posts WHERE published=1 ORDER BY views DESC LIMIT 5");
    foreach($trend->fetchAll() as $t){
      echo '<li>
              <a href="post.php?id='.$t['id'].'" class="trending-item">
                <span class="trending-title">'.h($t['title']).'</span>
                <span class="trending-views">👁️ '.h($t['views']).'</span>
              </a>
            </li>';
    }
    ?>
  </ul>
</div>


    
  </aside>
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
