<?php
/**
 * queue.php
 * GET /api/loans/queue
 * Returns all pending/under-review applications for the officer dashboard.
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
Response::requireRole('officer');

$loanModel = new LoanModel();
$queue     = $loanModel->getQueue();
$metrics   = $loanModel->getOfficerMetrics();
$monthly   = $loanModel->getMonthlyVolume();
$dist      = $loanModel->getStatusDistribution();

Response::success('ok', [
    'queue'        => $queue,
    'metrics'      => $metrics,
    'monthly'      => $monthly,
    'distribution' => $dist,
]);
