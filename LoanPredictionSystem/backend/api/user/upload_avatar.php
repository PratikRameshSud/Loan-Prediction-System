<?php
/**
 * upload_avatar.php
 * POST /api/user/upload_avatar
 * Accepts multipart/form-data with file field "avatar".
 * Max: 800KB, PNG/JPG only.
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
Response::requireAuth();
Response::verifyCsrf();

if (empty($_FILES['avatar'])) {
    Response::error('No file uploaded.');
}

$file      = $_FILES['avatar'];
$maxBytes  = 800 * 1024;   // 800 KB
$allowedMime = ['image/jpeg', 'image/png'];
$allowedExt  = ['jpg', 'jpeg', 'png'];

// ── Validate ───────────────────────────────────────────────────────────────
if ($file['error'] !== UPLOAD_ERR_OK) {
    Response::error('Upload error code: ' . $file['error']);
}

if ($file['size'] > $maxBytes) {
    Response::error('Avatar must be under 800KB.');
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedMime, true)) {
    Response::error('Only JPG and PNG images are allowed.');
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    Response::error('Invalid file extension.');
}

// ── Store ──────────────────────────────────────────────────────────────────
$avatarDir = BASE_PATH . '/uploads/avatars/';
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

$userId   = (int)$_SESSION['user_id'];
$newName  = 'avatar_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $avatarDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    Response::error('Failed to save the file.', 500);
}

// ── Update DB ──────────────────────────────────────────────────────────────
$userModel = new UserModel();
$userModel->updateAvatar($userId, $newName);

Response::success('Avatar updated.', ['avatar' => $newName]);
