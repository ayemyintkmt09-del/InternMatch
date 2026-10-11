<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentProfileController.php';

$user = require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

require_post();
verify_csrf();

try {
    StudentProfileController::uploadPhoto(
        (int) $user['user_id'],
        $_FILES['profile_photo'] ?? []
    );
    flash('profile_success', 'Your profile photo has been updated.');
} catch (InvalidArgumentException $exception) {
    flash('profile_error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log((string) $exception);
    flash('profile_error', 'The profile photo could not be uploaded.');
}

redirect('student-profile.php#profile-photo');
