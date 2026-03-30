<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

$order_number = sanitize($_GET['order_number'] ?? '');
if (!$order_number) { redirect(SITE_URL . '/'); }

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->bind_param('s', $order_number);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { redirect(SITE_URL . '/'); }

$page_title = 'Order Confirmed - #' . $order_number;
require_once 'includes/header.php';
?>

<section class="py-5" style="background:#faf8f5;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9">
                <div class="confirmation-card card shadow-lg border-0 rounded-3 text-center p-5">

                    <!-- Check icon -->
                    <div class="mb-4">
                        <i class="fas fa-check-circle" style="font-size:5rem;color:#28a745;"></i>
                    </div>

                    <h2 class="confirmation-title mb-2">Order Confirmed!</h2>
                    <div class="gold-divider mx-auto my-3"></div>
                    <p class="text-muted mb-4 fs-5">Thank you for your purchase. Your order has been received and is being processed.</p>

                    <!-- Order Number Box -->
                    <div class="order-number-box mx-auto mb-4">
                        <p class="text-muted small mb-1 text-uppercase letter-spacing-1">Order Number</p>
                        <p class="order-number mb-0">#<?php echo htmlspecialchars($order_number); ?></p>
                    </div>

                    <!-- Delivery Info -->
                    <div class="delivery-info d-flex align-items-center justify-content-center gap-2 mb-5">
                        <i class="fas fa-truck" style="color:#c9a84c;font-size:1.2rem;"></i>
                        <span class="text-muted">Expected delivery: <strong>5–7 business days</strong></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/shop.php'); ?>" class="btn btn-gold btn-lg px-4">
                            <i class="fas fa-store me-2"></i>Continue Shopping
                        </a>
                        <a href="<?php echo htmlspecialchars(SITE_URL . '/orders.php'); ?>" class="btn btn-outline-gold btn-lg px-4">
                            <i class="fas fa-list-alt me-2"></i>View Orders
                        </a>
                    </div>

                </div>

                <!-- Additional info -->
                <div class="text-center mt-4">
                    <p class="text-muted small">
                        <i class="fas fa-envelope me-1"></i>
                        A confirmation email has been sent to your registered email address.
                    </p>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
.confirmation-card { background: #fff; }
.confirmation-title { font-family: 'Playfair Display', serif; color: #c9a84c; font-size: 2.2rem; }
.gold-divider { width: 80px; height: 3px; background: linear-gradient(90deg, #c9a84c, #e8c96e, #c9a84c); border-radius: 2px; }
.order-number-box { background: linear-gradient(135deg, #fdf3dc, #fef9ee); border: 2px solid #e8c96e; border-radius: 10px; padding: 1rem 2rem; max-width: 340px; }
.order-number { font-family: 'Playfair Display', serif; font-size: 1.6rem; color: #2c2c2c; font-weight: 700; letter-spacing: 1px; }
.delivery-info { background: #f8f4ee; border-radius: 8px; padding: 0.75rem 1.5rem; }
.btn-gold { background: linear-gradient(135deg, #c9a84c, #e8c96e); color: #fff; border: none; font-weight: 600; letter-spacing: 0.5px; }
.btn-gold:hover { background: linear-gradient(135deg, #b8932f, #d4b44a); color: #fff; }
.btn-outline-gold { background: transparent; color: #c9a84c; border: 2px solid #c9a84c; font-weight: 600; }
.btn-outline-gold:hover { background: #c9a84c; color: #fff; }
.letter-spacing-1 { letter-spacing: 1px; }
</style>

<?php require_once 'includes/footer.php'; ?>
