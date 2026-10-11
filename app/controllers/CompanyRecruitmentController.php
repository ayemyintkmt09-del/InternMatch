<?php
declare(strict_types=1);

require_once __DIR__ . '/CompanyInternshipController.php';

final class CompanyRecruitmentController
{
    public static function summary(int $userId): array
    {
        $companyId = CompanyInternshipController::companyId(
            $userId
        );

        $stmt = db()->prepare("
            SELECT
                COUNT(a.application_id) AS total,
                COALESCE(SUM(a.status = 'Pending'), 0)
                    AS pending,
                COALESCE(SUM(a.status = 'Under Review'), 0)
                    AS under_review,
                COALESCE(SUM(a.status = 'Shortlisted'), 0)
                    AS shortlisted,
                COALESCE(SUM(a.status = 'Accepted'), 0)
                    AS accepted,
                COALESCE(SUM(a.status = 'Rejected'), 0)
                    AS rejected,
                COALESCE(SUM(a.status = 'Withdrawn'), 0)
                    AS withdrawn
            FROM internships AS i
            LEFT JOIN applications AS a
                ON a.internship_id = i.internship_id
            WHERE i.company_id = :company_id
        ");

        $stmt->execute([
            'company_id' => $companyId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    
public static function upcomingInterviews(
    int $userId
): array {
    $companyId = CompanyInternshipController::companyId(
        $userId
    );

    $stmt = db()->prepare("
        SELECT
            a.application_id,
            a.interview_date,
            a.status,
            i.title,
            u.name AS student_name
        FROM applications AS a
        JOIN internships AS i
            ON i.internship_id = a.internship_id
        JOIN student_profiles AS sp
            ON sp.student_id = a.student_id
        JOIN users AS u
            ON u.user_id = sp.user_id
        WHERE i.company_id = :company_id
          AND a.interview_date IS NOT NULL
          AND a.interview_date >= :local_now
          AND a.status IN ('Under Review', 'Shortlisted')
        ORDER BY a.interview_date ASC
        LIMIT 10
    ");

    $stmt->execute([
        'company_id' => $companyId,
        'local_now' => date('Y-m-d H:i:s')
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





}
