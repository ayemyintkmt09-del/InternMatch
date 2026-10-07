<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminInternshipController
{
    public static function search(
        string $query,
        string $status,
        int $page
    ): array {
        require_role('admin');

        $query = trim($query);

        if (mb_strlen($query, 'UTF-8') > 100) {
            throw new InvalidArgumentException(
                'Search must not exceed 100 characters.'
            );
        }

        if (!in_array(
            $status,
            ['', 'Draft', 'Published', 'Closed', 'Expired'],
            true
        )) {
            throw new InvalidArgumentException('Invalid status filter.');
        }

        $where = [];
        $parameters = [];

        if ($query !== '') {
            $where[] = '(
                LOCATE(:title_query, i.title) > 0
                OR LOCATE(:company_query, c.company_name) > 0
            )';

            $parameters['title_query'] = $query;
            $parameters['company_query'] = $query;
        }

        if ($status !== '') {
            $where[] = 'i.status = :status';
            $parameters['status'] = $status;
        }

        $whereSql = $where === []
            ? ''
            : ' WHERE ' . implode(' AND ', $where);

        $fromSql = '
            FROM internships i
            INNER JOIN companies c
                ON c.company_id = i.company_id
            INNER JOIN users u
                ON u.user_id = c.user_id
            LEFT JOIN academic_fields af
                ON af.field_id = i.field_id
        ';

        $pdo = db();

        $count = $pdo->prepare(
            'SELECT COUNT(*)' . $fromSql . $whereSql
        );

        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $pageSize = 20;
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pages));

        $statement = $pdo->prepare(
            "SELECT
                i.*,
                c.company_name,
                c.verification_status,
                u.status AS company_account_status,
                af.field_name,
                CASE
                    WHEN i.status = 'Published'
                     AND c.verification_status = 'verified'
                     AND u.status = 'active'
                     AND u.role = 'company'
                     AND i.deadline >= :today
                    THEN 1
                    ELSE 0
                END AS available_to_students,
                (
                    SELECT COUNT(*)
                    FROM applications a
                    WHERE a.internship_id = i.internship_id
                ) AS application_count"
            . $fromSql
            . $whereSql
            . ' ORDER BY i.internship_id DESC
                LIMIT :page_size OFFSET :page_offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }

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


        $statement->bindValue(
            ':today',
            date('Y-m-d'),
            PDO::PARAM_STR
        );

        $statement->execute();

        return [
            'items' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}