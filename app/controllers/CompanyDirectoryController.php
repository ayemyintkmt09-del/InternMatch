<?php

declare(strict_types=1);

final class CompanyDirectoryController
{
    private static function text(
        array $input,
        string $key,
        int $limit
    ): string {
        $value = $input[$key] ?? '';

        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid filter input.');
        }

        $value = trim($value);

        if (mb_strlen($value, 'UTF-8') > $limit) {
            throw new InvalidArgumentException(
                ucfirst($key) . " must not exceed {$limit} characters."
            );
        }

        return $value;
    }

    private static function eligibility(): string
    {
        return "c.verification_status = 'verified'
            AND u.status = 'active'
            AND u.role = 'company'";
    }

    public static function industries(): array
    {
        return db()->query(
            'SELECT DISTINCT TRIM(c.industry) AS industry
             FROM companies AS c
             JOIN users AS u ON u.user_id = c.user_id
             WHERE ' . self::eligibility() . "
               AND c.industry IS NOT NULL
               AND TRIM(c.industry) <> ''
             ORDER BY industry"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function search(array $input): array
    {
        $filters = [
            'q' => self::text($input, 'q', 100),
            'industry' => self::text($input, 'industry', 100),
            'location' => self::text($input, 'location', 150),
            'hiring' => self::text($input, 'hiring', 1),
            'sort' => self::text($input, 'sort', 20),
        ];

        if ($filters['sort'] === '') {
            $filters['sort'] = 'name';
        }

        if (!in_array($filters['sort'], ['name', 'open'], true)) {
            throw new InvalidArgumentException('Invalid sort option.');
        }

        if (!in_array($filters['hiring'], ['', '1'], true)) {
            throw new InvalidArgumentException('Invalid hiring filter.');
        }

        $rawPage = self::text($input, 'page', 10);

        $page = $rawPage === ''
            ? 1
            : filter_var(
                $rawPage,
                FILTER_VALIDATE_INT,
                ['options' => [
                    'min_range' => 1,
                    'max_range' => 1000000,
                ]]
            );

        if ($page === false) {
            throw new InvalidArgumentException('Invalid page number.');
        }

        $conditions = [self::eligibility()];
        $parameters = [];

        if ($filters['q'] !== '') {
            $conditions[] =
                "LOCATE(LOWER(:company_q), LOWER(c.company_name)) > 0";
            $parameters['company_q'] = $filters['q'];
        }

        if ($filters['industry'] !== '') {
            $conditions[] =
                'TRIM(c.industry) = :industry';
            $parameters['industry'] = $filters['industry'];
        }

        if ($filters['location'] !== '') {
            $conditions[] =
                "LOCATE(
                    LOWER(:location),
                    LOWER(COALESCE(c.location, ''))
                ) > 0";
            $parameters['location'] = $filters['location'];
        }

        if ($filters['hiring'] === '1') {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM internships AS available
                WHERE available.company_id = c.company_id
                  AND available.status = 'Published'
                  AND available.deadline >= :hiring_today
            )";

            $parameters['hiring_today'] = date('Y-m-d');
        }

        $from = ' FROM companies AS c
                  JOIN users AS u ON u.user_id = c.user_id
                  WHERE ' . implode(' AND ', $conditions);

        $count = db()->prepare('SELECT COUNT(*)' . $from);
        $count->execute($parameters);

        $total = (int) $count->fetchColumn();
        $perPage = 12;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $order = $filters['sort'] === 'open'
            ? 'open_count DESC, c.company_name ASC, c.company_id ASC'
            : 'c.company_name ASC, c.company_id ASC';

        $statement = db()->prepare(
            "SELECT
                c.company_id,
                c.company_name,
                c.industry,
                c.location,
                CASE
                    WHEN c.logo_path IS NOT NULL
                         AND c.logo_path <> ''
                    THEN 1 ELSE 0
                END AS company_has_logo,
                (
                    SELECT COUNT(*)
                    FROM internships AS available
                    WHERE available.company_id = c.company_id
                      AND available.status = 'Published'
                      AND available.deadline >= :count_today
                ) AS open_count"
            . $from .
            ' ORDER BY ' . $order .
            ' LIMIT :limit OFFSET :offset'
        );

        $parameters['count_today'] = date('Y-m-d');

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                PDO::PARAM_STR
            );
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(
            ':offset',
            ($page - 1) * $perPage,
            PDO::PARAM_INT
        );

        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'filters' => $filters,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }
}