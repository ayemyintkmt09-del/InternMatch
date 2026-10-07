<?php

declare(strict_types=1);

require_once __DIR__ . '/StudentProfileController.php';
require_once __DIR__ . '/StudentInternshipController.php';

final class StudentDashboardController
{
    public static function load(int $userId): array
    {
        $pdo = db();

        $profile = StudentProfileController::load($userId);
        $studentId = (int) $profile['student_id'];

        // Interview dates currently use the application's local timezone.
        $localNow = date('Y-m-d H:i:s');

        $applicationStatement = $pdo->prepare(
            "SELECT
                COUNT(*) AS total_applications,
                COALESCE(
                    SUM(
                        status IN ('Under Review', 'Shortlisted')
                        AND interview_date >= :local_now
                    ),
                    0
                ) AS upcoming_interviews
             FROM applications
             WHERE student_id = :student_id"
        );

        $applicationStatement->execute([
            'local_now' => $localNow,
            'student_id' => $studentId,
        ]);

        $applicationStats = $applicationStatement->fetch();

        $savedStatement = $pdo->prepare(
            'SELECT COUNT(*)
             FROM saved_internships
             WHERE student_id = :student_id'
        );

        $savedStatement->execute([
            'student_id' => $studentId,
        ]);

        // Reuse the same availability rules as the Opportunities page.
        $opportunities = StudentInternshipController::search([
            'sort' => 'newest',
        ]);

        $recentStatement = $pdo->prepare(
            'SELECT
                a.application_id,
                a.status,
                i.title,
                c.company_name
             FROM applications AS a
             INNER JOIN internships AS i
                ON i.internship_id = a.internship_id
             INNER JOIN companies AS c
                ON c.company_id = i.company_id
             WHERE a.student_id = :student_id
             ORDER BY a.application_date DESC, a.application_id DESC
             LIMIT 5'
        );

        $recentStatement->execute([
            'student_id' => $studentId,
        ]);

        $interviewStatement = $pdo->prepare(
            "SELECT
                a.application_id,
                a.interview_date,
                i.title,
                c.company_name
             FROM applications AS a
             INNER JOIN internships AS i
                ON i.internship_id = a.internship_id
             INNER JOIN companies AS c
                ON c.company_id = i.company_id
             WHERE a.student_id = :student_id
               AND a.status IN ('Under Review', 'Shortlisted')
               AND a.interview_date >= :local_now
             ORDER BY a.interview_date ASC, a.application_id ASC
             LIMIT 3"
        );

        $interviewStatement->execute([
            'student_id' => $studentId,
            'local_now' => $localNow,
        ]);

        return [
            'profile' => $profile,
            'profile_completion' =>
                StudentProfileController::completion($profile),
            'available_internships' => (int) $opportunities['total'],
            'total_applications' =>
                (int) $applicationStats['total_applications'],
            'saved_internships' =>
                (int) $savedStatement->fetchColumn(),
            'upcoming_interview_count' =>
                (int) $applicationStats['upcoming_interviews'],
            'latest_internships' =>
                array_slice($opportunities['items'], 0, 3),
            'recent_applications' => $recentStatement->fetchAll(),
            'upcoming_interviews' => $interviewStatement->fetchAll(),
        ];
    }
}