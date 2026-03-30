<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF — accept from POST body or X-CSRF-Token header
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = sanitize($_POST['action'] ?? '');

switch ($action) {

    case 'add':
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$product_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            exit;
        }

        // Verify product exists and is active
        $check = $conn->prepare("SELECT id, stock FROM products WHERE id = ? AND status = 'active'");
        $check->bind_param('i', $product_id);
        $check->execute();
        $prod = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$prod) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        if ((int)$prod['stock'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit;
        }

        $result     = addToCart($conn, $product_id, $quantity);
        $cart_count = getCartCount($conn);
        echo json_encode([
            'success'    => $result,
            'message'    => $result ? 'Added to cart!' : 'Failed to add',
            'cart_count' => $cart_count,
        ]);
        break;

    case 'remove':
        $cart_id = (int)($_POST['cart_id'] ?? 0);
        if (!$cart_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        $result     = removeFromCart($conn, $cart_id);
        $cart_count = getCartCount($conn);
        echo json_encode([
            'success'    => $result,
            'message'    => $result ? 'Removed from cart' : 'Failed',
            'cart_count' => $cart_count,
        ]);
        break;

    case 'update':
        $cart_id  = (int)($_POST['cart_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        if (!$cart_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }
        $result     = updateCartQuantity($conn, $cart_id, $quantity);
        $cart_count = getCartCount($conn);

        // Get updated item total
        $item_stmt = $conn->prepare(
            "SELECT c.quantity, COALESCE(p.sale_price, p.price) AS price
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.id = ?"
        );
        $item_stmt->bind_param('i', $cart_id);
        $item_stmt->execute();
        $item       = $item_stmt->get_result()->fetch_assoc();
        $item_stmt->close();
        $item_total = $item ? formatPrice($item['price'] * $item['quantity']) : '₹0';

        $cart_total = getCartTotal($conn);
        echo json_encode([
            'success'    => $result,
            'cart_count' => $cart_count,
            'item_total' => $item_total,
            'cart_total' => formatPrice($cart_total),
        ]);
        break;

    case 'get':
        $items    = getCartItems($conn);
        $subtotal = getCartTotal($conn);
        $shipping = $subtotal >= 5000 ? 0 : 500;
        $total    = $subtotal + $shipping;
        echo json_encode([
            'success'  => true,
            'items'    => $items,
            'subtotal' => formatPrice($subtotal),
            'shipping' => formatPrice($shipping),
            'total'    => formatPrice($total),
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
