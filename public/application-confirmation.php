<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');
$userId = (int) $user['user_id'];

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
    $application = ApplicationController::findForStudent(
        $userId,
        $applicationId
    );
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('The application could not be loaded.');
}

if ($application === null) {
    http_response_code(404);
    exit('Application not found.');
}

$statusLabels = [
    'Pending' => 'Submitted',
    'Under Review' => 'Under review',
    'Shortlisted' => 'Shortlisted',
    'Accepted' => 'Accepted',
    'Rejected' => 'Not selected',
    'Withdrawn' => 'Withdrawn',
];

$statusClasses = [
    'Pending' => 'text-bg-primary',
    'Under Review' => 'text-bg-warning',
    'Shortlisted' => 'text-bg-info',
    'Accepted' => 'text-bg-success',
    'Rejected' => 'text-bg-secondary',
    'Withdrawn' => 'text-bg-dark',
];

$currentStatus = (string) $application['status'];

$statusLabel = $statusLabels[$currentStatus]
    ?? $currentStatus;

$statusClass = $statusClasses[$currentStatus]
    ?? 'text-bg-secondary';

render_header(
    $user,
    'Application Submitted',
    'applications'
);
?>

<main class="profile-page" id="main-content" tabindex="-1">
    <div class="container py-5">

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5 text-center">

                        <div class="mb-3">
                            <i
                                class="bi bi-check-circle-fill text-success"
                                style="font-size: 4rem;"
                                aria-hidden="true">
                            </i>
                        </div>

                        <h1 class="h3 mb-3">
                            Application submitted successfully
                        </h1>

                        <p class="text-muted mb-4">
                            Your application has been sent to
                            <?= e($application['company_name']) ?>.
                        </p>

                        <div class="alert alert-success text-start">
                            <strong>Application #<?= e(
                                (string) $application['application_id']
                            ) ?></strong>
                            <br>
                            Keep this number for your records.
                        </div>

                        <div class="text-start border rounded p-3 mb-4">
                            <h2 class="h5 mb-3">
                                Application details
                            </h2>

                            <dl class="row mb-0">

                                <dt class="col-sm-5">
                                    Internship
                                </dt>

                                <dd class="col-sm-7">
                                    <?= e($application['title']) ?>
                                </dd>

                                <dt class="col-sm-5">
                                    Company
                                </dt>

                                <dd class="col-sm-7">
                                    <?= e($application['company_name']) ?>
                                </dd>

                                <dt class="col-sm-5">
                                    Submitted
                                </dt>

                                <dd class="col-sm-7">
                                    <?= e(
                                        format_utc_datetime(
                                            $application['application_date']
                                        )
                                    ) ?>
                                </dd>

                                <dt class="col-sm-5">
                                    Status
                                </dt>

                                <dd class="col-sm-7">
                                    <span class="badge <?= e($statusClass) ?>">
                                        <?= e($statusLabel) ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5">
                                    CV attached
                                </dt>

                                <dd class="col-sm-7">
                                    <?= e(
                                        $application['cv_original_name']
                                        ?: 'Submitted CV'
                                    ) ?>
                                </dd>

                            </dl>
                        </div>

                        <p class="text-muted small">
                            You can check status updates from
                            <strong>My Applications</strong>.
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-2">

                            <a
                                class="btn btn-primary"
                                href="<?= e(url(
                                    'my-applications.php'
                                )) ?>">
                                <i class="bi bi-folder2-open me-2"></i>
                                View My Applications
                            </a>

                            <a
                                class="btn btn-outline-secondary"
                                href="<?= e(url(
                                    'opportunities.php'
                                )) ?>">
                                <i class="bi bi-search me-2"></i>
                                Browse More Internships
                            </a>

                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<?php render_footer($user); ?>