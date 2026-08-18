<?php
/**
 * register.php
 * POST /api/auth/register
 * Registers a new customer or loan officer.
 *
 * Expected POST body (JSON or form-data):
 *   fullname, email, phone, password, role, income*, credit_score*, bank_number*
 *   (* required for respective roles)
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

// ── Only POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::verifyCsrf();

// ── Parse input ────────────────────────────────────────────────────────────
$raw = Response::getRawInput();
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST;   // fall back to form submission
}

$fullname     = Response::sanitize($body['fullname']     ?? '');
$email        = Response::sanitize($body['email']        ?? '');
$phone        = Response::sanitize($body['phone']        ?? '');
$password     = $body['password']                        ?? '';   // NOT sanitized – hashed raw
$role         = Response::sanitize($body['role']         ?? 'customer');
$income       = isset($body['income'])       ? (float)$body['income']       : null;
$creditScore  = isset($body['credit_score']) ? (int)$body['credit_score']   : null;
$bankNumber   = $body['bank_number']                     ?? '';

// ── Input validation ───────────────────────────────────────────────────────
$errors = [];

if (strlen($fullname) < 2 || strlen($fullname) > 120) {
    $errors[] = 'Full name must be between 2 and 120 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
}

if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $phone))) {
    $errors[] = 'Phone number must be 10 digits.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if (!in_array($role, ['customer', 'officer'], true)) {
    $errors[] = 'Invalid account type.';
}

if ($role === 'customer') {
    if ($income === null || $income <= 0) {
        $errors[] = 'Annual income is required for customer accounts.';
    }
    if ($creditScore === null || $creditScore < 300 || $creditScore > 850) {
        $errors[] = 'Credit score must be between 300 and 850.';
    }
}

if ($role === 'officer') {
    if ($bankNumber !== OFFICER_SECRET) {
        $errors[] = 'Invalid bank authorization code.';
    }
}

if (!empty($errors)) {
    Response::error(implode(' ', $errors), 422);
}

// ── Check duplicate email ──────────────────────────────────────────────────
$userModel = new UserModel();

if ($userModel->findByEmail($email)) {
    Response::error('An account with this email already exists.', 409);
}

// ── Create user ────────────────────────────────────────────────────────────
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

try {
    $userId = $userModel->create([
        'fullname'      => $fullname,
        'email'         => $email,
        'phone'         => preg_replace('/\D/', '', $phone),
        'password_hash' => $passwordHash,
        'role'          => $role,
        'income'        => $income,
        'credit_score'  => $creditScore,
    ]);
} catch (PDOException $e) {
    // Duplicate key race condition
    Response::error('Email already registered.', 409);
}

// ── Welcome notification ───────────────────────────────────────────────────
$notifModel = new NotificationModel();
$notifModel->create(
    $userId,
    'Welcome to LoanSecure!',
    'Your account has been created successfully. You can now apply for a loan.',
    'success'
);

// ── Audit ──────────────────────────────────────────────────────────────────
$userModel->logAudit(
    $userId,
    'user_registered',
    'users',
    $userId,
    "Role: {$role}",
    Response::clientIp(),
    $_SERVER['HTTP_USER_AGENT'] ?? ''
);

Response::success('Account created successfully. Please login.', ['user_id' => $userId], 201);
