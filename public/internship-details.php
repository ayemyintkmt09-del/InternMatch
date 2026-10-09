<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';

require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';

require_once __DIR__ . '/../app/controllers/MatchingController.php';
require_once __DIR__ . '/../app/layout.php';




header('Cache-Control: no-store');

$user = current_user();
$isStudent = ($user['role'] ?? null) === 'student';

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

    $isSaved = false;
    $applicationStatus = null;
    $matchScore = null;

    if ($internship !== null && $isStudent) {
        $isSaved = SavedInternshipController::isSaved(
            (int) $user['user_id'],
            $internshipId
        );

        $applicationStatus = ApplicationController::applicationStatus(
            (int) $user['user_id'],
            $internshipId
        );

        $matchScore = MatchingController::forInternship(
            (int) $user['user_id'],
            $internship
        );
    }

    $hasApplied = $applicationStatus !== null;

    $applicationButtonClass = match ($applicationStatus) {
        'Accepted' => 'btn-success',
        'Rejected' => 'btn-danger',
        'Shortlisted' => 'btn-info text-dark',
        'Under Review' => 'btn-warning text-dark',
        'Withdrawn' => 'btn-outline-secondary',
        default => 'btn-secondary',
    };



} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The internship could not be loaded. Please try again.');
}

$savedSuccess = take_flash('saved_internship_success');
$savedError = take_flash('saved_internship_error');

$activeNav = 'opportunities';

if ($internship === null) {
    http_response_code(404);

    $pageTitle = 'Opportunity unavailable';

    render_header($user, $pageTitle, $activeNav);
?>



    <main class="internship-details-page" id="main-content" tabindex="-1">
        <div class="container">
            
                <?php if ($savedSuccess !== null): ?>
                    <div class="alert alert-success" role="status">
                        <?= e($savedSuccess) ?>
                    </div>
                <?php endif; ?>

                <?php if ($savedError !== null): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= e($savedError) ?>
                    </div>
                <?php endif; ?>

            <section class="details-content-card text-center">
                <h1>Opportunity unavailable</h1>

                <p class="text-muted">
                    This internship may have passed its application deadline,
                    been closed, or become unavailable.
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
    render_footer($user);


    exit;
}

$pageTitle = $internship['title'];

render_header($user, $pageTitle, $activeNav);
?>




<main class="internship-details-page" id="main-content" tabindex="-1">
    <div class="container">


    <?php if ($savedSuccess !== null): ?>
        <div class="alert alert-success" role="status">
            <?= e($savedSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($savedError !== null): ?>
        <div class="alert alert-danger" role="alert">
            <?= e($savedError) ?>
        </div>
    <?php endif; ?>


    
        <a
            href="<?= e(url('opportunities.php')) ?>"
            class="back-opportunities">
            <i class="bi bi-arrow-left"></i>
            Back to Opportunities
        </a>

        <section class="internship-details-header">

            <div class="internship-company-logo">
                <?= company_logo(
                    (int) $internship['company_id'],
                    (string) $internship['company_name'],
                    (int) ($internship['company_has_logo'] ?? 0) === 1,
                    'internship-company-logo-image'
                ) ?>
            </div>

            <div class="internship-header-content">

                <div class="internship-header-top">
                    <div>
                        <p class="dashboard-small-title">
                            INTERNSHIP OPPORTUNITY
                        </p>

                        <h1><?= e($internship['title']) ?></h1>

                        <p class="internship-company-name">
                            <a
                                class="opportunity-company-link"
                                href="<?= e(url(
                                    'company-public-profile.php?id='
                                    . (int) $internship['company_id']
                                )) ?>">
                                <?= e($internship['company_name']) ?>
                            </a>
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








        <div class="internship-action-bar d-flex flex-wrap gap-2">

            <?php if ($isStudent): ?>

                <form
                    method="post"
                    action="<?= e(url('saved-internship-action.php')) ?>"
                    class="m-0">

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="return_to"
                        value="internship-details.php">

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
                        class="btn btn-outline-primary">
                        <?= $isSaved
                            ? 'Remove from Saved'
                            : 'Save Internship' ?>
                    </button>
                </form>

                <a
                    class="btn btn-outline-secondary"
                    href="<?= e(url('saved-internships.php')) ?>">
                    View Saved
                </a>

                <?php if ($hasApplied): ?>

                    <span
                        class="btn <?= e($applicationButtonClass) ?>"
                        role="status">
                        Application <?= e((string) $applicationStatus) ?>
                    </span>

                <?php else: ?>

                    <a
                        class="btn btn-primary"
                        href="<?= e(url(
                            'apply.php?id='
                            . (int) $internship['internship_id']
                        )) ?>">
                        Apply Now
                    </a>

                <?php endif; ?>

            <?php elseif ($user === null): ?>

                <a
                    class="btn btn-primary"
                    href="<?= e(url(
                        'student-access.php?id='
                        . (int) $internship['internship_id']
                    )) ?>">
                    Sign in to apply or save
                </a>

                <p class="text-muted small mb-0 align-self-center">
                    New here? You can create an account from the sign-in page.
                </p>

            <?php else: ?>

                <p class="text-muted mb-0">
                    You are viewing this internship with a
                    <?= e($user['role']) ?> account.
                    Applications and saved internships are available
                    to student accounts.
                </p>

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

                    <a
                        class="btn btn-outline-primary mt-3"
                        href="<?= e(url(
                            'company-public-profile.php?id='
                            . (int) $internship['company_id']
                        )) ?>">
                        View Company Profile
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>

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

                        <div
                            class="display-6 text-primary fw-bold mb-3"
                            aria-label="Overall profile match score">

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

                        <?php if ($matchScore['required_skill_count'] > 0): ?>
                            <p class="small text-muted mb-0">
                                <?= (int) $matchScore['matched_skill_count'] ?>
                                of
                                <?= (int) $matchScore['required_skill_count'] ?>
                                required skills match your profile.
                            </p>
                        <?php endif; ?>

                    

                        <p class="small text-muted mb-3">
                            This is a transparent profile-fit score based on your skills,
                            academic field, interests, availability, and location.
                            It is not a hiring probability.
                        </p>

                        
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

render_footer($user);