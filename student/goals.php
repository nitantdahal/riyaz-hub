<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT * FROM streaks WHERE student_id = ?');
$stmt->execute([$studentId]);
$streak = $stmt->fetch() ?: ['current_streak' => 0, 'longest_streak' => 0, 'last_practice_date' => null];

$stmt = $db->prepare('SELECT g.*, (SELECT COALESCE(SUM(duration_minutes),0) FROM practice_sessions WHERE student_id=g.student_id AND created_at >= g.created_at) AS progress_minutes
                       FROM goals g WHERE g.student_id = ? ORDER BY FIELD(status,"in_progress","completed","missed"), deadline ASC');
$stmt->execute([$studentId]);
$goals = $stmt->fetchAll();

$pageTitle = 'Goals & Streaks';
$pageSubtitle = 'Set targets, track progress, and keep your practice streak alive.';
$activeNav = 'goals';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-3">
  <div class="card stat-card accent-magenta text-center">
    <div class="streak-flame" style="font-size:2.4rem; justify-content:center;">🔥 <?= (int)$streak['current_streak'] ?></div>
    <div class="stat-label" style="margin-top:8px;">Current Streak (days)</div>
  </div>
  <div class="card stat-card accent-cyan text-center">
    <div class="stat-value">🏔️ <?= (int)$streak['longest_streak'] ?></div>
    <div class="stat-label">Longest Streak (days)</div>
  </div>
  <div class="card stat-card text-center">
    <div class="stat-value" style="font-size:1.4rem;">📆 <?= $streak['last_practice_date'] ? date('M j, Y', strtotime($streak['last_practice_date'])) : '—' ?></div>
    <div class="stat-label">Last Practice Date</div>
  </div>
</div>

<div class="eq-divider">My Goals</div>
<div class="card">
  <div class="card-header">
    <h3>Goal Tracker</h3>
    <button class="btn btn-primary btn-sm" onclick="openModal('addGoalModal')">+ New Goal</button>
  </div>

  <?php if (!$goals): ?>
    <div class="empty-state">
      <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
      <h3>No goals set yet</h3>
      <p>Create a goal like "Practice 300 minutes this week" to stay motivated.</p>
    </div>
  <?php else: foreach ($goals as $g):
    $pct = min(100, round(($g['progress_minutes'] / max(1,$g['target_minutes'])) * 100));
  ?>
    <div class="list-item" style="align-items:flex-start;">
      <div class="li-icon">🎯</div>
      <div style="flex:1">
        <div class="flex justify-between items-center">
          <div class="li-title"><?= h($g['title']) ?></div>
          <?php
            $statusBadge = ['in_progress'=>'badge-cyan','completed'=>'badge-success','missed'=>'badge-danger'];
          ?>
          <span class="badge <?= $statusBadge[$g['status']] ?>"><?= h(str_replace('_',' ',ucfirst($g['status']))) ?></span>
        </div>
        <div class="li-meta" style="margin:6px 0;">Target: <?= (int)$g['target_minutes'] ?> min <?php if($g['deadline']): ?>· Deadline: <?= date('M j, Y', strtotime($g['deadline'])) ?><?php endif; ?></div>
        <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
        <div class="li-meta" style="margin-top:6px;"><?= (int)$g['progress_minutes'] ?> / <?= (int)$g['target_minutes'] ?> min (<?= $pct ?>%)</div>
      </div>
      <div class="flex gap-8">
        <?php if ($g['status'] === 'in_progress'): ?>
        <form action="goal_action.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="action" value="complete">
          <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button type="submit" class="btn btn-outline btn-sm">Mark Done</button>
        </form>
        <?php endif; ?>
        <form action="goal_action.php" method="POST" onsubmit="return confirmDelete('Delete this goal?')">
          <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button type="submit" class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<div class="modal-overlay" id="addGoalModal">
  <div class="modal">
    <div class="modal-header"><h3>New Practice Goal</h3><button class="modal-close" onclick="closeModal('addGoalModal')">✕</button></div>
    <form action="goal_action.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
      <input type="hidden" name="action" value="create">
      <div class="field"><label>Goal title</label><input type="text" name="title" placeholder="e.g. Practice 300 minutes this week" required></div>
      <div class="field"><label>Target minutes</label><input type="number" name="target_minutes" min="1" required></div>
      <div class="field"><label>Deadline</label><input type="date" name="deadline"></div>
      <button type="submit" class="btn btn-primary btn-block">Create Goal 🎯</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
