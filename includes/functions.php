<?php
/**
 * Shared business-logic helpers: streaks, achievements, stats
 */

/** Where practice videos/recordings are stored, relative to the project root */
define('VIDEO_UPLOAD_DIR', __DIR__ . '/../uploads/practice_videos');
define('VIDEO_MAX_BYTES', 150 * 1024 * 1024); // 150 MB (voice recordings can run long)
define('VIDEO_ALLOWED_EXT', ['mp4', 'mov', 'webm', 'ogg', 'avi', 'mkv', 'mp3', 'wav', 'm4a', 'aac', 'weba']);
/** Extensions that should be played back with an <audio> tag instead of <video> */
define('AUDIO_ONLY_EXT', ['mp3', 'wav', 'm4a', 'aac', 'weba']);

/**
 * Validate & move an uploaded practice video into /uploads/practice_videos.
 * Returns ['path' => 'uploads/practice_videos/xxx.mp4', 'error' => null] on success,
 * or ['path' => null, 'error' => 'message'] on failure.
 * If no file was submitted, returns ['path' => null, 'error' => null] (not an error).
 */
function uploadPracticeVideo(array $file = null) {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'The video failed to upload (error code ' . $file['error'] . ').'];
    }
    if ($file['size'] > VIDEO_MAX_BYTES) {
        return ['path' => null, 'error' => 'Video is too large. Maximum allowed size is 100 MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, VIDEO_ALLOWED_EXT, true)) {
        return ['path' => null, 'error' => 'Unsupported video format. Allowed: ' . implode(', ', VIDEO_ALLOWED_EXT) . '.'];
    }

    if (!is_dir(VIDEO_UPLOAD_DIR)) {
        mkdir(VIDEO_UPLOAD_DIR, 0755, true);
    }

    $filename = 'vid_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = VIDEO_UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['path' => null, 'error' => 'Could not save the uploaded video. Check folder permissions.'];
    }

    return ['path' => 'uploads/practice_videos/' . $filename, 'error' => null];
}

/** Delete a previously stored practice video file (safe no-op if missing) */
function deletePracticeVideo($relativePath) {
    if (!$relativePath) return;
    $full = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (is_file($full)) {
        @unlink($full);
    }
}

/** True if the stored media file is audio-only (e.g. a mic recording) and should use an <audio> player */
function isAudioOnlyMedia($relativePath) {
    if (!$relativePath) return false;
    $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
    return in_array($ext, AUDIO_ONLY_EXT, true);
}

/** Render a small on-time/late/overdue badge for an assignment row (expects status, due_date, completed_at keys) */
function renderAssignmentTimingBadge($assignment) {
    if (!$assignment['due_date']) return '';
    $today = date('Y-m-d');
    if ($assignment['status'] === 'completed') {
        if ($assignment['completed_at'] && substr($assignment['completed_at'], 0, 10) <= $assignment['due_date']) {
            return '<span class="badge badge-success">✅ On time</span>';
        }
        return '<span class="badge badge-danger">⏰ Late</span>';
    }
    if ($assignment['due_date'] < $today) {
        return '<span class="badge badge-danger">⚠️ Overdue</span>';
    }
    return '';
}
function renderStars($rating) {
    if (!$rating) return '<span class="text-faint" style="font-size:.78rem;">Not rated</span>';
    $rating = max(1, min(5, (int) $rating));
    $out = '<span style="color:var(--gold); letter-spacing:1px;">';
    $out .= str_repeat('★', $rating);
    $out .= '<span style="color:var(--line-strong);">' . str_repeat('★', 5 - $rating) . '</span>';
    $out .= '</span>';
    return $out;
}

/**
 * Recalculate a student's streak after a new practice session is logged.
 */
function updateStreak(PDO $db, $studentId, $sessionDate) {
    $stmt = $db->prepare('SELECT * FROM streaks WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $streak = $stmt->fetch();

    $sessionDate = new DateTime($sessionDate);

    if (!$streak) {
        $db->prepare('INSERT INTO streaks (student_id, current_streak, longest_streak, last_practice_date) VALUES (?, 1, 1, ?)')
           ->execute([$studentId, $sessionDate->format('Y-m-d')]);
        return;
    }

    $last = $streak['last_practice_date'] ? new DateTime($streak['last_practice_date']) : null;
    $current = (int) $streak['current_streak'];
    $longest = (int) $streak['longest_streak'];

    if ($last) {
        $diff = (int) $last->diff($sessionDate)->format('%r%a');
        if ($diff === 0) {
            // same day, no change to streak count
        } elseif ($diff === 1) {
            $current += 1;
        } elseif ($diff > 1) {
            $current = 1; // streak broken
        }
        // negative diff (logging a past date) is ignored for streak math
        if ($diff >= 0 && $sessionDate > $last) {
            $longest = max($longest, $current);
        }
    } else {
        $current = 1;
        $longest = 1;
    }

    $newLastDate = $last && $sessionDate < $last ? $last->format('Y-m-d') : $sessionDate->format('Y-m-d');

    $db->prepare('UPDATE streaks SET current_streak = ?, longest_streak = ?, last_practice_date = ? WHERE student_id = ?')
       ->execute([$current, $longest, $newLastDate, $studentId]);
}

/**
 * Check + award milestone achievements. Call after logging a session.
 */
function checkAchievements(PDO $db, $studentId) {
    $awarded = [];

    // total sessions
    $stmt = $db->prepare('SELECT COUNT(*) AS c FROM practice_sessions WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $totalSessions = (int) $stmt->fetch()['c'];

    // total minutes
    $stmt = $db->prepare('SELECT COALESCE(SUM(duration_minutes),0) AS m FROM practice_sessions WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $totalMinutes = (int) $stmt->fetch()['m'];

    // current streak
    $stmt = $db->prepare('SELECT current_streak FROM streaks WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $row = $stmt->fetch();
    $streak = $row ? (int) $row['current_streak'] : 0;

    $milestones = [
        ['cond' => $totalSessions >= 1,   'title' => 'First Steps',        'desc' => 'Logged your very first practice session', 'icon' => '🎵'],
        ['cond' => $totalSessions >= 10,  'title' => 'Dedicated Player',    'desc' => 'Logged 10 practice sessions',             'icon' => '🎶'],
        ['cond' => $totalSessions >= 50,  'title' => 'Practice Veteran',    'desc' => 'Logged 50 practice sessions',             'icon' => '🎖️'],
        ['cond' => $totalMinutes  >= 600, 'title' => '10 Hour Club',        'desc' => 'Accumulated 10 hours of practice',        'icon' => '⏱️'],
        ['cond' => $totalMinutes  >= 3000,'title' => '50 Hour Club',        'desc' => 'Accumulated 50 hours of practice',        'icon' => '🌟'],
        ['cond' => $streak        >= 3,   'title' => 'On a Roll',          'desc' => '3-day practice streak',                   'icon' => '🔥'],
        ['cond' => $streak        >= 7,   'title' => 'Week Warrior',       'desc' => '7-day practice streak',                   'icon' => '🏆'],
        ['cond' => $streak        >= 30,  'title' => 'Unstoppable',        'desc' => '30-day practice streak',                  'icon' => '👑'],
    ];

    foreach ($milestones as $m) {
        if (!$m['cond']) continue;

        $check = $db->prepare('SELECT id FROM achievements WHERE student_id = ? AND title = ?');
        $check->execute([$studentId, $m['title']]);
        if ($check->fetch()) continue; // already awarded

        $db->prepare('INSERT INTO achievements (student_id, title, description, icon) VALUES (?, ?, ?, ?)')
           ->execute([$studentId, $m['title'], $m['desc'], $m['icon']]);
        $awarded[] = $m;
    }

    return $awarded;
}

/** Minutes practiced this week (Mon-Sun) for a student */
function weeklyMinutes(PDO $db, $studentId) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(duration_minutes),0) AS m FROM practice_sessions
                           WHERE student_id = ? AND YEARWEEK(session_date, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute([$studentId]);
    return (int) $stmt->fetch()['m'];
}

/** Last N days of practice minutes, grouped by date, for charting */
function dailyMinutes(PDO $db, $studentId, $days = 7) {
    $stmt = $db->prepare("SELECT session_date, SUM(duration_minutes) AS minutes
                           FROM practice_sessions
                           WHERE student_id = ? AND session_date >= (CURDATE() - INTERVAL ? DAY)
                           GROUP BY session_date
                           ORDER BY session_date ASC");
    $stmt->execute([$studentId, $days]);
    $rows = $stmt->fetchAll();

    $map = [];
    foreach ($rows as $r) {
        $map[$r['session_date']] = (int) $r['minutes'];
    }

    $result = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        $result[] = ['date' => $d, 'label' => date('D', strtotime($d)), 'minutes' => $map[$d] ?? 0];
    }
    return $result;
}

function timeAgo($datetime) {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}
