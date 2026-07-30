<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
}

if (empty($_SESSION['otp_pending_email'])) {
    setFlash('error', 'No pending registration found. Please sign up again.');
    redirect('register.php');
}

$email = $_SESSION['otp_pending_email'];
$devOtp = $_SESSION['otp_dev_fallback'] ?? null;

// mask the email for display, e.g. em***@musictrack.com
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
      <h1>Verify your email.</h1>
      <p>We've sent a 6-digit code to your email. Enter it below to continue.</p>
      <div class="waveform-row" aria-hidden="true">
        <?php for ($i = 0; $i < 24; $i++): ?><span style="animation-delay:-<?= ($i * 0.07) ?>s"></span><?php endfor; ?>
      </div>
    </div>
    <div class="stat-strip">
      <div class="stat"><b>6</b><span>Digit Code</span></div>
      <div class="stat"><b>10</b><span>Minutes Valid</span></div>
      <div class="stat"><b>🔒</b><span>Secure Sign-up</span></div>
    </div>
  </div>

  <div class="auth-form-wrap">
    <div class="auth-card">
      <h2>Enter verification code</h2>
      <p class="sub">We sent a 6-digit code to <strong><?= h($maskedEmail) ?></strong>.</p>

      <?php if ($flash): ?><div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

      <?php if ($devOtp): ?>
        <div class="alert alert-info">
          🛠️ <strong>Dev mode:</strong> SMTP isn't configured, so here's your code for testing: <strong style="font-family:var(--font-mono); letter-spacing:.1em;"><?= h($devOtp) ?></strong>
        </div>
      <?php endif; ?>

      <form action="auth/verify_otp_process.php" method="POST" id="otpForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <input type="hidden" name="otp_full" id="otp_full" value="">
        <div class="otp-input-row">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-digit" autocomplete="one-time-code" <?= $i === 0 ? 'autofocus' : '' ?>>
          <?php endfor; ?>
        </div>
        <p class="hint text-center" style="margin-bottom:20px;">Didn't get it? Check spam, or resend below.</p>
        <button type="submit" class="btn btn-primary btn-block">Verify &amp; Create Account →</button>
      </form>

      <form action="auth/resend_otp.php" method="POST" style="margin-top:12px;">
        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
        <button type="submit" class="btn btn-outline btn-block">↻ Resend Code</button>
      </form>

      <p class="form-footer-link">Wrong email? <a href="register.php">Start over</a></p>
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

  document.getElementById('otpForm').addEventListener('submit', syncFull);
</script>
</body>
</html>
