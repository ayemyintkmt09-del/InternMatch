<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';

require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';

require_once __DIR__ . '/../app/controllers/MatchingController.php';



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

        $applicationStatus = $internship !== null
        ? ApplicationController::applicationStatus(
            (int) $user['user_id'],
            $internshipId
        )
        : null;

    $hasApplied = $applicationStatus !== null;


    $matchScore = $internship !== null
    ? MatchingController::forInternship(
        (int) $user['user_id'],
        $internship
    )
    : null;




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





            <?php if ($hasApplied): ?>

            <button
                type="button"
                class="btn btn-success"
                disabled>
                <i class="bi bi-check-circle me-2"></i>
                <?= $applicationStatus === 'Withdrawn'
                    ? 'Application Withdrawn'
                    : 'Application Submitted' ?>
            </button>

        <?php else: ?>

            <a
                class="btn btn-primary"
                href="<?= e(url(
                    'apply.php?id=' . (int) $internship['internship_id']
                )) ?>">
                <i class="bi bi-send me-2"></i>
                Apply Now
            </a>

        <?php endif; ?>

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


                <?php if ($matchScore !== null): ?>
                    <section class="details-sidebar-card">
                        <h3>Your Match</h3>

                        <div class="display-6 text-primary fw-bold mb-3">
                            <?= number_format(
                                (float) $matchScore['overall'],
                                1
                            ) ?>%
                        </div>

                        <?php
                        $matchRows = [
                            'Skills' => $matchScore['skill'],
                            'Academic Field' => $matchScore['academic_field'],
                            'Interests' => $matchScore['interest'],
                            'Availability' => $matchScore['availability'],
                            'Location' => $matchScore['location'],
                        ];
                        ?>

                        <?php foreach ($matchRows as $label => $value): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?= e($label) ?></span>
                                    <strong><?= (int) $value ?>%</strong>
                                </div>

                                <div
                                    class="progress"
                                    role="progressbar"
                                    aria-label="<?= e($label) ?> match"
                                    aria-valuenow="<?= (int) $value ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">

                                    <div
                                        class="progress-bar bg-primary"
                                        style="width: <?= (int) $value ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (
                            $matchScore['required_skill_count'] > 0
                        ): ?>
                            <p class="small text-muted mb-0">
                                <?= (int) $matchScore['matched_skill_count'] ?>
                                of
                                <?= (int) $matchScore['required_skill_count'] ?>
                                required skills match your profile.
                            </p>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>




                

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