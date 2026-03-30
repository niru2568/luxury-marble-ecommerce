<?php
ob_start();
session_start();
require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/functions.php';

requireLogin();

$stmt = $conn->prepare(
    "SELECT o.*, COUNT(oi.id) as item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'My Orders';
require_once 'includes/header.php';
?>

<section class="py-4" style="background:#faf8f5;min-height:80vh;">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL); ?>" class="text-gold">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(SITE_URL . '/my-account.php'); ?>" class="text-gold">My Account</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Orders</li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 style="font-family:'Playfair Display',serif;color:#2c2c2c;">
                <i class="fas fa-shopping-bag me-2" style="color:#c9a84c;"></i>My Orders
            </h1>
            <span class="badge bg-secondary fs-6"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?></span>
        </div>

        <?php if (empty($orders)): ?>
            <!-- Empty state -->
            <div class="text-center py-5">
                <div class="empty-state-icon mx-auto mb-4">
                    <i class="fas fa-shopping-bag" style="font-size:4rem;color:#ddd;"></i>
                </div>
                <h3 style="color:#555;">No Orders Yet</h3>
                <p class="text-muted mb-4">You haven't placed any orders yet. Start exploring our luxury marble collection!</p>
                <a href="<?php echo htmlspecialchars(SITE_URL . '/shop.php'); ?>" class="btn btn-gold btn-lg">
                    <i class="fas fa-store me-2"></i>Shop Now
                </a>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead style="background:linear-gradient(135deg,#2c2c2c,#444);color:#e8c96e;">
                                <tr>
                                    <th class="ps-3 py-3">Order #</th>
                                    <th class="py-3">Date</th>
                                    <th class="py-3">Items</th>
                                    <th class="py-3">Total</th>
                                    <th class="py-3">Payment</th>
                                    <th class="py-3">Status</th>
                                    <th class="pe-3 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    $status_map = [
                                        'pending'    => 'warning',
                                        'processing' => 'primary',
                                        'shipped'    => 'info',
                                        'delivered'  => 'success',
                                        'cancelled'  => 'danger',
                                    ];
                                    $s = strtolower($order['status'] ?? 'pending');
                                    $badge = $status_map[$s] ?? 'secondary';

                                    $pay_status = strtolower($order['payment_status'] ?? 'pending');
                                    $pay_map = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info'];
                                    $pay_badge = $pay_map[$pay_status] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo (int)$order['item_count']; ?></td>
                                        <td class="fw-semibold"><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $pay_badge; ?>">
                                                <?php echo htmlspecialchars(ucfirst($pay_status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo htmlspecialchars(ucfirst($s)); ?>
                                            </span>
                                        </td>
                                        <td class="pe-3">
                                            <a href="<?php echo htmlspecialchars(SITE_URL . '/order-detail.php?id=' . (int)$order['id']); ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<style>
.btn-gold { background: linear-gradient(135deg, #c9a84c, #e8c96e); color: #fff; border: none; font-weight: 600; }
.btn-gold:hover { background: linear-gradient(135deg, #b8932f, #d4b44a); color: #fff; }
.text-gold { color: #c9a84c !important; }
.table thead th { border-bottom: none; }
.table tbody tr:hover { background: #fdf9f0; }
</style>

<?php require_once 'includes/footer.php'; ?>
