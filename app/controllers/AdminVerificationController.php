<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

require_once __DIR__ . '/CompanyFileController.php';
require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/AdminActivityController.php';

final class AdminVerificationController
{
    public static function all(): array
    {
        $stmt = db()->query(
            "SELECT
                c.company_id,
                c.company_name,
                c.industry,
                c.location,
                c.verification_status,
                c.verification_doc_path,
                c.verification_reviewed_at,
                u.name AS account_name,
                u.email AS account_email
             FROM companies c
             INNER JOIN users u ON u.user_id = c.user_id
             ORDER BY
                FIELD(c.verification_status, 'pending', 'rejected', 'verified'),
                c.company_name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $companyId): ?array
    {
        $stmt = db()->prepare(
            "SELECT
                c.*,
                u.name AS account_name,
                u.email AS account_email
             FROM companies c
             INNER JOIN users u ON u.user_id = c.user_id
             WHERE c.company_id = :company_id
             LIMIT 1"
        );

        $stmt->execute([
            'company_id' => $companyId,
        ]);

        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        return $company ?: null;
    }




    public static function reviewToken(array $company): string
    {
        $fields = [
            'company_id',
            'user_id',
            'company_name',
            'description',
            'industry',
            'location',
            'phone',
            'contact_email',
            'website',
            'verification_doc_path',
            'verification_status',
            'verification_reviewed_by',
            'verification_reviewed_at',
            'verification_notes',
            'updated_at',
        ];

        $snapshot = [];

        foreach ($fields as $field) {
            $snapshot[$field] = isset($company[$field])
                ? (string) $company[$field]
                : null;
        }

        return hash_hmac(
            'sha256',
            json_encode($snapshot, JSON_THROW_ON_ERROR),
            csrf_token()
        );
    }


    

    public static function review(
        int $adminUserId,
        int $companyId,
        string $status,
        string $notes,
        string $expectedToken
    ): void {
    $admin = require_role('admin');

    if ((int) $admin['user_id'] !== $adminUserId) {
        throw new InvalidArgumentException('Invalid administrator.');
    }

    if (!in_array($status, ['verified', 'rejected'], true)) {
        throw new InvalidArgumentException(
            'Please select Approve or Reject.'
        );
    }

    $notes = trim($notes);

    if (mb_strlen($notes, 'UTF-8') > 2000) {
        throw new InvalidArgumentException(
            'Review notes must not exceed 2,000 characters.'
        );
    }

    if ($status === 'rejected' && $notes === '') {
        throw new InvalidArgumentException(
            'Please explain why the company was rejected.'
        );
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT *
             FROM companies
             WHERE company_id = :company_id
             FOR UPDATE'
        );

        $stmt->execute([
            'company_id' => $companyId,
        ]);

        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            throw new InvalidArgumentException(
                'Company account not found.'
            );
        }


        if (
            $expectedToken === ''
            || !hash_equals(
                self::reviewToken($company),
                $expectedToken
            )
        ) {
            throw new InvalidArgumentException(
                'The company information or verification document changed. '
                . 'Reload this page and review the current information and document '
                . 'before submitting another decision.'
            );
        }



        if (
            $status === 'verified'
            && CompanyFileController::path($company, 'verification') === null
        ) {
            throw new InvalidArgumentException(
                'The company must upload a verification document before approval.'
            );
        }

        $stmt = $pdo->prepare(
            'UPDATE companies
             SET verification_status = :status,
                 verification_reviewed_by = :admin_id,
                 verification_reviewed_at = NOW(),
                 verification_notes = :notes
             WHERE company_id = :company_id'
        );

        $stmt->execute([
            'status' => $status,
            'admin_id' => $adminUserId,
            'notes' => $notes !== '' ? $notes : null,
            'company_id' => $companyId,
        ]);

        $decisionChanged =
            $company['verification_status'] !== $status
            || (string) ($company['verification_notes'] ?? '') !== $notes;

        if ($decisionChanged) {
            $message = $status === 'verified'
                ? 'Your company verification was approved.'
                : 'Your company verification was rejected.';

            $message .= ' Open Company Profile to view your current status and feedback.';

            NotificationController::create(
                $pdo,
                (int) $company['user_id'],
                $message,
                'System'
            );

            AdminActivityController::record(
    $pdo,
    $adminUserId,
    $status === 'verified'
        ? 'verification_approved'
        : 'verification_rejected',
    'company',
    $companyId,
    (string) $company['company_name'],
    $notes !== ''
        ? $notes
        : 'No review notes provided.'
);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
}