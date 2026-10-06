<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

$user = require_role('student');

require_post();
verify_csrf();

try {
    $action = $_POST['action'] ?? null;
    $rawId = $_POST['internship_id'] ?? null;

    if (
        !is_string($action)
        || !in_array($action, ['save', 'remove'], true)
        || !is_string($rawId)
    ) {
        throw new InvalidArgumentException(
            'Invalid saved-internship request.'
        );
    }

    $internshipId = filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($internshipId === false) {
        throw new InvalidArgumentException(
            'Invalid internship ID.'
        );
    }

    $userId = (int) $user['user_id'];

    if ($action === 'save') {
        SavedInternshipController::save($userId, $internshipId);

        $message = 'This internship is in your saved list.';
    } else {
        SavedInternshipController::remove($userId, $internshipId);

        $message = 'This internship has been removed from your saved list.';
    }

    flash('saved_internship_success', $message);
} catch (InvalidArgumentException $exception) {
    flash('saved_internship_error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log((string) $exception);

    flash(
        'saved_internship_error',
        'Your saved internships could not be updated. Please try again.'
    );
}

redirect('saved-internships.php');