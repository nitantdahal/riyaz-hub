<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
}

if (empty($_SESSION['reset_pending_email'])) {
    setFlash('error', 'No password reset in progress. Please start again.');
    redirect('forgot_password.php');
}

$email = $_SESSION['reset_pending_email'];
$devOtp = $_SESSION['reset_dev_fallback'] ?? null;
$maskedEmail = preg_replace('/^(.{2}).*(@.*)$/', '$1***$2', $email);

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

<style>
  .otp-input-row{ display:flex; gap:10px; justify-content:center; margin-bottom:8px; }
  .otp-input-row input{
    width:52px; height:60px; text-align:center; font-family:var(--font-mono); font-size:1.6rem; font-weight:700;
    padding:0;
  }
</style>
</head>
<body>
<div class="auth-shell">
  <div class="auth-visual">
    <div class="brand">
      <img src="assets/images/favicon.png" alt="RiyazHub Logo" class="logo-mark">
      <span>RiyazHub</span>
    </div>
    <div class="pitch">
      <h1>Almost there.</h1>
      <p>Enter the 6-digit code we sent to your email, then choose a new password. You'll be practicing again soon.</p>
      <div class="waveform-row" aria-hidden="true">
        <?php for ($i = 0; $i < 24; $i++): ?><span style="animation-delay:-<?= ($i * 0.07) ?>s"></span><?php endfor; ?>
      </div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Enter code &amp; new password</h2>
      <p class="sub">Code sent to <strong><?= h($maskedEmail) ?></strong>.</p>

      <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

      <?php if ($devOtp): ?>
        <div class="alert alert-info">
          🛠️ <strong>Dev mode:</strong> SMTP isn't configured, so here's your code for testing: <strong style="font-family:var(--font-mono); letter-spacing:.1em;"><?= h($devOtp) ?></strong>
        </div>
      <?php endif; ?>

      <form action="auth/reset_password_process.php" method="POST" id="resetForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="otp_full" id="otp_full" value="">
        <div class="otp-input-row">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" autocomplete="one-time-code" <?= $i === 0 ? 'autofocus' : '' ?>>
          <?php endfor; ?>
        </div>
        <p class="hint text-center" style="margin-bottom:20px;">Didn't get it? Check spam, or resend below.</p>

        <div class="field">
          <label for="new_password">New password</label>
          <div class="password-wrap">
            <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" minlength="6" required>
            <button type="button" class="password-toggle" data-target="new_password" aria-label="Show password">👁️</button>
          </div>
        </div>
        <div class="field">
          <label for="confirm_password">Confirm new password</label>
          <div class="password-wrap">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your new password" minlength="6" required>
            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Show password">👁️</button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Reset Password →</button>
      </form>

      <form action="auth/resend_reset_otp.php" method="POST" style="margin-top:12px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <button type="submit" class="btn btn-outline btn-block">↻ Resend Code</button>
      </form>

      <p class="form-footer-link">Wrong email? <a href="forgot_password.php">Start over</a></p>
    </div>
  </div>
</div>

<script>
  const digits = Array.from(document.querySelectorAll('.otp-digit'));
  const fullInput = document.getElementById('otp_full');

  function syncFull() {
    fullInput.value = digits.map(d => d.value).join('');
  }

  digits.forEach((d, i) => {
    d.addEventListener('input', () => {
      d.value = d.value.replace(/[^0-9]/g, '').slice(0, 1);
      if (d.value && digits[i + 1]) digits[i + 1].focus();
      syncFull();
    });
    d.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !d.value && digits[i - 1]) digits[i - 1].focus();
    });
    d.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
      text.split('').forEach((ch, idx) => { if (digits[idx]) digits[idx].value = ch; });
      syncFull();
      if (digits[Math.min(text.length, 5)]) digits[Math.min(text.length, 5)].focus();
    });
  });

  document.getElementById('resetForm').addEventListener('submit', syncFull);
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
