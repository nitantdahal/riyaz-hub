<?php
/**
 * Mailer helper — sends the registration OTP email via SMTP (PHPMailer).
 * Falls back to on-screen / logged OTP in dev mode if SMTP isn't configured
 * or sending fails, so the app is still testable without a real mailbox.
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * @return array{success:bool, dev_fallback:bool, error:?string}
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): array {
    $looksUnconfigured = (
    trim(SMTP_USERNAME) === '' ||
    trim(SMTP_PASSWORD) === ''
);

    logOtpForDev($toEmail, $otp); // always keep a local record, handy while testing

    if ($looksUnconfigured) {
        if (MAIL_DEV_FALLBACK) {
            return ['success' => true, 'dev_fallback' => true, 'error' => null];
        }
        return ['success' => false, 'dev_fallback' => false, 'error' => 'Mail server is not configured yet. Please contact the site administrator.'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 12;

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "RiyazHub - Email Verification OTP";
        $mail->Body    = otpEmailHtml($toName, $otp);
        $mail->AltBody = "Hi $toName,\n\nYour RiyazHub verification code is: $otp\n\nThis code expires in 2 minutes.\n\nIf you didn't request this, you can ignore this email.";

        $mail->send();
        return ['success' => true, 'dev_fallback' => false, 'error' => null];
    } catch (PHPMailerException $e) {
        if (MAIL_DEV_FALLBACK) {
            return [
                'success' => true,
                'dev_fallback' => true,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'dev_fallback' => false,
            'error' => $mail->ErrorInfo
        ];
    }
}

function otpEmailHtml(string $name, string $otp): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return '
    <div style="font-family:Arial,sans-serif; background:#f6f4fc; padding:32px;">
      <div style="max-width:440px; margin:0 auto; background:#ffffff; border-radius:16px; padding:32px; border:1px solid #ece7f7;">
        <div style="font-size:20px; font-weight:700; color:#241b3d; margin-bottom:6px;">RiyazHub</div>
        <p style="color:#5c5478; font-size:14px;">Hi ' . $safeName . ',</p>
        <p style="color:#5c5478; font-size:14px;">Use the verification code below to finish creating your student account:</p>
        <div style="font-family:monospace; font-size:32px; font-weight:700; letter-spacing:8px; color:#d9820a; background:#fdf3e4; border-radius:12px; padding:16px; text-align:center; margin:20px 0;">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</div>
        <p style="color:#8c85a6; font-size:13px;">This code expires in 2 minutes. If you didn\'t request it, you can safely ignore this email.</p>
      </div>
    </div>';
}

/**
 * @return array{success:bool, dev_fallback:bool, error:?string}
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $otp): array {
    $looksUnconfigured = (
    trim(SMTP_USERNAME) === '' ||
    trim(SMTP_PASSWORD) === ''
);

    logOtpForDev($toEmail, $otp);

    if ($looksUnconfigured) {
        if (MAIL_DEV_FALLBACK) {
            return ['success' => true, 'dev_fallback' => true, 'error' => null];
        }
        return ['success' => false, 'dev_fallback' => false, 'error' => 'Mail server is not configured yet. Please contact the site administrator.'];
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 12;

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "RiyazHub - Password Reset OTP";
        $mail->Body    = resetEmailHtml($toName, $otp);
        $mail->AltBody = "Hi $toName,\n\nYour RiyazHub password reset code is: $otp\n\nThis code expires in 2 minutes. If you didn't request this, you can ignore this email.";

        $mail->send();
        return ['success' => true, 'dev_fallback' => false, 'error' => null];
    } catch (PHPMailerException $e) {
        if (MAIL_DEV_FALLBACK) {
            return [
                'success' => true,
                'dev_fallback' => true,
                'error' => null
            ];
        }

        return [
            'success' => false,
            'dev_fallback' => false,
            'error' => $mail->ErrorInfo
        ];
    }
}

function resetEmailHtml(string $name, string $otp): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return '
    <div style="font-family:Arial,sans-serif; background:#f6f4fc; padding:32px;">
      <div style="max-width:440px; margin:0 auto; background:#ffffff; border-radius:16px; padding:32px; border:1px solid #ece7f7;">
        <div style="font-size:20px; font-weight:700; color:#241b3d; margin-bottom:6px;">RiyazHub</div>
        <p style="color:#5c5478; font-size:14px;">Hi ' . $safeName . ',</p>
        <p style="color:#5c5478; font-size:14px;">Use the code below to reset your password:</p>
        <div style="font-family:monospace; font-size:32px; font-weight:700; letter-spacing:8px; color:#d43e8f; background:#fdeef6; border-radius:12px; padding:16px; text-align:center; margin:20px 0;">' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</div>
        <p style="color:#8c85a6; font-size:13px;">This code expires in 2 minutes. If you didn\'t request a password reset, you can safely ignore this email — your password will stay the same.</p>
      </div>
    </div>';
}

/** Keep a local, append-only record of issued OTPs — useful during local development. */
function logOtpForDev(string $email, string $otp): void {
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . "] $email -> $otp\n";
    @file_put_contents($dir . '/otp_log.txt', $line, FILE_APPEND);
}