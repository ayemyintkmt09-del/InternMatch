<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';

require_once __DIR__ . '/../app/controllers/CompanyFileController.php';
require_once __DIR__ . '/../app/controllers/CompanyInternshipController.php';

$user = require_role('company');

try {
    $company = CompanyProfileController::load(
        (int) $user['user_id']
    );

    $dashboard = CompanyInternshipController::dashboard(
        (int) $user['user_id']
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





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Company Dashboard - InternMatch</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- =================================
         NAVBAR
    ================================== -->

    <nav class="navbar navbar-expand-lg main-navbar">

        <div class="container">

            <!-- Logo -->
            <a class="navbar-brand logo" href="index.html">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Intern<span class="logo-green">Match</span></span>
            </a>


            <!-- Mobile Menu -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#companyNavbar"
            >
                <i class="bi bi-list"></i>
            </button>


            <div
                class="collapse navbar-collapse"
                id="companyNavbar"
            >

                <!-- Navigation -->
                <ul class="navbar-nav mx-auto">

                    <li class="nav-item">
                        <a
                            class="nav-link active"
                            href="company-dashboard.php"
                        >
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= e(url('company-internships.php')) ?>"
                        >
                            Internships
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= e(url('company-internships.php')) ?>"
                        >
                            Applications
                        </a>
                    </li>

                </ul>


                <!-- Right Side -->
                <div class="dashboard-nav-right">

                    <!-- Notification -->
                   <button
                        type="button"
                        class="notification-button"
                        disabled
                        title="Notifications will be available after integration"
                        aria-label="Notifications are not available yet">
                        <i class="bi bi-bell"></i>
                    </button>

                        <i class="bi bi-bell"></i>

                        <span class="notification-dot"></span>

                    </a>


                    <!-- Company User -->
                    <div class="dashboard-user">

                        <div class="dropdown">
                            <button
                                type="button"
                                class="dashboard-user border-0 bg-transparent text-start"
                                id="companyAccountMenu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Company account menu">

                                <div class="dashboard-avatar company-avatar">
                                    <?= e(mb_strtoupper(
                                        mb_substr($user['name'], 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    )) ?>
                                </div>

                                <div class="dashboard-user-info">
                                    <strong><?= e($user['name']) ?></strong>
                                    <span>Company</span>
                                </div>

                                <i class="bi bi-chevron-down dashboard-chevron"></i>
                            </button>

                            <ul
                                class="dropdown-menu dropdown-menu-end"
                                aria-labelledby="companyAccountMenu">



                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="<?= e(url('company-profile.php')) ?>">
                                        <i class="bi bi-building me-2"></i>
                                        Company Profile
                                    </a>
                                </li>

                                <li>
                                    <form
                                        method="post"
                                        action="<?= e(url('logout.php')) ?>"
                                        class="m-0">

                                        <?= csrf_field() ?>

                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </nav>


    <!-- =================================
         MAIN
    ================================== -->

    <main class="company-dashboard-page">

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

                        <span>Total Applicants</span>

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
                                    href="<?= e(url('company-internships.php')) ?>">
                                    View All Internships
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
                            href="<?= e(url('company-internships.php')) ?>"
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
                                <p>Current applicant activity.</p>
                            </div>
                        </div>

                        <div class="company-status-item">
                            <span>Pending</span>
                            <strong>12</strong>
                        </div>

                        <div class="company-status-item">
                            <span>Under Review</span>
                            <strong>16</strong>
                        </div>

                        <div class="company-status-item">
                            <span>Shortlisted</span>
                            <strong>8</strong>
                        </div>

                        <div class="company-status-item">
                            <span>Accepted</span>
                            <strong>5</strong>
                        </div>

                        <div class="company-status-item">
                            <span>Rejected</span>
                            <strong>7</strong>
                        </div>

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

                <div class="text-center py-4">
                    <i class="bi bi-people fs-1 text-primary"></i>
                    <p class="mt-3 mb-0">
                        Select an internship above to view and manage its applicants.
                    </p>
                </div>
            </div>


        </div>

    </main>


    <!-- FOOTER -->

    <footer class="dashboard-footer">

        <div class="container">

            <div class="footer-content">

                <div>

                    <strong>
                        <i class="bi bi-mortarboard-fill me-2"></i>
                        InternMatch
                    </strong>

                    <span>
                        Internship Opportunity & Student Matching System
                    </span>

                </div>

                <p>
                    © 2026 InternMatch. All rights reserved.
                </p>

            </div>

        </div>

    </footer>


    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Main JS -->
    <script src="js/script.js"></script>

</body>

</html>
