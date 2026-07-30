<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../includes/pdf_helpers.php';

$yearMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
    $yearMonth = date('Y-m');
}

try {
    $db = getDB();
    $pdf = buildMonthlyReportPdf($db, $yearMonth);

    if (ob_get_length()) {
        ob_clean();
    }

    $pdf->Output('I', 'monthly_report_' . $yearMonth . '.pdf');
    exit;
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }
    setFlash('error', 'Could not generate the monthly report: ' . $e->getMessage());
    redirect('reports.php');
}