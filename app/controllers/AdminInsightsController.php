<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminInsightsController
{
    public static function monthlyActivity(): array
    {
        require_role('admin');

        $sql = "
            SELECT
                month_key,
                SUM(registrations) AS registrations,
                SUM(applications) AS applications
            FROM (
                SELECT
                    DATE_FORMAT(created_at, '%Y-%m') AS month_key,
                    COUNT(*) AS registrations,
                    0 AS applications
                FROM users
                WHERE created_at >= DATE_FORMAT(
                    DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
                    '%Y-%m-01'
                )
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')

                UNION ALL

                SELECT
                    DATE_FORMAT(application_date, '%Y-%m') AS month_key,
                    0 AS registrations,
                    COUNT(*) AS applications
                FROM applications
                WHERE application_date >= DATE_FORMAT(
                    DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
                    '%Y-%m-01'
                )
                GROUP BY DATE_FORMAT(application_date, '%Y-%m')
            ) AS monthly_data
            GROUP BY month_key
            ORDER BY month_key
        ";

        $rows = db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        
$byMonth = [];

foreach ($rows as $row) {
    $byMonth[$row['month_key']] = $row;
}

$months = [];

$firstMonth = (new DateTimeImmutable(
    'first day of this month'
))->modify('-5 months');

for ($i = 0; $i < 6; $i++) {
    $date = $firstMonth->modify("+$i months");
    $key = $date->format('Y-m');

    $row = $byMonth[$key] ?? null;

    $months[] = [
        'month' => $date->format('M Y'),
        'registrations' => (int) ($row['registrations'] ?? 0),
        'applications' => (int) ($row['applications'] ?? 0),
    ];
}

return $months;
}



public static function topCompanies(): array
{
    require_role('admin');

    $sql = "
        SELECT
            c.company_id,
            c.company_name,
            c.verification_status,
            COUNT(DISTINCT i.internship_id)
                AS total_postings,
            COUNT(a.application_id)
                AS total_applications
        FROM companies AS c
        LEFT JOIN internships AS i
            ON i.company_id = c.company_id
        LEFT JOIN applications AS a
            ON a.internship_id = i.internship_id
        GROUP BY
            c.company_id,
            c.company_name,
            c.verification_status
        ORDER BY
            total_applications DESC,
            total_postings DESC,
            c.company_name ASC
        LIMIT 10
    ";

    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


}
