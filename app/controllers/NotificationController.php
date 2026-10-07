<?php

declare(strict_types=1);

final class NotificationController
{
    public static function create(
        PDO $pdo,
        int $userId,
        string $message,
        string $type = 'System',
        ?int $applicationId = null,
        ?int $internshipId = null
    ): void {
        if (!$pdo->inTransaction()) {
            throw new LogicException(
                'Notifications must be created inside an event transaction.'
            );
        }

        $message = trim($message);

        if (
            $userId < 1
            || $message === ''
            || mb_strlen($message, 'UTF-8') > 4000
            || !in_array(
                $type,
                ['Application', 'Status Update', 'Internship', 'System', 'Deadline'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid notification data.'
            );
        }

        $statement = $pdo->prepare(
            'INSERT INTO notifications (
                user_id,
                message,
                notification_type,
                related_application_id,
                related_internship_id,
                is_read
             ) VALUES (
                :user_id,
                :message,
                :notification_type,
                :application_id,
                :internship_id,
                0
             )'
        );

        $statement->execute([
            'user_id' => $userId,
            'message' => $message,
            'notification_type' => $type,
            'application_id' => $applicationId,
            'internship_id' => $internshipId,
        ]);
    }

    public static function applicationEvent(
        PDO $pdo,
        int $applicationId,
        string $audience,
        string $message,
        string $type = 'Status Update'
    ): void {
        if (!in_array($audience, ['student', 'company'], true)) {
            throw new InvalidArgumentException(
                'Invalid notification recipient.'
            );
        }

        $statement = $pdo->prepare(
            'SELECT
                a.application_id,
                a.internship_id,
                i.title,
                sp.user_id AS student_user_id,
                c.user_id AS company_user_id
             FROM applications AS a
             INNER JOIN student_profiles AS sp
                ON sp.student_id = a.student_id
             INNER JOIN internships AS i
                ON i.internship_id = a.internship_id
             INNER JOIN companies AS c
                ON c.company_id = i.company_id
             WHERE a.application_id = :application_id'
        );

        $statement->execute([
            'application_id' => $applicationId,
        ]);

        $application = $statement->fetch();

        if (!$application) {
            throw new RuntimeException(
                'Notification application not found.'
            );
        }

        $recipientId = $audience === 'student'
            ? (int) $application['student_user_id']
            : (int) $application['company_user_id'];

        self::create(
            $pdo,
            $recipientId,
            $message . ' — ' . $application['title'],
            $type,
            $applicationId,
            (int) $application['internship_id']
        );
    }

    public static function unreadCount(int $userId): int
    {
        $statement = db()->prepare(
            'SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :user_id
               AND is_read = 0'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);

        return (int) $statement->fetchColumn();
    }

    public static function listing(
        int $userId,
        string $filter,
        int $page
    ): array {
        $conditions = [
            'all' => '',
            'unread' => ' AND is_read = 0',
            'applications' =>
                " AND notification_type IN ('Application', 'Status Update')",
            'system' => " AND notification_type = 'System'",
        ];

        if (!array_key_exists($filter, $conditions)) {
            throw new InvalidArgumentException(
                'Invalid notification filter.'
            );
        }

        $pdo = db();

        $summary = $pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                COALESCE(SUM(is_read = 0), 0) AS unread
             FROM notifications
             WHERE user_id = :user_id'
        );

        $summary->execute([
            'user_id' => $userId,
        ]);

        $totals = $summary->fetch();

        $where = ' WHERE user_id = :user_id'
            . $conditions[$filter];

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM notifications' . $where
        );

        $count->execute([
            'user_id' => $userId,
        ]);

        $total = (int) $count->fetchColumn();
        $pageSize = 20;
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare(
            'SELECT *
             FROM notifications'
            . $where
            . ' ORDER BY created_at DESC, notification_id DESC
                LIMIT :page_size OFFSET :page_offset'
        );

        $statement->bindValue(
            ':user_id',
            $userId,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':page_size',
            $pageSize,
            PDO::PARAM_INT
        );

        $statement->bindValue(
            ':page_offset',
            ($page - 1) * $pageSize,
            PDO::PARAM_INT
        );

        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'all_count' => (int) $totals['total'],
            'unread_count' => (int) $totals['unread'],
            'read_count' =>
                (int) $totals['total'] - (int) $totals['unread'],
        ];
    }

    public static function markRead(
        int $userId,
        int $notificationId
    ): void {
        $statement = db()->prepare(
            'UPDATE notifications
             SET is_read = 1,
                 read_at = COALESCE(read_at, UTC_TIMESTAMP())
             WHERE notification_id = :notification_id
               AND user_id = :user_id
               AND is_read = 0'
        );

        $statement->execute([
            'notification_id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    public static function markAllRead(int $userId): void
    {
        $statement = db()->prepare(
            'UPDATE notifications
             SET is_read = 1,
                 read_at = COALESCE(read_at, UTC_TIMESTAMP())
             WHERE user_id = :user_id
               AND is_read = 0'
        );

        $statement->execute([
            'user_id' => $userId,
        ]);
    }

    public static function destination(
        array $notification,
        string $role
    ): ?string {
        if (!empty($notification['related_application_id'])) {
            if ($role === 'student') {
                return 'my-applications.php';
            }

            if (
                $role === 'company'
                && !empty($notification['related_internship_id'])
            ) {
                return 'company-applicants.php?internship_id='
                    . (int) $notification['related_internship_id'];
            }
        }

        if (
            $role === 'company'
            && $notification['notification_type'] === 'System'
        ) {
            return 'company-profile.php';
        }

        return null;
    }
}