<?php
/**
 * NotificationModel.php
 * Database operations for the notifications table.
 * Dependencies: Model.php
 */

require_once __DIR__ . '/../core/Model.php';

class NotificationModel extends Model
{
    public function create(int $userId, string $title, string $message, string $type = 'info', ?int $loanId = null): int
    {
        $this->execute(
            'INSERT INTO notifications (user_id, title, message, type, loan_id) VALUES (?, ?, ?, ?, ?)',
            [$userId, $title, $message, $type, $loanId]
        );
        return (int)$this->lastId();
    }

    public function getByUser(int $userId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public function getUnreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function markAllRead(int $userId): void
    {
        $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?',
            [$userId]
        );
    }

    public function markOneRead(int $notifId, int $userId): void
    {
        $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$notifId, $userId]
        );
    }
}
