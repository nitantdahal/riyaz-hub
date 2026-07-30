<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Your session expired. Please try again.');
    redirect('../register.php');
}

$fullName     = trim($_POST['full_name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$password     = (string) ($_POST['password'] ?? '');
$instrumentId = $_POST['instrument_id'] !== '' ? (int) $_POST['instrument_id'] : null;
$instructorId = !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null;

if ($fullName === '' || $email === '' || strlen($password) < 6 || !$instrumentId) {
    setFlash('error', 'Please fill in all required fields (password must be at least 6 characters).');
    redirect('../register.php');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect('../register.php');
}

$db = getDB();

$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    setFlash('error', 'An account with that email already exists. Try logging in instead.');
    redirect('../register.php');
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$otp  = createPendingRegistration($db, $email, $fullName, $hash, $instrumentId, $instructorId);

$result = sendOtpEmail($email, $fullName, $otp);
if (!$result['success']) {
    setFlash('error', $result['error'] ?? 'Could not send the verification email. Please try again.');
    redirect('../register.php');
}

$_SESSION['otp_pending_email'] = $email;
$_SESSION['otp_dev_fallback'] = $result['dev_fallback'] ? $otp : null;

setFlash('info', "We've sent a 6-digit verification code to $email. Enter it below to finish creating your account.");
redirect('../verify_otp.php');
