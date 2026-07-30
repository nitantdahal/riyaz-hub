<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('instructor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('logs.php');
}

$db = getDB();
$instructorId = $_SESSION['user_id'];
$studentId = (int) ($_POST['student_id'] ?? 0);
$sessionId = !empty($_POST['session_id']) ? (int) $_POST['session_id'] : null;
$message = trim($_POST['message'] ?? '');
$rating = isset($_POST['rating']) && in_array((int) $_POST['rating'], [1,2,3,4,5], true) ? (int) $_POST['rating'] : null;

// verify this student is actually assigned to this instructor
$stmt = $db->prepare('SELECT id FROM users WHERE id = ? AND instructor_id = ?');
$stmt->execute([$studentId, $instructorId]);
if (!$stmt->fetch() || $message === '') {
    setFlash('error', 'Unable to submit feedback.');
    redirect('logs.php');
}

$db->prepare('INSERT INTO feedback (instructor_id, student_id, session_id, message, rating) VALUES (?, ?, ?, ?, ?)')
   ->execute([$instructorId, $studentId, $sessionId, $message, $rating]);

setFlash('success', 'Feedback sent to student.');
redirect($sessionId ? 'logs.php' : 'students.php');
