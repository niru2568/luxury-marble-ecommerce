<?php
$flash = getFlashMessage();
?>
<footer class="site-footer">
  <div class="container">
    <div class="row py-5">
      <div class="col-lg-4 col-md-6 mb-4">
        <h5 class="footer-brand">Luxe <span class="gold-text">Marble</span></h5>
        <p class="footer-tagline">Crafting timeless elegance in marble since 1995. Premium quality, unmatched craftsmanship.</p>
        <div class="social-links mt-3">
          <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-link"><i class="fab fa-pinterest-p"></i></a>
          <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-6 mb-4">
        <h6 class="footer-heading">Quick Links</h6>
        <ul class="footer-links">
          <li><a href="<?php echo SITE_URL; ?>/">Home</a></li>
          <li><a href="<?php echo SITE_URL; ?>/shop.php">Shop</a></li>
          <li><a href="<?php echo SITE_URL; ?>/blog.php">Blog</a></li>
          <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
          <li><a href="<?php echo SITE_URL; ?>/my-account.php">My Account</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 class="footer-heading">Categories</h6>
        <ul class="footer-links">
          <li><a href="<?php echo SITE_URL; ?>/shop.php?category=marble-mandirs">Marble Mandirs</a></li>
          <li><a href="<?php echo SITE_URL; ?>/shop.php?category=marble-fireplaces">Marble Fireplaces</a></li>
          <li><a href="<?php echo SITE_URL; ?>/shop.php?category=wall-panels">Wall Panels</a></li>
          <li><a href="<?php echo SITE_URL; ?>/shop.php?category=marble-flooring">Marble Flooring</a></li>
          <li><a href="<?php echo SITE_URL; ?>/shop.php?category=decorative-pieces">Decorative Pieces</a></li>
        </ul>
      </div>
      <div class="col-lg-3 col-md-6 mb-4">
        <h6 class="footer-heading">Contact Info</h6>
        <div class="contact-info">
          <p><i class="fas fa-phone-alt gold-text me-2"></i><?php echo SITE_PHONE; ?></p>
          <p><i class="fas fa-envelope gold-text me-2"></i><?php echo SITE_EMAIL; ?></p>
          <p><i class="fas fa-map-marker-alt gold-text me-2"></i>123 Marble Street, Rajasthan, India 302001</p>
        </div>
      </div>
    </div>
    <hr class="footer-divider">
    <div class="row py-3">
      <div class="col-md-6">
        <p class="copyright mb-0">&copy; <?php echo date('Y'); ?> Luxe Marble. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-md-end">
        <a href="<?php echo SITE_URL; ?>/sitemap.php" class="footer-link-sm">Sitemap</a>
      </div>
    </div>
  </div>
</footer>

<!-- Bootstrap Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

<?php if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showToast(<?php echo json_encode($flash['message']); ?>, <?php echo json_encode($flash['type']); ?>);
});
</script>
<?php endif; ?>
</body>
</html>
