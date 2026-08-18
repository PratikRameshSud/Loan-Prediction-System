<?php
/**
 * change_password.php
 * POST /api/user/change_password
 * Allows a logged-in user to change their password.
 *
 * Body: { current_password, new_password, csrf_token }
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
Response::requireAuth();
Response::verifyCsrf();

$userId    = (int)$_SESSION['user_id'];
$raw       = Response::getRawInput();
$body      = json_decode($raw, true) ?: $_POST;

$currentPassword = $body['current_password'] ?? '';
$newPassword     = $body['new_password']     ?? '';

if (!$currentPassword || !$newPassword) {
    Response::error('Both current and new passwords are required.');
}

if (strlen($newPassword) < 8) {
    Response::error('New password must be at least 8 characters.');
}

$userModel = new UserModel();

// Fetch full row to get hash
$user = $userModel->fetchOne(
    'SELECT * FROM users WHERE id = ? LIMIT 1',
    [$userId]
);

// Expose findById to include hash (internal helper via direct PDO in model)
// We'll do it by accessing the protected query via a public wrapper
$pdo  = Database::getInstance();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$row  = $stmt->fetch();

if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
    Response::error('Current password is incorrect.', 401);
}

if ($currentPassword === $newPassword) {
    Response::error('New password must differ from the current password.');
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
$userModel->updatePassword($userId, $newHash);

$userModel->logAudit($userId, 'password_changed', 'users', $userId,
    '', Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '');

Response::success('Password changed successfully.');
