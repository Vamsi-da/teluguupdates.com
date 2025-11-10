<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

admin_require_login();




global $pdo;



// ✅ Load all categories from DB
try {
    $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC");
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_categories = []; // fallback if query fails
}



// ✅ Extract first <img src="..."> from HTML and normalize path
function extract_first_image_url($html) {
    if (empty($html)) return '';
    if (preg_match('/<img[^>]+src=["\']?([^"\'>\s]+)["\']?/i', $html, $m)) {
        $src = trim($m[1]);
        // remove ../ or ./ at beginning
        $src = preg_replace('~^(\.\./|\.\/)+~', '', $src);
        // if it already starts with uploads/ or /uploads/
        if (preg_match('~^/?uploads/~i', $src)) {
            return 'uploads/' . ltrim(preg_replace('~^/?uploads/~i', '', $src), '/');
        }
        // if it's a full URL (TinyMCE sometimes uses absolute URLs)
        if (preg_match('~^https?://~i', $src)) {
            $filename = basename(parse_url($src, PHP_URL_PATH));
            return 'uploads/' . $filename;
        }
        // else assume filename only
        return 'uploads/' . ltrim($src, '/');
    }
    return '';
}


$id = $_GET['id'] ?? null;
$post = [
    'title' => '',
    'slug' => '',
    'content_html' => '',
    'published' => 0,
    'labels' => '',
    'location' => '',
	'category_id' => '',
    'meta_description' => '',
    'meta_keywords' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $fetched_post = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fetched_post) {
        $post = array_merge($post, $fetched_post);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_image_id = null;
    if (!empty($_FILES['upload_image']) && $_FILES['upload_image']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['upload_image'];
        $raw = file_get_contents($f['tmp_name']);
        $mime = mime_content_type($f['tmp_name']);
        $stmtImg = $pdo->prepare('INSERT INTO images (filename,mime,data,alt,description) VALUES (?,?,?,?,?)');
        $stmtImg->execute([$f['name'],$mime,$raw, trim($_POST['image_alt'] ?? ''), trim($_POST['image_desc'] ?? '')]);
        $last_image_id = $pdo->lastInsertId();
    }

    $title = trim($_POST['title'] ?? '');
$slug = trim($_POST['slug'] ?? '') ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
$content_html = $_POST['content_html'] ?? '';
$published = isset($_POST['published']) ? 1 : 0;
$labels = trim($_POST['labels'] ?? '');
$location = trim($_POST['location'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);
$meta_description = trim($_POST['meta_description'] ?? '');
$meta_keywords = trim($_POST['meta_keywords'] ?? '');
// ✅ Extract first image and save as thumbnail path
$thumb = extract_first_image_url($content_html);

if ($id) {
    $stmt = $pdo->prepare("UPDATE posts SET title=?, slug=?, content_html=?, published=?, labels=?, location=?, category_id=?, meta_description=?, meta_keywords=?, thumb=? WHERE id=?");
        $stmt->execute([$title, $slug, $content_html, $published, $labels, $location, $category_id, $meta_description, $meta_keywords, $thumb, $id]);
    } else {
        // 🧩 Insert new post
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content_html, published, labels, location, category_id, meta_description, meta_keywords, thumb, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $slug, $content_html, $published, $labels, $location, $category_id, $meta_description, $meta_keywords, $thumb]);
        $id = $pdo->lastInsertId();

        // ✅ Update category count
        if ($category_id) {
            $pdo->prepare("UPDATE categories SET post_count = post_count + 1 WHERE id = ?")->execute([$category_id]);
        }
    }
    
// Fetch all categories
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert labels into an array for checked boxes
$selected_labels = array_map('trim', explode(',', $post['labels']));


    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo $id ? "Edit Post" : "New Post"; ?></title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background:#f8f9fa; margin:20px; }
    .card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); max-width:900px; margin:auto; }
    label { display:block; margin:10px 0 5px; font-weight:bold; }
    input[type=text] { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; }
    textarea { width:100%; height:400px; }
    .btn { background:#007bff; color:white; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; }
    .btn:hover { background:#0056b3; }
    .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
  </style>
  <script src="https://cdn.tiny.cloud/1/ksvirophjd9nxhhgvd9gsfffhwxlwo2tiuue2qv7ecx9bw8e/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
 </head>
<body>
<div class="card">
  <div class="top-bar">
    <h2><?php echo $id ? "✏️ Edit Post" : "➕ New Post"; ?></h2>
    <a href="dashboard.php" class="btn" style="background:#6c757d;">← Back</a>
  </div>

  <form method="post" enctype="multipart/form-data">
    <label>Title</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

    <label>Slug</label>
    <input type="text" name="slug" value="<?php echo htmlspecialchars($post['slug']); ?>">

  <label><strong>Categories:</strong></label><br>
  <select name="category_id" class="form-control" required>
  <option value="">-- Select Category --</option>
  <?php if (!empty($all_categories)): ?>
      <?php foreach ($all_categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat['id']) ?>">
              <?= htmlspecialchars($cat['name']) ?>
          </option>
      <?php endforeach; ?>
  <?php else: ?>
      <option disabled>No categories found</option>
  <?php endif; ?>
</select>

    <label>Content</label>
    <textarea id="content_html" name="content_html"><?php echo htmlspecialchars($post['content_html']); ?></textarea>

    <label><input type="checkbox" name="published" <?php echo $post['published'] ? 'checked' : ''; ?>> Published</label>
	
	
	
<label>Labels (comma separated)</label>
<input type="text" name="labels" value="<?php echo htmlspecialchars($post['labels']); ?>">

<label>Location</label>
<input type="text" name="location" value="<?php echo htmlspecialchars($post['location']); ?>">

<label>Meta Description</label>
    <input type="text" name="meta_description" value="<?php echo htmlspecialchars($post['meta_description']); ?>">

    <label>Meta Keywords</label>
    <input type="text" name="meta_keywords" value="<?php echo htmlspecialchars($post['meta_keywords']); ?>">




    <br><button type="submit" class="btn">💾 Save Post</button>
  </form>
</div>

<script>
tinymce.init({
  selector: '#content_html',
  height: 500,
  plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount',
  toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | fullscreen',
  automatic_uploads: true,
  images_upload_url: 'upload_image.php',
  file_picker_types: 'image',

  // ✅ Working upload handler (Promise based)
  images_upload_handler: function (blobInfo) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'upload_image.php');
      xhr.onload = function() {
        if (xhr.status !== 200) {
          reject('HTTP Error: ' + xhr.status);
          return;
        }

        try {
          var json = JSON.parse(xhr.responseText);
          if (json && json.url) {
            resolve(json.url);
          } else {
            reject('Invalid JSON: ' + xhr.responseText);
          }
        } catch (e) {
          reject('Parse error: ' + e.message);
        }
      };

      xhr.onerror = function() {
        reject('Image upload failed due to a network error.');
      };

      var formData = new FormData();
      formData.append('file', blobInfo.blob(), blobInfo.filename());
      xhr.send(formData);
    });
  }
});
</script>

</body>
</html>
	