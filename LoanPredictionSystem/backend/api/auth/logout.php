<?php
/**
 * logout.php
 * POST /api/auth/logout
 * Destroys session and cookie.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';

header('Content-Type: application/json; charset=utf-8');

Response::startSession();

// Destroy session data
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

$scriptDir   = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$projectRoot = preg_replace('#/backend/api/auth$#', '', $scriptDir);

Response::success('Logged out successfully.', ['redirect' => $projectRoot . '/frontend/login.html']);
