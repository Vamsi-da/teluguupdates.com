<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    http_response_code(404);
    echo 'Invalid post';
    exit;
}

// helper: check if column exists in table
function column_exists(PDO $pdo, $table, $column) {
    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

$views_available = false;
try {
    $views_available = column_exists($pdo, 'posts', 'views');
} catch (Exception $e) {
    // INFORMATION_SCHEMA check failed — leave $views_available false
    $views_available = false;
}

// If views column missing, try to add it (best-effort)
if (!$views_available) {
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER created_at");
        $views_available = true;
    } catch (Exception $e) {
        // Could not add column (permissions?), continue without views support
        $views_available = false;
    }
}

// fetch the post (only published)
$stmt = $pdo->prepare('SELECT p.*, c.name AS category FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.published = 1 LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
// comments save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $message = trim($_POST['message'] ?? '');
    if ($name !== '' && $message !== '') {
        $stmtc = $pdo->prepare('INSERT INTO comments (post_id,name,email,message,created_at,published) VALUES (?,?,?,?,NOW(),0)');
        $stmtc->execute([$post['id'],$name,$email,$message]);
        $comment_notice = 'Thank you — your comment has been submitted for review.';
    } else { $comment_error = 'Name and message are required'; }
}

if (!$post) {
    http_response_code(404);
    echo 'Post not found';
    exit;
}

// increase view count only if column exists
if ($views_available) {
    try {
        $up = $pdo->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
        $up->execute([$id]);
        // reflect incremented value in $post for immediate display if needed
        $post['views'] = (isset($post['views']) ? $post['views'] + 1 : 1);
    } catch (Exception $e) {
        // ignore update errors
    }
}

// Recent 2 posts (most recent by created_at)
try {
    $recentStmt = $pdo->prepare('SELECT id, title, thumb, views FROM posts WHERE published = 1 AND id != ? ORDER BY created_at DESC LIMIT 2');
    $recentStmt->execute([$id]);
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent = [];
}

// Top viewed 2 posts — if views available use it, otherwise fallback to recent
try {
    if ($views_available) {
        $popularStmt = $pdo->prepare('SELECT id, title, thumb, views FROM posts WHERE published = 1 AND id != ? ORDER BY views DESC LIMIT 2');
        $popularStmt->execute([$id]);
        $popular = $popularStmt->fetchAll(PDO::FETCH_ASSOC);
        // if popular empty (maybe all views are 0) fallback to recent
        if (empty($popular)) {
            $popular = $recent;
        }
    } else {
        // fallback: pick top by created_at
        $popular = $recent;
    }
} catch (Exception $e) {
    $popular = $recent;
}


$settings = get_settings();
?>
<!doctype html>
<html lang="te">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($post['title']); ?> | <?php echo h($settings['site_title']); ?></title>
  <meta name="description" content="<?php echo h(mb_substr(strip_tags($post['excerpt'] ?? ''),0,160)); ?>">
    <meta name="keywords" content="Telugu updates, Teluguupdates, Telugu, schemes, government schemes, government, govt, ap, ts, apndhra pradesh, telangana, India, services" >
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    /* --- Post view styling --- */
    body {
      font-family: 'Segoe UI', Roboto, Arial, sans-serif;
      background-color: #f3f6fa;
      color: #333;
      margin: 0;
    }
    header {
      background: linear-gradient(90deg, #0074b7, #005fa3);
      color: #fff;
      padding: 14px 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    header .wrap {
      max-width: 1100px;
      margin: 0 auto;
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding: 0 16px;
    }
    header a { color: #fff; text-decoration: none; font-weight: 600; }

    .container {
      max-width: 1100px;
      margin: 10px auto;
      padding: 5px 8px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 10px;
    }

    /* Main post card */
    .card {
      background: #fff;
      margin: auto 5px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      overflow: hidden;
      padding: 12px;
     /* border-left: 6px solid #0074b7; /* left border color */
     /* border-top: 6px solid #f59f00;  /* top border color */
    }   
    .card img.feature {
      border-radius: 8px;
      width: 100%;
      height: 420px;
      object-fit: cover;
      margin-bottom: 16px;
    }
    .meta {
      color: #6b7280;
      font-size: 14px;
      margin-bottom: 8px;
    }
    h1 {
      font-size: 30px;
      margin: 6px 0 14px;
      color: #111827;
    }
    .post-content {
      font-size: 17px;
      line-height: 1.75;
      color: #374151;
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


    /* right column widgets */
    aside .widget {
      background: #fff;
      padding: 14px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
      margin-bottom: 18px;
      border-left: 6px solid #10b981; /* accent for widgets */
      border-top: 6px solid #06b6d4;
    }
    .widget h4 { margin: 0 0 10px 0; color: #0f172a; }

    .tiles {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-top: 10px;
    }
    .tile {
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      border-left: 6px solid #0074b7;
      border-top: 6px solid #f59f00;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
      transition: transform .18s ease, box-shadow .18s ease;
    }
    .tile:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .tile img { width:100%; height:150px; object-fit:cover; display:block; }
    .tile .title { padding: 8px; font-size:14px; color:#0b5ed7; font-weight:600; text-decoration:none; display:block; }
    .tile .title:hover { text-decoration:underline; }

    .adbox {
      height:120px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#f8fafc;
      border-radius:8px;
      border:1px dashed #e5e7eb;
      color:#6b7280;
      font-style:italic;
    }

    footer {
      margin-top: 34px;
      padding: 20px;
      color: #6b7280;
      text-align: center;
    }

    @media(max-width:1000px) {
      .container { grid-template-columns: 1fr; }
      .card img.feature { height: 260px; }
    }

.tiles-slider {
  position: relative;
  overflow: hidden;
  width: 100%;
  max-width: 900px; /* adjust as needed */
  margin: auto;
}

.tiles-track {
  display: flex;
  transition: transform 0.4s ease;
}

.tile {
  min-width: 100%; /* show 2 tiles at a time */
  box-sizing: border-box;
  padding: 8px;
}

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
    <div class="wrap">
      <div><strong><?php echo h($settings['site_title']); ?></strong></div>
      <span class="toggle-dark" onclick="toggleDarkMode()">🌙 / ☀️</span>
    </div>
  </header>

  <main class="container">
    <section>
      <article class="card">
        <?php if (!empty($post['thumb'])): ?>
          <img class="feature" src="<?php echo h($post['thumb']); ?>" alt="<?php echo h($post['title']); ?>" width="200px" height="150px">
        <?php endif; ?>

        <div class="meta"><?php echo h($post['category'] ?? 'General'); ?> • <?php echo h(date('F j, Y', strtotime($post['created_at']))); ?>
          <?php if (isset($post['views'])): ?> • Views: <?php echo (int)$post['views']; ?><?php endif; ?>
        </div>

        <h1><?php echo h($post['title']); ?></h1>

        <div class="post-content">
          <?php
            // content_html may contain trusted HTML inserted by admin.
            // If content may be untrusted, sanitize here before echo.
            echo $post['content_html'];
          ?>
        </div>

        <hr style="margin:25px 0; border:none; border-top:1px solid #e5e7eb;">

<!-- Views count at bottom -->
<div style="font-size:15px; color:#555; text-align:right;">
  👁️ Views: <strong><?php echo (int)($post['views'] ?? 0); ?></strong>
</div>
      </article>

      <!-- Tiles: Recent and Top Viewed (two sections) -->
<div style="margin-top:28px">
        <div style="margin-bottom:16px">
          <h3 style="margin:0 0 10px 0; color:#111827; border-left:5px solid #f59f00; padding-left:10px;">Recent Posts</h3>
      


      <div class="tiles-slider popular-slider">
  <div class="tiles-track">
    <?php if (empty($popular)): ?>
      <div class="tile"><div style="padding:12px">No popular posts</div></div>
      <div class="tile"><div style="padding:12px">No popular posts</div></div>
    <?php else: foreach (array_slice($popular, 0, 10) as $p): ?>
      <div class="tile">
        <a class="tile-link" href="post.php?id=<?php echo (int)$p['id']; ?>">
          <?php if (!empty($p['thumb'])): ?>
            <img src="<?php echo h($p['thumb']); ?>" alt="<?php echo h($p['title']); ?>">
          <?php else: ?>
            <div style="height:110px;background:#f0fff4;display:flex;align-items:center;justify-content:center;color:#059669">
              No Image
            </div>
          <?php endif; ?>
          <div class="title">
            <?php echo h($p['title']); ?><br>
            <small style="color:#666;">👁️ <?php echo (int)($p['views'] ?? 0); ?> views</small>
          </div>
        </a>
      </div>
    <?php endforeach; endif; ?>
  </div>


</div>


 
</div>

        </div>
      </div>







          <div class="tiles-slider">
  <div class="tiles-track">
    <?php if (empty($recent)): ?>
      <div class="tile"><div style="padding:12px">No recent posts</div></div>
      <div class="tile"><div style="padding:12px">No recent posts</div></div>
    <?php else: foreach (array_slice($recent, 0, 10) as $r): ?>
      <div class="tile">
        <a class="tile-link" href="post.php?id=<?php echo (int)$r['id']; ?>">
          <?php if (!empty($r['thumb'])): ?>
            <img src="<?php echo h($r['thumb']); ?>" alt="<?php echo h($r['title']); ?>" width="200px" height="150px">
          <?php else: ?>
            <div style="height:110px;background:#eef2ff;display:flex;align-items:center;justify-content:center;color:#7c3aed">No Image</div>
          <?php endif; ?>
          <div class="title"><?php echo h($r['title']); ?><br>
            <small style="color:#666;">👁️ <?php echo (int)($r['views'] ?? 0); ?> views</small>
          </div>
        </a>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- Slider controls -->
</div>
          </div>
        </div>

    </section>

    <aside>
      <div class="widget">
        <h4>Categories</h4>
        <ul style="margin:0;padding:0;list-style:none;">
        <?php
          try {
              $cstmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
              foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
                  echo '<li style="margin:6px 0"><a href="?cat=' . h($c['id']) . '">' . h($c['name']) . '</a></li>';
              }
          } catch (Exception $e) {
              echo '<li>—</li>';
          }
        ?>
        </ul>
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
  </main>
  <footer>
    &copy; <?php echo date('Y') . ' ' . h($settings['site_title']); ?>
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