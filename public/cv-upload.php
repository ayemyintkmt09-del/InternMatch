<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CvController.php';

$user = require_role('student');

require_post();

// If PHP rejects an oversized request, POST and FILES may be empty.
if (empty($_POST) && empty($_FILES)) {
    http_response_code(413);
    exit('Upload could not be processed. Choose a PDF under 5 MB.');
}

verify_csrf();

try {
    $file = $_FILES['cv'] ?? null;

    if (!is_array($file)) {
        throw new InvalidArgumentException('Please select a PDF CV.');
    }

    CvController::upload((int) $user['user_id'], $file);

    flash('cv_success', 'Your CV has been uploaded.');
} catch (InvalidArgumentException $exception) {
    flash('cv_error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log((string) $exception);

    flash(
        'cv_error',
        'Your CV could not be uploaded. Please try again.'
    );
}

redirect('student-profile.php#cv-section');