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

$razorpay_payment_id = sanitize($_POST['razorpay_payment_id'] ?? '');
$razorpay_order_id   = sanitize($_POST['razorpay_order_id']   ?? '');
$razorpay_signature  = $_POST['razorpay_signature'] ?? '';
$order_number        = sanitize($_POST['order_number']        ?? '');

if (!$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature || !$order_number) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Verify Razorpay signature
$generated_signature = hash_hmac(
    'sha256',
    $razorpay_order_id . '|' . $razorpay_payment_id,
    RAZORPAY_KEY_SECRET
);

if (hash_equals($generated_signature, $razorpay_signature)) {
    $stmt = $conn->prepare(
        "UPDATE orders
         SET payment_status = 'paid', order_status = 'processing',
             razorpay_order_id = ?, razorpay_payment_id = ?
         WHERE order_number = ?"
    );
    $stmt->bind_param('sss', $razorpay_order_id, $razorpay_payment_id, $order_number);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Payment verified successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
}
