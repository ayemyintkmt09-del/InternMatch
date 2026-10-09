<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

header('Cache-Control: no-store');

if (array_key_exists('id', $_GET)) {
    $rawId = $_GET['id'];

    $id = is_string($rawId)
        ? filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        )
        : false;

    if ($id === false) {
        http_response_code(400);
        exit('Invalid internship ID.');
    }

    redirect('internship-details.php?id=' . $id);
}

$filters = [];

foreach (
    ['q', 'location', 'field_id', 'type', 'sort', 'page']
    as $key
) {
    if (array_key_exists($key, $_GET)) {
        $filters[$key] = $_GET[$key];
    }
}

$query = http_build_query($filters);

redirect(
    'opportunities.php'
    . ($query !== '' ? '?' . $query : '')
);