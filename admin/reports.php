<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$db = getDB();

$summary = [
    'users'        => (int) $db->query('SELECT COUNT(*) c FROM users')->fetch()['c'],
    'students'     => (int) $db->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch()['c'],
    'instructors'  => (int) $db->query("SELECT COUNT(*) c FROM users WHERE role='instructor'")->fetch()['c'],
    'sessions'     => (int) $db->query('SELECT COUNT(*) c FROM practice_sessions')->fetch()['c'],
    'minutes'      => (int) $db->query('SELECT COALESCE(SUM(duration_minutes),0) m FROM practice_sessions')->fetch()['m'],
    'goals_done'   => (int) $db->query("SELECT COUNT(*) c FROM goals WHERE status='completed'")->fetch()['c'],
    'achievements' => (int) $db->query('SELECT COUNT(*) c FROM achievements')->fetch()['c'],
    'assignments'  => (int) $db->query('SELECT COUNT(*) c FROM assignments')->fetch()['c'],
];

$studentReport = $db->query('SELECT u.id, u.full_name, u.email, i.name AS instrument, ins.full_name AS instructor,
                              COUNT(ps.id) sessions, COALESCE(SUM(ps.duration_minutes),0) minutes,
                              (SELECT current_streak FROM streaks WHERE student_id = u.id) AS streak
                              FROM users u
                              LEFT JOIN instruments i ON i.id = u.instrument_id
                              LEFT JOIN users ins ON ins.id = u.instructor_id
                              LEFT JOIN practice_sessions ps ON ps.student_id = u.id
                              WHERE u.role = "student"
                              GROUP BY u.id ORDER BY minutes DESC')->fetchAll();

$pageTitle = 'Reports';
$pageSubtitle = 'Export operational summaries and generate structured PDF reports.';
$activeNav = 'reports';
require __DIR__ . '/../includes/sidebar.php';
?>

<div class="grid grid-4">
  <div class="card stat-card"><div class="stat-value"><?= $summary['users'] ?></div><div class="stat-label">Total Users</div></div>
  <div class="card stat-card accent-cyan"><div class="stat-value"><?= $summary['sessions'] ?></div><div class="stat-label">Sessions Logged</div></div>
  <div class="card stat-card accent-magenta"><div class="stat-value"><?= $summary['minutes'] ?></div><div class="stat-label">Minutes Practiced</div></div>
  <div class="card stat-card accent-violet"><div class="stat-value"><?= $summary['achievements'] ?></div><div class="stat-label">Achievements Earned</div></div>
</div>

<div class="eq-divider">Monthly Cumulative Report</div>
<div class="card">
  <div class="card-header"><h3>📄 Generate Monthly PDF Report</h3></div>
  <p class="text-faint" style="margin-bottom:16px;">Produces a platform-wide, up-to-date PDF covering every student's sessions and minutes for the chosen month — ideal to run at month-end.</p>
  <form action="generate_monthly_report.php" method="GET" target="_blank" class="flex gap-12 items-end" style="flex-wrap:wrap;">
    <div class="field mb-0">
      <label for="month">Month</label>
      <input type="month" id="month" name="month" value="<?= date('Y-m') ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Generate PDF →</button>
  </form>
</div>

<div class="eq-divider">Student Performance Report</div>
<div class="card">
  <div class="card-header">
    <h3>Per-Student Summary</h3>
    <a class="btn btn-primary btn-sm" href="reports_export.php">⬇ Export CSV</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Email</th><th>Instrument</th><th>Instructor</th><th>Sessions</th><th>Total Minutes</th><th>Streak</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($studentReport as $r): ?>
          <tr>
            <td><?= h($r['full_name']) ?></td>
            <td class="text-faint"><?= h($r['email']) ?></td>
            <td><?= h($r['instrument'] ?? '—') ?></td>
            <td><?= h($r['instructor'] ?? '—') ?></td>
            <td><?= (int)$r['sessions'] ?></td>
            <td><?= (int)$r['minutes'] ?></td>
            <td>🔥 <?= (int)($r['streak'] ?? 0) ?></td>
            <td><a class="btn btn-outline btn-sm" href="generate_report.php?student_id=<?= (int)$r['id'] ?>" target="_blank">📄 PDF Report</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
