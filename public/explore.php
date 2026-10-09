<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/student-entry.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';

header('Cache-Control: no-store');

try {
    // Validate the current account before storing any guest intent.
    $user = current_user();

    // Company and admin accounts keep their own dashboard flow.
    if ($user !== null && $user['role'] !== 'student') {
        unset($_SESSION['student_entry']);

        redirect(dashboard_path($user['role']));
    }

    if (array_key_exists('id', $_GET)) {
        $rawId = $_GET['id'];

        $id = is_string($rawId)
            ? filter_var(
                $rawId,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            )
            : false;

        if ($id === false) {
            throw new InvalidArgumentException(
                'Invalid internship selection.'
            );
        }

        $internship = StudentInternshipController::detail($id);

        if ($internship === null) {
            http_response_code(404);
            exit('This internship is no longer available.');
        }

        $intent = [
            'kind' => 'detail',
            'id' => $id,
            'expires' => time() + 1800,
        ];
    } else {
        // Reuse existing search validation and supported filter values.
        $result = StudentInternshipController::search($_GET);

        $intent = [
            'kind' => 'search',
            'filters' => $result['filters'],
            'expires' => time() + 1800,
        ];
    }

    $destination = student_entry_path($intent);

    if ($destination === null) {
        throw new InvalidArgumentException(
            'Invalid internship destination.'
        );
    }

    if ($user !== null) {
        unset($_SESSION['student_entry']);

        redirect($destination);
    }

    $_SESSION['student_entry'] = $intent;

    flash(
        'login_success',
        'Sign in with your student account to continue. '
        . 'Your selection will be kept for 30 minutes.'
    );

    redirect('login.php');
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(503);
    exit('Internships are temporarily unavailable. Please try again.');
}