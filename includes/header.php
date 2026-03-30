<?php
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/config.php';
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/functions.php';
$cart_count = getCartCount($conn);
$page_title = isset($page_title) ? htmlspecialchars($page_title) . ' | ' . SITE_NAME : SITE_NAME;
$meta_description = isset($meta_description) ? htmlspecialchars($meta_description) : 'Premium luxury marble products - Mandirs, Fireplaces, Wall Panels, Flooring and Decorative pieces';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description; ?>">
    <title><?php echo $page_title; ?></title>
    <!-- CSRF meta tag for JS -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light sticky-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand" href="<?php echo SITE_URL; ?>/">
      <span class="brand-logo">Luxe <span class="gold-text">Marble</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/shop.php">Shop</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/blog.php">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
      </ul>
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-2">
          <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
            <i class="fas fa-shopping-cart fa-lg"></i>
            <span class="cart-badge" id="cartCount"><?php echo $cart_count; ?></span>
          </a>
        </li>
        <li class="nav-item">
          <?php if (isLoggedIn()): ?>
            <a class="nav-link" href="<?php echo SITE_URL; ?>/my-account.php"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
          <?php else: ?>
            <a class="btn btn-gold btn-sm" href="<?php echo SITE_URL; ?>/login.php">Login</a>
          <?php endif; ?>
        </li>
      </ul>
    </div>
  </div>
</nav>
