<?php
/**
 * csrf.php
 * GET /api/auth/csrf
 * Returns a fresh CSRF token. Called once on page load by every form page.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';

header('Content-Type: application/json; charset=utf-8');

Response::startSession();

$token = Response::generateCsrf();

Response::success('ok', ['csrf_token' => $token]);
