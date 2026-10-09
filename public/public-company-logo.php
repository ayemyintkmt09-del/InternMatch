<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';
require_once __DIR__ . '/../app/controllers/CompanyFileController.php';


header('Cache-Control: private, no-store, max-age=0');

$rawId = $_GET['company_id'] ?? null;

$companyId = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

if ($companyId === false) {
    http_response_code(404);
    exit;
}

try {
    $company = CompanyProfileController::publicProfile($companyId);

    if ($company === null) {
        http_response_code(404);
        exit;
    }

    $path = CompanyFileController::path($company, 'logo');

    if ($path === null) {
        http_response_code(404);
        exit;
    }

    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Logo could not be opened.');
    }

    $information = fstat($handle);

    if ($information === false) {
        fclose($handle);
        throw new RuntimeException('Logo information unavailable.');
    }

    session_write_close();

    header('Content-Type: image/png');
    header('Content-Length: ' . $information['size']);
    header('X-Content-Type-Options: nosniff');

    fpassthru($handle);
    fclose($handle);
    exit;
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(404);
    exit;
}