<?php

declare(strict_types=1);

final class StudentInternshipController
{
    public static function visibility(): string
    {
        return "i.status = 'Published'
            AND i.deadline >= :today
            AND c.verification_status = 'verified'
            AND u.status = 'active'
            AND u.role = 'company'";
    }

    private static function input(
        array $input,
        string $key,
        int $limit
    ): string {
        $value = $input[$key] ?? '';

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Invalid search input.'
            );
        }

        $value = trim($value);

        if (mb_strlen($value, 'UTF-8') > $limit) {
            throw new InvalidArgumentException(
                ucfirst($key) . " must not exceed {$limit} characters."
            );
        }

        return $value;
    }

    public static function fields(): array
    {
        return db()->query(
            'SELECT field_id, field_name
             FROM academic_fields
             ORDER BY field_name'
        )->fetchAll();
    }

    public static function search(array $input): array
    {
        $filters = [
            'q' => self::input($input, 'q', 100),
            'location' => self::input($input, 'location', 150),
            'field_id' => self::input($input, 'field_id', 10),
            'type' => self::input($input, 'type', 20),
            'sort' => self::input($input, 'sort', 20),
        ];

        if ($filters['sort'] === '') {
            $filters['sort'] = 'newest';
        }

        if (
            !in_array(
                $filters['type'],
                ['', 'On-site', 'Remote', 'Hybrid'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid internship type.'
            );
        }

        if (!in_array($filters['sort'], ['newest', 'deadline'], true)) {
            throw new InvalidArgumentException('Invalid sort option.');
        }

        $where = [self::visibility()];
        $parameters = ['today' => date('Y-m-d')];

        if ($filters['q'] !== '') {
            $where[] = '(
                LOCATE(:title_keyword, i.title) > 0
                OR LOCATE(:company_keyword, c.company_name) > 0
            )';

            $parameters['title_keyword'] = $filters['q'];
            $parameters['company_keyword'] = $filters['q'];
        }

        if ($filters['location'] !== '') {
            $where[] = 'LOCATE(:location_keyword, i.location) > 0';
            $parameters['location_keyword'] = $filters['location'];
        }

        if ($filters['field_id'] !== '') {
            $fieldId = filter_var(
                $filters['field_id'],
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($fieldId === false) {
                throw new InvalidArgumentException(
                    'Invalid academic field.'
                );
            }

            $where[] = 'i.field_id = :field_id';
            $parameters['field_id'] = $fieldId;
        }

        if ($filters['type'] !== '') {
            $where[] = 'i.internship_type = :internship_type';
            $parameters['internship_type'] = $filters['type'];
        }

        $pageValue = self::input($input, 'page', 10);
        $page = 1;

        if ($pageValue !== '') {
            $page = filter_var(
                $pageValue,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                        'max_range' => 1000000,
                    ],
                ]
            );

            if ($page === false) {
                throw new InvalidArgumentException(
                    'Invalid page number.'
                );
            }
        }

        $from = '
            FROM internships AS i
            JOIN companies AS c
                ON c.company_id = i.company_id
            JOIN users AS u
                ON u.user_id = c.user_id
            LEFT JOIN academic_fields AS af
                ON af.field_id = i.field_id
        ';

        $condition = implode(' AND ', $where);

        $countStatement = db()->prepare(
            'SELECT COUNT(*) ' . $from . ' WHERE ' . $condition
        );

        $countStatement->execute($parameters);

        $total = (int) $countStatement->fetchColumn();
        $perPage = 10;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        $order = $filters['sort'] === 'deadline'
            ? 'i.deadline ASC, i.internship_id DESC'
            : 'i.published_at DESC, i.internship_id DESC';

        $statement = db()->prepare(
            'SELECT
                i.*,
                c.company_name,
                af.field_name
             ' . $from . '
             WHERE ' . $condition . '
             ORDER BY ' . $order . '
             LIMIT :limit OFFSET :offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(
                ':' . $key,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
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

    public static function detail(int $internshipId): ?array
    {
        $statement = db()->prepare(
            'SELECT
                i.*,
                c.company_name,
                c.description AS company_description,
                c.industry,
                c.location AS company_location,
                af.field_name
             FROM internships AS i
             JOIN companies AS c
                ON c.company_id = i.company_id
             JOIN users AS u
                ON u.user_id = c.user_id
             LEFT JOIN academic_fields AS af
                ON af.field_id = i.field_id
             WHERE i.internship_id = :internship_id
               AND ' . self::visibility() . '
             LIMIT 1'
        );

        $statement->execute([
            'internship_id' => $internshipId,
            'today' => date('Y-m-d'),
        ]);

        $internship = $statement->fetch();

        if (!$internship) {
            return null;
        }

        $skills = db()->prepare(
            'SELECT s.skill_id, s.skill_name
             FROM internship_skills AS isk
             JOIN skills AS s
                ON s.skill_id = isk.skill_id
             WHERE isk.internship_id = :internship_id
             ORDER BY s.skill_name'
        );

        $skills->execute([
            'internship_id' => $internshipId,
        ]);

        $internship['skills'] = $skills->fetchAll();

        return $internship;
    }
}