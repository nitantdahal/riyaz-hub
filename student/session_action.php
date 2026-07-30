<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('sessions.php');
}

$db = getDB();
$studentId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $instrumentId = !empty($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : null;
    $sessionDate  = $_POST['session_date'] ?? date('Y-m-d');
    $duration     = max(1, (int) ($_POST['duration_minutes'] ?? 0));
    $focus        = trim($_POST['focus_area'] ?? '');
    $mood         = in_array($_POST['mood'] ?? '', ['great','good','okay','tough'], true) ? $_POST['mood'] : 'good';
    $notes        = trim($_POST['notes'] ?? '');

    $upload = uploadPracticeVideo($_FILES['practice_video'] ?? null);
    if ($upload['error']) {
        setFlash('error', $upload['error']);
        redirect('sessions.php');
    }
    $videoPath = $upload['path'];

    $stmt = $db->prepare('INSERT INTO practice_sessions (student_id, instrument_id, session_date, duration_minutes, focus_area, notes, mood, video_path)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$studentId, $instrumentId, $sessionDate, $duration, $focus, $notes, $mood, $videoPath]);

    updateStreak($db, $studentId, $sessionDate);
    $awarded = checkAchievements($db, $studentId);

    if ($awarded) {
        $names = implode(', ', array_map(fn($a) => $a['icon'] . ' ' . $a['title'], $awarded));
        setFlash('success', "Session logged! 🎉 New achievement unlocked: $names");
    } else {
        setFlash('success', 'Practice session logged successfully. Keep the streak going!');
    }
    redirect('sessions.php');
}

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $instrumentId = !empty($_POST['instrument_id']) ? (int) $_POST['instrument_id'] : null;
    $sessionDate  = $_POST['session_date'] ?? date('Y-m-d');
    $duration     = max(1, (int) ($_POST['duration_minutes'] ?? 0));
    $focus        = trim($_POST['focus_area'] ?? '');
    $mood         = in_array($_POST['mood'] ?? '', ['great','good','okay','tough'], true) ? $_POST['mood'] : 'good';
    $notes        = trim($_POST['notes'] ?? '');
    $removeVideo  = !empty($_POST['remove_video']);

    // confirm ownership + fetch existing video path
    $stmt = $db->prepare('SELECT video_path FROM practice_sessions WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $studentId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        setFlash('error', 'Session not found.');
        redirect('sessions.php');
    }
    $videoPath = $existing['video_path'];

    $upload = uploadPracticeVideo($_FILES['practice_video'] ?? null);
    if ($upload['error']) {
        setFlash('error', $upload['error']);
        redirect('sessions.php');
    }

    if ($upload['path']) {
        // a new video was uploaded — replace the old one
        deletePracticeVideo($videoPath);
        $videoPath = $upload['path'];
    } elseif ($removeVideo) {
        deletePracticeVideo($videoPath);
        $videoPath = null;
    }

    $stmt = $db->prepare('UPDATE practice_sessions SET instrument_id=?, session_date=?, duration_minutes=?, focus_area=?, notes=?, mood=?, video_path=?
                           WHERE id = ? AND student_id = ?');
    $stmt->execute([$instrumentId, $sessionDate, $duration, $focus, $notes, $mood, $videoPath, $id, $studentId]);

    setFlash('success', 'Session updated.');
    redirect('sessions.php');
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $db->prepare('SELECT video_path FROM practice_sessions WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $studentId]);
    $row = $stmt->fetch();
    if ($row && $row['video_path']) {
        deletePracticeVideo($row['video_path']);
    }

    $stmt = $db->prepare('DELETE FROM practice_sessions WHERE id = ? AND student_id = ?');
    $stmt->execute([$id, $studentId]);
    setFlash('success', 'Session deleted.');
    redirect('sessions.php');
}

redirect('sessions.php');
