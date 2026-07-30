<?php
/**
 * Session / authentication / authorization helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'          => $_SESSION['user_id'],
        'full_name'   => $_SESSION['full_name'],
        'email'       => $_SESSION['email'],
        'role'        => $_SESSION['role'],
        'avatar_color'=> $_SESSION['avatar_color'] ?? '#7c3aed',
    ];
}

/** Redirect helper */
function redirect($path) {
    header('Location: ' . $path);
    exit;
}

/** Force login; otherwise send to landing/login page */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(basePath() . '/index.php');
    }
}

/** Restrict a page to a specific role (or array of roles) */
function requireRole($roles) {
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles, true)) {
        redirect(basePath() . '/index.php');
    }
}

/** Compute relative base path so links work regardless of subfolder depth */
function basePath() {
    return '..'; // pages live one level deep (admin/, instructor/, student/)
}

/** Simple CSRF token */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

/** Flash messages */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function h($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}
