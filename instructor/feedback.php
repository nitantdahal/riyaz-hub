<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT f.*, u.full_name AS student_name, u.avatar_color FROM feedback f
                       JOIN users u ON u.id = f.student_id
                       WHERE f.instructor_id = ? ORDER BY f.created_at DESC');
$stmt->execute([$instructorId]);
$feedback = $stmt->fetchAll();

$pageTitle = 'Feedback Sent';
$pageSubtitle = 'A history of the notes you\'ve given your students.';
$activeNav = 'feedback';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card">
  <div class="card-header"><h3>Feedback History (<?= count($feedback) ?>)</h3></div>
  <?php if (!$feedback): ?>
    <div class="empty-state">
      <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
      <h3>No feedback sent yet</h3>
      <p>Go to Practice Logs to leave feedback on a student's session.</p>
    </div>
  <?php else: foreach ($feedback as $f): ?>
    <div class="list-item">
      <div class="avatar" style="width:38px;height:38px;background:<?= h($f['avatar_color']) ?>"><?= h(strtoupper(substr($f['student_name'],0,1))) ?></div>
      <div>
        <div class="flex justify-between items-center">
          <div class="li-title"><?= h($f['student_name']) ?></div>
          <?= renderStars($f['rating']) ?>
        </div>
        <p style="margin:4px 0; font-size:.88rem; color:var(--text-dim);"><?= h($f['message']) ?></p>
        <div class="li-meta"><?= timeAgo($f['created_at']) ?></div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
