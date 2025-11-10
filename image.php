<?php
require_once __DIR__ . '/inc/db.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


// Serve image from DB by id with optional width/height (resized using GD)
// Usage example: image.php?id=12&w=100&h=100
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$w = isset($_GET['w']) ? (int)$_GET['w'] : 100;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 100;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT mime, data FROM images WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$img) { header('HTTP/1.0 404 Not Found'); exit; }
    $mime = $img['mime'];
    $data = $img['data'];
    $src = @imagecreatefromstring($data);
    if (!$src) { header('Content-Type: ' . $mime); echo $data; exit; }
    $orig_w = imagesx($src);
    $orig_h = imagesy($src);
    $dst = imagecreatetruecolor($w, $h);
    // preserve transparency
    if ($mime === 'image/png' || $mime === 'image/x-png') {
        imagealphablending($dst,false);
        imagesavealpha($dst,true);
    } elseif ($mime === 'image/gif') {
        $transparent_index = imagecolortransparent($src);
        if ($transparent_index >= 0) {
            $transparent_color = imagecolorsforindex($src, $transparent_index);
            $transparent_index_dst = imagecolorallocate($dst, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
            imagefill($dst,0,0,$transparent_index_dst);
            imagecolortransparent($dst,$transparent_index_dst);
        }
    }
    imagecopyresampled($dst,$src,0,0,0,0,$w,$h,$orig_w,$orig_h);
    header('Content-Type: ' . $mime);
    if ($mime === 'image/png' || $mime === 'image/x-png') imagepng($dst);
    elseif ($mime === 'image/gif') imagegif($dst);
    else imagejpeg($dst, NULL, 85);
    imagedestroy($src); imagedestroy($dst);
    exit;
}
header('HTTP/1.0 400 Bad Request');
echo 'Bad request';