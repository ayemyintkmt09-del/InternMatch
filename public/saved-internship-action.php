<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

$user = require_role('student');

require_post();
verify_csrf();

$returnPage = $_POST['return_to'] ?? 'saved-internships.php';

$allowedPages = [
    'saved-internships.php',
    'opportunities.php',
    'internship-details.php',
];

if (
    !is_string($returnPage)
    || !in_array($returnPage, $allowedPages, true)
) {
    $returnPage = 'saved-internships.php';
}

$returnFilters = $_POST['return_filters'] ?? [];

if (!is_array($returnFilters)) {
    $returnFilters = [];
}

$allowedFields = match ($returnPage) {
    'opportunities.php' => [
        'q' => 100,
        'location' => 150,
        'field_id' => 10,
        'type' => 20,
        'sort' => 20,
        'page' => 10,
    ],
    'saved-internships.php' => [
        'q' => 100,
        'availability' => 20,
        'sort' => 20,
    ],
    default => [],
};

$query = [];

foreach ($allowedFields as $key => $limit) {
    $value = $returnFilters[$key] ?? '';

    if (!is_string($value)) {
        continue;
    }

    $value = trim($value);

    if (
        $value !== ''
        && mb_strlen($value, 'UTF-8') <= $limit
    ) {
        $query[$key] = $value;
    }
}

$allowedValues = $returnPage === 'opportunities.php'
    ? [
        'type' => ['On-site', 'Remote', 'Hybrid'],
        'sort' => ['newest', 'deadline'],
    ]
    : [
        'availability' => ['available', 'unavailable'],
        'sort' => ['newest', 'oldest', 'deadline'],
    ];

foreach ($allowedValues as $key => $values) {
    if (
        isset($query[$key])
        && !in_array($query[$key], $values, true)
    ) {
        unset($query[$key]);
    }
}

foreach (['page', 'field_id'] as $key) {
    if (!isset($query[$key])) {
        continue;
    }

    $number = filter_var(
        $query[$key],
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => $key === 'page'
                    ? 1000000
                    : PHP_INT_MAX,
            ],
        ]
    );

    if ($number === false) {
        unset($query[$key]);
    } else {
        $query[$key] = $number;
    }
}

$rawId = $_POST['internship_id'] ?? null;

$internshipId = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

if ($returnPage === 'internship-details.php') {
    if ($internshipId !== false) {
        $query = ['id' => $internshipId];
    } else {
        $returnPage = 'saved-internships.php';
        $query = [];
    }
}

try {
    $action = $_POST['action'] ?? null;

    if (
        !is_string($action)
        || !in_array($action, ['save', 'remove'], true)
        || $internshipId === false
    ) {
        throw new InvalidArgumentException(
            'Invalid saved-internship request.'
        );
    }

    $userId = (int) $user['user_id'];

    if ($action === 'save') {
        SavedInternshipController::save(
            $userId,
            $internshipId
        );

        $message = 'This internship is in your saved list.';
    } else {
        SavedInternshipController::remove(
            $userId,
            $internshipId
        );

        $message = 'This internship has been removed from your saved list.';
    }

    flash('saved_internship_success', $message);
} catch (InvalidArgumentException $exception) {
    flash(
        'saved_internship_error',
        $exception->getMessage()
    );
} catch (Throwable $exception) {
    error_log((string) $exception);

    flash(
        'saved_internship_error',
        'Your saved internships could not be updated. Please try again.'
    );
}

$destination = $returnPage;

if ($query !== []) {
    $destination .= '?' . http_build_query($query);
}

redirect($destination);