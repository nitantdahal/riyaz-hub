<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT COUNT(*) c FROM users WHERE instructor_id = ? AND role = "student"');
$stmt->execute([$instructorId]);
$studentCount = (int) $stmt->fetch()['c'];

$stmt = $db->prepare('SELECT COUNT(*) c FROM practice_sessions ps JOIN users u ON u.id = ps.student_id
                       WHERE u.instructor_id = ? AND ps.session_date >= (CURDATE() - INTERVAL 7 DAY)');
$stmt->execute([$instructorId]);
$weekSessions = (int) $stmt->fetch()['c'];

$stmt = $db->prepare('
    SELECT COUNT(*) c 
    FROM assignments 
    WHERE instructor_id = ? 
    AND status = "submitted"
');
$stmt->execute([$instructorId]);
$pendingAssignments = (int) $stmt->fetch()['c'];

$stmt = $db->prepare('SELECT COUNT(*) c FROM feedback WHERE instructor_id = ?');
$stmt->execute([$instructorId]);
$feedbackCount = (int) $stmt->fetch()['c'];

$stmt = $db->prepare('SELECT u.id, u.full_name, u.avatar_color, i.name AS instrument_name, i.icon,
                       (SELECT COALESCE(SUM(duration_minutes),0) FROM practice_sessions WHERE student_id=u.id AND YEARWEEK(session_date,1)=YEARWEEK(CURDATE(),1)) AS week_minutes,
                       (SELECT current_streak FROM streaks WHERE student_id = u.id) AS streak
                       FROM users u LEFT JOIN instruments i ON i.id = u.instrument_id
                       WHERE u.instructor_id = ? ORDER BY week_minutes DESC LIMIT 6');
$stmt->execute([$instructorId]);
$students = $stmt->fetchAll();

$stmt = $db->prepare('SELECT ps.*, u.full_name AS student_name, u.avatar_color, i.name AS instrument_name, i.icon
                       FROM practice_sessions ps
                       JOIN users u ON u.id = ps.student_id
                       LEFT JOIN instruments i ON i.id = ps.instrument_id
                       WHERE u.instructor_id = ? ORDER BY ps.created_at DESC LIMIT 6');
$stmt->execute([$instructorId]);
$recentLogs = $stmt->fetchAll();

$pageTitle = 'Instructor Dashboard';
$pageSubtitle = 'An overview of your students\' progress this week.';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-4">
  <div class="card stat-card"><div class="stat-icon">🎓</div><div class="stat-value"><?= $studentCount ?></div><div class="stat-label">Assigned Students</div></div>
  <div class="card stat-card accent-cyan"><div class="stat-icon">🎼</div><div class="stat-value"><?= $weekSessions ?></div><div class="stat-label">Sessions This Week</div></div>
  <div class="card stat-card accent-magenta"><div class="stat-icon">📝</div><div class="stat-value"><?= $pendingAssignments ?></div><div class="stat-label">Assignments to Review</div></div>
  <div class="card stat-card accent-violet"><div class="stat-icon">💬</div><div class="stat-value"><?= $feedbackCount ?></div><div class="stat-label">Feedback Given</div></div>
</div>

<div class="eq-divider">Top Students This Week</div>
<div class="card">
  <?php if (!$students): ?>
    <div class="empty-state"><h3>No students assigned yet</h3><p>Students choose their instructor when they register.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Instrument</th><th>Minutes This Week</th><th>Streak</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($students as $s): ?>
        <tr>
          <td>
    <div class="student-info">
        <!-- <div class="avatar" style="width:32px;height:32px;font-size:.8rem;background:<?= h($s['avatar_color']) ?>">
            <?= h(strtoupper(substr($s['full_name'],0,1))) ?>
        </div> -->

        <div class="student-details">
            <div class="student-name">
                <?= h($s['full_name']) ?>
            </div>
            <!-- <div class="student-label">
                Student
            </div> -->
        </div>
    </div>
</td>

<td>
    <div class="instrument-info">
        <div class="instrument-name">
            <?= h($s['icon'] ?? '🎵') ?> <?= h($s['instrument_name'] ?? '—') ?>
        </div>
        <!-- <div class="instrument-label">
            Instrument
        </div> -->
    </div>
</td>
          <td><?= (int)$s['week_minutes'] ?> min</td>
          <td><span class="streak-flame">🔥 <?= (int)($s['streak'] ?? 0) ?></span></td>
          <td><a class="btn btn-outline btn-sm" href="logs.php?student_id=<?= (int)$s['id'] ?>">View Logs</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="eq-divider">Recent Practice Logs</div>
<div class="card">
  <?php if (!$recentLogs): ?>
    <div class="empty-state"><h3>No recent activity</h3><p>Your students haven't logged any sessions yet.</p></div>
  <?php else: foreach ($recentLogs as $log): ?>
    <div class="list-item">
      <div class="li-icon"><?= h($log['icon'] ?? '🎵') ?></div>
      <div style="flex:1">
        <div class="li-title"><?= h($log['student_name']) ?> — <?= h($log['focus_area'] ?: $log['instrument_name']) ?></div>
        <div class="li-meta"><?= (int)$log['duration_minutes'] ?> min · <?= date('M j', strtotime($log['session_date'])) ?> · <?= timeAgo($log['created_at']) ?></div>
      </div>
      <a class="btn btn-ghost btn-sm" href="logs.php?student_id=<?= (int)$log['student_id'] ?>">Give Feedback</a>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
