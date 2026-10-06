<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';
require_once __DIR__ . '/../app/controllers/CompanyFileController.php';

$user = require_role('company');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('This action requires a GET request.');
}

$kind = $_GET['kind'] ?? null;

if (
    !is_string($kind)
    || !in_array($kind, ['logo', 'verification'], true)
) {
    http_response_code(400);
    exit('Invalid file type.');
}

try {
    $company = CompanyProfileController::load(
        (int) $user['user_id']
    );

    $path = CompanyFileController::path($company, $kind);

    if ($path === null) {
        http_response_code(404);
        exit('File unavailable. Please upload it again.');
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Could not open file.');
    }

    $information = fstat($handle);

    if ($information === false) {
        fclose($handle);
        throw new RuntimeException('Could not read file information.');
    }

    session_write_close();

    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . $information['size']);

    if ($kind === 'logo') {
        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="company-logo.png"');
    } else {
        header('Content-Type: application/pdf');
        header(
            'Content-Disposition: attachment; '
            . 'filename="company-verification.pdf"'
        );
    }

    fpassthru($handle);
    fclose($handle);
    exit;
} catch (Throwable $exception) {
    error_log((string) $exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    exit('The file could not be loaded.');
}