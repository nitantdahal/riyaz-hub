<?php
/**
 * Smart Music Practice Tracking System
 * Database connection (PDO / MySQL)
 *
 * Update the constants below to match your local MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'riyaz_hub');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;max-width:560px;margin:60px auto;padding:32px;color:#241b3d;background:#fff;border:1px solid rgba(76,61,122,.18);border-radius:16px;box-shadow:0 4px 20px -8px rgba(60,40,110,.12);">
                    <h2 style="margin-top:0;">⚠️ Database Connection Failed</h2>
                    <p>Please make sure MySQL is running and that you have imported <code>database/schema.sql</code>.</p>
                    <p style="opacity:.65">Details: ' . htmlspecialchars($e->getMessage()) . '</p>
                 </div>');
        }
    }
    return $pdo;
}
