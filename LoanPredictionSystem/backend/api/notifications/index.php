<?php
/**
 * index.php
 * GET  /api/notifications/         – list notifications
 * POST /api/notifications/?action=read_all   – mark all read
 * POST /api/notifications/?action=read_one   – mark one read (body: { id })
 */

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../models/NotificationModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

Response::startSession();
Response::requireAuth();

$userId      = (int)$_SESSION['user_id'];
$notifModel  = new NotificationModel();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

if ($method === 'GET') {
    $notifications = $notifModel->getByUser($userId);
    $unreadCount   = $notifModel->getUnreadCount($userId);
    Response::success('ok', [
        'notifications' => $notifications,
        'unread_count'  => $unreadCount,
    ]);
}

if ($method === 'POST') {
    Response::verifyCsrf();

    if ($action === 'read_all') {
        $notifModel->markAllRead($userId);
        Response::success('All notifications marked as read.');
    }

    if ($action === 'read_one') {
        $raw  = Response::getRawInput();
        $body = json_decode($raw, true) ?: $_POST;
        $id   = isset($body['id']) ? (int)$body['id'] : 0;
        if (!$id) {
            Response::error('Notification ID required.');
        }
        $notifModel->markOneRead($id, $userId);
        Response::success('Notification marked as read.');
    }

    Response::error('Unknown action.', 400);
}

Response::error('Method not allowed.', 405);
