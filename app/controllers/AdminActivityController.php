<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminActivityController
{


public static function recent(int $limit = 100): array
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
                l.action AS event_type,
                l.activity_id AS event_id,
                CONCAT(
                    CASE l.action
                        WHEN 'verification_approved'
                            THEN 'Verification approved'
                        WHEN 'verification_rejected'
                            THEN 'Verification rejected'
                        WHEN 'user_suspended'
                            THEN 'User suspended'
                        WHEN 'user_reactivated'
                            THEN 'User reactivated'
                        WHEN 'internship_closed'
                            THEN 'Internship closed'
                        WHEN 'application_status_changed'
                            THEN 'Application status changed'
                        ELSE 'System activity'
                    END,
                    ': ',
                    l.target_label
                ) AS title,
                CONCAT(
                    'Target: ',
                    l.target_label,
                    ' | By: ',
                    actor.name,
                    CASE
                        WHEN l.details IS NULL
                             OR l.details = ''
                        THEN ''
                        ELSE CONCAT(' | ', l.details)
                    END
                ) AS detail,
                l.created_at AS event_at,
                CASE
                    WHEN l.target_type = 'company'
                    THEN l.target_id
                    ELSE NULL
                END AS company_id
            FROM activity_log l
            INNER JOIN users actor
                ON actor.user_id = l.actor_user_id
        ) AS activity
        ORDER BY event_at DESC, event_id DESC
        LIMIT :row_limit
    ";

    $statement = db()->prepare($sql);
    $statement->bindValue(
        ':row_limit',
        $limit,
        PDO::PARAM_INT
    );
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
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
            'internship_closed' => [
    'icon' => 'bi-lock',
    'color' => 'red',
],

'verification_approved' => [
    'icon' => 'bi-building-check',
    'color' => 'green',
],

'verification_rejected' => [
    'icon' => 'bi-building-x',
    'color' => 'red',
],

'user_suspended' => [
    'icon' => 'bi-person-lock',
    'color' => 'red',
],

'user_reactivated' => [
    'icon' => 'bi-person-check',
    'color' => 'green',
],

'application_status_changed' => [
    'icon' => 'bi-arrow-repeat',
    'color' => 'blue',
],
            default => [
                'icon' => 'bi-clock-history',
                'color' => 'blue',
            ],
        };
    }




    public static function record(
    PDO $pdo,
    int $actorUserId,
    string $action,
    string $targetType,
    int $targetId,
    string $targetLabel,
    ?string $details = null
): void {
    $statement = $pdo->prepare(
        'INSERT INTO activity_log (
            actor_user_id,
            action,
            target_type,
            target_id,
            target_label,
            details
         ) VALUES (
            :actor_user_id,
            :action,
            :target_type,
            :target_id,
            :target_label,
            :details
         )'
    );

    $statement->execute([
        'actor_user_id' => $actorUserId,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'target_label' => $targetLabel,
        'details' => $details,
    ]);
}
}