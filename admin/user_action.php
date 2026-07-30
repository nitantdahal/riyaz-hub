<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('manage_users.php');
}

$db = getDB();
$action = $_POST['action'] ?? '';
$colors = ['#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#06b6d4', '#f4694a', '#7c3aed'];

if ($action === 'create') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','instructor','student'], true) ? $_POST['role'] : 'student';
    $instrumentId = !empty($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : null;
    $instructorId = ($role === 'student' && !empty($_POST['instructor_id'])) ? (int) $_POST['instructor_id'] : null;

    if ($fullName === '' || $email === '' || strlen($password) < 6 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please fill all required fields with a valid email and 6+ character password.');
        redirect('manage_users.php');
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'A user with that email already exists.');
        redirect('manage_users.php');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (full_name, email, password, role, instrument_id, instructor_id, avatar_color) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([$fullName, $email, $hash, $role, $instrumentId, $instructorId, $colors[array_rand($colors)]]);

    if ($role === 'student') {
        $newId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO streaks (student_id, current_streak, longest_streak) VALUES (?,0,0)')->execute([$newId]);
    }

    setFlash('success', 'User created successfully.');
    redirect('manage_users.php');
}

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['admin','instructor','student'], true) ? $_POST['role'] : 'student';
    $instrumentId = !empty($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : null;
    $instructorId = !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null;
    $status = in_array($_POST['status'] ?? '', ['active','inactive'], true) ? $_POST['status'] : 'active';

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid name and email.');
        redirect('manage_users.php');
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        setFlash('error', 'Another user already uses that email.');
        redirect('manage_users.php');
    }

    if ($password !== '' && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET full_name=?, email=?, password=?, role=?, instrument_id=?, instructor_id=?, status=? WHERE id=?');
        $stmt->execute([$fullName, $email, $hash, $role, $instrumentId, $instructorId, $status, $id]);
    } else {
        $stmt = $db->prepare('UPDATE users SET full_name=?, email=?, role=?, instrument_id=?, instructor_id=?, status=? WHERE id=?');
        $stmt->execute([$fullName, $email, $role, $instrumentId, $instructorId, $status, $id]);
    }

    // ensure a streaks row exists for students
    if ($role === 'student') {
        $check = $db->prepare('SELECT id FROM streaks WHERE student_id = ?');
        $check->execute([$id]);
        if (!$check->fetch()) {
            $db->prepare('INSERT INTO streaks (student_id, current_streak, longest_streak) VALUES (?,0,0)')->execute([$id]);
        }
    }

    setFlash('success', 'User updated successfully.');
    redirect('manage_users.php');
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id === (int) $_SESSION['user_id']) {
        setFlash('error', 'You cannot delete your own account while logged in.');
        redirect('manage_users.php');
    }
    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    setFlash('success', 'User deleted.');
    redirect('manage_users.php');
}

redirect('manage_users.php');
