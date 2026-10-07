<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminActivityController
{
    public static function recent(int $limit = 10): array
    {
        require_role('admin');

        $limit = max(1, min($limit, 100));

        $sql = "
            SELECT *
            FROM (
                SELECT
                    'registration' AS event_type,
                    user_id AS event_id,
                    CONCAT(name, ' registered') AS title,
                    CONCAT('Account role: ', role) AS detail,
                    created_at AS event_at,
                    NULL AS company_id
                FROM users

                UNION ALL

                SELECT
                    'publication' AS event_type,
                    i.internship_id AS event_id,
                    CONCAT('Internship published: ', i.title) AS title,
                    c.company_name AS detail,
                    i.published_at AS event_at,
                    NULL AS company_id
                FROM internships i
                INNER JOIN companies c
                    ON c.company_id = i.company_id
                WHERE i.published_at IS NOT NULL

                UNION ALL

                SELECT
                    'application' AS event_type,
                    a.application_id AS event_id,
                    CONCAT(
                        'Application #',
                        a.application_id,
                        ' submitted'
                    ) AS title,
                    i.title AS detail,
                    a.application_date AS event_at,
                    NULL AS company_id
                FROM applications a
                INNER JOIN internships i
                    ON i.internship_id = a.internship_id

                UNION ALL

                SELECT
                    'status_change' AS event_type,
                    h.history_id AS event_id,
                    CONCAT(
                        'Application #',
                        h.application_id,
                        ': ',
                        h.new_status
                    ) AS title,
                    CONCAT(
                        COALESCE(h.old_status, 'New'),
                        ' → ',
                        h.new_status
                    ) AS detail,
                    h.changed_at AS event_at,
                    NULL AS company_id
                FROM application_status_history h
                WHERE h.old_status IS NOT NULL

                UNION ALL

                SELECT
                    'verification' AS event_type,
                    c.company_id AS event_id,
                    CONCAT(
                        c.company_name,
                        ': ',
                        c.verification_status
                    ) AS title,
                    'Latest company verification decision' AS detail,
                    c.verification_reviewed_at AS event_at,
                    c.company_id AS company_id
                FROM companies c
                WHERE c.verification_reviewed_at IS NOT NULL
                  AND c.verification_status IN ('verified', 'rejected')
            
                                  UNION ALL

                SELECT
                    'account_status' AS event_type,
                    h.history_id AS event_id,
                    CONCAT(
                        'Account #',
                        h.user_id,
                        ': ',
                        target.name,
                        CASE h.new_status
                            WHEN 'suspended' THEN ' was suspended'
                            WHEN 'active' THEN ' was reactivated'
                            ELSE ' status changed'
                        END
                    ) AS title,
                    CONCAT(
                        h.old_status,
                        ' → ',
                        h.new_status,
                        ' | By ',
                        actor.name,
                        ' (admin #',
                        h.changed_by,
                        ')'
                    ) AS detail,
                    h.changed_at AS event_at,
                    NULL AS company_id
                FROM user_status_history h
                INNER JOIN users target
                    ON target.user_id = h.user_id
                INNER JOIN users actor
                    ON actor.user_id = h.changed_by
            
                  ) AS activity
            ORDER BY
                event_at DESC,
                event_type ASC,
                event_id DESC
            LIMIT :row_limit
        ";

        $stmt = db()->prepare($sql);
        $stmt->bindValue(':row_limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function appearance(string $type): array
    {
        return match ($type) {

            'account_status' => [
                'icon' => 'bi-person-lock',
                'color' => 'orange',
            ],


            'registration' => [
                'icon' => 'bi-person-plus',
                'color' => 'blue',
            ],
            'publication' => [
                'icon' => 'bi-briefcase',
                'color' => 'orange',
            ],
            'application' => [
                'icon' => 'bi-file-earmark-text',
                'color' => 'purple',
            ],
            'status_change' => [
                'icon' => 'bi-arrow-repeat',
                'color' => 'blue',
            ],
            'verification' => [
                'icon' => 'bi-building-check',
                'color' => 'green',
            ],
            default => [
                'icon' => 'bi-clock-history',
                'color' => 'blue',
            ],
        };
    }
}