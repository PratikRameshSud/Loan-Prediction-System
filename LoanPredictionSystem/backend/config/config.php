<?php
/**
 * config.php
 * Global application constants and settings.
 * Dependencies: none – included by every backend entry point.
 */

// ── Environment ────────────────────────────────────────────────────────────
define('ENV', 'development');   // 'production' in live deployment

// ── Paths ──────────────────────────────────────────────────────────────────
define('BASE_PATH',    dirname(__DIR__));                 // /backend
define('UPLOAD_PATH',  BASE_PATH . '/uploads/documents'); // file storage
define('ML_SCRIPT',    BASE_PATH . '/ml/predict.py');     // Python predictor

// ── Security ───────────────────────────────────────────────────────────────
define('BCRYPT_COST',       12);
define('SESSION_LIFETIME',  7200);           // 2 hours in seconds
define('CSRF_TOKEN_LENGTH', 32);

// ── Loan officer secret code (must match what register.html sends) ─────────
// Change this to a strong random string in production.
define('OFFICER_SECRET', 'BANK-STAFF-2024');

// ── File upload constraints ────────────────────────────────────────────────
define('MAX_FILE_SIZE',    10 * 1024 * 1024);   // 10 MB
define('ALLOWED_MIME',     ['application/pdf', 'image/jpeg', 'image/png']);
define('ALLOWED_EXT',      ['pdf', 'jpg', 'jpeg', 'png']);

// ── Python interpreter path (XAMPP Windows default) ───────────────────────
define('PYTHON_BIN', 'python');   // or full path e.g. 'C:/Python311/python.exe'

// ── Error display (off in production) ─────────────────────────────────────
if (ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Session hardening ──────────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', ENV === 'production' ? 'Strict' : 'Lax');
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
