<?php
/**
 * DocumentModel.php
 * Database operations for file upload records.
 * Dependencies: Model.php
 */

require_once __DIR__ . '/../core/Model.php';

class DocumentModel extends Model
{
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO documents (loan_id, customer_id, file_name, stored_name, file_type, file_size, doc_type)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['loan_id']     ?? null,
                $data['customer_id'],
                $data['file_name'],
                $data['stored_name'],
                $data['file_type'],
                $data['file_size'],
                $data['doc_type']    ?? 'other',
            ]
        );
        return (int)$this->lastId();
    }

    public function getByCustomer(int $customerId): array
    {
        return $this->fetchAll(
            'SELECT d.*, la.loan_number
             FROM documents d
             LEFT JOIN loan_applications la ON la.id = d.loan_id
             WHERE d.customer_id = ?
             ORDER BY d.uploaded_at DESC',
            [$customerId]
        );
    }

    public function getByLoan(int $loanId): array
    {
        return $this->fetchAll(
            'SELECT * FROM documents WHERE loan_id = ? ORDER BY uploaded_at DESC',
            [$loanId]
        );
    }

    /** All documents (officer view) */
    public function getAll(): array
    {
        return $this->fetchAll(
            'SELECT d.*, u.fullname AS customer_name, la.loan_number
             FROM documents d
             JOIN users u ON u.id = d.customer_id
             LEFT JOIN loan_applications la ON la.id = d.loan_id
             ORDER BY d.uploaded_at DESC'
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM documents WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public function markVerified(int $id): bool
    {
        return $this->execute(
            'UPDATE documents SET verified = 1 WHERE id = ?',
            [$id]
        ) > 0;
    }
}
