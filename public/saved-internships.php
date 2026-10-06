<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

$user = require_role('student');

try {
    $savedInternships = SavedInternshipController::listing(
        (int) $user['user_id']
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('Your saved internships could not be loaded.');
}

$success = take_flash('saved_internship_success');
$error = take_flash('saved_internship_error');

$pageTitle = 'Saved Internships';
$activeNav = 'saved';

require __DIR__ . '/../app/views/student-header.php';
?>

<main class="opportunities-page">
    <div class="container">

        <div class="opportunities-header">
            <div>
                <p class="dashboard-small-title">YOUR SHORTLIST</p>
                <h1>Saved Internships</h1>
                <p>Keep track of opportunities you want to revisit.</p>
            </div>
        </div>

        <?php if ($success !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="opportunity-results-header">
            <p class="mb-0">
                <strong><?= count($savedInternships) ?></strong>
                saved internships
            </p>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('opportunities.php')) ?>">
                Browse Opportunities
            </a>
        </div>

        <?php if ($savedInternships === []): ?>

            <div class="opportunity-card text-center py-5">
                <i class="bi bi-bookmark fs-1 text-primary"></i>

                <h3 class="mt-3">No saved internships yet</h3>

                <p class="text-muted">
                    Open an internship's details and click Save Internship.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= e(url('opportunities.php')) ?>">
                    Find Internships
                </a>
            </div>

        <?php else: ?>

            <?php foreach ($savedInternships as $internship): ?>
                <?php
                $available = (int) $internship['is_available'] === 1;
                ?>

                <div class="opportunity-card">

                    <div class="opportunity-card-top">
                        <div class="company-logo-placeholder">
                            <i class="bi bi-bookmark-fill"></i>
                        </div>

                        <div class="opportunity-main">

                            <?php if ($available): ?>

                                <div class="opportunity-title-row">
                                    <div>
                                        <h3>
                                            <?= e($internship['title']) ?>
                                        </h3>

                                        <p>
                                            <?= e($internship['company_name']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="opportunity-meta">
                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        <?= e(
                                            $internship['location']
                                            ?? 'Location not specified'
                                        ) ?>
                                    </span>

                                    <span>
                                        <i class="bi bi-building"></i>
                                        <?= e($internship['internship_type']) ?>
                                    </span>

                                    <span>
                                        <i class="bi bi-calendar3"></i>
                                        Apply by <?= e($internship['deadline']) ?>
                                    </span>
                                </div>

                            <?php else: ?>

                                <h3>Opportunity unavailable</h3>

                                <p class="text-muted mb-0">
                                    This saved internship is no longer
                                    available to browse. You can remove it
                                    from your saved list.
                                </p>

                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="opportunity-card-bottom">

                        <span class="posted-date">
                            Saved <?= e(substr(
                                $internship['saved_at'],
                                0,
                                10
                            )) ?>
                        </span>

                        <div class="d-flex flex-wrap gap-2">

                            <?php if ($available): ?>
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= e(url(
                                        'internship-details.php?id='
                                        . (int) $internship['internship_id']
                                    )) ?>">
                                    View Details
                                </a>
                            <?php endif; ?>

                            <form
                                method="post"
                                action="<?= e(url('saved-internship-action.php')) ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove">

                                <input
                                    type="hidden"
                                    name="internship_id"
                                    value="<?= (int) $internship['internship_id'] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger">
                                    <i class="bi bi-bookmark-dash me-1"></i>
                                    Remove
                                </button>

                            </form>

                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>