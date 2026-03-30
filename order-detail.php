<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) { redirect(SITE_URL . '/orders.php'); }

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { redirect(SITE_URL . '/orders.php'); }

$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'Order #' . $order['order_number'];
require_once 'includes/header.php';

// Status badge helper
$status_map = [
    'pending'    => 'warning',
    'processing' => 'primary',
    'shipped'    => 'info',
    'delivered'  => 'success',
    'cancelled'  => 'danger',
];
$s = strtolower($order['status'] ?? 'pending');
$order_badge = $status_map[$s] ?? 'secondary';

$pay_status = strtolower($order['payment_status'] ?? 'pending');
$pay_map = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info'];
$pay_badge = $pay_map[$pay_status] ?? 'secondary';
?>

<section class="py-4" style="background:#faf8f5;min-height:80vh;">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL); ?>" class="text-gold">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL . '/orders.php'); ?>" class="text-gold">Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order #<?php echo htmlspecialchars($order['order_number']); ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="font-family:'Playfair Display',serif;color:#2c2c2c;">
                Order #<?php echo htmlspecialchars($order['order_number']); ?>
            </h1>
            <span class="badge bg-<?php echo $order_badge; ?> fs-6"><?php echo htmlspecialchars(ucfirst($s)); ?></span>
        </div>

        <div class="row g-4">

            <!-- Left: Order Items -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead style="background:#f8f4ee;">
                                    <tr>
                                        <th class="ps-3 py-3">Product</th>
                                        <th class="py-3">Price</th>
                                        <th class="py-3">Qty</th>
                                        <th class="pe-3 py-3 text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No items found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if (!empty($item['product_image'])): ?>
                                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>"
                                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                                 class="rounded" style="width:56px;height:56px;object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                                                 style="width:56px;height:56px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                            <?php if (!empty($item['product_sku'])): ?>
                                                                <small class="text-muted">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo formatPrice($item['price']); ?></td>
                                                <td><?php echo (int)$item['quantity']; ?></td>
                                                <td class="pe-3 text-end fw-semibold">
                                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="<?php echo htmlspecialchars(SITE_URL . '/orders.php'); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                </div>
            </div>

            <!-- Right: Order Info + Shipping + Summary -->
            <div class="col-lg-4">

                <!-- Order Info -->
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="text-muted ps-0">Order #</td>
                                <td class="fw-semibold text-end"><?php echo htmlspecialchars($order['order_number']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Date</td>
                                <td class="text-end"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Payment</td>
                                <td class="text-end"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Payment Status</td>
                                <td class="text-end">
                                    <span class="badge bg-<?php echo $pay_badge; ?>">
                                        <?php echo htmlspecialchars(ucfirst($pay_status)); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Order Status</td>
                                <td class="text-end">
                                    <span class="badge bg-<?php echo $order_badge; ?>">
                                        <?php echo htmlspecialchars(ucfirst($s)); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card shadow-sm border-0 rounded-3 mb-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Shipping Address</h6>
                    </div>
                    <div class="card-body">
                        <address class="mb-0">
                            <strong><?php echo htmlspecialchars($order['shipping_name'] ?? $order['name'] ?? ''); ?></strong><br>
                            <?php if (!empty($order['shipping_phone'] ?? $order['phone'] ?? '')): ?>
                                <i class="fas fa-phone fa-xs me-1 text-muted"></i>
                                <?php echo htmlspecialchars($order['shipping_phone'] ?? $order['phone']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($order['shipping_address'] ?? $order['address'] ?? '')): ?>
                                <?php echo htmlspecialchars($order['shipping_address'] ?? $order['address']); ?><br>
                            <?php endif; ?>
                            <?php
                            $city_state = array_filter([
                                $order['shipping_city'] ?? $order['city'] ?? '',
                                $order['shipping_state'] ?? $order['state'] ?? '',
                                $order['shipping_pincode'] ?? $order['pincode'] ?? ''
                            ]);
                            if (!empty($city_state)) echo htmlspecialchars(implode(', ', $city_state));
                            ?>
                        </address>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header" style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $shipping_amount = $order['shipping_amount'] ?? $order['shipping_cost'] ?? 0;
                        $subtotal = ($order['total_amount'] ?? 0) - $shipping_amount;
                        ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span><?php echo $shipping_amount > 0 ? formatPrice($shipping_amount) : '<span class="text-success">Free</span>'; ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span style="color:#c9a84c;font-size:1.1rem;"><?php echo formatPrice($order['total_amount']); ?></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<style>
.text-gold { color: #c9a84c !important; }
.table tbody tr:hover { background: #fdf9f0; }
address { line-height: 1.7; }
</style>

<?php require_once 'includes/footer.php'; ?>
