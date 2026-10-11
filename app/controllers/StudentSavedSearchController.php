<?php
declare(strict_types=1);

final class StudentSavedSearchController
{
    public static function filters(array $input): array
    {
        $limits = [
            'q' => 100,
            'location' => 150
        ];

        $filters = [];

        foreach ($limits as $key => $limit) {
            $value = $input[$key] ?? '';

            if (
                !is_string($value)
                || mb_strlen(trim($value), 'UTF-8') > $limit
            ) {
                throw new InvalidArgumentException(
                    'Invalid search filter: ' . $key
                );
            }

            $filters[$key] = trim($value);
        }

        foreach (['field_id', 'company_id'] as $key) {
            $value = $input[$key] ?? '';

            if (
                !is_string($value)
                || (
                    $value !== ''
                    && (
                        !ctype_digit($value)
                        || (int) $value < 1
                        || (int) $value > 2147483647
                    )
                )
            ) {
                throw new InvalidArgumentException(
                    'Invalid search filter: ' . $key
                );
            }

            $filters[$key] = $value;
        }

        $type = $input['type'] ?? '';
        $sort = $input['sort'] ?? 'newest';

        if (
            !is_string($type)
            || !in_array(
                $type,
                ['', 'On-site', 'Remote', 'Hybrid'],
                true
            )
            || !is_string($sort)
            || !in_array(
                $sort,
                ['newest', 'deadline'],
                true
            )
        ) {
            throw new InvalidArgumentException(
                'Invalid type or sort selection.'
            );
        }

        $filters['type'] = $type;
        $filters['sort'] = $sort;

        return $filters;
    }


    
public static function save(
    int $userId,
    array $input
): void {
    $name = $input['name'] ?? null;

    if (
        !is_string($name)
        || trim($name) === ''
        || mb_strlen(trim($name), 'UTF-8') > 80
    ) {
        throw new InvalidArgumentException(
            'Name must be 1–80 characters.'
        );
    }

    $filters = self::filters($input);
    $pdo = db();

    $count = $pdo->prepare(
        'SELECT COUNT(*)
         FROM student_saved_searches
         WHERE user_id = :user_id'
    );

    $count->execute([
        'user_id' => $userId
    ]);

    if ((int) $count->fetchColumn() >= 20) {
        throw new InvalidArgumentException(
            'You can save up to 20 searches.'
        );
    }

    $stmt = $pdo->prepare(
        'INSERT INTO student_saved_searches
         (user_id, name, keyword, location,
          field_id, company_id, internship_type, sort_order)
         VALUES
         (:user_id, :name, :keyword, :location,
          :field_id, :company_id, :internship_type, :sort_order)'
    );

    try {
        $stmt->execute([
            'user_id' => $userId,
            'name' => trim($name),
            'keyword' => $filters['q'],
            'location' => $filters['location'],
            'field_id' => $filters['field_id'] === ''
                ? null : (int) $filters['field_id'],
            'company_id' => $filters['company_id'] === ''
                ? null : (int) $filters['company_id'],
            'internship_type' => $filters['type'],
            'sort_order' => $filters['sort'],
        ]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            throw new InvalidArgumentException(
                'Use a different saved-search name.',
                0,
                $exception
            );
        }

        throw $exception;
    }
}



public static function all(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT
            saved_search_id,
            name,
            keyword,
            location,
            field_id,
            company_id,
            internship_type,
            sort_order,
            created_at
         FROM student_saved_searches
         WHERE user_id = :user_id
         ORDER BY created_at DESC, saved_search_id DESC'
    );

    $stmt->execute([
        'user_id' => $userId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public static function remove(
    int $userId,
    int $id
): void {
    if ($id < 1) {
        throw new InvalidArgumentException(
            'Invalid saved search.'
        );
    }

    $stmt = db()->prepare(
        'DELETE FROM student_saved_searches
         WHERE user_id = :user_id
           AND saved_search_id = :saved_search_id'
    );

    $stmt->execute([
        'user_id' => $userId,
        'saved_search_id' => $id,
    ]);
}




public static function openUrl(array $row): string
{
    return url(
        'opportunities.php?'
        . http_build_query([
            'q' => $row['keyword'],
            'location' => $row['location'],
            'field_id' => $row['field_id'] ?? '',
            'company_id' => $row['company_id'] ?? '',
            'type' => $row['internship_type'],
            'sort' => $row['sort_order'],
        ])
    );
}
}
