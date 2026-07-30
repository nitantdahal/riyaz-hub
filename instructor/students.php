<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT u.*, i.name AS instrument_name, i.icon,
                       (SELECT current_streak FROM streaks WHERE student_id = u.id) AS streak,
                       (SELECT COALESCE(SUM(duration_minutes),0) FROM practice_sessions WHERE student_id = u.id) AS total_minutes,
                       (SELECT COUNT(*) FROM practice_sessions WHERE student_id = u.id) AS total_sessions
                       FROM users u LEFT JOIN instruments i ON i.id = u.instrument_id
                       WHERE u.instructor_id = ? ORDER BY u.full_name ASC');
$stmt->execute([$instructorId]);
$students = $stmt->fetchAll();

$pageTitle = 'My Students';
$pageSubtitle = count($students) . ' student(s) assigned to you';
$activeNav = 'students';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-3">
  <?php if (!$students): ?>
    <div class="card empty-state" style="grid-column: 1/-1;">
      <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
      <h3>No students assigned yet</h3>
      <p>Students choose their instructor at registration time, or an administrator can assign them.</p>
    </div>
  <?php else: foreach ($students as $s): ?>
    <div class="card">
      <div class="flex items-center gap-12">
        <div class="avatar" style="background:<?= h($s['avatar_color']) ?>"><?= h(strtoupper(substr($s['full_name'],0,1))) ?></div>
        <div>
          <h3 style="font-size:1rem;"><?= h($s['full_name']) ?></h3>
          <div class="text-faint" style="font-size:.8rem;"><?= h($s['email']) ?></div>
        </div>
      </div>
      <div class="eq-divider" style="margin:16px 0 12px;">Snapshot</div>
      <div class="flex justify-between" style="margin-bottom:8px;">
        <span class="tag-instrument"><?= h($s['icon'] ?? '🎵') ?> <?= h($s['instrument_name'] ?? '—') ?></span>
        <span class="streak-flame">🔥 <?= (int)($s['streak'] ?? 0) ?>d</span>
      </div>
      <p class="text-faint" style="font-size:.82rem; margin-bottom:16px;"><?= (int)$s['total_sessions'] ?> sessions · <?= (int)$s['total_minutes'] ?> total minutes</p>
      <a class="btn btn-primary btn-block btn-sm" href="logs.php?student_id=<?= (int)$s['id'] ?>">View Logs &amp; Give Feedback</a>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
