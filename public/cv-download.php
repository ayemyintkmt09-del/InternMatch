<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CvController.php';

$user = require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('This action requires a GET request.');
}

try {
    $profile = StudentProfileController::load(
        (int) $user['user_id']
    );

    $path = CvController::downloadPath($profile);

    if ($path === null) {
        http_response_code(404);
        exit('No downloadable CV found. Please upload your CV again.');
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Could not open CV.');
    }

    $fileInfo = fstat($handle);

    if ($fileInfo === false) {
        fclose($handle);
        throw new RuntimeException('Could not read CV information.');
    }

    $originalName = $profile['cv_original_name'] ?: 'My-CV.pdf';

    // Release the session lock before transferring the file.
    session_write_close();

    header('Content-Type: application/pdf');
    header('Content-Length: ' . $fileInfo['size']);
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');

    header(
        "Content-Disposition: attachment; filename=\"My-CV.pdf\"; "
        . "filename*=UTF-8''"
        . rawurlencode($originalName)
    );

    fpassthru($handle);
    fclose($handle);
    exit;
} catch (Throwable $exception) {
    error_log((string) $exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    exit('The CV could not be downloaded.');
}