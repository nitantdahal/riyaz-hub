<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect($role . '/dashboard.php');
}
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
      <h1>Your Next Great Performance Starts Here.</h1>
      <p>Consistency builds excellence. Sign in to continue your daily practice, celebrate your achievements, and watch your musical skills grow with every session.</p>
      <div class="waveform-row" aria-hidden="true">
        <?php for ($i = 0; $i < 24; $i++): ?><span style="animation-delay:-<?= ($i * 0.07) ?>s"></span><?php endfor; ?>
      </div>
    </div>

    <div class="stat-strip">
      <div class="stat"><b>2</b><span>User Types</span></div>
      <div class="stat"><b>24/7</b><span>Practice Logging</span></div>
      <div class="stat"><b>∞</b><span>Streaks Tracked</span></div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Log in to your account</h2>
      <p class="sub">Enter your credentials to reach your dashboard.</p>

      <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

      <form action="auth/login_process.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="password-toggle" data-target="password" aria-label="Show password">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log In →</button>
      </form>

      <p class="form-footer-link" style="margin-top:14px; margin-bottom:0;"><a href="forgot_password.php" style="color:var(--cyan); font-weight:600;">Forgot your password?</a></p>

      <p class="form-footer-link">New student? <a href="register.php">Create an account</a></p>
  </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
