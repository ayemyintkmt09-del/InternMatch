<?php

declare(strict_types=1);

require_once __DIR__ . '/CompanyInternshipController.php';

final class CompanyDashboardChartController
{
    public static function load(int $userId): array
    {
        $companyId = CompanyInternshipController::companyId($userId);

        $localTimezone = new DateTimeZone('Asia/Yangon');
        $utcTimezone = new DateTimeZone('UTC');

        $monthStart = (new DateTimeImmutable('now', $localTimezone))
            ->modify('first day of this month')
            ->setTime(0, 0);

        $firstMonth = $monthStart->modify('-5 months');
        $nextMonth = $monthStart->modify('+1 month');

        $months = [];

        for ($index = 0; $index < 6; $index++) {
            $month = $firstMonth->modify("+{$index} months");

            $months[$month->format('Y-m')] = [
                'label' => $month->format('M Y'),
                'count' => 0,
            ];
        }

        // Database timestamp session is UTC.
        // Group into Myanmar calendar months.
        $trend = db()->prepare(
            "SELECT
                DATE_FORMAT(
                    DATE_ADD(a.application_date, INTERVAL 390 MINUTE),
                    '%Y-%m'
                ) AS month_key,
                COUNT(*) AS total
             FROM applications AS a
             JOIN internships AS i
                ON i.internship_id = a.internship_id
             WHERE i.company_id = :company_id
               AND a.application_date >= :from_date
               AND a.application_date < :until_date
             GROUP BY month_key"
        );

        $trend->execute([
            'company_id' => $companyId,
            'from_date' => $firstMonth
                ->setTimezone($utcTimezone)
                ->format('Y-m-d H:i:s'),
            'until_date' => $nextMonth
                ->setTimezone($utcTimezone)
                ->format('Y-m-d H:i:s'),
        ]);

        foreach ($trend->fetchAll() as $row) {
            if (isset($months[$row['month_key']])) {
                $months[$row['month_key']]['count'] =
                    (int) $row['total'];
            }
        }

        $statuses = [
            'Pending' => 0,
            'Under Review' => 0,
            'Shortlisted' => 0,
            'Accepted' => 0,
            'Rejected' => 0,
            'Withdrawn' => 0,
        ];

        $distribution = db()->prepare(
            'SELECT a.status, COUNT(*) AS total
             FROM applications AS a
             JOIN internships AS i
                ON i.internship_id = a.internship_id
             WHERE i.company_id = :company_id
             GROUP BY a.status'
        );

        $distribution->execute(['company_id' => $companyId]);

        foreach ($distribution->fetchAll() as $row) {
            if (array_key_exists($row['status'], $statuses)) {
                $statuses[$row['status']] = (int) $row['total'];
            }
        }

        return [
            'months' => array_values($months),
            'statuses' => $statuses,
            'total' => array_sum($statuses),
        ];
    }
}