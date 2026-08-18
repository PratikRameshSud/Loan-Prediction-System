<?php
/**
 * profile.php
 * GET  /api/user/profile  – fetch logged-in user's data
 * POST /api/user/profile  – update profile fields
 *
 * POST Body: { fullname, email, phone, address, csrf_token }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/UserModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

Response::startSession();
Response::requireAuth();

$userId    = (int)$_SESSION['user_id'];
$userModel = new UserModel();

// ── GET: return profile ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = $userModel->findById($userId);
    if (!$user) {
        Response::error('User not found.', 404);
    }
    Response::success('ok', ['user' => $user]);
}

// ── POST: update profile ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Response::verifyCsrf();

    $raw  = Response::getRawInput();
    $body = json_decode($raw, true) ?: $_POST;

    $fullname = Response::sanitize($body['fullname'] ?? '');
    $email    = Response::sanitize($body['email']    ?? '');
    $phone    = Response::sanitize($body['phone']    ?? '');
    $address  = Response::sanitize($body['address']  ?? '');

    if (!$fullname || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Response::error('Full name and a valid email are required.');
    }

    // Ensure email isn't taken by another user
    $existing = $userModel->findByEmail($email);
    if ($existing && (int)$existing['id'] !== $userId) {
        Response::error('That email is already in use by another account.', 409);
    }

    $userModel->updateProfile($userId, [
        'fullname' => $fullname,
        'email'    => $email,
        'phone'    => $phone,
        'address'  => $address,
    ]);

    // Keep session name in sync
    $_SESSION['user_name']  = $fullname;
    $_SESSION['user_email'] = $email;

    $userModel->logAudit($userId, 'profile_updated', 'users', $userId,
        '', Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '');

    Response::success('Profile updated successfully.');
}

Response::error('Method not allowed.', 405);
