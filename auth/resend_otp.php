<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('../verify_otp.php');
}

if (empty($_SESSION['otp_pending_email'])) {
    setFlash('error', 'No pending registration found. Please sign up again.');
    redirect('../register.php');
}

$email = $_SESSION['otp_pending_email'];
$db = getDB();
$pending = getPendingRegistration($db, $email);

if (!$pending) {
    setFlash('error', 'Your registration session expired. Please sign up again.');
    unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);
    redirect('../register.php');
}

$otp = createPendingRegistration(
    $db,
    $email,
    $pending['full_name'],
    $pending['password_hash'],
    $pending['instrument_id'] ?: null,
    $pending['instructor_id'] ?: null
);

$result = sendOtpEmail($email, $pending['full_name'], $otp);
if (!$result['success']) {
    setFlash('error', $result['error'] ?? 'Could not resend the verification email.');
    redirect('../verify_otp.php');
}

$_SESSION['otp_dev_fallback'] = $result['dev_fallback'] ? $otp : null;
setFlash('info', "A new code has been sent to $email.");
redirect('../verify_otp.php');
