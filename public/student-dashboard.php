<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentDashboardController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('student');

try {
    $dashboard = StudentDashboardController::load(
        (int) $user['user_id']
    );

    $profile = $dashboard['profile'];
    $profileCompletion = $dashboard['profile_completion'];
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);

    exit('The dashboard could not be loaded. Please try again.');
}
?>



<?php render_header($user, 'Student Dashboard', 'dashboard'); ?>

    <main class="dashboard-main" id="main-content" tabindex="-1">

        <div class="container-fluid dashboard-container">


            <!-- WELCOME HEADER-->

            <section class="dashboard-header">

                <div>
                    <p class="dashboard-small-title">STUDENT DASHBOARD</p>

                    <h1>Welcome back, <?= e($user['name']) ?>! 👋</h1>
                    <p>
                        Find internship opportunities that
                        match your skills and career goals.
                    </p>
                </div>

                <div class="dashboard-header-actions">
                    <a href="<?= e(url('opportunities.php')) ?>" class="btn btn-primary-custom">
                        <i class="bi bi-search"></i>Find Internships
                    </a>
                </div>

            </section>
            
            <!-- PROFILE COMPLETION -->

            <section class="profile-completion-card">

                <div class="profile-completion-icon">
                    <i class="bi bi-person-check-fill"></i>
                </div>

                <div class="profile-completion-content">

                    <div class="profile-completion-top">
                        <div>
                            
                            <h5>
                                <?= $profileCompletion === 100
                                    ? 'Your profile is complete'
                                    : 'Complete your profile' ?>
                            </h5>
                            <p>
                                A complete profile helps us find
                                more suitable internship opportunities.
                            </p>
                        </div>
                        <strong><?= $profileCompletion ?>%</strong>
                    </div>

                        <div class="progress profile-progress">

                        <div
                            class="progress-bar"
                            role="progressbar"
                            style="width: <?= $profileCompletion ?>%;"
                            aria-valuenow="<?= $profileCompletion ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>

                    </div>

                </div>


                <a
                    href="<?= e(url('student-profile.php')) ?>"
                    class="profile-complete-link">

                    <?= $profileCompletion === 100
                        ? 'Update Profile'
                        : 'Complete Profile' ?>

                    <i class="bi bi-arrow-right"></i>
                </a>



                

            </section>



            <!-- STATISTICS -->

            <section class="row g-4 dashboard-stat-row">

                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-stat-card">
                        <div class="stat-icon stat-blue">
                            <i class="bi bi-briefcase"></i>
                        </div>

                        <div class="stat-content">
                            <span>Available Internships</span>
                            <h3><?= number_format(
                                $dashboard['available_internships']
                            ) ?></h3>
                            <small>Currently open opportunities</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-stat-card">
                        <div class="stat-icon stat-green">
                            <i class="bi bi-send-check-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Applications</span>
                            <h3><?= number_format(
                                $dashboard['total_applications']
                            ) ?></h3>
                            <small>All submitted applications</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-stat-card">
                        <div class="stat-icon stat-orange">
                            <i class="bi bi-bookmark-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Saved</span>
                            <h3><?= number_format(
                                $dashboard['saved_internships']
                            ) ?></h3>
                            <small>Entries in your saved list</small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dashboard-stat-card">
                        <div class="stat-icon stat-purple">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Interviews</span>
                            <h3><?= number_format(
                                $dashboard['upcoming_interview_count']
                            ) ?></h3>
                            <small>Upcoming interviews</small>
                        </div>
                    </div>
                </div>

            </section>


            <!--MAIN DASHBOARD CONTENT-->
            <section class="row g-4 dashboard-content-row">

                <div class="col-lg-8">
                    <div class="dashboard-panel">

                        <div class="dashboard-panel-header">
                            <div>
                                <h3>Latest Internships</h3>
                                <p>Recently published opportunities from verified companies</p>
                            </div>

                            <a href="<?= e(url('opportunities.php')) ?>">
                                View All <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <?php if ($dashboard['latest_internships'] === []): ?>

                            <div class="text-center py-4">
                                <i class="bi bi-briefcase fs-1 text-primary"></i>
                                <h5 class="mt-3">No open internships yet</h5>
                                <p class="text-muted mb-0">
                                    Available opportunities will appear here.
                                </p>
                            </div>

                        <?php else: ?>

                            <?php foreach (
                                $dashboard['latest_internships'] as $internship
                            ): ?>

                                <div class="internship-card">

                                    <div class="company-logo-placeholder">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div class="internship-card-main">

                                        <div class="internship-card-title-row">
                                            <div>
                                                <h4><?= e($internship['title']) ?></h4>
                                                <p><?= e($internship['company_name']) ?></p>
                                            </div>
                                        </div>

                                        <div class="internship-meta">
                                            <span>
                                                <i class="bi bi-geo-alt"></i>
                                                <?= e(
                                                    $internship['location']
                                                    ?: 'Location not specified'
                                                ) ?>
                                            </span>

                                            <span>
                                                <i class="bi bi-clock"></i>
                                                <?= e(
                                                    $internship['duration']
                                                    ?: 'Duration not specified'
                                                ) ?>
                                            </span>

                                            <span>
                                                <i class="bi bi-laptop"></i>
                                                <?= e($internship['internship_type']) ?>
                                            </span>

                                            <span>
                                                <i class="bi bi-calendar-event"></i>
                                                Apply by <?= e($internship['deadline']) ?>
                                            </span>
                                        </div>

                                        <div class="internship-tags">
                                            <span>
                                                <?= e(
                                                    $internship['field_name']
                                                    ?: 'General'
                                                ) ?>
                                            </span>
                                        </div>

                                    </div>

                                    <div class="internship-card-action">
                                        <a
                                            class="btn btn-small-primary"
                                            href="<?= e(url(
                                                'internship-details.php?id='
                                                . (int) $internship['internship_id']
                                            )) ?>">
                                            View Details
                                        </a>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                </div>

                <div class="col-lg-4">

                    <div class="dashboard-panel application-panel">

                        <div class="dashboard-panel-header">
                            <div>
                                <h3>Application Status</h3>
                                <p>Your five most recent submissions</p>
                            </div>

                            <a
                                href="<?= e(url('my-applications.php')) ?>"
                                class="text-decoration-none dashboard-card-link">
                                
                                View All
                            </a>
                        </div>

                        <?php if ($dashboard['recent_applications'] === []): ?>

                            <p class="text-muted mb-0">
                                You have not submitted any applications yet.
                            </p>

                        <?php else: ?>

                            <?php foreach (
                                $dashboard['recent_applications'] as $application
                            ): ?>

                                <?php
                                $badgeClass = match ($application['status']) {
                                    'Accepted' => 'bg-success',
                                    'Rejected' => 'bg-danger',
                                    'Withdrawn' => 'bg-secondary',
                                    'Shortlisted' => 'bg-primary',
                                    'Under Review' => 'bg-info text-dark',
                                    default => 'bg-warning text-dark',
                                };
                                ?>

                                <div class="application-item">
                                    <div class="application-icon">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div class="application-info">
                                        <h5><?= e($application['title']) ?></h5>
                                        <p><?= e($application['company_name']) ?></p>
                                    </div>

                                    <span class="badge <?= e($badgeClass) ?>">
                                        <?= e($application['status']) ?>
                                    </span>
                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                    <div class="dashboard-panel">

                        <div class="dashboard-panel-header">
                            <div>
                                <h3>Upcoming Interviews</h3>
                                <p>Next three interviews · Myanmar time</p>
                            </div>
                        </div>

                        <?php if ($dashboard['upcoming_interviews'] === []): ?>

                            <p class="text-muted mb-0">
                                No upcoming interviews are scheduled.
                            </p>

                        <?php else: ?>

                            <?php foreach (
                                $dashboard['upcoming_interviews'] as $interview
                            ): ?>

                                <div class="border-top py-3">
                                    <strong class="d-block small">
                                        <?= e($interview['title']) ?>
                                    </strong>

                                    <div class="small text-muted">
                                        <?= e($interview['company_name']) ?>
                                    </div>

                                    <div class="small text-primary mt-1">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <?= e(
                                            (new DateTimeImmutable(
                                                $interview['interview_date'],
                                                new DateTimeZone(date_default_timezone_get())
                                            ))->format('d M Y, g:i A')
                                        ) ?>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                    <div class="dashboard-panel quick-actions-panel">

                        <div class="dashboard-panel-header">
                            <h3>Quick Actions</h3>
                        </div>

                        <a
                            class="quick-action"
                            href="<?= e(url('student-profile.php')) ?>">
                            <div class="quick-action-icon blue">
                                <i class="bi bi-person"></i>
                            </div>

                            <div>
                                <strong>Edit Profile</strong>
                                <small>Update your information</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a
                            class="quick-action"
                            href="<?= e(url('student-profile.php#cv-section')) ?>">
                            <div class="quick-action-icon green">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>

                            <div>
                                <strong>Manage CV</strong>
                                <small>Upload or update your CV</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                        <a
                            class="quick-action"
                            href="<?= e(url('saved-internships.php')) ?>">
                            <div class="quick-action-icon orange">
                                <i class="bi bi-bookmark"></i>
                            </div>

                            <div>
                                <strong>Saved Internships</strong>
                                <small>View your saved opportunities</small>
                            </div>

                            <i class="bi bi-chevron-right"></i>
                        </a>

                    </div>

                </div>

            </section>



            <!-- =====================================
                 FOOTER
            ====================================== -->

            <div class="dashboard-footer">

                <span>
                    © 2026 InternMatch
                </span>

                <span>
                    Internship Opportunity & Student Matching System
                </span>

            </div>


        </div>

    </main>

<?php render_footer($user); ?>