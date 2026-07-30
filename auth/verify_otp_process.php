<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('../verify_otp.php');
}

if (empty($_SESSION['otp_pending_email'])) {
    setFlash('error', 'No pending registration found. Please sign up again.');
    redirect('../register.php');
}

$email = $_SESSION['otp_pending_email'];
$submittedOtp = trim($_POST['otp_full'] ?? '');

if ($submittedOtp === '' || strlen($submittedOtp) !== 6) {
    setFlash('error', 'Please enter the full 6-digit code.');
    redirect('../verify_otp.php');
}

$db = getDB();
$pending = getPendingRegistration($db, $email);

if (!$pending) {
    setFlash('error', 'Your verification code has expired or is invalid. Please sign up again.');
    unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);
    redirect('../register.php');
}

if (strtotime($pending['expires_at']) < time()) {
    deletePendingRegistration($db, $email);
    setFlash('error', 'That code expired. Please sign up again to receive a new one.');
    unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);
    redirect('../register.php');
}

if ((int) $pending['attempts'] >= OTP_MAX_ATTEMPTS) {
    deletePendingRegistration($db, $email);
    setFlash('error', 'Too many incorrect attempts. Please sign up again to receive a new code.');
    unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);
    redirect('../register.php');
}

if (!hash_equals($pending['otp_code'], $submittedOtp)) {
    incrementOtpAttempts($db, (int) $pending['id']);
    $remaining = OTP_MAX_ATTEMPTS - ((int) $pending['attempts'] + 1);
    setFlash('error', "That code wasn't right. $remaining attempt(s) remaining.");
    redirect('../verify_otp.php');
}

// --- OTP correct: create the real account now ---
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    // extremely rare race condition: someone else registered this email meanwhile
    deletePendingRegistration($db, $email);
    setFlash('error', 'An account with that email already exists. Try logging in instead.');
    unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);
    redirect('../index.php');
}

$colors = ['#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#f4694a'];
$avatarColor = $colors[array_rand($colors)];

$stmt = $db->prepare('INSERT INTO users (full_name, email, password, role, instrument_id, instructor_id, avatar_color)
                       VALUES (?, ?, ?, "student", ?, ?, ?)');
$stmt->execute([$pending['full_name'], $email, $pending['password_hash'], $pending['instrument_id'], $pending['instructor_id'], $avatarColor]);
$studentId = (int) $db->lastInsertId();

$db->prepare('INSERT INTO streaks (student_id, current_streak, longest_streak) VALUES (?, 0, 0)')
   ->execute([$studentId]);

deletePendingRegistration($db, $email);
unset($_SESSION['otp_pending_email'], $_SESSION['otp_dev_fallback']);

setFlash('success', 'Email verified and account created! Please log in.');
redirect('../index.php');
