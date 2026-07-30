<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = $_SESSION['user_id'];

$stmt = $db->prepare('SELECT * FROM achievements WHERE student_id = ? ORDER BY earned_at DESC');
$stmt->execute([$studentId]);
$earned = $stmt->fetchAll();
$earnedTitles = array_column($earned, 'title');

// The full catalogue (kept in sync with includes/functions.php::checkAchievements)
$catalogue = [
    ['title' => 'First Steps',      'desc' => 'Logged your very first practice session', 'icon' => '🎵'],
    ['title' => 'Dedicated Player', 'desc' => 'Logged 10 practice sessions',             'icon' => '🎶'],
    ['title' => 'Practice Veteran', 'desc' => 'Logged 50 practice sessions',             'icon' => '🎖️'],
    ['title' => '10 Hour Club',     'desc' => 'Accumulated 10 hours of practice',        'icon' => '⏱️'],
    ['title' => '50 Hour Club',     'desc' => 'Accumulated 50 hours of practice',        'icon' => '🌟'],
    ['title' => 'On a Roll',        'desc' => '3-day practice streak',                   'icon' => '🔥'],
    ['title' => 'Week Warrior',     'desc' => '7-day practice streak',                   'icon' => '🏆'],
    ['title' => 'Unstoppable',      'desc' => '30-day practice streak',                  'icon' => '👑'],
];

$pageTitle = 'Achievements';
$pageSubtitle = count($earned) . ' of ' . count($catalogue) . ' badges unlocked';
$activeNav = 'achievements';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-4">
  <?php foreach ($catalogue as $badge):
    $isEarned = in_array($badge['title'], $earnedTitles, true);
    $earnedRow = null;
    foreach ($earned as $e) { if ($e['title'] === $badge['title']) { $earnedRow = $e; break; } }
  ?>
    <div class="card text-center" style="<?= $isEarned ? '' : 'opacity:.4; filter:grayscale(1);' ?>">
      <div style="font-size:2.6rem; margin-bottom:10px;"><?= $badge['icon'] ?></div>
      <h3 style="font-size:1rem;"><?= h($badge['title']) ?></h3>
      <p class="text-faint" style="font-size:.82rem;"><?= h($badge['desc']) ?></p>
      <?php if ($isEarned): ?>
        <span class="badge badge-success">Earned <?= date('M j, Y', strtotime($earnedRow['earned_at'])) ?></span>
      <?php else: ?>
        <span class="badge badge-muted">🔒 Locked</span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
