<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/leaderboard.php';
requireRole('instructor');

$db = getDB();
$instructorId = $_SESSION['user_id'];
$leaderboard = getInstructorLeaderboard($db, $instructorId);

$pageTitle = 'Student Leaderboard';
$pageSubtitle = 'Ranked by practice minutes, your ratings, and on-time assignments.';
$activeNav = 'leaderboard';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div class="flex items-center gap-12" style="flex-wrap:wrap;">
    <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
    <p class="mb-0" style="font-size:.85rem;">
      <strong>Score formula:</strong> practice minutes + (average rating × 20) + (on-time assignment rate × 0.5).
    </p> 
    <!-- This rewards consistent practice, strong feedback, and reliably submitting work on time. -->
  </div>
</div>



<?php if (!$leaderboard): ?>
  <div class="card empty-state">
    <div class="eq-bars"><span></span><span></span><span></span><span></span><span></span></div>
    <h3>No students assigned yet</h3>
    <p>Once students choose you as their instructor, their ranking will appear here.</p>
  </div>
<?php else: ?>
  <?php foreach ($leaderboard as $i => $row):
    $rank = $i + 1;
    $rankClass = $rank <= 3 ? 'rank-' . $rank : '';
    $medal = ['🥇','🥈','🥉'][$rank - 1] ?? null;
  ?>
    <div class="leaderboard-row <?= $rankClass ?>">
      <div class="lb-rank"><?= $medal ?: '#' . $rank ?></div>
      <div class="avatar" style="background:<?= h($row['avatar_color']) ?>"><?= h(strtoupper(substr($row['full_name'], 0, 1))) ?></div>
      <div style="flex:1; min-width:0;">
        <div style="font-weight:700;"><?= h($row['full_name']) ?></div>
        <div class="lb-metrics">
          <span>⏱️ <?= (int)$row['minutes'] ?> min · <?= (int)$row['session_count'] ?> sessions</span>
          <span>⭐ <?= $row['avg_rating'] !== null ? $row['avg_rating'] . '/5' : 'No ratings yet' ?><?php if ($row['rating_count']): ?> (<?= (int)$row['rating_count'] ?>)<?php endif; ?></span>
          <span>✅ <?= $row['on_time_pct'] !== null ? $row['on_time_pct'] . '% on-time' : 'No graded assignments' ?></span>
        </div>
      </div>
      <div class="text-center">
        <div class="lb-score"><?= $row['score'] ?></div>
        <div class="text-faint" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.06em;">points</div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
