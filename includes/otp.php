<?php
/**
 * OTP (one-time password) helpers for email-verified student registration.
 */

const OTP_TTL_MINUTES = 2;
const OTP_MAX_ATTEMPTS = 5;

function generateOtpCode(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Create (or replace) a pending registration + OTP for this email.
 * Returns the plaintext OTP code that was generated.
 */
function createPendingRegistration(PDO $db, string $email, string $fullName, string $passwordHash, ?int $instrumentId, ?int $instructorId): string {
    // Remove any previous pending attempts for this email first
    $db->prepare('DELETE FROM otp_verifications WHERE email = ?')->execute([$email]);

    $otp = generateOtpCode();
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60);

    $stmt = $db->prepare('INSERT INTO otp_verifications (email, otp_code, full_name, password_hash, instrument_id, instructor_id, attempts, expires_at)
                           VALUES (?, ?, ?, ?, ?, ?, 0, ?)');
    $stmt->execute([$email, $otp, $fullName, $passwordHash, $instrumentId, $instructorId, $expiresAt]);

    return $otp;
}

function getPendingRegistration(PDO $db, string $email) {
    $stmt = $db->prepare('SELECT * FROM otp_verifications WHERE email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function deletePendingRegistration(PDO $db, string $email): void {
    $db->prepare('DELETE FROM otp_verifications WHERE email = ?')->execute([$email]);
}

function incrementOtpAttempts(PDO $db, int $id): void {
    $db->prepare('UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?')->execute([$id]);
}

/* =====================================================================
   Forgot-password OTP helpers (separate table: password_resets)
   ===================================================================== */

function createPasswordResetOtp(PDO $db, string $email): string {
    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

    $otp = generateOtpCode();
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60);

    $stmt = $db->prepare('INSERT INTO password_resets (email, otp_code, attempts, expires_at) VALUES (?, ?, 0, ?)');
    $stmt->execute([$email, $otp, $expiresAt]);

    return $otp;
}

function getPasswordReset(PDO $db, string $email) {
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function deletePasswordReset(PDO $db, string $email): void {
    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
}

function incrementResetAttempts(PDO $db, int $id): void {
    $db->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?')->execute([$id]);
}
