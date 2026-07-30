<?php
/**
 * SMTP configuration for sending registration OTP emails.
 *
 * Fill these in with your own mail provider's details, e.g.:
 *   - Gmail:     smtp.gmail.com, port 587, TLS, an "App Password" (not your normal password)
 *   - Mailtrap (great for local testing): sandbox.smtp.mailtrap.io, port 2525
 *   - SendGrid / Mailgun / your host's SMTP — check their docs for exact values
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');       // 'tls' or 'ssl'
define('SMTP_USERNAME', 'nitantdahal123@gmail.com');
define('SMTP_PASSWORD', 'kvshkcdvxtywdzoq');

define('MAIL_FROM_ADDRESS', 'nitantdahal123@gmail.com');
define('MAIL_FROM_NAME', 'Riyaz Hub');

/**
 * Developer fallback: if SMTP credentials above are still placeholders
 * (or sending fails for any reason), the OTP is shown directly on the
 * verification page instead of blocking sign-up, and logged to
 * includes/../uploads/otp_log.txt.
 *
 * ⚠️ Set this to false once real SMTP credentials are configured for
 * a production deployment — showing OTPs on screen is for local
 * testing/demo purposes only.
 */
define('MAIL_DEV_FALLBACK', true);
