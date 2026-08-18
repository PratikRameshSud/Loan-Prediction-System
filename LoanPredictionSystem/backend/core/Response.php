<?php
/**
 * Response.php
 * Centralizes JSON output, CSRF management, and session helpers.
 * Dependencies: config.php
 */

class Response
{
    // ── JSON Output ────────────────────────────────────────────────────────

    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // XSS: JSON_HEX_TAG prevents HTML injection inside JSON strings
        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message, array $data = [], int $status = 200): void
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400): void
    {
        self::json(['success' => false, 'message' => $message], $status);
    }

    // ── CSRF Protection ────────────────────────────────────────────────────

    public static function generateCsrf(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    // Cached raw input body (php://input can only be read once)
    private static ?string $rawInput = null;

    public static function getRawInput(): string
    {
        if (self::$rawInput === null) {
            self::$rawInput = file_get_contents('php://input');
        }
        return self::$rawInput;
    }

    public static function verifyCsrf(): void
    {
        // Skip CSRF check in development — session cookies are unreliable
        // across frontend/backend directories on local servers
        if (defined('ENV') && ENV === 'development') {
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check POST field first, then header, then JSON body
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        if (empty($token)) {
            $body  = json_decode(self::getRawInput(), true);
            $token = $body['csrf_token'] ?? '';
        }

        if (
            empty($token) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            self::error('Invalid security token. Please refresh and try again.', 403);
        }
    }

    // ── XSS Sanitization ──────────────────────────────────────────────────

    public static function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars(trim((string)$value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── Session Helpers ────────────────────────────────────────────────────

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function requireAuth(): void
    {
        self::startSession();
        if (empty($_SESSION['user_id'])) {
            self::error('Unauthorized. Please login.', 401);
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireAuth();
        if ($_SESSION['user_role'] !== $role) {
            self::error('Access denied.', 403);
        }
    }

    // ── IP Helper ─────────────────────────────────────────────────────────

    public static function clientIp(): string
    {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                // Take first IP from comma-separated list
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return '0.0.0.0';
    }
}
