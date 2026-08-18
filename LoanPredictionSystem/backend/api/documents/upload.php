<?php
/**
 * upload.php
 * POST /api/documents/upload
 * Secure file upload for loan verification documents.
 * Accepts multipart/form-data: file (required), loan_id (optional), doc_type (optional)
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/DocumentModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

Response::startSession();
Response::requireRole('customer');
Response::verifyCsrf();

$customerId = (int)$_SESSION['user_id'];

// ── File presence check ────────────────────────────────────────────────────
if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    Response::error('No file was uploaded.');
}

$file    = $_FILES['file'];
$loanId  = isset($_POST['loan_id'])  ? (int)$_POST['loan_id'] : null;
$docType = Response::sanitize($_POST['doc_type'] ?? 'other');

if (!in_array($docType, ['identity', 'income', 'property', 'other'], true)) {
    $docType = 'other';
}

// ── Upload error check ─────────────────────────────────────────────────────
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'No temporary folder available.',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk.',
    ];
    Response::error($uploadErrors[$file['error']] ?? 'Upload failed.');
}

// ── Size check ─────────────────────────────────────────────────────────────
if ($file['size'] > MAX_FILE_SIZE) {
    Response::error('File exceeds maximum size of 10MB.');
}

// ── MIME validation (read magic bytes, not just extension) ─────────────────
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, ALLOWED_MIME, true)) {
    Response::error('Only PDF, JPG, and PNG files are accepted.');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXT, true)) {
    Response::error('Invalid file extension.');
}

// ── Sanitize original filename (prevent path traversal) ───────────────────
$safeOriginalName = preg_replace('/[^a-zA-Z0-9_.\-]/', '_', basename($file['name']));
$safeOriginalName = substr($safeOriginalName, 0, 200);

// ── Build storage path ─────────────────────────────────────────────────────
$uploadDir = UPLOAD_PATH . '/' . $customerId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// UUID-based stored name prevents enumeration
$storedName = bin2hex(random_bytes(16)) . '.' . $ext;
$destPath   = $uploadDir . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    Response::error('Failed to store the file. Please try again.', 500);
}

// ── Persist metadata ───────────────────────────────────────────────────────
$docModel = new DocumentModel();
$docId    = $docModel->create([
    'loan_id'     => $loanId,
    'customer_id' => $customerId,
    'file_name'   => $safeOriginalName,
    'stored_name' => $storedName,
    'file_type'   => $mimeType,
    'file_size'   => $file['size'],
    'doc_type'    => $docType,
]);

// ── Notify customer ────────────────────────────────────────────────────────
$notifModel = new NotificationModel();
$notifModel->create(
    $customerId,
    'Document Uploaded',
    "Your document \"{$safeOriginalName}\" was uploaded successfully and is pending review.",
    'success',
    $loanId
);

// ── Audit ──────────────────────────────────────────────────────────────────
$userModel = new UserModel();
$userModel->logAudit($customerId, 'document_uploaded', 'documents', $docId,
    "File: {$safeOriginalName}", Response::clientIp(), $_SERVER['HTTP_USER_AGENT'] ?? '');

Response::success('Document uploaded successfully.', [
    'document_id' => $docId,
    'file_name'   => $safeOriginalName,
    'doc_type'    => $docType,
], 201);
