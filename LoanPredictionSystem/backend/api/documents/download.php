<?php
/**
 * download.php
 * GET /api/documents/download?id=X
 * Streams a document file to authorized users.
 * Officers can view any doc; customers only their own.
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/DocumentModel.php';

Response::startSession();
Response::requireAuth();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['user_role'];

if (!$id) {
    http_response_code(400);
    exit('Invalid document ID.');
}

$docModel = new DocumentModel();
$doc      = $docModel->findById($id);

if (!$doc) {
    http_response_code(404);
    exit('Document not found.');
}

// Ownership guard for customers
if ($role === 'customer' && (int)$doc['customer_id'] !== $userId) {
    http_response_code(403);
    exit('Access denied.');
}

$filePath = UPLOAD_PATH . '/' . $doc['customer_id'] . '/' . $doc['stored_name'];
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found on disk.');
}

// Officer: mark document verified when they view it
if ($role === 'officer') {
    $docModel->markVerified($id);
}

// ── Stream file ────────────────────────────────────────────────────────────
header('Content-Type: '          . $doc['file_type']);
header('Content-Disposition: inline; filename="' . addslashes($doc['file_name']) . '"');
header('Content-Length: '        . $doc['file_size']);
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
