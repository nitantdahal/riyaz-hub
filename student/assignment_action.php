<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('feedback.php');
}

$db = getDB();
$id = (int) ($_POST['id'] ?? 0);
$studentId = $_SESSION['user_id'];

// Student submits assignment for instructor review (changed 'assigned' to 'pending')
$db->prepare('
    UPDATE assignments
    SET
        status = "submitted",
        completed_at = NOW()
    WHERE id = ?
    AND student_id = ?
    AND status IN ("pending", "rejected")
')->execute([
    $id,
    $studentId
]);

setFlash('success', 'Assignment submitted for instructor review.');
redirect('feedback.php');