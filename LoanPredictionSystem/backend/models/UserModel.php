<?php
/**
 * UserModel.php
 * All database operations for the users table.
 * Dependencies: Model.php
 */

require_once __DIR__ . '/../core/Model.php';

class UserModel extends Model
{
    // ── Read ───────────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
            [strtolower(trim($email))]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, fullname, email, phone, role, avatar, income, credit_score, address, created_at
             FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$id]
        );
    }

    // ── Create ─────────────────────────────────────────────────────────────

    /**
     * Register a new user.
     * Returns the new user ID or throws on duplicate email.
     */
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO users (fullname, email, phone, password_hash, role, income, credit_score)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['fullname'],
                strtolower(trim($data['email'])),
                $data['phone'],
                $data['password_hash'],
                $data['role'],
                $data['income']       ?? null,
                $data['credit_score'] ?? null,
            ]
        );
        return (int)$this->lastId();
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function updateProfile(int $id, array $data): bool
    {
        $rows = $this->execute(
            'UPDATE users SET fullname = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
             WHERE id = ?',
            [$data['fullname'], strtolower($data['email']), $data['phone'], $data['address'], $id]
        );
        return $rows > 0;
    }

    public function updatePassword(int $id, string $hash): bool
    {
        return $this->execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$hash, $id]
        ) > 0;
    }

    public function updateAvatar(int $id, string $filename): bool
    {
        return $this->execute(
            'UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?',
            [$filename, $id]
        ) > 0;
    }

    // ── Password Reset ─────────────────────────────────────────────────────

    public function createResetToken(int $userId, string $tokenHash): bool
    {
        // Invalidate old tokens first
        $this->execute(
            'UPDATE password_resets SET used = 1 WHERE user_id = ?',
            [$userId]
        );
        return $this->execute(
            'INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
            [$userId, $tokenHash]
        ) > 0;
    }

    public function findValidResetToken(string $tokenHash): ?array
    {
        return $this->fetchOne(
            'SELECT pr.*, u.email FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND pr.used = 0 AND pr.expires_at > NOW()
             LIMIT 1',
            [$tokenHash]
        );
    }

    public function markResetTokenUsed(int $resetId): void
    {
        $this->execute(
            'UPDATE password_resets SET used = 1 WHERE id = ?',
            [$resetId]
        );
    }

    // ── Audit ──────────────────────────────────────────────────────────────

    public function logAudit(
        ?int   $userId,
        string $action,
        string $entityType = '',
        int    $entityId   = 0,
        string $detail     = '',
        string $ip         = '',
        string $userAgent  = ''
    ): void {
        $this->execute(
            'INSERT INTO audit_log (user_id, action, entity_type, entity_id, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $action, $entityType ?: null, $entityId ?: null, $detail ?: null, $ip ?: null, $userAgent ?: null]
        );
    }
}
