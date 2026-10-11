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

            <section class="dashboard-header im-student-welcome">

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

                    
<a href="<?= e(url('student-digital-cv.php')) ?>"
   class="btn btn-outline-primary">
    <i class="bi bi-file-earmark-person me-1"></i>
    My Digital CV
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

<section class="row g-3 dashboard-stat-row">
    <?php
    $studentStats = [
        ['Applications', 'total_applications',
            'bi-send-check', 'All submitted applications'],
        ['Interviews', 'upcoming_interview_count',
            'bi-calendar-check', 'Upcoming interviews'],
        ['Shortlisted', 'shortlisted_count',
            'bi-person-check', 'Currently shortlisted'],
        ['Accepted', 'accepted_count',
            'bi-patch-check', 'Accepted applications'],
    ];
    ?>

    <?php foreach ($studentStats as [$label, $key, $icon, $hint]): ?>
        <div class="col-xl-3 col-sm-6">
            <div class="dashboard-stat-card">
                <div class="stat-icon stat-blue" aria-hidden="true">
                    <i class="bi <?= e($icon) ?>"></i>
                </div>
                <div class="stat-content">
                    <span><?= e($label) ?></span>
                    <h3><?= number_format($dashboard[$key]) ?></h3>
                    <small><?= e($hint) ?></small>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>

<div class="im-student-toolkit">

            

            <!-- Existing skill catalogue and stored proficiency levels; no fake percentages. -->
            <section class="im-student-skills" aria-labelledby="studentSkillsTitle">
                <div class="im-student-skills-heading">
                    <div><span class="section-label">YOUR PROFESSIONAL TOOLKIT</span><h2 id="studentSkillsTitle">Technologies I Master</h2></div>
                    <a class="btn btn-outline-success btn-sm" href="<?= e(url('student-skill-levels.php')) ?>"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Edit skills</a>
                </div>
                <?php if ($dashboard['skills'] === []): ?>
                    <div class="im-skills-empty">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                        <p>Add your skills to your profile to showcase what you know.</p>
                        <a href="<?= e(url('student-profile.php')) ?>">Add skills <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                <?php else: ?>
                    <div class="im-skills-grid">
                        <?php foreach ($dashboard['skills'] as $skill): ?>
                            <?php
                            $level = trim((string)$skill['proficiency_level']);
                           
                            $levelWidths = [
                                'Beginner' => 25,
                                'Intermediate' => 50,
                                'Advanced' => 75,
                                'Expert' => 100
                            ];

                            $barWidth = $levelWidths[$level] ?? 0;
                            ?>
                            <div class="im-skill-item">
                                <div class="im-skill-label"><span><i class="bi bi-lightning-charge" aria-hidden="true"></i> <?= e($skill['skill_name']) ?></span><small><?= e($level !== '' ? $level : 'Not rated') ?></small></div>
                                <div class="im-skill-track" role="img" aria-label="<?= e($skill['skill_name'] . ': ' . ($level !== '' ? $level : 'Not rated')) ?>"><div style="width: <?= $barWidth ?>%"></div></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="im-skill-disclaimer">Proficiency levels are profile entries, not certified assessment scores.</p>
                <?php endif; ?>
            </section>

                <section class="im-digital-cv-card"
             aria-labelledby="digitalCvCardTitle">
        <div class="im-cv-art" aria-hidden="true">
            <i class="bi bi-file-earmark-person"></i>
            <span><i class="bi bi-check-lg"></i></span>
        </div>

        <h2 id="digitalCvCardTitle">My Digital CV</h2>
        <p>
            Keep your education, skills and experience
            ready for your next opportunity.
        </p>

        <a class="btn btn-primary"
           href="<?= e(url('student-digital-cv.php')) ?>">
            View Digital CV
        </a>

        <a class="btn btn-outline-primary"
           href="<?= e(url('student-profile.php#cv-section')) ?>">
            Manage Uploaded CV
        </a>
    </section>
</div>
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

                            <a href="<?= e(url('student-recommendations.php')) ?>">
                                View All <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <?php if ($dashboard['recommended_internships'] === []): ?>

                            <div class="text-center py-4">
                                <i class="bi bi-briefcase fs-1 text-primary"></i>
                                <h5 class="mt-3">No open internships yet</h5>
                                <p class="text-muted mb-0">
                                    Available opportunities will appear here.
                                </p>
                            </div>

                        <?php else: ?>

                            <?php foreach (
                                $dashboard['recommended_internships'] as $internship
                            ): ?>

                                <div class="internship-card">

                                    <div class="company-logo-placeholder">
                                        <i class="bi bi-building"></i>
                                    </div>

                                    <div class="internship-card-main">

                                        <div class="internship-card-title-row">
                                            <div>
                                                <h4><?= e($internship['title']) ?></h4>
                                                <span class="im-match-badge">
                                                    <?= (int) ($internship['match_score']['overall'] ?? 0) ?>%
                                                    profile match
                                                </span>
                                                <p>
    <a href="<?= e(url(
        'company-view-profile.php?id='
        . (int) $internship['company_id']
    )) ?>">
        <?= e($internship['company_name']) ?>
    </a>
</p>

                                                                                                <p class="small text-muted">
                                                    Match scores describe profile alignment, not hiring probability.
                                                </p>
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
                                        <p>
    <a href="<?= e(url(
        'company-view-profile.php?id='
        . (int) $application['company_id']
    )) ?>">
        <?= e($application['company_name']) ?>
    </a>
</p>
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
                                        <a href="<?= e(url(
    'company-view-profile.php?id='
    . (int) $interview['company_id']
)) ?>">
    <?= e($interview['company_name']) ?>
</a>
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

                        
<a class="quick-action"
   href="<?= e(url('student-digital-cv.php')) ?>">

    <div class="quick-action-icon blue">
        <i class="bi bi-file-earmark-person"></i>
    </div>

    <div>
        <strong>My Digital CV</strong>
        <small>Preview and export your profile</small>
    </div>

    <i class="bi bi-chevron-right"></i>
</a>


<a class="quick-action"
   href="<?= e(url('student-portfolio.php')) ?>">

    <div class="quick-action-icon blue">
        <i class="bi bi-collection"></i>
    </div>

    <div>
        <strong>My Portfolio</strong>
        <small>Manage projects and certificates</small>
    </div>

    <i class="bi bi-chevron-right"></i>
</a>


<a class="quick-action"
   href="<?= e(url('student-cv-sharing.php')) ?>">

    <div class="quick-action-icon green">
        <i class="bi bi-share"></i>
    </div>

    <div>
        <strong>Share Digital CV</strong>
        <small>Manage your public career profile</small>
    </div>

    <i class="bi bi-chevron-right"></i>

</a>


<a class="quick-action"
   href="<?= e(url('student-skill-levels.php')) ?>">

    <div class="quick-action-icon green">
        <i class="bi bi-bar-chart"></i>
    </div>

    <div>
        <strong>Skill Levels</strong>
        <small>Update your proficiency levels</small>
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


                        
<a class="quick-action"
   href="<?= e(url('student-recommendations.php')) ?>">

    <div class="quick-action-icon green">
        <i class="bi bi-stars"></i>
    </div>

    <div>
        <h3>Recommended Internships</h3>
<p>Open opportunities ranked against your profile</p>
    </div>

    <i class="bi bi-chevron-right"></i>
</a>

<a class="quick-action"
   href="<?= e(url('student-saved-searches.php')) ?>">

    <div class="quick-action-icon blue">
        <i class="bi bi-bookmarks"></i>
    </div>

    <div>
        <strong>Saved Searches</strong>
        <small>Revisit your search filters</small>
    </div>

    <i class="bi bi-chevron-right"></i>
</a>




<a class="quick-action"
   href="<?= e(url('career-articles.php')) ?>">

    <div class="quick-action-icon green">
        <i class="bi bi-journal-text"></i>
    </div>

    <div>
        <strong>Career Resources</strong>
        <small>News, CV tips, interviews and career guidance</small>
    </div>

    <i class="bi bi-chevron-right"></i>

</a>



                    </div>

                </div>

            </section>






        </div>

    </main>

<?php render_footer($user); ?>