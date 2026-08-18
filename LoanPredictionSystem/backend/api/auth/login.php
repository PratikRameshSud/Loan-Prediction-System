<?php
/**
 * login.php
 * POST /api/auth/login
 * Authenticates a customer or officer and sets a secure PHP session.
 *
 * Body: { email, password, role }
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

// ── Parse ──────────────────────────────────────────────────────────────────
$raw  = Response::getRawInput();
$body = json_decode($raw, true) ?: $_POST;

$email    = Response::sanitize($body['email']    ?? '');
$password = $body['password']                    ?? '';
$role     = Response::sanitize($body['role']     ?? '');

if (!$email || !$password || !$role) {
    Response::error('Email, password, and role are required.');
}

if (!in_array($role, ['customer', 'officer'], true)) {
    Response::error('Invalid role.');
}

// ── Look up user ───────────────────────────────────────────────────────────
$userModel = new UserModel();
$user      = $userModel->findByEmail($email);

// Constant-time failure: verify even if user not found (prevents timing attacks)
$dummyHash = '$2y$12$invalidhashthatisinvalidandfails00000000000000000000000';
$hashToVerify = $user['password_hash'] ?? $dummyHash;

if (!$user || !password_verify($password, $hashToVerify)) {
    Response::error('Invalid email or password.', 401);
}

// ── Role guard ─────────────────────────────────────────────────────────────
if ($user['role'] !== $role) {
    Response::error('Incorrect portal assignment for this account.', 403);
}

// ── Regenerate session to prevent fixation ─────────────────────────────────
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_role']  = $user['role'];
$_SESSION['user_name']  = $user['fullname'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['last_active']= time();

// ── Audit ──────────────────────────────────────────────────────────────────
$userModel->logAudit(
    $user['id'],
    'user_login',
    'users',
    $user['id'],
    '',
    Response::clientIp(),
    $_SERVER['HTTP_USER_AGENT'] ?? ''
);

// ── Build base URL for redirect (project root relative to docroot) ────────
// e.g. /LoanPredictionSystem
$scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // /LoanPredictionSystem/backend/api/auth
$projectRoot = preg_replace('#/backend/api/auth$#', '', $scriptDir);    // /LoanPredictionSystem

// ── Respond with safe user data (never send password_hash) ────────────────
Response::success('Login successful.', [
    'user' => [
        'id'           => $user['id'],
        'fullname'     => $user['fullname'],
        'email'        => $user['email'],
        'role'         => $user['role'],
        'avatar'       => $user['avatar'],
        'credit_score' => $user['credit_score'],
        'income'       => $user['income'],
    ],
    'redirect' => $user['role'] === 'officer'
        ? $projectRoot . '/frontend/loan_officer_dashboard.html'
        : $projectRoot . '/frontend/customer_dashboard.html',
]);
