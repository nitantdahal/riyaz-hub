<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT id, full_name FROM users WHERE instructor_id = ? ORDER BY full_name');
$stmt->execute([$instructorId]);
$myStudents = $stmt->fetchAll();
$myStudentIds = array_column($myStudents, 'id');

$selectedStudent = isset($_GET['student_id']) ? (int) $_GET['student_id'] : null;
if ($selectedStudent && !in_array($selectedStudent, $myStudentIds, true)) {
    $selectedStudent = null; // guard against viewing students not assigned to you
}

$sql = 'SELECT ps.*, u.full_name AS student_name, u.avatar_color, i.name AS instrument_name, i.icon,
        (SELECT COUNT(*) FROM feedback WHERE session_id = ps.id) AS feedback_count
        FROM practice_sessions ps
        JOIN users u ON u.id = ps.student_id
        LEFT JOIN instruments i ON i.id = ps.instrument_id
        WHERE u.instructor_id = ?';
$params = [$instructorId];
if ($selectedStudent) {
    $sql .= ' AND ps.student_id = ?';
    $params[] = $selectedStudent;
}
$sql .= ' ORDER BY ps.session_date DESC, ps.created_at DESC LIMIT 60';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Practice Logs';
$pageSubtitle = 'Review session details and leave feedback.';
$activeNav = 'logs';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="margin-bottom:20px;">
  <form method="GET" class="flex gap-12 items-center">
    <div class="field mb-0" style="flex:1; max-width:320px;">
      <label>Filter by student</label>
      <select name="student_id" onchange="this.form.submit()">
        <option value="">All students</option>
        <?php foreach ($myStudents as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $selectedStudent == $s['id'] ? 'selected' : '' ?>><?= h($s['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header"><h3>Practice Logs (<?= count($logs) ?>)</h3></div>
  <?php if (!$logs): ?>
    <div class="empty-state"><h3>No sessions found</h3><p>Try clearing the filter, or check back once your students log practice.</p></div>
  <?php else: foreach ($logs as $log): ?>
    <div class="list-item" style="align-items:flex-start;">
      <div class="avatar" style="width:38px;height:38px;background:<?= h($log['avatar_color']) ?>"><?= h(strtoupper(substr($log['student_name'],0,1))) ?></div>
      <div style="flex:1">
        <div class="flex justify-between items-center">
          <div class="li-title"><?= h($log['student_name']) ?> — <?= h($log['focus_area'] ?: ($log['instrument_name'] ?? 'Practice')) ?></div>
          <span class="badge badge-gold"><?= (int)$log['duration_minutes'] ?> min</span>
        </div>
        <div class="li-meta"><?= h($log['icon'] ?? '🎵') ?> <?= h($log['instrument_name'] ?? '') ?> · <?= date('M j, Y', strtotime($log['session_date'])) ?> · mood: <?= h(ucfirst($log['mood'])) ?></div>
        <?php if ($log['notes']): ?><p class="text-faint" style="font-size:.85rem; margin:6px 0 0;">"<?= h($log['notes']) ?>"</p><?php endif; ?>
        <div class="flex gap-8" style="margin-top:10px;">
          <?php if (!empty($log['video_path'])): ?>
            <button class="btn btn-outline btn-sm" onclick="openModal('watch-<?= (int)$log['id'] ?>')">Open</button>
          <?php endif; ?>
          <button class="btn btn-outline btn-sm" onclick="openModal('fb-<?= (int)$log['id'] ?>')">💬 Give Feedback</button>
          <?php if ($log['feedback_count'] > 0): ?><span class="badge badge-success">Feedback given</span><?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!empty($log['video_path'])): ?>
    <div class="modal-overlay" id="watch-<?= (int)$log['id'] ?>">
      <div class="modal" style="max-width:640px;">
        <div class="modal-header">
          <h3>🎥 <?= h($log['student_name']) ?> — <?= h($log['focus_area'] ?: ($log['instrument_name'] ?? 'Practice Video')) ?></h3>
          <button class="modal-close" onclick="closeModal('watch-<?= (int)$log['id'] ?>')">✕</button>
        </div>
        <?php if (isAudioOnlyMedia($log['video_path'])): ?>
          <audio controls style="width:100%;" src="../<?= h($log['video_path']) ?>"></audio>
        <?php else: ?>
          <video controls style="width:100%; border-radius: var(--radius-sm); background:#000;" src="../<?= h($log['video_path']) ?>"></video>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="modal-overlay" id="fb-<?= (int)$log['id'] ?>">
      <div class="modal">
        <div class="modal-header"><h3>Feedback for <?= h($log['student_name']) ?></h3><button class="modal-close" onclick="closeModal('fb-<?= (int)$log['id'] ?>')">✕</button></div>
        <form action="feedback_action.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="student_id" value="<?= (int)$log['student_id'] ?>">
          <input type="hidden" name="session_id" value="<?= (int)$log['id'] ?>">
          <div class="field">
            <label>Session: <?= h($log['focus_area'] ?: $log['instrument_name']) ?> (<?= date('M j', strtotime($log['session_date'])) ?>)</label>
            <textarea name="message" placeholder="Write encouraging, specific feedback…" required></textarea>
          </div>
          <div class="field">
            <label>Rating <span class="text-faint">(used in the student leaderboard)</span></label>
            <div class="star-rating">
              <input type="radio" name="rating" id="r5-<?= (int)$log['id'] ?>" value="5"><label for="r5-<?= (int)$log['id'] ?>">★</label>
              <input type="radio" name="rating" id="r4-<?= (int)$log['id'] ?>" value="4"><label for="r4-<?= (int)$log['id'] ?>">★</label>
              <input type="radio" name="rating" id="r3-<?= (int)$log['id'] ?>" value="3" checked><label for="r3-<?= (int)$log['id'] ?>">★</label>
              <input type="radio" name="rating" id="r2-<?= (int)$log['id'] ?>" value="2"><label for="r2-<?= (int)$log['id'] ?>">★</label>
              <input type="radio" name="rating" id="r1-<?= (int)$log['id'] ?>" value="1"><label for="r1-<?= (int)$log['id'] ?>">★</label>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send Feedback</button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
