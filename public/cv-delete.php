<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CvController.php';

$user = require_role('student');

require_post();
verify_csrf();

try {
    CvController::remove((int) $user['user_id']);

    flash('cv_success', 'The CV has been removed from your profile.');
} catch (Throwable $exception) {
    error_log((string) $exception);

    flash(
        'cv_error',
        'Your CV could not be removed. Please try again.'
    );
}

redirect('student-profile.php#cv-section');