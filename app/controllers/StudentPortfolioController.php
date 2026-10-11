<?php
declare(strict_types=1);

final class StudentPortfolioController
{
    public const TYPES = [
        'project' => 'Project',
        'experience' => 'Experience',
        'certificate' => 'Certificate',
    ];

    public static function all(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT portfolio_id, item_type, title,
                    organization, description, link_url,
                    start_date, end_date, is_public
             FROM student_portfolio_items
             WHERE user_id = :user_id
             ORDER BY portfolio_id DESC'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
public static function one(int $userId, int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT portfolio_id, item_type, title,
                organization, description, link_url,
                start_date, end_date, is_public
         FROM student_portfolio_items
         WHERE user_id = :user_id
           AND portfolio_id = :portfolio_id
         LIMIT 1'
    );

    $stmt->execute([
        'user_id' => $userId,
        'portfolio_id' => $id,
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


public static function save(int $userId, array $post): void
{
    $id = filter_var(
        $post['portfolio_id'] ?? '0',
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id < 0) {
        throw new InvalidArgumentException(
            'Invalid portfolio entry.'
        );
    }

    $values = [];

    foreach ([
        'item_type',
        'title',
        'organization',
        'description',
        'link_url',
        'start_date',
        'end_date'
    ] as $key) {
        if (!is_string($post[$key] ?? '')) {
            throw new InvalidArgumentException(
                'Invalid form input.'
            );
        }

        $values[$key] = trim($post[$key] ?? '');
    }

    if (!isset(self::TYPES[$values['item_type']])) {
        throw new InvalidArgumentException(
            'Select a valid entry type.'
        );
    }

    $limits = [
        'title' => 150,
        'organization' => 150,
        'description' => 3000,
        'link_url' => 500,
    ];

    foreach ($limits as $key => $max) {
        if (mb_strlen($values[$key], 'UTF-8') > $max) {
            throw new InvalidArgumentException(
                "$key is too long."
            );
        }
    }

    if ($values['title'] === '') {
        throw new InvalidArgumentException(
            'Title is required.'
        );
    }

    if ($values['link_url'] !== '') {
        $scheme = strtolower((string) parse_url(
            $values['link_url'],
            PHP_URL_SCHEME
        ));

        if (
            !filter_var($values['link_url'], FILTER_VALIDATE_URL)
            || !in_array($scheme, ['https', 'http'], true)
        ) {
            throw new InvalidArgumentException(
                'Enter a valid HTTP or HTTPS link.'
            );
        }
    }

    
foreach (['start_date', 'end_date'] as $key) {
    $dateValue = $values[$key];

    if ($dateValue === '') {
        continue;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $dateValue
    );

    if (
        !$date
        || $date->format('Y-m-d') !== $dateValue
        || $dateValue < '1900-01-01'
        || $dateValue > '2100-12-31'
    ) {
        throw new InvalidArgumentException(
            'Enter valid dates.'
        );
    }
}

if (
    $values['start_date'] !== ''
    && $values['end_date'] !== ''
    && $values['end_date'] < $values['start_date']
) {
    throw new InvalidArgumentException(
        'End date cannot be before start date.'
    );
}



$visibilityInput = $post['is_public'] ?? '0';

if (!in_array($visibilityInput, ['0', '1'], true)) {
    throw new InvalidArgumentException(
        'Invalid portfolio visibility.'
    );
}

$isPublic = $visibilityInput === '1' ? 1 : 0;


$params = [
    'user_id' => $userId,
    'item_type' => $values['item_type'],
    'title' => $values['title'],
    'organization' => $values['organization'] ?: null,
    'description' => $values['description'] ?: null,
    'link_url' => $values['link_url'] ?: null,
    'start_date' => $values['start_date'] ?: null,
    'end_date' => $values['end_date'] ?: null,
    'is_public' => $isPublic,
];

if ($id > 0) {
    if (self::one($userId, $id) === null) {
        throw new InvalidArgumentException(
            'Portfolio entry not found.'
        );
    }

    $params['portfolio_id'] = $id;

    $stmt = db()->prepare(
        'UPDATE student_portfolio_items SET
         item_type = :item_type,
         title = :title,
         organization = :organization,
         description = :description,
         link_url = :link_url,
         start_date = :start_date,
         end_date = :end_date,
         is_public = :is_public         
         WHERE user_id = :user_id
           AND portfolio_id = :portfolio_id'
    );
} else {
    $stmt = db()->prepare(
        'INSERT INTO student_portfolio_items
         (user_id, item_type, title, organization,
          description, link_url, start_date, end_date, is_public)
         VALUES
         (:user_id, :item_type, :title, :organization,
          :description, :link_url, :start_date, :end_date, :is_public)'
    );
}

$stmt->execute($params);




}



public static function delete(int $userId, int $id): void
{
    if ($id <= 0) {
        throw new InvalidArgumentException(
            'Invalid entry.'
        );
    }

    $stmt = db()->prepare(
        'DELETE FROM student_portfolio_items
         WHERE user_id = :user_id
           AND portfolio_id = :portfolio_id'
    );

    $stmt->execute([
        'user_id' => $userId,
        'portfolio_id' => $id,
    ]);
}









}
