<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';

require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

$user = require_role('student');

$rawId = $_GET['id'] ?? null;

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

    $isSaved = $internship !== null
        && SavedInternshipController::isSaved(
            (int) $user['user_id'],
            $internshipId
        );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The internship could not be loaded. Please try again.');
}

$activeNav = 'opportunities';

if ($internship === null) {
    http_response_code(404);

    $pageTitle = 'Opportunity unavailable';

    require __DIR__ . '/../app/views/student-header.php';
    ?>

    <main class="internship-details-page">
        <div class="container">
            <section class="details-content-card text-center">
                <h1>Opportunity unavailable</h1>

                <p>
                    This internship may have closed or is no longer available.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= e(url('opportunities.php')) ?>">
                    Browse Opportunities
                </a>
            </section>
        </div>
    </main>

    <?php
    require __DIR__ . '/../app/views/student-footer.php';
    exit;
}

$pageTitle = $internship['title'];

require __DIR__ . '/../app/views/student-header.php';
?>



<main class="internship-details-page">
    <div class="container">

        <a
            href="<?= e(url('opportunities.php')) ?>"
            class="back-opportunities">
            <i class="bi bi-arrow-left"></i>
            Back to Opportunities
        </a>

        <section class="internship-details-header">

            <div class="internship-company-logo">
                <?= e(mb_strtoupper(
                    mb_substr(
                        $internship['company_name'],
                        0,
                        1,
                        'UTF-8'
                    ),
                    'UTF-8'
                )) ?>
            </div>

            <div class="internship-header-content">

                <div class="internship-header-top">
                    <div>
                        <p class="dashboard-small-title">
                            INTERNSHIP OPPORTUNITY
                        </p>

                        <h1><?= e($internship['title']) ?></h1>

                        <p class="internship-company-name">
                            <?= e($internship['company_name']) ?>
                        </p>
                    </div>

                    <span class="badge bg-success-subtle text-success">
                        <i class="bi bi-patch-check-fill me-1"></i>
                        Verified Company
                    </span>
                </div>

                <div class="details-meta">
                    <span>
                        <i class="bi bi-geo-alt"></i>
                        <?= e(
                            $internship['location']
                            ?? 'Location not specified'
                        ) ?>
                    </span>

                    <span>
                        <i class="bi bi-clock"></i>
                        <?= e(
                            $internship['duration']
                            ?? 'Duration not specified'
                        ) ?>
                    </span>

                    <span>
                        <i class="bi bi-building"></i>
                        <?= e($internship['internship_type']) ?>
                    </span>
                </div>

            </div>
        </section>









        <div class="internship-action-bar">

            <form
                method="post"
                action="<?= e(url('saved-internship-action.php')) ?>"
                class="m-0">

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="internship_id"
                    value="<?= (int) $internship['internship_id'] ?>">

                <input
                    type="hidden"
                    name="action"
                    value="<?= $isSaved ? 'remove' : 'save' ?>">

                <button
                    type="submit"
                    class="btn <?= $isSaved
                        ? 'btn-outline-secondary'
                        : 'btn-outline-primary' ?>">

                    <i class="bi <?= $isSaved
                        ? 'bi-bookmark-fill'
                        : 'bi-bookmark' ?> me-2"></i>

                    <?= $isSaved ? 'Remove from Saved' : 'Save Internship' ?>

                </button>
            </form>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('saved-internships.php')) ?>">
                View Saved Internships
            </a>

        </div>










        
        <div class="row g-4">

            <div class="col-lg-8">

                <section class="details-content-card">
                    <h3>About This Internship</h3>

                    <div class="text-break">
                        <?= nl2br(e($internship['description'])) ?>
                    </div>
                </section>

                <section class="details-content-card">
                    <h3>Required Skills</h3>

                    <?php if ($internship['skills'] === []): ?>
                        <p>No specific skills listed.</p>
                    <?php else: ?>
                        <div class="details-skills">
                            <?php foreach ($internship['skills'] as $skill): ?>
                                <span>
                                    <?= e($skill['skill_name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="details-content-card">
                    <h3>About the Company</h3>

                    <h5><?= e($internship['company_name']) ?></h5>

                    <?php if (!empty($internship['industry'])): ?>
                        <p>
                            <strong>Industry:</strong>
                            <?= e($internship['industry']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($internship['company_location'])): ?>
                        <p>
                            <strong>Company Location:</strong>
                            <?= e($internship['company_location']) ?>
                        </p>
                    <?php endif; ?>

                    <div class="text-break">
                        <?= nl2br(e(
                            $internship['company_description']
                            ?: 'No company description provided.'
                        )) ?>
                    </div>
                </section>

            </div>

            <aside class="col-lg-4">

                <section class="details-sidebar-card">
                    <h3>Internship Information</h3>

                    <dl class="mt-3">
                        <dt>Academic Field</dt>
                        <dd>
                            <?= e(
                                $internship['field_name']
                                ?? 'Not specified'
                            ) ?>
                        </dd>

                        <dt>Internship Type</dt>
                        <dd><?= e($internship['internship_type']) ?></dd>

                        <dt>Duration</dt>
                        <dd>
                            <?= e(
                                $internship['duration']
                                ?? 'Not specified'
                            ) ?>
                        </dd>

                        <dt>Start Date</dt>
                        <dd>
                            <?= e($internship['start_date'] ?? 'Not specified') ?>
                        </dd>

                        <dt>End Date</dt>
                        <dd>
                            <?= e($internship['end_date'] ?? 'Not specified') ?>
                        </dd>

                        <dt>Application Deadline</dt>
                        <dd><?= e($internship['deadline']) ?></dd>

                        <dt>Positions Offered</dt>
                        <dd><?= (int) $internship['interns_needed'] ?></dd>
                    </dl>

                    <p class="small text-muted mb-0">
                        Positions offered is the company's requested number
                        of interns, not a live remaining-vacancy count.
                    </p>
                </section>

            </aside>
        </div>

    </div>
</main>

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>