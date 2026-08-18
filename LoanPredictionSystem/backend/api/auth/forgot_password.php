<?php
/**
 * forgot_password.php
 * POST /api/auth/forgot_password
 * Generates a secure reset token (emails in production; returns token in dev).
 *
 * Body: { email }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/UserModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::verifyCsrf();

$raw  = Response::getRawInput();
$body = json_decode($raw, true) ?: $_POST;
$email = Response::sanitize($body['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Valid email is required.');
}

$userModel = new UserModel();
$user      = $userModel->findByEmail($email);

// Always return success to prevent email enumeration
if (!$user) {
    Response::success('If that email exists, a reset link has been sent.');
}

// Generate token
$plainToken = bin2hex(random_bytes(32));
$tokenHash  = hash('sha256', $plainToken);

$userModel->createResetToken($user['id'], $tokenHash);

$resetUrl = 'http://localhost/loansecure/frontend/reset_password.html?token=' . $plainToken;

// ── In production: use PHPMailer / mail() here ─────────────────────────────
// mail($email, 'Password Reset', 'Click: ' . $resetUrl);

// ── Dev: return token in response so you can test without SMTP ────────────
$responseData = [];
if (ENV === 'development') {
    $responseData['reset_url'] = $resetUrl;
}

$userModel->logAudit(
    $user['id'], 'password_reset_requested', 'users', $user['id'],
    '', Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? ''
);

Response::success('If that email exists, a reset link has been sent.', $responseData);
