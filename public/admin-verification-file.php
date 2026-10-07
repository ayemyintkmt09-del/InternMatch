<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminVerificationController.php';
require_once __DIR__ . '/../app/controllers/CompanyFileController.php';

require_role('admin');

$companyId = filter_input(
    INPUT_GET,
    'company_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$companyId) {
    http_response_code(400);
    exit('Invalid company ID.');
}

try {
    $company = AdminVerificationController::find($companyId);

    if (!$company) {
        http_response_code(404);
        exit('Company not found.');
    }

    $path = CompanyFileController::path($company, 'verification');

    if ($path === null) {
        http_response_code(404);
        exit('Verification document not found.');
    }

    $stream = fopen($path, 'rb');

    if ($stream === false) {
        throw new RuntimeException('Cannot open verification document.');
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The document could not be downloaded.');
}

header('Content-Type: application/octet-stream');
header(
    'Content-Disposition: attachment; filename="company-'
    . $companyId
    . '-verification.'
    . pathinfo($path, PATHINFO_EXTENSION)
    . '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

session_write_close();

fpassthru($stream);
fclose($stream);
exit;