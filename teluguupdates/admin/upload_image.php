<?php
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

header('Content-Type: application/json');

$uploadDir = __DIR__ . '/../uploads/';
$baseUrl = 'uploads/';

// ✅ Create upload folder if missing
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ✅ Handle upload
if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        echo json_encode(['url' => $baseUrl . $filename]);
        exit;
    } else {
        echo json_encode(['error' => 'Failed to move uploaded file']);
        exit;
    }
}