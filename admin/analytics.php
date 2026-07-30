<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();

$monthData = [];
for ($i = 29; $i >= 0; $i -= 5) {
    // sample every 5 days across the last 30 for a compact 6-bar view
}
$days = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $stmt = $db->prepare('SELECT COALESCE(SUM(duration_minutes),0) m FROM practice_sessions WHERE session_date = ?');
    $stmt->execute([$d]);
    $days[] = ['label' => date('j M', strtotime($d)), 'minutes' => (int) $stmt->fetch()['m']];
}
$maxMinutes = max(1, max(array_column($days, 'minutes')));

$topStudents = $db->query('SELECT u.full_name, u.avatar_color, COALESCE(SUM(ps.duration_minutes),0) total_minutes, COUNT(ps.id) sessions,
                            (SELECT current_streak FROM streaks WHERE student_id = u.id) AS streak
                            FROM users u LEFT JOIN practice_sessions ps ON ps.student_id = u.id
                            WHERE u.role = "student" GROUP BY u.id ORDER BY total_minutes DESC LIMIT 8')->fetchAll();

$instructorLoad = $db->query('SELECT u.full_name, u.avatar_color, (SELECT COUNT(*) FROM users s WHERE s.instructor_id = u.id) AS student_count,
                               (SELECT COUNT(*) FROM feedback f WHERE f.instructor_id = u.id) AS feedback_count,
                               (SELECT COUNT(*) FROM assignments a WHERE a.instructor_id = u.id) AS assignment_count
                               FROM users u WHERE u.role = "instructor" ORDER BY student_count DESC')->fetchAll();

$moodBreakdown = $db->query('SELECT mood, COUNT(*) c FROM practice_sessions GROUP BY mood')->fetchAll();
$moodTotal = max(1, array_sum(array_column($moodBreakdown, 'c')));

$pageTitle = 'Analytics';
$pageSubtitle = 'Platform-wide trends across all students and instruments.';
$activeNav = 'analytics';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><h3>Practice Minutes — Last 14 Days</h3></div>
  <div class="bar-chart" style="height:180px;">
    <?php foreach ($days as $d): ?>
      <div class="bc-col">
        <span class="bc-val"><?= $d['minutes'] ?></span>
        <div class="bc-bar" style="height:<?= max(4, round(($d['minutes']/$maxMinutes)*100)) ?>%"></div>
        <span class="bc-label" style="writing-mode: vertical-rl; font-size:.62rem;"><?= h($d['label']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="two-col">
  <div class="card">
    <div class="card-header"><h3>Top Students by Total Minutes</h3></div>
    <?php foreach ($topStudents as $s): ?>
      <div class="list-item">
        <div class="avatar" style="width:34px;height:34px;font-size:.8rem;background:<?= h($s['avatar_color']) ?>"><?= h(strtoupper(substr($s['full_name'],0,1))) ?></div>
        <div style="flex:1">
          <div class="li-title"><?= h($s['full_name']) ?></div>
          <div class="li-meta"><?= (int)$s['sessions'] ?> sessions · streak 🔥<?= (int)($s['streak'] ?? 0) ?></div>
        </div>
        <span class="badge badge-gold"><?= (int)$s['total_minutes'] ?> min</span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3>Session Mood Breakdown</h3></div>
    <?php
      $moodMeta = ['great'=>['😄 Great','var(--success)'],'good'=>['🙂 Good','var(--gold)'],'okay'=>['😐 Okay','var(--cyan)'],'tough'=>['😓 Tough','var(--danger)']];
      foreach ($moodBreakdown as $m):
        [$label,$color] = $moodMeta[$m['mood']] ?? [$m['mood'], 'var(--text-faint)'];
        $pct = round(($m['c'] / $moodTotal) * 100);
    ?>
      <div style="margin-bottom:14px;">
        <div class="flex justify-between" style="font-size:.85rem; margin-bottom:6px;"><span><?= $label ?></span><span class="text-faint"><?= $pct ?>%</span></div>
        <div class="progress"><span style="width:<?= $pct ?>%; background:<?= $color ?>;"></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="eq-divider">Instructor Workload</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Instructor</th><th>Students</th><th>Feedback Given</th><th>Assignments</th></tr></thead>
      <tbody>
        <?php foreach ($instructorLoad as $i): ?>
          <tr>
            <td class="flex items-center gap-12"><div class="avatar" style="width:30px;height:30px;font-size:.75rem;background:<?= h($i['avatar_color']) ?>"><?= h(strtoupper(substr($i['full_name'],0,1))) ?></div><?= h($i['full_name']) ?></td>
            <td><?= (int)$i['student_count'] ?></td>
            <td><?= (int)$i['feedback_count'] ?></td>
            <td><?= (int)$i['assignment_count'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
