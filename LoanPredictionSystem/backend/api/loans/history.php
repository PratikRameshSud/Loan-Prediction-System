<?php
/**
 * history.php
 * GET /api/loans/history
 * Returns officer's resolved decision log or customer's closed loan history.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/LoanModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::requireAuth();

$userId    = (int)$_SESSION['user_id'];
$role      = $_SESSION['user_role'];
$loanModel = new LoanModel();

if ($role === 'officer') {
    $records = $loanModel->getHistory($userId);
} else {
    // Customer: return all their loans (status panel + history combined)
    $records = $loanModel->findByCustomer($userId);
}

Response::success('ok', ['records' => $records]);
