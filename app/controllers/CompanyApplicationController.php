<?php

declare(strict_types=1);

final class CompanyApplicationController
{
    private static function companyId(int $userId): int
    {
        $statement = db()->prepare(
            'SELECT company_id
             FROM companies
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $companyId = $statement->fetchColumn();

        if ($companyId === false) {
            throw new RuntimeException('Company profile not found.');
        }

        return (int) $companyId;
    }

    public static function internships(int $userId): array
    {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            'SELECT
                i.internship_id,
                i.title,
                i.status,
                COUNT(a.application_id) AS application_count
             FROM internships AS i
             LEFT JOIN applications AS a
                ON a.internship_id = i.internship_id
             WHERE i.company_id = :company_id
             GROUP BY
                i.internship_id,
                i.title,
                i.status
             ORDER BY i.created_at DESC, i.internship_id DESC'
        );

        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }

    public static function applicants(
        int $userId,
        int $internshipId
    ): array {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            'SELECT
                a.*,
                i.title,
                u.name AS student_name,
                u.email AS student_email,
                sp.phone,
                sp.university,
                sp.degree
             FROM applications AS a
             JOIN internships AS i
                ON i.internship_id = a.internship_id
             JOIN student_profiles AS sp
                ON sp.student_id = a.student_id
             JOIN users AS u
                ON u.user_id = sp.user_id
             WHERE a.internship_id = :internship_id
               AND i.company_id = :company_id
             ORDER BY a.application_date DESC, a.application_id DESC'
        );

        $statement->execute([
            'internship_id' => $internshipId,
            'company_id' => $companyId,
        ]);

        $items = $statement->fetchAll();

        return [
            'items' => $items,
            'title' => $items[0]['title'] ?? 'Internship Applicants',
        ];
    }

    public static function application(
        int $userId,
        int $applicationId
    ): array {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            'SELECT
                a.*,
                i.title,
                i.company_id,
                u.name AS student_name,
                u.email AS student_email,
                sp.phone,
                sp.university,
                sp.degree
             FROM applications AS a
             JOIN internships AS i
                ON i.internship_id = a.internship_id
             JOIN student_profiles AS sp
                ON sp.student_id = a.student_id
             JOIN users AS u
                ON u.user_id = sp.user_id
             WHERE a.application_id = :application_id
               AND i.company_id = :company_id
             LIMIT 1'
        );

        $statement->execute([
            'application_id' => $applicationId,
            'company_id' => $companyId,
        ]);

        $application = $statement->fetch();

        if (!$application) {
            throw new InvalidArgumentException(
                'Application not found.'
            );
        }

        return $application;
    }

    public static function updateStatus(
        int $userId,
        int $applicationId,
        array $input
    ): void {
        $application = self::application($userId, $applicationId);

        $newStatus = $input['status'] ?? null;
        $notes = $input['interview_notes'] ?? '';
        $interviewDate = $input['interview_date'] ?? '';

        if (
            !is_string($newStatus)
            || !in_array(
                $newStatus,
                [
                    'Under Review',
                    'Shortlisted',
                    'Accepted',
                    'Rejected',
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid application status.'
            );
        }

        if (!is_string($notes) || mb_strlen($notes, 'UTF-8') > 3000) {
            throw new InvalidArgumentException(
                'Interview notes must not exceed 3,000 characters.'
            );
        }

        if (!is_string($interviewDate)) {
            throw new InvalidArgumentException(
                'Invalid interview date.'
            );
        }

        $interviewDate = trim($interviewDate);
        $notes = trim($notes);

        if ($interviewDate !== '') {
            $date = DateTimeImmutable::createFromFormat(
                '!Y-m-d\TH:i',
                $interviewDate
            );

            if (
                !$date
                || $date->format('Y-m-d\TH:i') !== $interviewDate
            ) {
                throw new InvalidArgumentException(
                    'Please enter a valid interview date.'
                );
            }

            $interviewDate = str_replace('T', ' ', $interviewDate) . ':00';
        } else {
            $interviewDate = null;
        }

        
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare(
                'SELECT status
                 FROM applications
                 WHERE application_id = :application_id
                 FOR UPDATE'
            );

            $lock->execute([
                'application_id' => $applicationId,
            ]);

            $lockedApplication = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$lockedApplication) {
                throw new InvalidArgumentException(
                    'Application not found.'
                );
            }

            $application['status'] = $lockedApplication['status'];

            if ($application['status'] === 'Withdrawn') {
                throw new InvalidArgumentException(
                    'A withdrawn application cannot be reviewed.'
                );
            }

            if ($application['status'] === $newStatus) {
                $detailsStatement = $pdo->prepare(
                    'UPDATE applications
                    SET interview_date = :interview_date,
                        interview_notes = :interview_notes,
                        reviewed_at = CURRENT_TIMESTAMP
                    WHERE application_id = :application_id'
                );

                $detailsStatement->execute([
                    'interview_date' => $interviewDate,
                    'interview_notes' => $notes === '' ? null : $notes,
                    'application_id' => $applicationId,
                ]);

                $pdo->commit();
                return;
            }

            $update = $pdo->prepare(
                'UPDATE applications SET
                    status = :new_status,
                    interview_date = :interview_date,
                    interview_notes = :interview_notes,
                    reviewed_at = CURRENT_TIMESTAMP
                 WHERE application_id = :application_id'
            );

            $update->execute([
                'new_status' => $newStatus,
                'interview_date' => $interviewDate,
                'interview_notes' => $notes === '' ? null : $notes,
                'application_id' => $applicationId,
            ]);

            $history = $pdo->prepare(
                'INSERT INTO application_status_history (
                    application_id,
                    old_status,
                    new_status,
                    changed_by,
                    notes
                 ) VALUES (
                    :application_id,
                    :old_status,
                    :new_status,
                    :changed_by,
                    :notes
                 )'
            );

            $history->execute([
                'application_id' => $applicationId,
                'old_status' => $application['status'],
                'new_status' => $newStatus,
                'changed_by' => $userId,
                'notes' => $notes === '' ? null : $notes,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}