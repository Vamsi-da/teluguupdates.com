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
if (!$id) { http_response_code(404); echo 'Invalid post'; exit; }

// Helper: check if column exists
function column_exists(PDO $pdo, $table, $column) {
    $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

$views_available = false;
try { $views_available = column_exists($pdo, 'posts', 'views'); } catch (Exception $e) {}

if (!$views_available) {
    try { $pdo->exec("ALTER TABLE posts ADD COLUMN views INT NOT NULL DEFAULT 0 AFTER created_at"); $views_available = true; } catch (Exception $e) {}
}

$stmt = $pdo->prepare('SELECT p.*, c.name AS category FROM posts p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=? AND p.published=1 LIMIT 1');
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) { http_response_code(404); echo 'Post not found'; exit; }

if ($views_available) {
    $pdo->prepare('UPDATE posts SET views=views+1 WHERE id=?')->execute([$id]);
    $post['views'] = ($post['views'] ?? 0) + 1;
}

$popular = $pdo->query("SELECT id,title,thumb,views FROM posts WHERE published=1 ORDER BY views DESC,created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$recent  = $pdo->query("SELECT id,title,thumb,views FROM posts WHERE published=1 ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$trend   = $pdo->query("SELECT id,title,views FROM posts WHERE published=1 ORDER BY views DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$cats    = $pdo->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$settings = get_settings();
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
?>
<!doctype html>
<html lang="te">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($post['title']); ?> | <?= h($settings['site_title']); ?></title>
<style>
body {
  margin:0;
  font-family:'Segoe UI',sans-serif;
  background:#f3f6fa;
  color:#111;
  overflow-x:hidden;
}
header {
  background:linear-gradient(90deg,#0074b7,#005fa3);
  color:#fff;
  padding:12px 16px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}
header .site-title {
  font-weight:700;
  font-size:18px;
}
.toggle-dark {
  cursor:pointer;
  background:rgba(255,255,255,.1);
  padding:6px 10px;
  border-radius:6px;
}
.category-ribbon {
  display:flex;
  gap:8px;
  overflow-x:auto;
  padding:10px 12px;
  background:linear-gradient(90deg,#3b82f6,#60a5fa);
  border-radius:12px;
  margin:10px auto;
  max-width:1100px;
  scrollbar-width:none;
}
.category-ribbon::-webkit-scrollbar { display:none; }
.cat-chip {
  padding:6px 16px;
  background:linear-gradient(90deg,#fff7d6,#ffecb3);
  border-radius:999px;
  font-weight:600;
  white-space:nowrap;
  color:#0b3d91;
  text-decoration:none;
  font-size:14px;
}

.container {
  max-width:1100px;
  margin:10px auto;
  display:grid;
  grid-template-columns:1fr 340px;
  gap:16px;
  padding:0 12px;
}
.card {
  background:#fff;
  border-radius:12px;
  padding:16px;
  box-shadow:0 3px 12px rgba(0,0,0,0.08);
}
.meta {
  color:#666;
  font-size:14px;
  margin-bottom:8px;
}
h1 {
  margin:8px 0 14px;
  font-size:26px;
  color:#0b2447;
}
.post-content {
  font-size:16px;
  line-height:1.75;
  color:#2d3748;
  overflow-wrap:break-word;
}
.post-content img {
  max-width:100%;
  border-radius:8px;
  height:auto;
  margin:12px 0;
}
.label-chip {
  display:inline-block;
  margin:6px 6px 6px 0;
  padding:6px 10px;
  border-radius:16px;
  background:#fff7ed;
  border:1px solid #f5d0a9;
  color:#8a3b00;
  text-decoration:none;
  font-weight:600;
}

.section-title { margin:25px 0 12px;font-weight:700;color:#0b2447; }
.tiles-slider {
  position:relative;
  overflow:hidden;
  padding:10px;
  background:#fffdf5;
  border-radius:10px;
  box-shadow:0 2px 6px rgba(0,0,0,.08);
}
.tiles-track {
  display:flex;
  transition:transform .5s ease;
  align-items:stretch;
  width:100%;
}
.tile {
  flex:0 0 100%;
  max-width:100%;
  padding:10px;
  box-sizing:border-box;
}
.tile-link {
  display:flex;
  flex-direction:column;
  align-items:flex-start;
  gap:12px;
  padding:14px;
  background:#fffbea;
  border-radius:12px;
  box-shadow:0 3px 12px rgba(0,0,0,0.1);
  text-decoration:none;
  color:#111;
  min-height:120px;
}
.tile-thumb {
  width:25%;
  height:120px;
  object-fit:cover;
  border-radius:8px;
}
.tile-title {
  font-weight:700;
  color:#0b3d91;
  font-size:17px;
  line-height:1.4;
}
.tile-views { font-size:14px;color:#555;margin-top:4px; }
.slider-btn {
  position:absolute;
  top:50%;
  transform:translateY(-50%);
  border:none;
  width:40px;
  height:40px;
  border-radius:50%;
  background:rgba(0,0,0,.35);
  color:#fff;
  font-size:20px;
  cursor:pointer;
  z-index:5;
}
.prev-btn{left:8px}
.next-btn{right:8px}

aside {
  background:#fff;
  border-radius:12px;
  padding:14px;
  box-shadow:0 3px 12px rgba(0,0,0,0.08);
  height:fit-content;
}
.sidebar-card h4 {
  margin:0 0 10px;
  border-bottom:3px solid #0074b7;
  padding-bottom:8px;
}
.trending-list {
  list-style:none;
  padding:0;
  margin:0;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.trending-item {
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:8px;
  border-radius:8px;
  background:#f5f7fa;
  text-decoration:none;
  color:#0074b7;
  font-weight:600;
}
.trending-item:hover {
  background:#0074b7;
  color:#fff;
}
footer {
  background:#222;
  color:#bbb;
  text-align:center;
  padding:20px;
  margin-top:30px;
}

.tile-content {
  width:100%;
  display:flex;
  align-items:center;
  gap:10px;
  background:#fffbea;
  border:1px solid #ddd;
  padding:1px;
  border-radius:8px;
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



/* ==== Mobile Responsiveness ==== */
@media(max-width:768px){
  .container {
    grid-template-columns:1fr;
    padding:0 10px;
  }
  aside {
    order:3;
    margin-top:20px;
    width:100%;
  }
  h1 {
    font-size:22px;
    line-height:1.4;
  }
  .tile-link {
    flex-direction:column;
    align-items:flex-start;
    min-height:auto;
  }
  .tile-thumb {
    width:100%;
    height:auto;
  }
  .tile-title {
    font-size:16px;
  }
  .meta {
    font-size:13px;
  }
  .post-content {
    font-size:15px;
  }
  .slider-btn {
    display:none;
  }
  .tiles-slider {
    overflow-x:auto;
    scroll-snap-type:x mandatory;
    display:flex;
    gap:10px;
  }
  .tiles-track {
    display:flex;
    width:auto;
    transform:none !important;
  }
  .tile {
    flex:0 0 85%;
    scroll-snap-align:center;
  }
}
</style>
</head>
<body>
<header>
  <div class="site-title"><?= h($settings['site_title']); ?></div>
  <div><span class="toggle-dark" onclick="toggleDarkMode()">🌙 / ☀️</span></div>
</header>

<main class="container">
<div>
  <article class="card">
    <div class="meta"><?= h($post['category'] ?? 'General'); ?> • <?= h(date('F j, Y', strtotime($post['created_at']))); ?> • 👁️ <?= (int)$post['views']; ?></div>
    <h1><?= h($post['title']); ?></h1>
    <div class="post-content"><?= $post['content_html']; ?></div>
    <div style="text-align:right;">Location: <b><?= h($post['location']); ?></b></div>
  </article>

  <br>

  <article class="card">
    <div class="tiles-slider popular-slider">
      <button class="slider-btn prev-btn">‹</button>
      <div class="tiles-track" id="popularTrack">
        <?php foreach($popular as $p): $thumb = $p['thumb'] ?? ''; ?>
        <div class="tile">
          <a class="tile-link" href="post.php?id=<?= (int)$p['id']; ?>">
            <div class="tile-content">
              <?php if(!empty($thumb)): ?>
                <img src="<?= h($thumb); ?>" alt="<?= h($p['title']); ?>" class="tile-thumb">
              <?php else: ?>
                <div class="tile-thumb" style="display:flex;align-items:center;justify-content:center;background:#eef7f1;color:#059669;font-weight:700">No Image</div>
              <?php endif; ?>
              <div>
                <div class="tile-title"><?= h($p['title']); ?></div>
                <div class="tile-views">👁️ <?= (int)$p['views']; ?> views</div>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="slider-btn next-btn">›</button>
    </div>

    <div class="tiles-slider recent-slider">
      <button class="slider-btn prev-btn">‹</button>
      <div class="tiles-track" id="recentTrack">
        <?php foreach($recent as $r): $thumb = $r['thumb'] ?? ''; ?>
        <div class="tile">
          <a class="tile-link" href="post.php?id=<?= (int)$r['id']; ?>">
            <div class="tile-content">
              <?php if(!empty($thumb)): ?>
                <img src="<?= h($thumb); ?>" alt="<?= h($r['title']); ?>" class="tile-thumb">
              <?php else: ?>
                <div class="tile-thumb" style="display:flex;align-items:center;justify-content:center;background:#eef7f1;color:#059669;font-weight:700">No Image</div>
              <?php endif; ?>
              <div>
                <div class="tile-title"><?= h($r['title']); ?></div>
                <div class="tile-views">👁️ <?= (int)$r['views']; ?> views</div>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <button class="slider-btn next-btn">›</button>
    </div>
  </article>
</div>

<div>
<!--  <aside>
    <div class="sidebar-card">
      <h4>Posts - Categories</h4>
      <div class="trending-list">
        <?php foreach($trend as $t): ?>
          <a class="trending-item" href="post.php?id=<?= (int)$t['id']; ?>">
            <span><?= h($t['title']); ?></span>
            <span>👁️ <?= (int)$t['views']; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
-->
  <aside>
    <div class="sidebar-card">
      <h4>Trending Posts 🔥</h4>
      <div class="trending-list">
        <?php foreach($trend as $t): ?>
          <a class="trending-item" href="post.php?id=<?= (int)$t['id']; ?>">
            <span><?= h($t['title']); ?></span>
            <span>👁️ <?= (int)$t['views']; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
</div>
</main>

<footer>&copy; <?= date('Y').' '.h($settings['site_title']); ?></footer>

<script>
function toggleDarkMode(){
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}
if(localStorage.getItem('darkMode')==='true')document.body.classList.add('dark-mode');

function initSingleSlider(trackId){
  const track=document.getElementById(trackId);
  const tiles=track.querySelectorAll('.tile');
  const total=tiles.length;
  let index=0;
  function showSlide(i){ track.style.transform=`translateX(-${i*100}%)`; }
  const next=track.parentElement.querySelector('.next-btn');
  const prev=track.parentElement.querySelector('.prev-btn');
  function nextSlide(){ index=(index+1)%total; showSlide(index); }
  function prevSlide(){ index=(index-1+total)%total; showSlide(index); }
  let timer=setInterval(nextSlide,5000);
  function reset(){ clearInterval(timer); timer=setInterval(nextSlide,5000); }
  next.addEventListener('click',()=>{nextSlide();reset();});
  prev.addEventListener('click',()=>{prevSlide();reset();});
  track.addEventListener('mouseenter',()=>clearInterval(timer));
  track.addEventListener('mouseleave',()=>reset());
}
document.addEventListener('DOMContentLoaded',()=>{
  initSingleSlider('popularTrack');
  initSingleSlider('recentTrack');
});
</script>
</body>
</html>