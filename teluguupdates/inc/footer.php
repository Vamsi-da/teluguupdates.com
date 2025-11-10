<?php
require_once __DIR__ . '/inc/functions.php'; // auto-inserted
// --- Auto-init to prevent undefined variable warnings ---
$cats = $cats ?? [];
$posts = $posts ?? [];
$trend = $trend ?? [];
$cstmt = $cstmt ?? [];
$all_categories = $all_categories ?? [];

// inc/footer.php
?>
    </section>
  </main>
  <footer style="padding:18px;text-align:center;color:#555">&copy; <?php echo date('Y') . ' ' . h(site_title()); ?></footer>
</body>
</html>