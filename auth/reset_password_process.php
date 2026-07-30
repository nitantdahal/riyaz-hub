<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('../reset_password.php');
}

if (empty($_SESSION['reset_pending_email'])) {
    setFlash('error', 'No password reset in progress. Please start again.');
    redirect('../forgot_password.php');
}

$email = $_SESSION['reset_pending_email'];
$submittedOtp = trim($_POST['otp_full'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($submittedOtp === '' || strlen($submittedOtp) !== 6) {
    setFlash('error', 'Please enter the full 6-digit code.');
    redirect('../reset_password.php');
}
if (strlen($newPassword) < 6) {
    setFlash('error', 'Your new password must be at least 6 characters.');
    redirect('../reset_password.php');
}
if ($newPassword !== $confirmPassword) {
    setFlash('error', 'Those passwords don\'t match. Please try again.');
    redirect('../reset_password.php');
}

$db = getDB();
$pending = getPasswordReset($db, $email);

if (!$pending) {
    setFlash('error', 'That code is invalid or has expired. Please request a new one.');
    unset($_SESSION['reset_pending_email'], $_SESSION['reset_dev_fallback']);
    redirect('../forgot_password.php');
}

if (strtotime($pending['expires_at']) < time()) {
    deletePasswordReset($db, $email);
    setFlash('error', 'That code expired. Please request a new one.');
    unset($_SESSION['reset_pending_email'], $_SESSION['reset_dev_fallback']);
    redirect('../forgot_password.php');
}

if ((int) $pending['attempts'] >= OTP_MAX_ATTEMPTS) {
    deletePasswordReset($db, $email);
    setFlash('error', 'Too many incorrect attempts. Please request a new code.');
    unset($_SESSION['reset_pending_email'], $_SESSION['reset_dev_fallback']);
    redirect('../forgot_password.php');
}

if (!hash_equals($pending['otp_code'], $submittedOtp)) {
    incrementResetAttempts($db, (int) $pending['id']);
    $remaining = OTP_MAX_ATTEMPTS - ((int) $pending['attempts'] + 1);
    setFlash('error', "That code wasn't right. $remaining attempt(s) remaining.");
    redirect('../reset_password.php');
}

$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // shouldn't normally happen since we only ever create a reset row for existing users
    deletePasswordReset($db, $email);
    setFlash('error', 'We could not find that account. Please try again.');
    unset($_SESSION['reset_pending_email'], $_SESSION['reset_dev_fallback']);
    redirect('../forgot_password.php');
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $user['id']]);

deletePasswordReset($db, $email);
unset($_SESSION['reset_pending_email'], $_SESSION['reset_dev_fallback']);

setFlash('success', 'Your password has been reset. Please log in with your new password.');
redirect('../index.php');
