<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/controllers/AssociatedProfileController.php';
require_once __DIR__ . '/../app/controllers/StudentProfileController.php';

header('Cache-Control: no-store');

$user = current_user();

if ($user === null) {
    flash('login_error', 'Please log in to view this profile.');
    redirect('login.php');
}

$rawId = $_GET['id'] ?? null;

$studentId = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

$profile = null;

try {
    if ($studentId !== false) {
        $profile = AssociatedProfileController::student(
            $user,
            $studentId
        );
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The profile could not be loaded. Please try again.');
}

if ($profile === null) {
    http_response_code(404);
    render_header($user, 'Profile unavailable');
    ?>
    <main id="main-content" class="container py-5" tabindex="-1">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h1 class="h3">Profile unavailable</h1>
            <p>This profile is unavailable or you do not have access.</p>
            <a class="btn btn-outline-primary"
               href="<?= e(url(dashboard_path($user['role']))) ?>">
                Back to Dashboard
            </a>
        </div>
    </main>
    <?php
    render_footer($user);
    exit;
}

$photo = StudentProfileController::photoUrl($profile);

$initial = mb_strtoupper(
    mb_substr(trim($profile['name']), 0, 1, 'UTF-8'),
    'UTF-8'
);

render_header($user, $profile['name'] . ' — Profile');
?>

<main id="main-content" class="container py-4 py-lg-5" tabindex="-1">
    <a class="btn btn-outline-primary mb-4"
       href="<?= e(url(dashboard_path($user['role']))) ?>">
        Back to Dashboard
    </a>

    <section class="im-profile-photo-card">
        <div class="im-profile-photo-preview">
            <?php if ($photo !== null): ?>
                <img src="<?= e($photo) ?>"
                     alt="<?= e($profile['name']) ?>">
            <?php else: ?>
                <span><?= e($initial) ?></span>
            <?php endif; ?>
        </div>
        <div class="im-profile-photo-copy">
            <p class="small text-uppercase mb-1">Student Profile</p>
            <h1 class="h3"><?= e($profile['name']) ?></h1>
            <p class="mb-0">
                <?= e($profile['university'] ?: 'University not provided') ?>
                · <?= e($profile['location'] ?: 'Location not provided') ?>
            </p>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5">About</h2>
                <p class="mb-0"><?= nl2br(e(
                    $profile['bio'] ?: 'No introduction added yet.'
                )) ?></p>
            </section>

            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5">Technologies I Master</h2>

                <?php if (!$profile['skills']): ?>
                    <p class="text-muted mb-0">No skills added yet.</p>
                <?php endif; ?>

                <?php foreach ($profile['skills'] as $skill): ?>
                    <div class="d-flex justify-content-between gap-3 py-2">
                        <strong><?= e($skill['skill_name']) ?></strong>
                        <span class="text-muted">
                            <?= e($skill['proficiency_level'] ?? 'Not specified') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5">Shared Portfolio</h2>

                <?php if (!$profile['portfolio']): ?>
                    <p class="text-muted mb-0">No shared entries yet.</p>
                <?php endif; ?>

                <?php foreach ($profile['portfolio'] as $entry): ?>
                    <article class="border-top pt-3 mt-3">
                        <p class="small text-muted mb-1">
                            <?= e(ucfirst($entry['item_type'])) ?>
                        </p>
                        <h3 class="h6"><?= e($entry['title']) ?></h3>
                        <p><?= e($entry['organization'] ?? '') ?></p>
                        <p class="mb-0"><?= nl2br(e(
                            $entry['description'] ?? ''
                        )) ?></p>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>

        <aside class="col-lg-4">
            <section class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h5">Education & Interests</h2>
                <dl class="mb-0">
                    <?php foreach ([
                        'Degree' => $profile['degree'],
                        'Academic field' => $profile['field_name'],
                        'Academic year' => $profile['academic_year'],
                        'Career interest' => $profile['career_interest'],
                        'Available from' => $profile['available_from'],
                        'Available until' => $profile['available_until'],
                    ] as $label => $value): ?>
                        <dt class="small text-muted"><?= e($label) ?></dt>
                        <dd><?= e($value ?: 'Not provided') ?></dd>
                    <?php endforeach; ?>
                </dl>
            </section>
        </aside>
    </div>
</main>

<?php render_footer($user); ?>