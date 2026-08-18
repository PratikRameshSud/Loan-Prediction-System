<?php
/**
 * status.php
 * GET /api/loans/status
 * Returns the current customer's loan applications with statuses and predictions.
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
Response::requireRole('customer');

$customerId = (int)$_SESSION['user_id'];
$loanModel  = new LoanModel();

$loans   = $loanModel->findByCustomer($customerId);
$metrics = $loanModel->getCustomerMetrics($customerId);

Response::success('ok', [
    'loans'   => $loans,
    'metrics' => $metrics,
]);
