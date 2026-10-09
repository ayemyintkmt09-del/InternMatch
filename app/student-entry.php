<?php

declare(strict_types=1);

function student_entry_path(mixed $intent): ?string
{
    if (!is_array($intent)) {
        return null;
    }

    $expires = $intent['expires'] ?? null;

    if (!is_int($expires) || $expires < time()) {
        return null;
    }

    $kind = $intent['kind'] ?? null;

    if ($kind === 'detail') {
        $id = $intent['id'] ?? null;

        if (!is_int($id) || $id < 1) {
            return null;
        }

        return 'internship-details.php?id=' . $id;
    }

    if ($kind !== 'search') {
        return null;
    }

    $filters = $intent['filters'] ?? null;

    if (!is_array($filters)) {
        return null;
    }

    $limits = [
        'q' => 100,
        'location' => 150,
        'field_id' => 10,
        'type' => 20,
        'sort' => 20,
    ];

    $query = [];

    foreach ($limits as $key => $limit) {
        $value = $filters[$key] ?? '';

        if (
            !is_string($value)
            || mb_strlen($value, 'UTF-8') > $limit
        ) {
            return null;
        }

        if ($value !== '') {
            $query[$key] = $value;
        }
    }

        return 'opportunities.php'
        . ($query !== []
            ? '?' . http_build_query($query)
            : '');
}