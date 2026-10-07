<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';

$user = require_role('company');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('This action requires a GET request.');
}

$rawId = $_GET['id'] ?? null;

if (!is_string($rawId)) {
    http_response_code(400);
    exit('Invalid application ID.');
}

$applicationId = filter_var(
    $rawId,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($applicationId === false) {
    http_response_code(400);
    exit('Invalid application ID.');
}

try {
    $application = CompanyApplicationController::application(
        (int) $user['user_id'],
        $applicationId
    );

    $stored = $application['cv_path'];

    if (!is_string($stored)) {
        throw new RuntimeException('CV path is unavailable.');
    }

    $prefix = 'storage/private/cvs/';

    if (
        !str_starts_with($stored, $prefix)
        || !preg_match(
            '~^storage/private/cvs/[a-f0-9]{48}\.pdf$~',
            $stored
        )
    ) {
        throw new RuntimeException('Invalid CV path.');
    }

    $directory = realpath(
        dirname(__DIR__) . '/storage/private/cvs'
    );

    if ($directory === false) {
        throw new RuntimeException('CV directory unavailable.');
    }

    $filename = basename($stored);
    $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);

    if (
        $path === false
        || dirname($path) !== $directory
        || !is_file($path)
        || !is_readable($path)
    ) {
        http_response_code(404);
        exit('The submitted CV is unavailable.');
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Could not open the CV.');
    }

    $information = fstat($handle);

    if ($information === false) {
        fclose($handle);
        throw new RuntimeException('Could not read the CV.');
    }

    session_write_close();

    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: application/pdf');
    header('Content-Length: ' . $information['size']);

    $downloadName = $application['cv_original_name'];

    if (
        !is_string($downloadName)
        || $downloadName === ''
        || !preg_match('/\.pdf$/i', $downloadName)
    ) {
        $downloadName = 'submitted-cv.pdf';
    }

    $downloadName = preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        $downloadName
    );

    header(
        'Content-Disposition: attachment; filename="'
        . $downloadName
        . '"'
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

    exit('The submitted CV could not be loaded.');
}