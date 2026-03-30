<?php
require_once 'config/database.php';
require_once 'config/config.php';

header('Content-Type: application/xml; charset=utf-8');

// Get all active products
$prod_stmt = $conn->prepare("SELECT slug, updated_at FROM products WHERE status = 'active'");
$prod_stmt->execute();
$products = $prod_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$prod_stmt->close();

// Get all active categories
$cat_stmt = $conn->prepare("SELECT slug FROM categories WHERE status = 'active'");
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cat_stmt->close();

// Get all active blog posts
$blog_stmt = $conn->prepare("SELECT slug, updated_at FROM blog WHERE status = 'active'");
$blog_stmt->execute();
$blog_posts = $blog_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$blog_stmt->close();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <!-- Static Pages -->
  <url><loc><?php echo htmlspecialchars(SITE_URL); ?>/</loc><priority>1.0</priority></url>
  <url><loc><?php echo htmlspecialchars(SITE_URL); ?>/shop.php</loc><priority>0.9</priority></url>
  <url><loc><?php echo htmlspecialchars(SITE_URL); ?>/blog.php</loc><priority>0.8</priority></url>
  <url><loc><?php echo htmlspecialchars(SITE_URL); ?>/contact.php</loc><priority>0.7</priority></url>
  <!-- Categories -->
  <?php foreach ($categories as $cat): ?>
  <url>
    <loc><?php echo htmlspecialchars(SITE_URL . '/shop.php?category=' . $cat['slug']); ?></loc>
    <priority>0.8</priority>
  </url>
  <?php endforeach; ?>
  <!-- Products -->
  <?php foreach ($products as $product): ?>
  <url>
    <loc><?php echo htmlspecialchars(SITE_URL . '/product.php?slug=' . $product['slug']); ?></loc>
    <lastmod><?php echo date('Y-m-d', strtotime($product['updated_at'])); ?></lastmod>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>
  <!-- Blog Posts -->
  <?php foreach ($blog_posts as $blog): ?>
  <url>
    <loc><?php echo htmlspecialchars(SITE_URL . '/blog-detail.php?slug=' . $blog['slug']); ?></loc>
    <lastmod><?php echo date('Y-m-d', strtotime($blog['updated_at'])); ?></lastmod>
    <priority>0.6</priority>
  </url>
  <?php endforeach; ?>
</urlset>
