<?php
/**
 * apply.php
 * POST /api/loans/apply
 * Submits a new loan application, triggers ML prediction, sends notifications.
 *
 * Body: { amount, term_months, purpose, employment_status, annual_income, csrf_token }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/LoanModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/NotificationModel.php';
require_once __DIR__ . '/../../services/MLService.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::requireRole('customer');
Response::verifyCsrf();

$userId = (int)$_SESSION['user_id'];

// ── Parse ──────────────────────────────────────────────────────────────────
$raw  = Response::getRawInput();
$body = json_decode($raw, true) ?: $_POST;

$amount           = isset($body['amount'])           ? (float)$body['amount']           : 0;
$termMonths       = isset($body['term_months'])      ? (int)$body['term_months']         : 0;
$purpose          = Response::sanitize($body['purpose']          ?? '');
$employmentStatus = Response::sanitize($body['employment_status'] ?? '');
$annualIncome     = isset($body['annual_income'])    ? (float)$body['annual_income']    : 0;

// ── Validate ───────────────────────────────────────────────────────────────
$errors = [];
if ($amount < 1000 || $amount > 1000000) {
    $errors[] = 'Loan amount must be between $1,000 and $1,000,000.';
}
if (!in_array($termMonths, [12, 24, 36, 60], true)) {
    $errors[] = 'Invalid loan term. Choose 12, 24, 36, or 60 months.';
}
if (!$purpose) {
    $errors[] = 'Loan purpose is required.';
}
if (!$employmentStatus) {
    $errors[] = 'Employment status is required.';
}
if ($annualIncome <= 0) {
    $errors[] = 'Annual income must be greater than zero.';
}

if (!empty($errors)) {
    Response::error(implode(' ', $errors), 422);
}

// ── Fetch customer profile for ML features ─────────────────────────────────
$userModel = new UserModel();
$customer  = $userModel->findById($userId);
if (!$customer) {
    Response::error('Customer profile not found.', 404);
}

// ── Save loan application ──────────────────────────────────────────────────
$loanModel = new LoanModel();
$loanId    = $loanModel->create([
    'customer_id'       => $userId,
    'amount'            => $amount,
    'term_months'       => $termMonths,
    'purpose'           => $purpose,
    'employment_status' => $employmentStatus,
    'annual_income'     => $annualIncome,
]);

// ── Trigger ML prediction ──────────────────────────────────────────────────
$mlFeatures = [
    'amount'            => $amount,
    'term_months'       => $termMonths,
    'annual_income'     => $annualIncome,
    'credit_score'      => (int)($customer['credit_score'] ?? 600),
    'employment_status' => $employmentStatus,
    'purpose'           => $purpose,
];

$mlService  = new MLService();
$prediction = $mlService->predict($mlFeatures);

if ($prediction) {
    $loanModel->savePrediction($loanId, $prediction);
}

// ── Notifications ──────────────────────────────────────────────────────────
$notifModel = new NotificationModel();
$loanRecord = $loanModel->findById($loanId);
$loanNumber = $loanRecord['loan_number'] ?? "#LN-{$loanId}";

// Notify customer
$notifModel->create(
    $userId,
    'Application Submitted',
    "Your loan application {$loanNumber} for $" . number_format($amount, 2) . " has been submitted and is under review.",
    'info',
    $loanId
);

// Notify all officers
$pdo = Database::getInstance();
$officers = $pdo->query("SELECT id FROM users WHERE role = 'officer' AND is_active = 1")->fetchAll();
foreach ($officers as $officer) {
    $notifModel->create(
        (int)$officer['id'],
        'New Loan Application',
        "Customer {$customer['fullname']} submitted application {$loanNumber} for $" . number_format($amount, 2) . ".",
        'info',
        $loanId
    );
}

// ── Audit ──────────────────────────────────────────────────────────────────
$userModel->logAudit($userId, 'loan_applied', 'loan_applications', $loanId,
    "Amount: {$amount}, Term: {$termMonths}m", Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '');

Response::success('Loan application submitted successfully.', [
    'loan_id'     => $loanId,
    'loan_number' => $loanNumber,
    'prediction'  => $prediction,
], 201);
