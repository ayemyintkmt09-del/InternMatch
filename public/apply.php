<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';

$user = require_role('student');
$userId = (int) $user['user_id'];

$rawId = $_GET['id'] ?? $_POST['internship_id'] ?? null;

if (!is_string($rawId)) {
    http_response_code(400);
    exit('Invalid internship ID.');
}

$internshipId = filter_var(
    $rawId,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($internshipId === false) {
    http_response_code(400);
    exit('Invalid internship ID.');
}

try {
    $internship = StudentInternshipController::detail($internshipId);

    if ($internship === null) {
        http_response_code(404);
        exit('This internship is no longer available.');
    }

    $applicationStatus = ApplicationController::applicationStatus(
    $userId,
    $internshipId
);

$hasApplied = $applicationStatus !== null;


} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The application page could not be loaded.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $applicationId = ApplicationController::apply(
            $userId,
            $internshipId,
            $_POST
        );

        redirect(
            'application-confirmation.php?id='
            . $applicationId
        );

        
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);

        $error = 'Your application could not be submitted.';
    }
}

$pageTitle = 'Apply for Internship';
$activeNav = 'opportunities';

render_header(
    $user,
    $pageTitle ?? 'InternMatch',
    $activeNav ?? ''
);?>

<main class="profile-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">APPLICATION</p>
                <h1>Apply for Internship</h1>
                <p><?= e($internship['title']) ?></p>
            </div>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($hasApplied): ?>

            <div class="alert alert-success">
                <?= $applicationStatus === 'Withdrawn'
                    ? 'You withdrew this application. Reapplication is not available.'
                    : 'You have already applied for this internship.' ?>
            </div>

            <a
                class="btn btn-primary"
                href="<?= e(url('my-applications.php')) ?>">
                View My Applications
            </a>

        <?php else: ?>

            <form
                method="post"
                action="<?= e(url(
                    'apply.php?id=' . $internshipId
                )) ?>">

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="internship_id"
                    value="<?= $internshipId ?>">

                <section class="profile-section">

                    <h3><?= e($internship['title']) ?></h3>

                    <p class="text-muted">
                        <?= e($internship['company_name']) ?>
                    </p>

                    <div class="alert alert-info">
                        Your current uploaded CV will be attached to this
                        application.
                    </div>

                    <label
                        class="profile-label"
                        for="message">
                        Message to the company
                    </label>

                    <textarea
                                id="message"
                                name="message"
                                class="form-control profile-input profile-textarea"
                                rows="8"
                                maxlength="3000"
                                placeholder="Introduce yourself and explain why you are interested."
                            ><?= e(
                                is_string($_POST['message'] ?? null)
                                    ? $_POST['message']
                                    : ''
                            ) ?>
                    </textarea>

                </section>

                <div class="profile-bottom-actions">
                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e(url(
                            'internship-details.php?id=' . $internshipId
                        )) ?>">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>
                        Submit Application
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </div>
</main>

<?php render_footer($user); ?>
