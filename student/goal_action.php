<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('goals.php');
}

$db = getDB();
$studentId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $title   = trim($_POST['title'] ?? '');
    $target  = max(1, (int) ($_POST['target_minutes'] ?? 0));
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($title === '') {
        setFlash('error', 'Please enter a goal title.');
        redirect('goals.php');
    }

    $db->prepare('INSERT INTO goals (student_id, title, target_minutes, deadline) VALUES (?, ?, ?, ?)')
       ->execute([$studentId, $title, $target, $deadline]);
    setFlash('success', 'New goal created. Go get it! 🎯');
}

if ($action === 'complete') {
    $id = (int) ($_POST['id'] ?? 0);
    $db->prepare('UPDATE goals SET status = "completed" WHERE id = ? AND student_id = ?')->execute([$id, $studentId]);
    setFlash('success', 'Goal marked as completed. Great work!');
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $db->prepare('DELETE FROM goals WHERE id = ? AND student_id = ?')->execute([$id, $studentId]);
    setFlash('success', 'Goal deleted.');
}

redirect('goals.php');
