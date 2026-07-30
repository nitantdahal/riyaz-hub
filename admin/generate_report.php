<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../includes/pdf_helpers.php';

$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
if (!$studentId) {
    setFlash('error', 'No student specified.');
    redirect('reports.php');
}

try {
    $db = getDB();
    $pdf = buildStudentReportPdf($db, $studentId);

    $stmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
    $stmt->execute([$studentId]);
    $name = $stmt->fetchColumn() ?: 'student';
    $slug = preg_replace('/[^a-z0-9]+/i', '_', $name);

    if (ob_get_length()) {
        ob_clean();
    }

    $pdf->Output('I', $slug . '_practice_report_' . date('Ymd') . '.pdf');
    exit;
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    setFlash('error', 'Could not generate the report: ' . $e->getMessage());
    redirect('reports.php');
}