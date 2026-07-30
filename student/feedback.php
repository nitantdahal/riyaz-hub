<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT f.*, u.full_name AS instructor_name, u.avatar_color FROM feedback f
                       JOIN users u ON u.id = f.instructor_id
                       WHERE f.student_id = ? ORDER BY f.created_at DESC');
$stmt->execute([$studentId]);
$feedback = $stmt->fetchAll();

$stmt = $db->prepare('
    SELECT a.*, u.full_name AS instructor_name 
    FROM assignments a
    JOIN users u ON u.id = a.instructor_id
    WHERE a.student_id = ?
    ORDER BY a.created_at DESC
');

$stmt->execute([$studentId]);
$assignments = $stmt->fetchAll();

$pageTitle = 'Feedback & Tasks';
$pageSubtitle = 'Notes and assignments from your instructor.';
$activeNav = 'feedback';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="two-col">
  <div class="card">
    <div class="card-header"><h3>Assignments</h3></div>
    <?php if (!$assignments): ?>
      <div class="empty-state"><h3>No assignments yet</h3><p>Your instructor hasn't assigned any tasks.</p></div>
    <?php else: foreach ($assignments as $a): ?>
      <div class="list-item" style="align-items:flex-start;">
        <div class="li-icon">📝</div>
        <div style="flex:1">
          <div class="flex justify-between items-center">
            <div class="li-title"><?= h($a['title']) ?></div>
            <div class="flex gap-8 items-center">
              <?php
              $statusClass = match($a['status']) {
                  'accepted' => 'badge-success',
                  'submitted' => 'badge-warning',
                  'rejected' => 'badge-danger',
                  default => 'badge-gold'
              };
              ?>

              <span class="badge <?= $statusClass ?>">
                  <?= h(ucfirst($a['status'])) ?>
              </span>
              <?= renderAssignmentTimingBadge($a) ?>
            </div>
          </div>
          <p class="text-faint" style="font-size:.85rem; margin:6px 0;"><?= h($a['description']) ?></p>
          <div class="li-meta">From <?= h($a['instructor_name']) ?> <?php if($a['due_date']): ?>· Due <?= date('M j, Y', strtotime($a['due_date'])) ?><?php endif; ?></div>
          <?php if (!empty($a['instructor_feedback'])): ?>

          <p class="text-faint" style="margin-top:8px;">
              <strong>Instructor Feedback:</strong>
              <?= h($a['instructor_feedback']) ?>
          </p>

          <?php endif; ?>
            <?php if ($a['status'] === 'pending'): ?>
          <form action="assignment_action.php" method="POST" style="margin-top:8px;">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <button type="submit" class="btn btn-outline btn-sm">Mark Completed</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3>Feedback</h3></div>
    <?php if (!$feedback): ?>
      <div class="empty-state"><h3>No feedback yet</h3><p>Check back after your next lesson.</p></div>
    <?php else: foreach ($feedback as $f): ?>
      <div class="list-item">
        <div class="avatar" style="background:<?= h($f['avatar_color']) ?>; width:38px;height:38px;"><?= h(strtoupper(substr($f['instructor_name'],0,1))) ?></div>
        <div>
          <div class="flex justify-between items-center">
            <div class="li-title"><?= h($f['instructor_name']) ?></div>
            <?= renderStars($f['rating']) ?>
          </div>
          <p style="margin:4px 0; font-size:.88rem; color:var(--text-dim);"><?= h($f['message']) ?></p>
          <div class="li-meta"><?= timeAgo($f['created_at']) ?></div>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
