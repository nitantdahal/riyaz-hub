<?php
/**
 * PDF report generation for admins — per-student detail reports and
 * platform-wide monthly cumulative reports. Built on the bundled FPDF
 * library (pure PHP, no external dependencies).
 */

require_once __DIR__ . '/FPDF/fpdf.php';
require_once __DIR__ . '/leaderboard.php';

class TrackerReportPDF extends FPDF {
    public string $reportTitle = 'Smart Music Practice Tracking System';
    public string $reportSubtitle = '';

    /** FPDF's core fonts expect Windows-1252, not UTF-8 — transcode every string that flows through */
    private function enc($txt) {
        $txt = (string) $txt;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $txt);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8');
            if ($converted !== false) {
                return $converted;
            }
        }
        // last-resort fallback: strip anything outside printable ASCII
        return preg_replace('/[^\x20-\x7E]/', '', $txt);
    }

    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        parent::Cell($w, $h, $this->enc($txt), $border, $ln, $align, $fill, $link);
    }

    function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        parent::MultiCell($w, $h, $this->enc($txt), $border, $align, $fill);
    }

    function GetStringWidth($s) {
        return parent::GetStringWidth($this->enc($s));
    }

    function Header() {
        $this->SetFillColor(217, 130, 10); // gold
        $this->Rect(0, 0, 210, 22, 'F');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 15);
        $this->SetXY(12, 6);
        $this->Cell(0, 8, $this->reportTitle, 0, 1);
        $this->SetFont('Helvetica', '', 10);
        $this->SetXY(12, 14);
        $this->Cell(0, 6, $this->reportSubtitle, 0, 1);
        $this->SetY(28);
        $this->SetTextColor(30, 25, 50);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(140, 133, 166);
        $this->Cell(0, 10, 'Generated ' . date('M j, Y \a\t g:i A') . '  |  Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($text) {
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(217, 130, 10);
        $this->Cell(0, 8, $text, 0, 1);
        $this->SetDrawColor(230, 224, 245);
        $this->Line($this->GetX(), $this->GetY(), 198, $this->GetY());
        $this->Ln(3);
        $this->SetTextColor(30, 25, 50);
        $this->SetFont('Helvetica', '', 10);
    }

    function KeyValueRow($label, $value) {
        $this->SetFont('Helvetica', 'B', 9.5);
        $this->SetTextColor(90, 84, 120);
        $this->Cell(45, 6, $label, 0, 0);
        $this->SetFont('Helvetica', '', 9.5);
        $this->SetTextColor(30, 25, 50);
        $this->Cell(0, 6, (string) $value, 0, 1);
    }

    function TableHeader(array $cols) {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetFillColor(244, 241, 251);
        $this->SetTextColor(90, 84, 120);
        foreach ($cols as $label => $width) {

    $this->Cell(
        $width,
        7,
        $label,
        0,
        0,
        'L',
        true
    );

}
        $this->Ln();
        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(30, 25, 50);
    }

    /** Draws one table row, wrapping the LAST column as multi-line text if needed */
    function TableRow(array $cols, array $values, $lineHeight = 5.5) {

    $x = $this->GetX();
    $y = $this->GetY();

    $keys = array_keys($cols);
    $lastKey = end($keys);

    // Safe last column value
    $wrapText = isset($values[$lastKey]) ? $values[$lastKey] : '';

    $wrapWidth = $cols[$lastKey];

    $lines = max(
        1,
        ceil(
            $this->GetStringWidth($wrapText) /
            max(1, $wrapWidth - 2)
        )
    );

    $rowHeight = max($lineHeight, $lines * $lineHeight);


    // Page break
    if ($y + $rowHeight > 275) {

        $this->AddPage();

        $x = $this->GetX();
        $y = $this->GetY();

    }


    foreach ($cols as $key => $width) {


        $value = '';

        // support numeric indexed arrays also
        if (isset($values[$key])) {

            $value = $values[$key];

        }


        $this->SetXY($x, $y);


        if ($key === $lastKey) {


            $this->MultiCell(
                $width,
                $lineHeight,
                $value,
                0,
                'L'
            );


        } else {


            $this->Cell(
                $width,
                $rowHeight,
                $value,
                0,
                0,
                'L'
            );


        }


        $x += $width;

    }


    $this->SetXY(
        $this->lMargin,
        $y + $rowHeight
    );


    $this->SetDrawColor(240,237,249);

    $this->Line(
        $this->lMargin,
        $this->GetY(),
        198,
        $this->GetY()
    );
}
}

/**
 * Build & stream a comprehensive per-student PDF report.
 * Includes profile, summary stats, sessions, goals, feedback, assignments, achievements.
 */
function buildStudentReportPdf(PDO $db, int $studentId): TrackerReportPDF {
    $stmt = $db->prepare('SELECT u.*, i.name AS instrument_name, ins.full_name AS instructor_name
                           FROM users u
                           LEFT JOIN instruments i ON i.id = u.instrument_id
                           LEFT JOIN users ins ON ins.id = u.instructor_id
                           WHERE u.id = ? AND u.role = "student"');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    if (!$student) {
        throw new RuntimeException('Student not found.');
    }

    $stmt = $db->prepare('SELECT COALESCE(SUM(duration_minutes),0) m, COUNT(*) c FROM practice_sessions WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $totals = $stmt->fetch();

    $stmt = $db->prepare('SELECT current_streak, longest_streak FROM streaks WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $streak = $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0];

    $stmt = $db->prepare('SELECT COUNT(*) c FROM achievements WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $achievementCount = (int) $stmt->fetch()['c'];

    $stats = computeStudentLeaderboardStats($db, $studentId, (int) ($student['instructor_id'] ?? 0));

    $stmt = $db->prepare('SELECT ps.*, i.name AS instrument_name FROM practice_sessions ps
                           LEFT JOIN instruments i ON i.id = ps.instrument_id
                           WHERE ps.student_id = ? ORDER BY ps.session_date DESC LIMIT 40');
    $stmt->execute([$studentId]);
    $sessions = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM goals WHERE student_id = ? ORDER BY created_at DESC');
    $stmt->execute([$studentId]);
    $goals = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT f.*, u.full_name AS instructor_name FROM feedback f
                           JOIN users u ON u.id = f.instructor_id
                           WHERE f.student_id = ? ORDER BY f.created_at DESC LIMIT 30');
    $stmt->execute([$studentId]);
    $feedback = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM assignments WHERE student_id = ? ORDER BY created_at DESC');
    $stmt->execute([$studentId]);
    $assignments = $stmt->fetchAll();

    $stmt = $db->prepare('SELECT * FROM achievements WHERE student_id = ? ORDER BY earned_at DESC');
    $stmt->execute([$studentId]);
    $achievements = $stmt->fetchAll();

    $pdf = new TrackerReportPDF();
    $pdf->reportTitle = 'Student Practice Report';
    $pdf->reportSubtitle = $student['full_name'] . '  ·  Generated ' . date('M j, Y');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);

    $pdf->SectionTitle('Student Profile');
    $pdf->KeyValueRow('Name:', $student['full_name']);
    $pdf->KeyValueRow('Email:', $student['email']);
    $pdf->KeyValueRow('Instrument:', $student['instrument_name'] ?? 'Not set');
    $pdf->KeyValueRow('Instructor:', $student['instructor_name'] ?? 'Unassigned');
    $pdf->KeyValueRow('Account status:', ucfirst($student['status']));
    $pdf->KeyValueRow('Joined:', date('M j, Y', strtotime($student['created_at'])));

    $pdf->SectionTitle('Summary Statistics');
    $pdf->KeyValueRow('Total sessions logged:', (int) $totals['c']);
    $pdf->KeyValueRow('Total minutes practiced:', (int) $totals['m'] . ' minutes (' . round($totals['m'] / 60, 1) . ' hours)');
    $pdf->KeyValueRow('Current streak:', (int) $streak['current_streak'] . ' days');
    $pdf->KeyValueRow('Longest streak:', (int) $streak['longest_streak'] . ' days');
    $pdf->KeyValueRow('Achievements earned:', $achievementCount);
    $pdf->KeyValueRow('Average instructor rating:', $stats['avg_rating'] !== null ? $stats['avg_rating'] . ' / 5' : 'No ratings yet');
    $pdf->KeyValueRow('On-time assignment rate:', $stats['on_time_pct'] !== null ? $stats['on_time_pct'] . '%' : 'No graded assignments');
    $pdf->KeyValueRow('Leaderboard score:', $stats['score'] . ' points');

    $pdf->SectionTitle('Practice Sessions (' . count($sessions) . ' most recent)');
    if ($sessions) {
        $cols = ['Date' => 22, 'Instrument' => 26, 'Duration' => 20, 'Mood' => 18, 'Focus / Notes' => 112];
        $pdf->TableHeader($cols);
        foreach ($sessions as $s) {
            $note = trim(($s['focus_area'] ? $s['focus_area'] . ' — ' : '') . ($s['notes'] ?: ''));
            $pdf->TableRow($cols, [
                date('m/d/Y', strtotime($s['session_date'])),
                $s['instrument_name'] ?? '—',
                $s['duration_minutes'] . ' min',
                ucfirst($s['mood']),
                $note !== '' ? $note : '—',
            ]);
        }
    } else {
        $pdf->Cell(0, 6, 'No practice sessions logged.', 0, 1);
    }

    $pdf->SectionTitle('Goals');
    if ($goals) {
        $cols = ['Title' => 80, 'Target' => 30, 'Deadline' => 30, 'Status' => 58];
        $pdf->TableHeader($cols);
        foreach ($goals as $g) {
            $pdf->TableRow($cols, [
                $g['title'],
                $g['target_minutes'] . ' min',
                $g['deadline'] ? date('m/d/Y', strtotime($g['deadline'])) : '—',
                ucfirst(str_replace('_', ' ', $g['status'])),
            ]);
        }
    } else {
        $pdf->Cell(0, 6, 'No goals set.', 0, 1);
    }

    $pdf->SectionTitle('Instructor Feedback (' . count($feedback) . ')');
    if ($feedback) {
        $cols = ['Date' => 22, 'Instructor' => 34, 'Rating' => 16, 'Message' => 126];
        $pdf->TableHeader($cols);
        foreach ($feedback as $f) {
            $pdf->TableRow($cols, [
                date('m/d/Y', strtotime($f['created_at'])),
                $f['instructor_name'],
                $f['rating'] ? $f['rating'] . '/5' : '—',
                $f['message'],
            ]);
        }
    } else {
        $pdf->Cell(0, 6, 'No feedback received yet.', 0, 1);
    }

    $pdf->SectionTitle('Assignments (' . count($assignments) . ')');
    if ($assignments) {
        $cols = ['Title' => 70, 'Due' => 24, 'Status' => 24, 'Completed' => 24, 'Timing' => 56];
        $pdf->TableHeader($cols);
        foreach ($assignments as $a) {
            $timing = '—';
            if ($a['due_date']) {
                if ($a['status'] === 'completed') {
                    $timing = ($a['completed_at'] && substr($a['completed_at'], 0, 10) <= $a['due_date']) ? 'On time' : 'Late';
                } elseif ($a['due_date'] < date('Y-m-d')) {
                    $timing = 'Overdue';
                } else {
                    $timing = 'Not yet due';
                }
            }
            $pdf->TableRow($cols, [
                $a['title'],
                $a['due_date'] ? date('m/d/Y', strtotime($a['due_date'])) : '—',
                ucfirst($a['status']),
                $a['completed_at'] ? date('m/d/Y', strtotime($a['completed_at'])) : '—',
                $timing,
            ]);
        }
    } else {
        $pdf->Cell(0, 6, 'No assignments given yet.', 0, 1);
    }

    $pdf->SectionTitle('Achievements (' . count($achievements) . ')');
    if ($achievements) {
        foreach ($achievements as $a) {
            $pdf->SetFont('Helvetica', '', 9.5);
            $pdf->Cell(0, 6, $a['title'] . '  -  ' . $a['description'] . '  (' . date('M j, Y', strtotime($a['earned_at'])) . ')', 0, 1);
        }
    } else {
        $pdf->Cell(0, 6, 'No achievements earned yet.', 0, 1);
    }

    return $pdf;
}

/**
 * Build & stream a platform-wide monthly cumulative report — every
 * student's activity for the given month, plus leaderboard scoring.
 */
function buildMonthlyReportPdf(PDO $db, string $yearMonth): TrackerReportPDF {
    $start = $yearMonth . '-01';
    $end = date('Y-m-t', strtotime($start));
    $monthLabel = date('F Y', strtotime($start));

    $stmt = $db->prepare('SELECT u.id, u.full_name, i.name AS instrument_name, ins.full_name AS instructor_name,
                           COALESCE(SUM(ps.duration_minutes),0) minutes, COUNT(ps.id) sessions,
                           AVG(f.rating) AS avg_rating
                           FROM users u
                           LEFT JOIN instruments i ON i.id = u.instrument_id
                           LEFT JOIN users ins ON ins.id = u.instructor_id
                           LEFT JOIN practice_sessions ps ON ps.student_id = u.id AND ps.session_date BETWEEN ? AND ?
                           LEFT JOIN feedback f ON f.student_id = u.id AND f.rating IS NOT NULL
                           WHERE u.role = "student"
                           GROUP BY u.id ORDER BY minutes DESC');
    $stmt->execute([$start, $end]);
    $students = $stmt->fetchAll();

    $totalMinutes = 0;
    $totalSessions = 0;
    $rows = [];
    foreach ($students as $s) {
        $totalMinutes += (int) $s['minutes'];
        $totalSessions += (int) $s['sessions'];
        $avgRating = $s['avg_rating'] !== null ? round((float) $s['avg_rating'], 2) : null;

        $rows[] = [
            'name' => $s['full_name'],
            'instrument' => $s['instrument_name'] ?? '—',
            'instructor' => $s['instructor_name'] ?? '—',
            'minutes' => (int) $s['minutes'],
            'sessions' => (int) $s['sessions'],
            'avg_rating' => $avgRating,
        ];
    }

    $pdf = new TrackerReportPDF();
    $pdf->reportTitle = 'Monthly Practice Report';
    $pdf->reportSubtitle = $monthLabel . '  ·  Generated ' . date('M j, Y');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 20);

    $pdf->SectionTitle('Platform Summary — ' . $monthLabel);
    $pdf->KeyValueRow('Total students:', count($students));
    $pdf->KeyValueRow('Total sessions this month:', $totalSessions);
    $pdf->KeyValueRow('Total minutes practiced:', $totalMinutes . ' minutes (' . round($totalMinutes / 60, 1) . ' hours)');
    $pdf->KeyValueRow('Average minutes / student:', $students ? round($totalMinutes / count($students), 1) : 0);

    $pdf->SectionTitle('Per-Student Activity (sorted by minutes this month)');
    if ($rows) {
        $cols = ['Student' => 42, 'Instrument' => 26, 'Instructor' => 32, 'Sessions' => 20, 'Minutes' => 20, 'Avg Rating' => 46];
        $pdf->TableHeader($cols);
        foreach ($rows as $r) {
            $pdf->TableRow($cols, [
                $r['name'],
                $r['instrument'],
                $r['instructor'],
                $r['sessions'],
                $r['minutes'],
                $r['avg_rating'] !== null ? $r['avg_rating'] . '/5' : 'No ratings yet',
            ]);
        }
    } else {
        $pdf->Cell(0, 6, 'No students found.', 0, 1);
    }

    return $pdf;
}