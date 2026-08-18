<?php
/**
 * list.php
 * GET /api/documents/list
 * Customer: returns their own documents.
 * Officer: returns all documents across all customers.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/DocumentModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::requireAuth();

$userId  = (int)$_SESSION['user_id'];
$role    = $_SESSION['user_role'];
$docModel = new DocumentModel();

if ($role === 'officer') {
    $docs = $docModel->getAll();
} else {
    $docs = $docModel->getByCustomer($userId);
}

Response::success('ok', ['documents' => $docs]);
