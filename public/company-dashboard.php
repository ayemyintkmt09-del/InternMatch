<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';

require_once __DIR__ . '/../app/controllers/CompanyFileController.php';
require_once __DIR__ . '/../app/controllers/CompanyInternshipController.php';

require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';
require_once __DIR__ . '/../app/controllers/CompanyReviewController.php';

require_once __DIR__
    . '/../app/controllers/CompanyDashboardChartController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('company');

try {
    $company = CompanyProfileController::load(
        (int) $user['user_id']
    );

        $companyCharts = CompanyDashboardChartController::load(
        (int) $user['user_id']
    );


    
$companyReviewSummary =
    CompanyReviewController::publicSummary(
        (int) $company['company_id']
    );

    $dashboard = CompanyInternshipController::dashboard(
        (int) $user['user_id']
    );

    
$recentApplicants = array_slice(
    CompanyApplicationController::all(
        (int) $user['user_id']
    ),
    0,
    5
);


} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('The company dashboard could not be loaded.');
}

$verificationStatus = $company['verification_status'];

$verificationLabel =
    CompanyProfileController::verificationLabel($verificationStatus);

$verificationClass =
    CompanyProfileController::verificationClass($verificationStatus);

$companyInitial = mb_strtoupper(
    mb_substr($company['company_name'], 0, 1, 'UTF-8'),
    'UTF-8'
);

$hasLogo = CompanyFileController::path($company, 'logo') !== null;
?>





<?php render_header($user, 'Company Dashboard', 'dashboard'); ?>

    <main class="company-dashboard-page" id="main-content" tabindex="-1">

        <div class="container">


            <!-- =================================
                 HEADER
            ================================== -->

            <div class="company-dashboard-header">

                <div>

                    <span class="dashboard-small-title">
                        COMPANY DASHBOARD
                    </span>

                    <h1>
                        Welcome back, <?= e($company['company_name']) ?>! 👋
                    </h1>

                    <p>
                        Manage your internship opportunities
                        and find suitable student candidates.
                    </p>

                </div>


                <button
                    class="btn btn-primary company-post-button"
                    onclick="window.location.href='<?= e(url('company-internship-create.php')) ?>'"
                >

                    <i class="bi bi-plus-lg me-2"></i>

                    Post Internship

                </button>

            </div>


            <!-- =================================
                 COMPANY PROFILE CARD
            ================================== -->

            <div class="company-profile-banner">

                <div class="company-profile-left">


                    <div class="company-large-logo">
                        <?php if ($hasLogo): ?>
                            <img
                                src="<?= e(url('company-file.php?kind=logo')) ?>"
                                alt="<?= e($company['company_name']) ?> logo"
                                class="company-logo-image"
                                width="64"
                                height="64">

                        <?php else: ?>
                            <?= e($companyInitial) ?>
                        <?php endif; ?>
                    </div>



                    

                    <div>
                        <h3><?= e($company['company_name']) ?></h3>


                        <a class="btn btn-outline-primary btn-sm mb-3"
                        href="<?= e(url(
                            'company-view-profile.php?id='
                            . (int) $company['company_id']
                        )) ?>">
                            View Company Profile
                        </a>

                        <p>
                            <?= e($company['industry'] ?: 'Industry not added yet') ?>
                        </p>

                        <span class="company-location">
                            <i class="bi bi-geo-alt me-1"></i>
                            <?= e($company['location'] ?: 'Location not added yet') ?>
                        </span>
                    </div>

                </div>

                <div class="company-profile-right">

                    <span class="verification-badge <?= e($verificationClass) ?>">
                        <i class="bi bi-shield-check me-1"></i>
                        <?= e($verificationLabel) ?>
                    </span>

                    <a
                        href="<?= e(url('company-profile.php')) ?>"
                        class="company-profile-link">
                        Manage Profile
                    </a>

                </div>

            </div>

            
<section class="im-review-dashboard-panel">

    <div>
        <span class="dashboard-small-title">
            COMPANY REPUTATION
        </span>

        <h2>Internship Reviews</h2>

        <p>
            Feedback published from eligible
            internship applications.
        </p>
    </div>

    <div class="im-review-dashboard-rating">

        <i class="bi bi-star-fill"></i>

        <strong>
            <?= (int) $companyReviewSummary['total'] > 0
                ? number_format(
                    (float) $companyReviewSummary['average_rating'],
                    1
                )
                : '—' ?>
        </strong>

        <span>
            <?= (int) $companyReviewSummary['total'] ?>
            published reviews
        </span>
    </div>

    <a class="btn btn-outline-primary"
       href="<?= e(url(
           'company-public-profile.php?id='
           . (int) $company['company_id']
       )) ?>">

        View Published Reviews

    </a>

</section>


            <!-- STATISTICS -->

            <div class="company-stat-grid">


                <!-- Active Internships -->
                <div class="company-stat-card">

                    <div class="company-stat-icon blue">

                        <i class="bi bi-briefcase"></i>

                    </div>

                    <div>

                        <span>Active Internships</span>

                        <strong>
                            <?= (int) $dashboard['active_internships'] ?>
                        </strong>

                    </div>

                </div>


                <!-- Total Applicants -->
                <div class="company-stat-card">

                    <div class="company-stat-icon green">

                        <i class="bi bi-people"></i>

                    </div>

                    <div>

                        <span>Total Applications</span>

                        <strong>
                            <?= (int) $dashboard['total_applications'] ?>
                        </strong>

                    </div>

                </div>


                <!-- Under Review -->
                <div class="company-stat-card">

                    <div class="company-stat-icon orange">

                        <i class="bi bi-hourglass-split"></i>

                    </div>

                    <div>

                        <span>Under Review</span>

                        <strong>
                            <?= (int) $dashboard['under_review'] ?>
                        </strong>

                    </div>

                </div>


                <!-- Shortlisted -->
                <div class="company-stat-card">

                    <div class="company-stat-icon purple">

                        <i class="bi bi-person-check"></i>

                    </div>

                    <div>

                        <span>Shortlisted</span>

                        <strong>
                            <?= (int) $dashboard['shortlisted'] ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================
                 MAIN CONTENT
            ================================== -->


            <section class="row g-4 mb-4" aria-label="Application charts">
    <div class="col-lg-7">
        <div class="company-panel im-chart-panel h-100">
            <h2 class="h5">Applications Received</h2>
            <p class="small text-muted">
                Last six Myanmar calendar months.
                The current month is in progress.
            </p>

            <?php
            $months = $companyCharts['months'];
            $maximum = max(1, ...array_column($months, 'count'));
            $points = [];

            foreach ($months as $index => $month) {
                $x = 30 + $index * 100;
                $y = 170 - ($month['count'] / $maximum) * 140;
                $points[] = $x . ',' . round($y, 2);
            }
            ?>

            <svg class="im-trend-chart"
                 viewBox="0 0 560 200"
                 role="img"
                 aria-labelledby="companyTrendTitle">
                <title id="companyTrendTitle">
                    Applications received by month.
                    Exact counts are listed below.
                </title>

                <path d="M30 30H530 M30 100H530 M30 170H530"
                      fill="none" stroke="#d3e7e7"/>

                <polyline
                    points="<?= e(implode(' ', $points)) ?>"
                    fill="none"
                    stroke="#168b91"
                    stroke-width="3"/>

                <?php foreach ($months as $index => $month): ?>
                    <?php
                    $x = 30 + $index * 100;
                    $y = 170 - ($month['count'] / $maximum) * 140;
                    ?>
                    <circle cx="<?= $x ?>"
                            cy="<?= round($y, 2) ?>"
                            r="4" fill="#168b91"/>

                    <text x="<?= $x ?>"
                          y="<?= round($y - 10, 2) ?>"
                          text-anchor="middle"
                          font-size="12" fill="#203844">
                        <?= $month['count'] ?>
                    </text>
                <?php endforeach; ?>
            </svg>

            <div class="im-chart-months">
                <?php foreach ($months as $month): ?>
                    <div>
                        <span><?= e($month['label']) ?></span>
                        <strong><?= $month['count'] ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="company-panel im-chart-panel h-100">
    <h2 class="h5">Application Status</h2>
    <p class="small text-muted">Current statuses across all applications.</p>

    <?php
    $statusColors = [
        'Pending' => '#efbf61',
        'Under Review' => '#6ebcc2',
        'Shortlisted' => '#a38bcf',
        'Accepted' => '#168b91',
        'Rejected' => '#d78989',
        'Withdrawn' => '#a5b2bd',
    ];

    $segments = [];
    $cursor = 0;
    $total = $companyCharts['total'];

    foreach ($companyCharts['statuses'] as $status => $count) {
        if ($count === 0 || $total === 0) {
            continue;
        }

        $end = $cursor + ($count / $total) * 100;

        $segments[] = $statusColors[$status]
            . ' ' . round($cursor, 4) . '% '
            . round($end, 4) . '%';

        $cursor = $end;
    }

    $donutBackground = $total > 0
        ? 'conic-gradient(' . implode(', ', $segments) . ')'
        : '#e4eeed';
    ?>

    <div class="im-status-chart">
        <div class="im-status-donut"
             style="background: <?= e($donutBackground) ?>"
             aria-hidden="true">
            <div>
                <strong><?= number_format($total) ?></strong>
                <span>Applications</span>
            </div>
        </div>

        <ul class="im-status-legend">
            <?php foreach ($companyCharts['statuses'] as $status => $count): ?>
                <li>
                    <span class="im-legend-dot"
                          style="background: <?= e($statusColors[$status]) ?>"
                          aria-hidden="true"></span>
                    <span><?= e($status) ?></span>
                    <strong><?= number_format($count) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php if ($total === 0): ?>
        <p class="small text-muted mb-0">
            Applications will appear here when students apply.
        </p>
    <?php endif; ?>
</div>
    </div>
</section>

            <div class="row g-4">

                <!-- INTERNSHIPS -->
                <div class="col-lg-8">

                    <div class="company-panel" id="internships">

                        <div class="company-panel-header">

                            <div>
                                <h3>Your Internship Opportunities</h3>
                                <p>Manage your current internship posts.</p>
                            </div>

                            <button
                                class="btn btn-primary btn-small-primary"
                                onclick="window.location.href='<?= e(url('company-internship-create.php')) ?>'">
                                <i class="bi bi-plus-lg me-1"></i>
                                Add New
                            </button>

                        </div>




                        <?php if ($dashboard['latest_internships'] === []): ?>

                            <div class="text-center py-5">
                                <i class="bi bi-briefcase fs-1 text-primary"></i>

                                <h5 class="mt-3">No internships yet</h5>

                                <p class="text-muted">
                                    Create your first internship draft to get started.
                                </p>

                                <a
                                    class="btn btn-primary"
                                    href="<?= e(url('company-internship-create.php')) ?>">
                                    Create Internship
                                </a>
                            </div>

                        <?php else: ?>

                            <?php foreach ($dashboard['latest_internships'] as $internship): ?>

                                <?php
                                $statusClass = match ($internship['status']) {
                                    'Published' => 'bg-success-subtle text-success',
                                    'Closed' => 'bg-secondary-subtle text-secondary',
                                    'Expired' => 'bg-danger-subtle text-danger',
                                    default => 'bg-warning-subtle text-warning-emphasis',
                                };

                                $managePath = $internship['status'] === 'Draft'
                                    ? 'company-internship-create.php?id='
                                        . (int) $internship['internship_id']
                                    : 'company-internships.php';

                                $deadlinePassed =
                                    $internship['status'] === 'Published'
                                    && !empty($internship['deadline'])
                                    && $internship['deadline'] < date('Y-m-d');
                                ?>

                                <div class="company-internship-row">

                                    <div class="company-internship-logo">
                                        <i class="bi bi-briefcase"></i>
                                    </div>

                                    <div class="company-internship-info">
                                        <h5><?= e($internship['title']) ?></h5>

                                        <div class="company-internship-meta">

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

                                            <span class="badge <?= e($statusClass) ?>">
                                                <?= e($internship['status']) ?>
                                            </span>

                                            <?php if ($deadlinePassed): ?>
                                                <span class="text-danger">
                                                    Deadline passed
                                                </span>
                                            <?php endif; ?>

                                        </div>
                                    </div>

                                    <div class="company-internship-applicants">
                                        <strong>
                                            <?= (int) $internship['application_count'] ?>
                                        </strong>

                                        <span>Applications</span>
                                    </div>

                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="<?= e(url(
                                            'company-applicants.php?internship_id='
                                            . (int) $internship['internship_id']
                                        )) ?>">
                                        <i class="bi bi-people me-1"></i>
                                        Applicants
                                    </a>

                                    <a
                                        class="company-more-button text-decoration-none"
                                        href="<?= e(url($managePath)) ?>"
                                        title="<?= $internship['status'] === 'Draft'
                                            ? 'Edit draft'
                                            : 'Manage internships' ?>"
                                        aria-label="<?= $internship['status'] === 'Draft'
                                            ? 'Edit draft'
                                            : 'Manage internships' ?>">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </a>

                                </div>

                            <?php endforeach; ?>

                            <div class="text-end mt-3">
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= e(url('company-applications.php')) ?>">
                                    View All Applications
                                </a>
                            </div>

                        <?php endif; ?>
                       
                    </div>


                        

                </div>


                <!-- =================================
                     QUICK ACTIONS
                ================================== -->

                <div class="col-lg-4">

                    <div class="company-panel">

                        <div class="company-panel-header">

                            <div>

                                <h3>
                                    Quick Actions
                                </h3>

                                <p>
                                    Manage your company activities.
                                </p>

                            </div>

                        </div>


                        <a
                            class="company-quick-action text-decoration-none text-reset"
                            href="<?= e(url('company-internship-create.php')) ?>">

                            <div class="company-quick-icon blue">
                                <i class="bi bi-plus-circle"></i>
                            </div>

                            <div>

                            
                                <strong>
                                    Post Internship
                                </strong>

                                <span>
                                    Create a new opportunity
                                </span>

                            </div>

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <a
                            class="company-quick-action text-decoration-none text-reset"
                            href="<?= e(url('company-applications.php')) ?>"
                        >

                            <div class="company-quick-icon green">
                                <i class="bi bi-people"></i>
                            </div>

                            <div>

                                <strong>
                                    Review Applicants
                                </strong>

                                <span>
                                    View student applications
                                </span>

                            </div>

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <a
                            class="company-quick-action text-decoration-none text-reset"
                            href="<?= e(url('company-profile.php')) ?>">

                            <div class="company-quick-icon orange">
                                <i class="bi bi-building"></i>
                            </div>

                            <div>

                                <strong>
                                    Company Profile
                                </strong>

                                <span>
                                    Update company information
                                </span>

                            </div>

                            <i class="bi bi-chevron-right"></i>

                        </a>

                    </div>


                    <!-- APPLICATION SUMMARY -->

                    <div
                        class="company-panel company-summary-panel"
                        id="applications">

                        <div class="company-panel-header">
                            <div>
                                <h3>Application Status</h3>
                                <p>All applications to your internships.</p>
                            </div>
                        </div>


<?php foreach (
    $dashboard['status_counts'] as $status => $total
): ?>

    <a class="company-status-item im-status-link"
       href="<?= e(url(
           'company-applications.php?status='
           . rawurlencode((string) $status)
       )) ?>">

        <span><?= e($status) ?></span>

        <strong>
            <?= number_format((int) $total) ?>
        </strong>

    </a>

<?php endforeach; ?>

                        <p class="small text-muted mt-3 mb-0">
                            Total applications includes withdrawn applications.
                        </p>

                    </div>

                </div>

            </div>


            <div class="company-panel recent-applications-panel">
                <div class="company-panel-header">
                    <div>
                        <h3>Applicant Management</h3>
                        <p>Review students who applied to your internships.</p>
                    </div>

                    <a
                        class="btn btn-outline-primary btn-small-outline"
                        href="<?= e(url('company-internships.php')) ?>">
                        View Internships
                    </a>
                </div>


<?php if (empty($recentApplicants)): ?>

    <div class="im-recruit-empty">
        <i class="bi bi-people"></i>
        <h4>No Recent Applicants</h4>
        <p>No applications have been received yet.</p>
    </div>

<?php else: ?>

    <div class="table-responsive">

        <table class="table align-middle">

            <thead>
                <tr>
                    <th>Student</th>
                    <th>Internship</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($recentApplicants as $app): ?>

                    <tr>
                        <td>
    <a class="fw-semibold text-decoration-none"
       href="<?= e(url(
           'student-view-profile.php?id='
           . (int) $app['student_id']
       )) ?>">
        <?= e($app['student_name']) ?>
    </a>
</td>

                        <td><?= e($app['title']) ?></td>

                        <td>
                            <span class="im-application-status">
                                <?= e($app['status']) ?>
                            </span>
                        </td>

                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= e(url(
                                   'company-application-review.php?id='
                                   . (int) $app['application_id']
                               )) ?>">
                                Review
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

<?php endif; ?>

<div class="text-end mt-3">
    <a href="<?= e(url('company-applications.php')) ?>"
       class="btn btn-outline-primary">
        View All Applicants
        <i class="bi bi-arrow-right ms-1"></i>
    </a>
</div>
            </div>


        </div>

    </main>


  <?php render_footer($user); ?>
