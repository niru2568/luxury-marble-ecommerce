<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('generateOTP')) {
    require_once __DIR__ . '/functions.php';
}

// ─── LOGIN ───────────────────────────────────────────────────────────────────

function loginUser(mysqli $conn, string $email, string $password): array
{
    $stmt = $conn->prepare(
        "SELECT id, name, email, password, email_verified FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if (!(int)$user['email_verified']) {
        return ['success' => false, 'message' => 'Please verify your email address before logging in.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    return ['success' => true];
}

// ─── REGISTER ────────────────────────────────────────────────────────────────

function registerUser(mysqli $conn, array $data): array
{
    $name     = trim($data['name']     ?? '');
    $email    = trim($data['email']    ?? '');
    $phone    = trim($data['phone']    ?? '');
    $password = $data['password']      ?? '';

    if ($name === '') {
        return ['success' => false, 'message' => 'Name is required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid email address is required.'];
    }
    if ($phone === '') {
        return ['success' => false, 'message' => 'Phone number is required.'];
    }
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    // Check email uniqueness
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }
    $check->close();

    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $otp    = generateOTP();

    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, phone, password, otp, otp_expires_at, email_verified, created_at)
         VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, NOW())"
    );
    $stmt->bind_param('sssss', $name, $email, $phone, $hashed, $otp);

    if (!$stmt->execute()) {
        $stmt->close();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
    $stmt->close();

    sendOTPEmail($conn, $email);

    return ['success' => true];
}

// ─── OTP VERIFICATION ────────────────────────────────────────────────────────

function verifyOTP(mysqli $conn, string $email, string $otp): array
{
    $stmt = $conn->prepare(
        "SELECT id, otp, otp_expires_at FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'message' => 'Account not found.'];
    }

    if ($user['otp'] !== $otp) {
        return ['success' => false, 'message' => 'Invalid OTP.'];
    }

    if (strtotime($user['otp_expires_at']) < time()) {
        return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
    }

    $upd = $conn->prepare(
        "UPDATE users SET email_verified = 1, otp = NULL, otp_expires_at = NULL WHERE id = ?"
    );
    $upd->bind_param('i', $user['id']);
    $upd->execute();
    $upd->close();

    return ['success' => true];
}

// ─── SEND OTP EMAIL ──────────────────────────────────────────────────────────

function sendOTPEmail(mysqli $conn, string $email): array
{
    $otp = generateOTP();

    $upd = $conn->prepare(
        "UPDATE users SET otp = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?"
    );
    $upd->bind_param('ss', $otp, $email);

    if (!$upd->execute() || $upd->affected_rows === 0) {
        $upd->close();
        return ['success' => false, 'message' => 'Could not generate OTP.'];
    }
    $upd->close();

    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Luxe Marble';
    $subject  = 'Your ' . $siteName . ' Verification Code';
    $body     = '<p>Hello,</p>'
              . '<p>Your one-time verification code is:</p>'
              . '<h2 style="letter-spacing:6px;">' . $otp . '</h2>'
              . '<p>This code expires in <strong>10 minutes</strong>.</p>'
              . '<p>If you did not request this, please ignore this email.</p>'
              . '<p>Regards,<br>' . $siteName . ' Team</p>';

    sendEmail($email, $subject, $body);

    return ['success' => true];
}

// ─── PASSWORD RESET ──────────────────────────────────────────────────────────

function resetPassword(mysqli $conn, string $token, string $password): array
{
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];
    }

    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW() LIMIT 1"
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid or expired reset link.'];
    }

    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $upd = $conn->prepare(
        "UPDATE users SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?"
    );
    $upd->bind_param('si', $hashed, $user['id']);
    $upd->execute();
    $upd->close();

    return ['success' => true];
}

// ─── LOGOUT ──────────────────────────────────────────────────────────────────

function logoutUser(): void
{
    session_unset();
    session_destroy();
}
