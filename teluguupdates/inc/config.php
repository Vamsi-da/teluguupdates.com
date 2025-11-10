<?php
// inc/config.php - DB credentials & site defaults. Update DB_* values.
define('DB_HOST', 'localhost');
define('DB_NAME', 'teluguupdates');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/teluguupdates.com');

// App secret (use a long random string on production)
define('APP_SECRET', 'replace_with_a_long_random_string');

// Default site settings (these will be overwritten by DB settings if present)
define('SITE_TITLE', 'Telugu Updates');
define('SITE_TAGLINE', 'తెలుగువారికి ప్రభుత్వ పథకాల, ఉద్యోగాల & విద్యా సమాచారం ఒకే చోట');

// Useful constants
define('ITEMS_PER_PAGE', 10);
//$conn = new mysqli($host, $user, $pass, $dbname);

//if ($conn->connect_error) {
//    die("Database Connection Failed: " . $conn->connect_error);
//}
// ✅ Add this line to make helper functions and DB available globally
require_once __DIR__ . '/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

?>