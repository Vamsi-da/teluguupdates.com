<?php
// inc/functions.php

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Include configuration and database credentials
require_once __DIR__ . '/config.php'; // Make sure this defines DB_HOST, DB_USER, DB_PASS, DB_NAME
require_once __DIR__ . '/db.php';     // Optional: if you want separate db.php for credentials
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];


// Ensure database variables are defined
$servername = $servername ?? DB_HOST ?? 'localhost';
$username   = $username ?? DB_USER ?? 'root';
$password   = $password ?? DB_PASS ?? '';
$dbname     = $dbname ?? DB_NAME ?? 'test';

// Escape HTML
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Simple slugify
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

// Create PDO connection
try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Settings helper
function get_settings() {
    static $settings = null;
    global $pdo;
    if ($settings === null) {
        $settings = [
            'site_title' => defined('SITE_TITLE') ? SITE_TITLE : 'My Site',
            'site_tagline' => defined('SITE_TAGLINE') ? SITE_TAGLINE : '',
            'adsense_id' => ''
        ];
        try {
            $stmt = $pdo->query("SELECT `key`,`value` FROM settings");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Table may not exist, ignore
        }
    }
    return $settings;
}

function site_title() { $s = get_settings(); return $s['site_title']; }
function site_tagline() { $s = get_settings(); return $s['site_tagline']; }
function adsense_id() { $s = get_settings(); return $s['adsense_id']; }

// CSRF
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash messages
function flash_set($k, $v) { $_SESSION['flash'][$k] = $v; }

function flash_get($k) {
    if (!empty($_SESSION['flash'][$k])) {
        $v = $_SESSION['flash'][$k];
        unset($_SESSION['flash'][$k]);
        return $v;
    }
    return null;
}

// Pagination helper
function paginate($queryBase, $params = [], $page = 1, $perPage = ITEMS_PER_PAGE) {
    global $pdo;
    $page = max(1, (int)$page);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$queryBase}) x");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;
    $dataStmt = $pdo->prepare($queryBase . " LIMIT $perPage OFFSET $offset");
    $dataStmt->execute($params);
    return [$dataStmt->fetchAll(PDO::FETCH_ASSOC), $totalPages, $page];
}


function get_post_thumbnail($content_html) {
    if (empty($content_html)) return '';
    if (preg_match('/<img[^>]+src=["\']?([^"\'>\s]+)["\']?/i', $content_html, $m)) {
        $thumb = trim($m[1]);
        $thumb = preg_replace('~^(\.\./|\.\/)+~', '', $thumb);
        if (preg_match('~^/?uploads/~i', $thumb)) {
            return 'uploads/' . ltrim(preg_replace('~^/?uploads/~i', '', $thumb), '/');
        }
        return 'uploads/' . ltrim($thumb, '/');
    }
    return '';
}


?>