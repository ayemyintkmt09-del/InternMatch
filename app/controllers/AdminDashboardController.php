<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminDashboardController
{

    public static function panels(): array
    {
        require_role('admin');

        $pdo = db();

        $userRoles = $pdo->query(
            'SELECT role, COUNT(*) AS total
             FROM users
             GROUP BY role
             ORDER BY role'
        )->fetchAll(PDO::FETCH_ASSOC);

        $userStatuses = $pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM users
             GROUP BY status
             ORDER BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        $companies = $pdo->query(
            'SELECT
                company_id,
                company_name,
                industry,
                verification_status
             FROM companies
             ORDER BY company_id DESC
             LIMIT 5'
        )->fetchAll(PDO::FETCH_ASSOC);

        $internships = [
            'Draft' => 0,
            'Published' => 0,
            'Closed' => 0,
            'Expired' => 0,
        ];

        $rows = $pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM internships
             GROUP BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $internships[$row['status']] = (int) $row['total'];
        }

        $applications = [
            'Pending' => 0,
            'Under Review' => 0,
            'Shortlisted' => 0,
            'Accepted' => 0,
            'Rejected' => 0,
            'Withdrawn' => 0,
        ];

        $rows = $pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM applications
             GROUP BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $applications[$row['status']] = (int) $row['total'];
        }

        return [
            'user_roles' => $userRoles,
            'user_statuses' => $userStatuses,
            'companies' => $companies,
            'internships' => $internships,
            'applications' => $applications,
        ];
    }
    public static function load(): array
    {
        require_role('admin');

        $stmt = db()->prepare(
            "SELECT
                (
                    SELECT COUNT(*)
                    FROM users
                    WHERE role = 'student'
                ) AS total_students,

                (
                    SELECT COUNT(*)
                    FROM companies
                ) AS total_companies,

                (
                    SELECT COUNT(*)
                    FROM internships i
                    INNER JOIN companies c
                        ON c.company_id = i.company_id
                    INNER JOIN users u
                        ON u.user_id = c.user_id
                    WHERE i.status = 'Published'
                      AND c.verification_status = 'verified'
                      AND u.status = 'active'
                      AND u.role = 'company'
                      AND i.deadline >= :today
                ) AS active_internships,

                (
                    SELECT COUNT(*)
                    FROM applications
                ) AS total_applications,

                (
                    SELECT COUNT(*)
                    FROM companies
                    WHERE verification_status = 'pending'
                ) AS pending_companies"
        );

        $stmt->execute([
            'today' => date('Y-m-d'),
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new RuntimeException(
                'Admin dashboard totals could not be loaded.'
            );
        }

        return array_map(
            static fn ($value): int => (int) $value,
            $result
        );
    }
}