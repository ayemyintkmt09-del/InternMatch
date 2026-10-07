<?php

declare(strict_types=1);

require_once __DIR__ . '/StudentInternshipController.php';
require_once __DIR__ . '/CvController.php';

final class ApplicationController
{
    private static function student(int $userId): array
    {
        $statement = db()->prepare(
            'SELECT
                student_id,
                cv_path,
                cv_original_name
             FROM student_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $student = $statement->fetch();

        if (!$student) {
            throw new RuntimeException('Student profile not found.');
        }

        return $student;
    }

    public static function applicationStatus(
        int $userId,
        int $internshipId
    ): ?string {
        $student = self::student($userId);

        $statement = db()->prepare(
            'SELECT status
            FROM applications
            WHERE student_id = :student_id
            AND internship_id = :internship_id
            LIMIT 1'
        );

        $statement->execute([
            'student_id' => $student['student_id'],
            'internship_id' => $internshipId,
        ]);

        $status = $statement->fetchColumn();

        return $status === false ? null : (string) $status;
    }

    public static function hasApplied(
        int $userId,
        int $internshipId
    ): bool {
        return self::applicationStatus(
            $userId,
            $internshipId
        ) !== null;
    }

    public static function apply(
        int $userId,
        int $internshipId,
        array $input
    ): void {
        $message = $input['message'] ?? '';

        if (!is_string($message)) {
            throw new InvalidArgumentException(
                'Invalid application message.'
            );
        }

        $message = trim($message);

        if (mb_strlen($message, 'UTF-8') > 3000) {
            throw new InvalidArgumentException(
                'Your message must not exceed 3,000 characters.'
            );
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // Lock the profile so its CV cannot change during submission.
            $profileStatement = $pdo->prepare(
                "SELECT
                    sp.student_id,
                    sp.cv_path,
                    sp.cv_original_name
                FROM student_profiles AS sp
                INNER JOIN users AS u ON u.user_id = sp.user_id
                WHERE sp.user_id = :user_id
                AND u.role = 'student'
                AND u.status = 'active'
                FOR UPDATE"
            );

            $profileStatement->execute([
                'user_id' => $userId,
            ]);

            $student = $profileStatement->fetch();

            if (!$student) {
                throw new InvalidArgumentException(
                    'Your student account is unavailable.'
                );
            }

            $existingStatement = $pdo->prepare(
                'SELECT application_id, status
                FROM applications
                WHERE student_id = :student_id
                AND internship_id = :internship_id
                FOR UPDATE'
            );

            $existingStatement->execute([
                'student_id' => $student['student_id'],
                'internship_id' => $internshipId,
            ]);

            $existing = $existingStatement->fetch();

            if ($existing) {
                throw new InvalidArgumentException(
                    $existing['status'] === 'Withdrawn'
                        ? 'A withdrawn application cannot be resubmitted.'
                        : 'You have already applied for this internship.'
                );
            }

            // Recheck availability while locking the relevant records.
            $availabilityStatement = $pdo->prepare(
                "SELECT
                    i.internship_id,
                    i.status AS internship_status,
                    i.deadline,
                    c.verification_status,
                    u.status AS company_account_status,
                    u.role AS company_account_role
                FROM internships AS i
                INNER JOIN companies AS c
                    ON c.company_id = i.company_id
                INNER JOIN users AS u
                    ON u.user_id = c.user_id
                WHERE i.internship_id = :internship_id
                FOR UPDATE"
            );

            $availabilityStatement->execute([
                'internship_id' => $internshipId,
            ]);

            $internship = $availabilityStatement->fetch();

            if (
                !$internship
                || $internship['internship_status'] !== 'Published'
                || $internship['verification_status'] !== 'verified'
                || $internship['company_account_status'] !== 'active'
                || $internship['company_account_role'] !== 'company'
                || empty($internship['deadline'])
                || $internship['deadline'] < date('Y-m-d')
            ) {
                throw new InvalidArgumentException(
                    'This internship is no longer available.'
                );
            }

            if (CvController::downloadPath($student) === null) {
                throw new InvalidArgumentException(
                    'Your CV is unavailable. Please upload a PDF again before applying.'
                );
            }

            $statement = $pdo->prepare(
                "INSERT INTO applications (
                    internship_id,
                    student_id,
                    cv_path,
                    cv_original_name,
                    message,
                    status
                ) VALUES (
                    :internship_id,
                    :student_id,
                    :cv_path,
                    :cv_original_name,
                    :message,
                    'Pending'
                )"
            );

            $statement->execute([
                'internship_id' => $internshipId,
                'student_id' => $student['student_id'],
                'cv_path' => $student['cv_path'],
                'cv_original_name' =>
                    $student['cv_original_name'] ?: 'submitted-cv.pdf',
                'message' => $message === '' ? null : $message,
            ]);

            $applicationId = (int) $pdo->lastInsertId();

            $history = $pdo->prepare(
                "INSERT INTO application_status_history (
                    application_id,
                    old_status,
                    new_status,
                    changed_by,
                    notes
                ) VALUES (
                    :application_id,
                    NULL,
                    'Pending',
                    :changed_by,
                    :notes
                )"
            );

            $history->execute([
                'application_id' => $applicationId,
                'changed_by' => $userId,
                'notes' => 'Application submitted by student.',
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public static function listing(int $userId): array
    {
        $student = self::student($userId);

        $statement = db()->prepare(
            'SELECT
                a.*,
                i.title,
                i.location,
                i.internship_type,
                c.company_name
             FROM applications AS a
             JOIN internships AS i
                ON i.internship_id = a.internship_id
             JOIN companies AS c
                ON c.company_id = i.company_id
             WHERE a.student_id = :student_id
             ORDER BY a.application_date DESC, a.application_id DESC'
        );

        $statement->execute([
            'student_id' => $student['student_id'],
        ]);

        return $statement->fetchAll();
    }

    public static function withdraw(
        int $userId,
        int $applicationId
    ): void {
        $student = self::student($userId);

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'SELECT status
                 FROM applications
                 WHERE application_id = :application_id
                   AND student_id = :student_id
                 FOR UPDATE'
            );

            $statement->execute([
                'application_id' => $applicationId,
                'student_id' => $student['student_id'],
            ]);

            $application = $statement->fetch();

            if (!$application) {
                throw new InvalidArgumentException(
                    'Application not found.'
                );
            }

            if (
                !in_array(
                    $application['status'],
                    ['Pending', 'Under Review'],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'This application can no longer be withdrawn.'
                );
            }

            $update = $pdo->prepare(
                'UPDATE applications SET
                    status = "Withdrawn",
                    reviewed_at = CURRENT_TIMESTAMP
                 WHERE application_id = :application_id
                   AND student_id = :student_id'
            );

            $update->execute([
                'application_id' => $applicationId,
                'student_id' => $student['student_id'],
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
                    "Withdrawn",
                    :changed_by,
                    :notes
                 )'
            );

            $history->execute([
                'application_id' => $applicationId,
                'old_status' => $application['status'],
                'changed_by' => $userId,
                'notes' => 'Application withdrawn by student.',
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