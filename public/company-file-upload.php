<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyFileController.php';

$user = require_role('company');

require_post();

if (empty($_POST) && empty($_FILES)) {
    http_response_code(413);
    exit('Upload could not be processed. Check the file size.');
}

verify_csrf();

$kind = $_POST['kind'] ?? null;

if (
    !is_string($kind)
    || !in_array($kind, ['logo', 'verification'], true)
) {
    http_response_code(400);
    exit('Invalid upload type.');
}

try {
    $file = $_FILES['company_file'] ?? null;

    if (!is_array($file)) {
        throw new InvalidArgumentException('Please select a file.');
    }

    CompanyFileController::upload(
        (int) $user['user_id'],
        $kind,
        $file
    );

    flash(
        'company_file_success',
        $kind === 'logo'
            ? 'Your company logo has been updated.'
            : 'Your document has been uploaded and is pending review.'
    );
} catch (InvalidArgumentException $exception) {
    flash('company_file_error', $exception->getMessage());
} catch (Throwable $exception) {
    error_log((string) $exception);

    flash(
        'company_file_error',
        'The file could not be uploaded. Please try again.'
    );
}

redirect('company-profile.php#company-files');