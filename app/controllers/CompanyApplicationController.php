<?php

declare(strict_types=1);

require_once __DIR__ . '/NotificationController.php';
require_once __DIR__ . '/AdminActivityController.php';

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

        $internshipStatement = db()->prepare(
            'SELECT title
            FROM internships
            WHERE internship_id = :internship_id
            AND company_id = :company_id
            LIMIT 1'
        );

        $internshipStatement->execute([
            'internship_id' => $internshipId,
            'company_id' => $companyId,
        ]);

        $internship = $internshipStatement->fetch();

        if (!$internship) {
            throw new InvalidArgumentException(
                'Internship not found.'
            );
        }

        $statement = db()->prepare(
            'SELECT
                a.*,
                i.title,
                i.interns_needed,
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
            'title' => $internship['title'],
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
        // Checks that the application belongs to this company.
        $application = self::application($userId, $applicationId);

        $newStatus = $input['status'] ?? null;
        $expectedStatus = $input['expected_status'] ?? null;
        $notes = $input['interview_notes'] ?? '';
        $interviewDate = $input['interview_date'] ?? '';

        $allowedStatuses = [
            'Under Review',
            'Shortlisted',
            'Accepted',
            'Rejected',
        ];

        if (
            !is_string($newStatus)
            || !in_array($newStatus, $allowedStatuses, true)
        ) {
            throw new InvalidArgumentException(
                'Invalid application status.'
            );
        }

        if (
            !is_string($expectedStatus)
            || !in_array(
                $expectedStatus,
                ['Pending', 'Under Review', 'Shortlisted', 'Accepted', 'Rejected'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Reload the application review page and try again.'
            );
        }

        if (!is_string($notes)) {
            throw new InvalidArgumentException(
                'Invalid interview notes.'
            );
        }

        $notes = trim($notes);

        if (mb_strlen($notes, 'UTF-8') > 3000) {
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

        if ($interviewDate !== '') {
            $timezone = new DateTimeZone(
                date_default_timezone_get()
            );

            $date = DateTimeImmutable::createFromFormat(
                '!Y-m-d\TH:i',
                $interviewDate,
                $timezone
            );

            $dateErrors = DateTimeImmutable::getLastErrors();

            if (
                !$date
                || (
                    $dateErrors !== false
                    && (
                        $dateErrors['warning_count'] > 0
                        || $dateErrors['error_count'] > 0
                    )
                )
                || $date->format('Y-m-d\TH:i') !== $interviewDate
                || (int) $date->format('Y') < 1000
                || (int) $date->format('Y') > 9999
            ) {
                throw new InvalidArgumentException(
                    'Please enter a valid interview date.'
                );
            }

            $now = new DateTimeImmutable('now', $timezone);

            if ($date < $now) {
                throw new InvalidArgumentException(
                    'The interview date cannot be in the past.'
                );
            }

            $interviewDate = $date->format('Y-m-d H:i:s');
        } else {
            $interviewDate = null;
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare(
                'SELECT status, interview_date, interview_notes
                FROM applications
                WHERE application_id = :application_id
                AND internship_id = :internship_id
                FOR UPDATE'
            );

            $lock->execute([
                'application_id' => $applicationId,
                'internship_id' => $application['internship_id'],
            ]);

            $current = $lock->fetch();

            if (!$current) {
                throw new InvalidArgumentException(
                    'Application not found.'
                );
            }

            if ($current['status'] === 'Withdrawn') {
                throw new InvalidArgumentException(
                    'A withdrawn application cannot be reviewed.'
                );
            }


            $allowedTransitions = [
    'Pending' => [
        'Pending',
        'Under Review',
        'Rejected',
    ],
    'Under Review' => [
        'Under Review',
        'Shortlisted',
        'Accepted',
        'Rejected',
    ],
    'Shortlisted' => [
        'Shortlisted',
        'Accepted',
        'Rejected',
    ],
    'Accepted' => [
        'Accepted',
    ],
    'Rejected' => [
        'Rejected',
    ],
];

if (
    !in_array(
        $newStatus,
        $allowedTransitions[$current['status']] ?? [],
        true
    )
) {
    throw new InvalidArgumentException(
        'This application cannot move from '
        . $current['status']
        . ' to '
        . $newStatus
        . '.'
    );
}

if (
    $newStatus === 'Accepted'
    && $current['status'] !== 'Accepted'
) {
    $internshipLock = $pdo->prepare(
        'SELECT interns_needed
         FROM internships
         WHERE internship_id = :internship_id
         FOR UPDATE'
    );

    $internshipLock->execute([
        'internship_id' => $application['internship_id'],
    ]);

    $internship = $internshipLock->fetch();

    if (!$internship) {
        throw new InvalidArgumentException(
            'The internship no longer exists.'
        );
    }

    $acceptedCountStatement = $pdo->prepare(
            "SELECT COUNT(*)
            FROM applications
            WHERE internship_id = :internship_id
            AND status = 'Accepted'"
        );

        $acceptedCountStatement->execute([
            'internship_id' => $application['internship_id'],
        ]);

        $acceptedCount = (int) $acceptedCountStatement->fetchColumn();

        if (
            $acceptedCount >= (int) $internship['interns_needed']
        ) {
            throw new InvalidArgumentException(
                'All internship positions have already been filled.'
            );
        }
    }

            if ($current['status'] !== $expectedStatus) {
                throw new InvalidArgumentException(
                    'The application status changed after you opened this page. '
                    . 'Review the current status and try again.'
                );
            }

            $statusChanged = $current['status'] !== $newStatus;

            $dateChanged =
                (string) ($current['interview_date'] ?? '')
                !== (string) ($interviewDate ?? '');

            $notesChanged =
                (string) ($current['interview_notes'] ?? '') !== $notes;

            if (!$statusChanged && !$dateChanged && !$notesChanged) {
                $pdo->commit();
                return;
            }

            $update = $pdo->prepare(
                'UPDATE applications
                SET status = :status,
                    interview_date = :interview_date,
                    interview_notes = :interview_notes,
                    reviewed_at = CURRENT_TIMESTAMP
                WHERE application_id = :application_id'
            );

            $update->execute([
                'status' => $newStatus,
                'interview_date' => $interviewDate,
                'interview_notes' => $notes === '' ? null : $notes,
                'application_id' => $applicationId,
            ]);

            if ($statusChanged) {
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
    'old_status' => $current['status'],
    'new_status' => $newStatus,
    'changed_by' => $userId,
    'notes' => $notes === '' ? null : $notes,
]);

AdminActivityController::record(
    $pdo,
    $userId,
    'application_status_changed',
    'application',
    $applicationId,
    'Application #' . $applicationId
        . ' — '
        . $application['title'],
    $current['status'] . ' → ' . $newStatus
);



}

            $activeInterviewStatus = in_array(
                $newStatus,
                ['Under Review', 'Shortlisted'],
                true
            );

            if (
                $statusChanged
                || ($dateChanged && $activeInterviewStatus)
            ) {
                $message = $statusChanged
                    ? 'Your application status is now ' . $newStatus . '.'
                    : 'Your interview schedule has changed.';

                if ($activeInterviewStatus && $interviewDate !== null) {
                    $message .= ' Interview: '
                        . (new DateTimeImmutable($interviewDate))
                            ->format('d M Y, g:i A')
                        . ' Myanmar time.';
                } elseif ($activeInterviewStatus && $dateChanged) {
                    $message .= ' No interview is currently scheduled.';
                }

                NotificationController::applicationEvent(
                    $pdo,
                    $applicationId,
                    'student',
                    $message
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



public static function all(
    int $userId,
    ?string $status = null
): array {
    $companyId = self::companyId($userId);

    $allowedStatuses = [
        'Pending',
        'Under Review',
        'Shortlisted',
        'Accepted',
        'Rejected',
        'Withdrawn',
    ];

    if (
        $status !== null
        && !in_array($status, $allowedStatuses, true)
    ) {
        throw new InvalidArgumentException(
            'Invalid application status.'
        );
    }

    $where = [
        'i.company_id = :company_id',
    ];

    $parameters = [
        'company_id' => $companyId,
    ];

    if ($status !== null) {
        $where[] = 'a.status = :status';
        $parameters['status'] = $status;
    }

    $statement = db()->prepare(
        'SELECT
            a.application_id,
            a.internship_id,
            a.status,
            a.application_date,
            a.cv_original_name,
            i.title,
            u.name AS student_name,
            u.email AS student_email,
            sp.university,
            sp.degree
         FROM applications AS a
         INNER JOIN internships AS i
            ON i.internship_id = a.internship_id
         INNER JOIN student_profiles AS sp
            ON sp.student_id = a.student_id
         INNER JOIN users AS u
            ON u.user_id = sp.user_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY
            a.application_date DESC,
            a.application_id DESC'
    );

    $statement->execute($parameters);

    return $statement->fetchAll();
}


}