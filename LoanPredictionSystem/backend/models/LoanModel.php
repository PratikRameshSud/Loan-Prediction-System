<?php
/**
 * LoanModel.php
 * All database operations for loan_applications and ml_predictions tables.
 * Dependencies: Model.php
 */

require_once __DIR__ . '/../core/Model.php';

class LoanModel extends Model
{
    // ── Loan Number Generator ──────────────────────────────────────────────

    private function generateLoanNumber(): string
    {
        // Pad to 5 digits, prefix LN-
        $stmt = $this->fetchOne('SELECT MAX(id) AS max_id FROM loan_applications');
        $next = (int)($stmt['max_id'] ?? 0) + 1;
        return 'LN-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ── Create ─────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        $loanNumber = $this->generateLoanNumber();
        $this->execute(
            'INSERT INTO loan_applications
             (customer_id, loan_number, amount, term_months, purpose, employment_status, annual_income, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "pending")',
            [
                $data['customer_id'],
                $loanNumber,
                $data['amount'],
                $data['term_months'],
                $data['purpose'],
                $data['employment_status'],
                $data['annual_income'],
            ]
        );
        return (int)$this->lastId();
    }

    // ── Read ───────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT la.*, u.fullname AS customer_name, u.email AS customer_email,
                    u.credit_score, u.income AS profile_income
             FROM loan_applications la
             JOIN users u ON u.id = la.customer_id
             WHERE la.id = ?
             LIMIT 1',
            [$id]
        );
    }

    public function findByCustomer(int $customerId): array
    {
        return $this->fetchAll(
            'SELECT la.*, mp.risk_score, mp.approval_probability, mp.default_probability, mp.risk_label
             FROM loan_applications la
             LEFT JOIN ml_predictions mp ON mp.loan_id = la.id
             WHERE la.customer_id = ?
             ORDER BY la.created_at DESC',
            [$customerId]
        );
    }

    /** Returns all pending/under_review applications for the officer queue */
    public function getQueue(): array
    {
        return $this->fetchAll(
            'SELECT la.*, u.fullname AS customer_name, u.email AS customer_email,
                    u.credit_score, u.income AS profile_income,
                    mp.risk_score, mp.approval_probability, mp.default_probability, mp.risk_label,
                    (SELECT COUNT(*) FROM documents d WHERE d.loan_id = la.id) AS doc_count
             FROM loan_applications la
             JOIN users u ON u.id = la.customer_id
             LEFT JOIN ml_predictions mp ON mp.loan_id = la.id
             WHERE la.status IN ("pending", "under_review")
             ORDER BY la.created_at ASC'
        );
    }

    /** Returns resolved applications for officer decision history */
    public function getHistory(int $officerId): array
    {
        return $this->fetchAll(
            'SELECT la.*, u.fullname AS customer_name, u.email AS customer_email,
                    mp.risk_label
             FROM loan_applications la
             JOIN users u ON u.id = la.customer_id
             LEFT JOIN ml_predictions mp ON mp.loan_id = la.id
             WHERE la.officer_id = ? AND la.status IN ("approved","rejected","disbursed","closed")
             ORDER BY la.updated_at DESC',
            [$officerId]
        );
    }

    // ── Update ─────────────────────────────────────────────────────────────

    public function updateStatus(int $loanId, string $status, int $officerId, string $note = ''): bool
    {
        return $this->execute(
            'UPDATE loan_applications
             SET status = ?, officer_id = ?, officer_note = ?, updated_at = NOW()
             WHERE id = ?',
            [$status, $officerId, $note ?: null, $loanId]
        ) > 0;
    }

    // ── Dashboard Metrics ──────────────────────────────────────────────────

    public function getOfficerMetrics(): array
    {
        $row = $this->fetchOne(
            "SELECT
               SUM(status IN ('pending','under_review'))               AS pending,
               SUM(status = 'approved' AND DATE(updated_at) = CURDATE()) AS approved_today,
               SUM(mp.risk_label = 'high')                             AS high_risk,
               COUNT(DISTINCT la.customer_id)                          AS total_pool
             FROM loan_applications la
             LEFT JOIN ml_predictions mp ON mp.loan_id = la.id"
        );
        return [
            'pending'       => (int)($row['pending']        ?? 0),
            'approved_today'=> (int)($row['approved_today'] ?? 0),
            'high_risk'     => (int)($row['high_risk']      ?? 0),
            'total_pool'    => (int)($row['total_pool']      ?? 0),
        ];
    }

    public function getCustomerMetrics(int $customerId): array
    {
        $row = $this->fetchOne(
            "SELECT
               SUM(status IN ('pending','under_review')) AS active_count,
               MAX(CASE WHEN mp.approval_probability IS NOT NULL
                        THEN mp.approval_probability ELSE 0 END) AS approval_prob,
               u.credit_score, u.income
             FROM loan_applications la
             LEFT JOIN ml_predictions mp ON mp.loan_id = la.id
             JOIN users u ON u.id = la.customer_id
             WHERE la.customer_id = ?",
            [$customerId]
        );
        return $row ?? [];
    }

    /** Chart data: monthly volume for the past 6 months */
    public function getMonthlyVolume(): array
    {
        return $this->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%b') AS month,
                    COUNT(*) AS total
             FROM loan_applications
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY created_at ASC"
        );
    }

    /** Chart data: status distribution */
    public function getStatusDistribution(): array
    {
        return $this->fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM loan_applications
             GROUP BY status"
        );
    }

    // ── ML Prediction ──────────────────────────────────────────────────────

    public function savePrediction(int $loanId, array $prediction): bool
    {
        $label = 'low';
        if ($prediction['default_probability'] > 0.6) {
            $label = 'high';
        } elseif ($prediction['default_probability'] > 0.35) {
            $label = 'medium';
        }

        // Upsert: replace if already exists
        return $this->execute(
            'INSERT INTO ml_predictions
               (loan_id, risk_score, approval_probability, default_probability, risk_label, model_version, raw_response)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               risk_score = VALUES(risk_score),
               approval_probability = VALUES(approval_probability),
               default_probability  = VALUES(default_probability),
               risk_label           = VALUES(risk_label),
               raw_response         = VALUES(raw_response),
               predicted_at         = NOW()',
            [
                $loanId,
                round($prediction['risk_score'], 4),
                round($prediction['approval_probability'], 4),
                round($prediction['default_probability'], 4),
                $label,
                $prediction['model_version'] ?? '1.0',
                json_encode($prediction),
            ]
        ) > 0;
    }

    public function getPrediction(int $loanId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM ml_predictions WHERE loan_id = ? LIMIT 1',
            [$loanId]
        );
    }
}
