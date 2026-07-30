<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('../reset_password.php');
}

if (empty($_SESSION['reset_pending_email'])) {
    setFlash('error', 'No password reset in progress. Please start again.');
    redirect('../forgot_password.php');
}

$email = $_SESSION['reset_pending_email'];
$db = getDB();

$stmt = $db->prepare('SELECT id, full_name FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $otp = createPasswordResetOtp($db, $email);
    $result = sendPasswordResetEmail($email, $user['full_name'], $otp);
    $_SESSION['reset_dev_fallback'] = (!empty($result['dev_fallback'])) ? $otp : null;
} else {
    $_SESSION['reset_dev_fallback'] = null;
}

setFlash('info', "If an account exists for $email, a new code has been sent.");
redirect('../reset_password.php');
