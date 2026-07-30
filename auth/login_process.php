<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Your session expired. Please try again.');
    redirect('../index.php');
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    setFlash('error', 'Please enter both email and password.');
    redirect('../index.php');
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Invalid email or password. Please try again.');
    redirect('../index.php');
}

if ($user['status'] === 'inactive') {
    setFlash('error', 'This account has been deactivated. Contact an administrator.');
    redirect('../index.php');
}

session_regenerate_id(true);
$_SESSION['user_id']      = $user['id'];
$_SESSION['full_name']    = $user['full_name'];
$_SESSION['email']        = $user['email'];
$_SESSION['role']         = $user['role'];
$_SESSION['avatar_color'] = $user['avatar_color'];

redirect('../' . $user['role'] . '/dashboard.php');
