<?php

declare(strict_types=1);

require_once __DIR__ . '/StudentInternshipController.php';

final class SavedInternshipController
{
    private static function studentId(int $userId): int
    {
        $statement = db()->prepare(
            'SELECT student_id
             FROM student_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $studentId = $statement->fetchColumn();

        if ($studentId === false) {
            throw new RuntimeException('Student profile not found.');
        }

        return (int) $studentId;
    }

    public static function isSaved(
        int $userId,
        int $internshipId
    ): bool {
        $statement = db()->prepare(
            'SELECT saved_id
             FROM saved_internships
             WHERE student_id = :student_id
               AND internship_id = :internship_id
             LIMIT 1'
        );

        $statement->execute([
            'student_id' => self::studentId($userId),
            'internship_id' => $internshipId,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public static function save(
        int $userId,
        int $internshipId
    ): void {
        $studentId = self::studentId($userId);
        $pdo = db();

        $pdo->beginTransaction();

        try {
            $check = $pdo->prepare(
                'SELECT i.internship_id
                 FROM internships AS i
                 JOIN companies AS c
                    ON c.company_id = i.company_id
                 JOIN users AS u
                    ON u.user_id = c.user_id
                 WHERE i.internship_id = :internship_id
                   AND ' . StudentInternshipController::visibility() . '
                 FOR UPDATE'
            );

            $check->execute([
                'internship_id' => $internshipId,
                'today' => date('Y-m-d'),
            ]);

            if ($check->fetchColumn() === false) {
                throw new InvalidArgumentException(
                    'This internship is no longer available to save.'
                );
            }

            $statement = $pdo->prepare(
                'INSERT INTO saved_internships (
                    student_id,
                    internship_id
                 ) VALUES (
                    :student_id,
                    :internship_id
                 )
                 ON DUPLICATE KEY UPDATE
                    saved_id = saved_internships.saved_id'
            );

            $statement->execute([
                'student_id' => $studentId,
                'internship_id' => $internshipId,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    public static function remove(
        int $userId,
        int $internshipId
    ): void {
        $statement = db()->prepare(
            'DELETE FROM saved_internships
             WHERE student_id = :student_id
               AND internship_id = :internship_id'
        );

        $statement->execute([
            'student_id' => self::studentId($userId),
            'internship_id' => $internshipId,
        ]);
    }

    public static function listing(int $userId): array
    {
        $statement = db()->prepare(
            'SELECT
                si.saved_id,
                si.saved_at,
                i.internship_id,
                i.title,
                i.location,
                i.internship_type,
                i.deadline,
                c.company_name,
                CASE
                    WHEN ' . StudentInternshipController::visibility() . '
                    THEN 1
                    ELSE 0
                END AS is_available
             FROM saved_internships AS si
             JOIN internships AS i
                ON i.internship_id = si.internship_id
             JOIN companies AS c
                ON c.company_id = i.company_id
             JOIN users AS u
                ON u.user_id = c.user_id
             WHERE si.student_id = :student_id
             ORDER BY si.saved_at DESC, si.saved_id DESC'
        );

        $statement->execute([
            'today' => date('Y-m-d'),
            'student_id' => self::studentId($userId),
        ]);

        return $statement->fetchAll();
    }




    public static function savedIds(int $userId): array
{
    $statement = db()->prepare(
        'SELECT si.internship_id
         FROM saved_internships AS si
         INNER JOIN student_profiles AS sp
            ON sp.student_id = si.student_id
         WHERE sp.user_id = :user_id'
    );

    $statement->execute([
        'user_id' => $userId,
    ]);

    return array_map(
        'intval',
        $statement->fetchAll(PDO::FETCH_COLUMN)
    );
}
}