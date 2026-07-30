<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
}

$db = getDB();
$instruments = $db->query('SELECT id, name, icon FROM instruments ORDER BY name')->fetchAll();
$instructors = $db->query("SELECT id, full_name FROM users WHERE role='instructor' AND status='active' ORDER BY full_name")->fetchAll();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RiyazHub</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" type="image/png" href="assets/images/favicon.png">

</head>
<body>
<div class="auth-shell">
  <div class="auth-visual">
    <div class="brand">
      <img src="assets/images/favicon.png" alt="RiyazHub Logo" class="logo-mark">
      <span>RiyazHub</span>
    </div>
    <div class="pitch">
      <h1>Start your streak today.</h1>
      <p>Create your profile, choose your instrument, and connect with your instructor. Track every practice session, build daily consistency, and unlock achievements as you grow.</p>
      <div class="waveform-row" aria-hidden="true">
        <?php for ($i = 0; $i < 24; $i++): ?><span style="animation-delay:-<?= ($i * 0.07) ?>s"></span><?php endfor; ?>
      </div>
    </div>
    <div class="stat-strip">
      <div class="stat"><b><?= count($instruments) ?></b><span>Instruments</span></div>
      <div class="stat"><b><?= count($instructors) ?></b><span>Instructors</span></div>
      <div class="stat"><b>Free</b><span>To Join</span></div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Create your account</h2>
      <p class="sub">Student accounts only <br> Instructors are added by the system administrator. </p>

      <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

      <form action="auth/register_process.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" placeholder="Jamie Rivera" required value="<?= h($_POST['full_name'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?= h($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="At least 6 characters" required minlength="6">
            <button type="button" class="password-toggle" data-target="password" aria-label="Show password">👁️</button>
          </div>
        </div>
        <div class="field">
          <label for="instrument_id">Primary instrument</label>
          <select id="instrument_id" name="instrument_id" required>
            <option value="">Select an instrument…</option>
            <?php foreach ($instruments as $ins): ?>
              <option value="<?= (int)$ins['id'] ?>"><?= h($ins['icon'] . ' ' . $ins['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="instructor_id">Instructor <span class="text-faint">(optional)</span></label>
          <select id="instructor_id" name="instructor_id">
            <option value="">No instructor yet</option>
            <?php foreach ($instructors as $ins): ?>
              <option value="<?= (int)$ins['id'] ?>"><?= h($ins['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Continue and Verify Email →</button>
      </form>
      <p class="form-footer-link">Already have an account? <a href="index.php">Log in</a></p>
    </div>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
