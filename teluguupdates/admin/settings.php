<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) { flash_set('error', 'Invalid CSRF'); header('Location: settings.php'); exit; }
    $site_title = $_POST['site_title'] ?? '';
    $site_tagline = $_POST['site_tagline'] ?? '';
    $adsense = $_POST['adsense_id'] ?? '';

    $up = $pdo->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $up->execute(['site_title', $site_title]);
    $up->execute(['site_tagline', $site_tagline]);
    $up->execute(['adsense_id', $adsense]);

    // refresh cached settings
    flash_set('success','Settings saved');
    header('Location: settings.php'); exit;
}

$settings = [];
$stmt = $pdo->query('SELECT `key`, `value` FROM settings')->fetchAll();
foreach($stmt as $s) $settings[$s['key']] = $s['value'];

require __DIR__ . '/../inc/header.php';
?>
<h2>Settings</h2>
<div class="card">
  <?php if($m=flash_get('success')): ?><div style="color:green"><?php echo h($m); ?></div><?php endif; ?>
  <form method="post">
    <div class="form-row"><label>Site Title</label><input type="text" name="site_title" value="<?php echo h($settings['site_title'] ?? ''); ?>"></div>
    <div class="form-row"><label>Site Tagline</label><input type="text" name="site_tagline" value="<?php echo h($settings['site_tagline'] ?? ''); ?>"></div>
    <div class="form-row"><label>AdSense Client ID (ca-pub-...)</label><input type="text" name="adsense_id" value="<?php echo h($settings['adsense_id'] ?? ''); ?>"></div>
    <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
    <div><button type="submit">Save</button></div>
  </form>
</div>
<?php require __DIR__ . '/../inc/footer.php'; ?>
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];
