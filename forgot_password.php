<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
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
      <h1>Forgot your password?</h1>
      <p>No worries! Enter your email address, and we'll send you a 6-digit code to reset your password.</p>
      <div class="waveform-row" aria-hidden="true">
        <?php for ($i = 0; $i < 24; $i++): ?><span style="animation-delay:-<?= ($i * 0.07) ?>s"></span><?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Reset your password</h2>
      <p class="sub">We'll email you a 6-digit code to confirm it's really you.</p>

      <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

      <form action="auth/forgot_password_process.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Reset Code →</button>
      </form>

      <p class="form-footer-link">Remembered it after all? <a href="index.php">Back to log in</a></p>
    </div>
  </div>
</div>
</body>
</html>
