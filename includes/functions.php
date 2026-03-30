<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── STRING / SECURITY HELPERS ───────────────────────────────────────────────

function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug(string $string): string
{
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

function generateCSRFToken(): string
{
    $token = uniqid('', true) . bin2hex(random_bytes(16));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function verifyCSRFToken(string $token): bool
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    unset($_SESSION['csrf_token']);
    return $valid;
}

// ─── NAVIGATION / FLASH ──────────────────────────────────────────────────────

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flashMessage(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ─── FORMATTING ──────────────────────────────────────────────────────────────

/**
 * Format a number in Indian numbering style (e.g. 1,50,000).
 */
function formatPrice(float $price): string
{
    $price   = (int) round($price);
    $last3   = $price % 1000;
    $rest    = (int) ($price / 1000);
    $result  = ($rest > 0) ? number_format($rest, 0, '.', ',') . ',' . str_pad((string)$last3, 3, '0', STR_PAD_LEFT)
                           : (string)$last3;
    return '₹' . $result;
}

function truncateText(string $text, int $length = 150): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '…';
}

// ─── EMAIL ───────────────────────────────────────────────────────────────────

function sendEmail(string $to, string $subject, string $body): bool
{
    if (!defined('SITE_NAME') || !defined('SITE_EMAIL')) {
        return false;
    }
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: ' . SITE_NAME . ' <' . SITE_EMAIL . '>' . "\r\n";
    $headers .= 'Reply-To: ' . SITE_EMAIL . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
    return mail($to, $subject, $body, $headers);
}

// ─── OTP ─────────────────────────────────────────────────────────────────────

function generateOTP(): string
{
    return str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ─── SESSION / AUTH HELPERS ──────────────────────────────────────────────────

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(defined('SITE_URL') ? SITE_URL . '/login.php' : '/login.php');
    }
}

// ─── CART ────────────────────────────────────────────────────────────────────

function _cartCondition(array &$params, array &$types): string
{
    if (!empty($_SESSION['user_id'])) {
        $params[] = (int)$_SESSION['user_id'];
        $types[]  = 'i';
        return 'user_id = ?';
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $params[] = session_id();
    $types[]  = 's';
    return 'session_id = ?';
}

function getCartCount(mysqli $conn): int
{
    $params = [];
    $types  = [];
    $cond   = _cartCondition($params, $types);
    $stmt   = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE {$cond}");
    $stmt->bind_param(implode('', $types), ...$params);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count;
}

function addToCart(mysqli $conn, int $product_id, int $quantity = 1): bool
{
    $userId    = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $sessionId = $userId ? null : session_id();

    // Check if item already exists in cart
    if ($userId) {
        $stmt = $conn->prepare(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?"
        );
        $stmt->bind_param('ii', $userId, $product_id);
    } else {
        $stmt = $conn->prepare(
            "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?"
        );
        $stmt->bind_param('si', $sessionId, $product_id);
    }
    $stmt->execute();
    $result   = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $newQty  = $existing['quantity'] + $quantity;
        $cartId  = $existing['id'];
        $upStmt  = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $upStmt->bind_param('ii', $newQty, $cartId);
        $ok = $upStmt->execute();
        $upStmt->close();
        return $ok;
    }

    $ins = $conn->prepare(
        "INSERT INTO cart (user_id, session_id, product_id, quantity, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())"
    );
    $ins->bind_param('isii', $userId, $sessionId, $product_id, $quantity);
    $ok = $ins->execute();
    $ins->close();
    return $ok;
}

function getCartItems(mysqli $conn): array
{
    $params = [];
    $types  = [];
    $cond   = _cartCondition($params, $types);
    $stmt   = $conn->prepare(
        "SELECT c.id AS cart_id, c.quantity,
                p.id AS product_id, p.name AS product_name,
                COALESCE(p.sale_price, p.price) AS price,
                pi.image_path AS product_image
         FROM cart c
         JOIN products p ON p.id = c.product_id
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
         WHERE c.{$cond}
         ORDER BY c.created_at ASC"
    );
    $stmt->bind_param(implode('', $types), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $items  = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $items;
}

function updateCartQuantity(mysqli $conn, int $cart_id, int $quantity): bool
{
    $params = [$quantity, $cart_id];
    $types  = ['ii'];
    $cond   = _cartCondition($params, $types);
    $stmt   = $conn->prepare(
        "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND {$cond}"
    );
    $stmt->bind_param(implode('', $types), ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function removeFromCart(mysqli $conn, int $cart_id): bool
{
    $params = [$cart_id];
    $types  = ['i'];
    $cond   = _cartCondition($params, $types);
    $stmt   = $conn->prepare("DELETE FROM cart WHERE id = ? AND {$cond}");
    $stmt->bind_param(implode('', $types), ...$params);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function getCartTotal(mysqli $conn): float
{
    $params = [];
    $types  = [];
    $cond   = _cartCondition($params, $types);
    $stmt   = $conn->prepare(
        "SELECT COALESCE(SUM(COALESCE(p.sale_price, p.price) * c.quantity), 0)
         FROM cart c
         JOIN products p ON p.id = c.product_id
         WHERE c.{$cond}"
    );
    $stmt->bind_param(implode('', $types), ...$params);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return (float)$total;
}

// ─── ORDER ───────────────────────────────────────────────────────────────────

function generateOrderNumber(): string
{
    return 'LM' . time() . rand(100, 999);
}

// ─── PRODUCT QUERIES ─────────────────────────────────────────────────────────

function getProductBySlug(mysqli $conn, string $slug): ?array
{
    $stmt = $conn->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.status = 'active'
         LIMIT 1"
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function getFeaturedProducts(mysqli $conn, int $limit = 4): array
{
    $stmt = $conn->prepare(
        "SELECT p.*, pi.image_path AS primary_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
         WHERE p.featured = 1 AND p.status = 'active'
         ORDER BY p.created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function getLatestProducts(mysqli $conn, int $limit = 8): array
{
    $stmt = $conn->prepare(
        "SELECT p.*, pi.image_path AS primary_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
         WHERE p.status = 'active'
         ORDER BY p.created_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ─── CATEGORY QUERIES ────────────────────────────────────────────────────────

function getCategoryBySlug(mysqli $conn, string $slug): ?array
{
    $stmt = $conn->prepare(
        "SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1"
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function getCategories(mysqli $conn): array
{
    $stmt = $conn->prepare(
        "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function getRelatedProducts(mysqli $conn, int $category_id, int $product_id, int $limit = 4): array
{
    $stmt = $conn->prepare(
        "SELECT p.*, pi.image_path AS primary_image
         FROM products p
         LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
         WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
         ORDER BY RAND()
         LIMIT ?"
    );
    $stmt->bind_param('iii', $category_id, $product_id, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ─── SLIDER ──────────────────────────────────────────────────────────────────

function getAllSliders(mysqli $conn): array
{
    $stmt = $conn->prepare(
        "SELECT * FROM slider WHERE status = 'active' ORDER BY sort_order ASC"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
