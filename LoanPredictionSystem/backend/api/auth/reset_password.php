<?php
/**
 * reset_password.php
 * POST /api/auth/reset_password
 * Validates token and updates password.
 *
 * Body: { token, password }
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

$raw       = Response::getRawInput();
$body      = json_decode($raw, true) ?: $_POST;
$plainToken = $body['token']    ?? '';
$password   = $body['password'] ?? '';

if (!$plainToken || strlen($password) < 8) {
    Response::error('Token and a password of at least 8 characters are required.');
}

$tokenHash = hash('sha256', $plainToken);
$userModel = new UserModel();
$reset     = $userModel->findValidResetToken($tokenHash);

if (!$reset) {
    Response::error('This reset link is invalid or has expired.', 400);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
$userModel->updatePassword((int)$reset['user_id'], $hash);
$userModel->markResetTokenUsed((int)$reset['id']);

$userModel->logAudit(
    (int)$reset['user_id'], 'password_reset_completed', 'users',
    (int)$reset['user_id'], '', Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? ''
);

Response::success('Password updated successfully. You can now login.');
