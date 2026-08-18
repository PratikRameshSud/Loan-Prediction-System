<?php
/**
 * decide.php
 * POST /api/loans/decide
 * Officer approves or rejects a loan application.
 *
 * Body: { loan_id, decision (approved|rejected), note, csrf_token }
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/LoanModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::requireRole('officer');
Response::verifyCsrf();

$officerId = (int)$_SESSION['user_id'];

$raw    = Response::getRawInput();
$body   = json_decode($raw, true) ?: $_POST;

$loanId   = isset($body['loan_id'])  ? (int)$body['loan_id'] : 0;
$decision = Response::sanitize($body['decision'] ?? '');
$note     = Response::sanitize($body['note']     ?? '');

if (!$loanId) {
    Response::error('Loan ID is required.');
}
if (!in_array($decision, ['approved', 'rejected'], true)) {
    Response::error('Decision must be "approved" or "rejected".');
}

// ── Fetch loan ─────────────────────────────────────────────────────────────
$loanModel = new LoanModel();
$loan      = $loanModel->findById($loanId);

if (!$loan) {
    Response::error('Loan application not found.', 404);
}

if (!in_array($loan['status'], ['pending', 'under_review'], true)) {
    Response::error('This application has already been resolved.', 409);
}

// ── Update status ──────────────────────────────────────────────────────────
$loanModel->updateStatus($loanId, $decision, $officerId, $note);

// ── Notify customer ────────────────────────────────────────────────────────
$notifModel  = new NotificationModel();
$loanNumber  = $loan['loan_number'];
$amount      = '$' . number_format((float)$loan['amount'], 2);

if ($decision === 'approved') {
    $title   = 'Loan Approved!';
    $message = "Congratulations! Your loan application {$loanNumber} for {$amount} has been approved.";
    $type    = 'success';
} else {
    $title   = 'Loan Application Update';
    $message = "Your loan application {$loanNumber} for {$amount} was not approved at this time.";
    if ($note) {
        $message .= " Officer note: {$note}";
    }
    $type = 'danger';
}

$notifModel->create((int)$loan['customer_id'], $title, $message, $type, $loanId);

// ── Audit ──────────────────────────────────────────────────────────────────
$userModel = new UserModel();
$userModel->logAudit(
    $officerId,
    "loan_{$decision}",
    'loan_applications',
    $loanId,
    "Decision: {$decision}. Note: {$note}",
    Response::clientIp(),
    $_SERVER['HTTP_USER_AGENT'] ?? ''
);

Response::success("Application {$loanNumber} has been {$decision}.", [
    'loan_id'  => $loanId,
    'decision' => $decision,
]);
