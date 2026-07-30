<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();

$userCounts = $db->query("SELECT role, COUNT(*) c FROM users GROUP BY role")->fetchAll();
$counts = ['admin'=>0,'instructor'=>0,'student'=>0];
foreach ($userCounts as $row) { $counts[$row['role']] = (int) $row['c']; }

$totalMinutes = (int) $db->query('SELECT COALESCE(SUM(duration_minutes),0) m FROM practice_sessions')->fetch()['m'];
$totalSessions = (int) $db->query('SELECT COUNT(*) c FROM practice_sessions')->fetch()['c'];
$instrumentCount = (int) $db->query('SELECT COUNT(*) c FROM instruments')->fetch()['c'];

$weekData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $stmt = $db->prepare('SELECT COALESCE(SUM(duration_minutes),0) m FROM practice_sessions WHERE session_date = ?');
    $stmt->execute([$d]);
    $weekData[] = ['label' => date('D', strtotime($d)), 'minutes' => (int) $stmt->fetch()['m']];
}
$maxMinutes = max(1, max(array_column($weekData, 'minutes')));

$topInstruments = $db->query('SELECT i.name, i.icon, COUNT(ps.id) sessions, COALESCE(SUM(ps.duration_minutes),0) minutes
                               FROM instruments i LEFT JOIN practice_sessions ps ON ps.instrument_id = i.id
                               GROUP BY i.id ORDER BY minutes DESC LIMIT 5')->fetchAll();

$recentUsers = $db->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'System-wide overview of activity, users, and performance.';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-4">
  <div class="card stat-card"><div class="stat-icon">👥</div><div class="stat-value"><?= array_sum($counts) ?></div><div class="stat-label">Total Users</div></div>
  <div class="card stat-card accent-cyan"><div class="stat-icon">🎼</div><div class="stat-value"><?= $totalSessions ?></div><div class="stat-label">Practice Sessions</div></div>
  <div class="card stat-card accent-magenta"><div class="stat-icon">⏱️</div><div class="stat-value"><?= $totalMinutes ?></div><div class="stat-label">Total Minutes Logged</div></div>
  <div class="card stat-card accent-violet"><div class="stat-icon">🎻</div><div class="stat-value"><?= $instrumentCount ?></div><div class="stat-label">Instruments Configured</div></div>
</div>

<div class="eq-divider">User Breakdown</div>
<div class="grid grid-3">
  <div class="card">
    <div class="flex justify-between items-center"><span class="badge badge-magenta">Admins</span><span style="font-family:var(--font-display); font-size:1.6rem;"><?= $counts['admin'] ?></span></div>
  </div>
  <div class="card">
    <div class="flex justify-between items-center"><span class="badge badge-cyan">Instructors</span><span style="font-family:var(--font-display); font-size:1.6rem;"><?= $counts['instructor'] ?></span></div>
  </div>
  <div class="card">
    <div class="flex justify-between items-center"><span class="badge badge-gold">Students</span><span style="font-family:var(--font-display); font-size:1.6rem;"><?= $counts['student'] ?></span></div>
  </div>
</div>

<div class="eq-divider">Platform Activity</div>
<div class="two-col">
  <div class="card">
    <div class="card-header"><h3>Minutes Practiced — Last 7 Days</h3></div>
    <div class="bar-chart">
      <?php foreach ($weekData as $day): ?>
        <div class="bc-col">
          <span class="bc-val"><?= $day['minutes'] ?>m</span>
          <div class="bc-bar" style="height:<?= max(4, round(($day['minutes']/$maxMinutes)*100)) ?>%"></div>
          <span class="bc-label"><?= h($day['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>Most Practiced Instruments</h3></div>
    <?php foreach ($topInstruments as $ins): ?>
      <div class="list-item">
        <div class="li-icon"><?= h($ins['icon']) ?></div>
        <div style="flex:1">
          <div class="li-title"><?= h($ins['name']) ?></div>
          <div class="li-meta"><?= (int)$ins['sessions'] ?> sessions · <?= (int)$ins['minutes'] ?> min</div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="eq-divider">Newest Members</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
      <tbody>
        <?php foreach ($recentUsers as $u): ?>
          <tr>
            <td class="flex items-center gap-12"><div class="avatar" style="width:30px;height:30px;font-size:.75rem;background:<?= h($u['avatar_color']) ?>"><?= h(strtoupper(substr($u['full_name'],0,1))) ?></div><?= h($u['full_name']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role']==='admin'?'magenta':($u['role']==='instructor'?'cyan':'gold') ?>"><?= h(ucfirst($u['role'])) ?></span></td>
            <td class="text-faint"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
