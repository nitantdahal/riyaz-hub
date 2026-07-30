<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('../forgot_password.php');
}

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect('../forgot_password.php');
}

$db = getDB();
$stmt = $db->prepare('SELECT id, full_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

// Always behave the same whether or not the account exists, so we don't leak
// which emails are registered. The OTP is only actually sent if it does.
if ($user) {
    $otp = createPasswordResetOtp($db, $email);
    $result = sendPasswordResetEmail($email, $user['full_name'], $otp);
    $_SESSION['reset_dev_fallback'] = (!empty($result['dev_fallback'])) ? $otp : null;
} else {
    $_SESSION['reset_dev_fallback'] = null;
}

$_SESSION['reset_pending_email'] = $email;
setFlash('info', "If an account exists for $email, a 6-digit reset code has been sent.");
redirect('../reset_password.php');
