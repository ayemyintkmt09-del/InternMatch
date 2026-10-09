<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('company');
$userId = (int) $user['user_id'];

$rawId = $_GET['id'] ?? $_POST['application_id'] ?? null;

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

$error = null;
$success = take_flash('company_application_success');

try {
    $application = CompanyApplicationController::application(
        $userId,
        $applicationId
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(404);
    exit('Application not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        CompanyApplicationController::updateStatus(
            $userId,
            $applicationId,
            $_POST
        );

        flash(
            'company_application_success',
            'Application review saved.'
        );

        redirect(
            'company-application-review.php?id=' . $applicationId
        );
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);

        $error = 'The application could not be updated.';
    }

    $application = CompanyApplicationController::application(
        $userId,
        $applicationId
    );
}

$pageTitle = 'Review Application';
render_header(
    $user,
    $pageTitle ?? 'InternMatch',
    $activeNav ?? ''
);

?>

<main class="profile-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">APPLICATION REVIEW</p>
                <h1><?= e($application['student_name']) ?></h1>
                <p><?= e($application['title']) ?></p>
            </div>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url(
                    'company-applicants.php?internship_id='
                    . (int) $application['internship_id']
                )) ?>">
                Back to Applicants
            </a>
        </div>

        <?php if ($success !== null): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <section class="profile-section">

            <h3>Applicant Information</h3>

            <p>
                <strong>Email:</strong>
                <?= e($application['student_email']) ?>
            </p>

            <p>
                <strong>Phone:</strong>
                <?= e($application['phone'] ?: 'Not provided') ?>
            </p>

            <p>
                <strong>University:</strong>
                <?= e($application['university'] ?: 'Not provided') ?>
            </p>

            <p>
                <strong>Degree:</strong>
                <?= e($application['degree'] ?: 'Not provided') ?>
            </p>

            <p>
                <strong>Application message:</strong>
            </p>

            <div class="border rounded p-3 mb-4">
                <?= nl2br(e(
                    $application['message']
                    ?: 'No message provided.'
                )) ?>
            </div>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url(
                    'company-cv-download.php?id=' . $applicationId
                )) ?>">
                <i class="bi bi-download me-2"></i>
                Download Submitted CV
            </a>

        </section>

        <section class="profile-section">

            <?php if ($application['status'] === 'Withdrawn'): ?>

                <div class="alert alert-warning mb-0" role="alert">
                    This application has been withdrawn by the student.
                    Reviewing and changing its status are no longer available.
                </div>

            <?php else: ?>

                <h3>Update Application</h3>

            <form
                method="post"
                action="<?= e(url(
                    'company-application-review.php?id='
                    . $applicationId
                )) ?>">

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="expected_status"
                    value="<?= e($application['status']) ?>">

                <input
                    type="hidden"
                    name="application_id"
                    value="<?= $applicationId ?>">

                <div class="mb-4">
                    <label
                        class="profile-label"
                        for="status">
                        Status
                    </label>



                    <?php
                        $acceptedCount = 0;
                        $internsNeeded = 0;
                    ?>


                    <select
                        id="status"
                        name="status"
                        class="form-select profile-input"
                        required>

                        <?php foreach ([
                            'Under Review',
                            'Shortlisted',
                            'Accepted',
                            'Rejected',
                        ] as $status): ?>

                            <option
                                value="<?= e($status) ?>"
                                <?= $application['status'] === $status
                                    ? 'selected' : '' ?>>
                                <?= e($status) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label
                        class="profile-label"
                        for="interview_date">
                        Interview Date — Myanmar time (optional)
                    </label>

                    <input
                        type="datetime-local"
                        id="interview_date"
                        name="interview_date"
                        class="form-control profile-input"
                        min="<?= e(
                            (new DateTimeImmutable(
                                'now',
                                new DateTimeZone(date_default_timezone_get())
                            ))->format('Y-m-d\TH:i')
                        ) ?>"

                        value="<?= !empty(
                            $application['interview_date']
                        ) ? e(str_replace(
                            ' ',
                            'T',
                            substr(
                                $application['interview_date'],
                                0,
                                16
                            )
                        )) : '' ?>">
                </div>

                <div class="mb-4">
                    <label
                        class="profile-label"
                        for="interview_notes">
                        Interview Notes
                    </label>

                    <textarea
                        id="interview_notes"
                        name="interview_notes"
                        class="form-control profile-input profile-textarea"
                        rows="6"
                        maxlength="3000"><?= e(
                            $application['interview_notes'] ?? ''
                        ) ?></textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary">
                    <i class="bi bi-check2-circle me-2"></i>
                    Save Review
                </button>

            </form>

            <?php endif; ?>

        </section>
    </div>
</main>
<?php render_footer($user); ?>
