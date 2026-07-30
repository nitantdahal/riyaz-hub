<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();
$studentReport = $db->query('SELECT u.full_name, u.email, i.name AS instrument, ins.full_name AS instructor,
                              COUNT(ps.id) sessions, COALESCE(SUM(ps.duration_minutes),0) minutes,
                              (SELECT current_streak FROM streaks WHERE student_id = u.id) AS streak
                              FROM users u
                              LEFT JOIN instruments i ON i.id = u.instrument_id
                              LEFT JOIN users ins ON ins.id = u.instructor_id
                              LEFT JOIN practice_sessions ps ON ps.student_id = u.id
                              WHERE u.role = "student"
                              GROUP BY u.id ORDER BY minutes DESC')->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="practice_report_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Student Name', 'Email', 'Instrument', 'Instructor', 'Sessions', 'Total Minutes', 'Current Streak (days)']);
foreach ($studentReport as $r) {
    fputcsv($out, [$r['full_name'], $r['email'], $r['instrument'] ?? '—', $r['instructor'] ?? '—', $r['sessions'], $r['minutes'], $r['streak'] ?? 0]);
}
fclose($out);
exit;
