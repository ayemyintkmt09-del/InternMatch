<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';

require_once __DIR__
    . '/../app/controllers/StudentProfileController.php';

$user = require_role('student');

try {
    $profile = StudentProfileController::load(
        (int) $user['user_id']
    );

    $profileCompletion =
        StudentProfileController::completion($profile);
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);

    exit('The dashboard could not be loaded. Please try again.');
}
?>



<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - InternMatch</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>


    <!-- NAVBAR-->

    <nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm">

        <div class="container-fluid dashboard-container">


            <!-- Logo -->
            <a class="navbar-brand logo" href="index.html">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Intern<span class="logo-green">Match</span></span>
            </a>

            <!-- Mobile menu button -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#dashboardNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navbar content -->
            <div class="collapse navbar-collapse"
                id="dashboardNavbar">
                    <ul class="navbar-nav mx-auto dashboard-nav">

                    <li class="nav-item">
                        <a class="nav-link active" href="student-dashboard.php">Dashboard</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(url('opportunities.php')) ?>">Opportunities</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(url('my-applications.php')) ?>">My Applications</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(url('saved-internships.php')) ?>">Saved</a>
                    </li>

                    </ul>


                <!-- Right side -->

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


                    <!-- Profile -->

                    <div class="dashboard-user">
                        
                        <div class="dropdown">
                            <button
                                type="button"
                                class="dashboard-user border-0 bg-transparent text-start"
                                id="studentAccountMenu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Student account menu">

                                <div class="dashboard-avatar">
                                    <?= e(mb_strtoupper(
                                        mb_substr($user['name'], 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    )) ?>
                                </div>

                                <div class="dashboard-user-info">
                                    <strong><?= e($user['name']) ?></strong>
                                    <small>Student</small>
                                </div>

                                <i class="bi bi-chevron-down"></i>
                            </button>

                            <ul
                                class="dropdown-menu dropdown-menu-end"
                                aria-labelledby="studentAccountMenu">
                                <li>

                                
                                    <a
                                        class="dropdown-item"
                                        href="<?= e(url('student-profile.php')) ?>">
                                        <i class="bi bi-person me-2"></i>
                                        My Profile
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

    <!--  DASHBOARD -->

    <main class="dashboard-main">

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
                
                <!-- Recommended -->
                <div class="col-xl-3 col-md-6">

                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-blue">
                            <i class="bi bi-stars"></i>
                        </div>

                        <div class="stat-content">
                            <span>Recommended</span>
                            <h3>12</h3>
                            <small>New opportunities</small>
                        </div>

                    </div>

                </div>


                <!-- Applications -->

                <div class="col-xl-3 col-md-6">

                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-green">
                            <i class="bi bi-send-check-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Applications</span>
                            <h3>8</h3>
                            <small>Total submitted</small>
                        </div>

                    </div>

                </div>

                <!-- Saved -->
                <div class="col-xl-3 col-md-6">

                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-orange">
                            <i class="bi bi-bookmark-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Saved</span>
                            <h3>5</h3>
                            <small>Saved internships</small>
                        </div>

                    </div>

                </div>


                <!-- Interviews -->
                <div class="col-xl-3 col-md-6">

                    <div class="dashboard-stat-card">

                        <div class="stat-icon stat-purple">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>

                        <div class="stat-content">
                            <span>Interviews</span>
                            <h3>2</h3>
                            <small>Upcoming</small>
                        </div>

                    </div>

                </div>

            </section>



            <!--MAIN DASHBOARD CONTENT-->
            <section class="row g-4 dashboard-content-row">
             <!--RECOMMENDED INTERNSHIPS -->
                <div class="col-lg-8">
                    <div class="dashboard-panel">


                        <!-- Panel heading -->
                        <div class="dashboard-panel-header">

                            <div>
                                <h3>Recommended for You</h3>
                                <p>Opportunities matched to your profile</p>
                            </div>

                            <a href="<?= e(url('opportunities.php')) ?>">
                                View All
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>

                        <!-- Internship Card 1 -->
                        <div class="internship-card">

                            <div class="company-logo-placeholder">
                                <i class="bi bi-building"></i>
                            </div>

                            <div class="internship-card-main">

                                <div class="internship-card-title-row">

                                    <div>

                                        <h4>
                                            Junior Web Developer Intern
                                        </h4>

                                        <p>
                                            TechWave Solutions
                                        </p>

                                    </div>


                                    <span class="match-badge">
                                        92% Match
                                    </span>

                                </div>


                                <div class="internship-meta">

                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        Yangon
                                    </span>

                                    <span>
                                        <i class="bi bi-clock"></i>
                                        3 Months
                                    </span>

                                    <span>
                                        <i class="bi bi-laptop"></i>
                                        Hybrid
                                    </span>

                                </div>


                                <div class="internship-tags">

                                    <span>
                                        HTML
                                    </span>

                                    <span>
                                        CSS
                                    </span>

                                    <span>
                                        JavaScript
                                    </span>

                                </div>

                            </div>


                            <div class="internship-card-action">

                                <button
                                    class="save-button"
                                    type="button"
                                    onclick="toggleSave(this)"
                                >

                                    <i class="bi bi-bookmark"></i>

                                </button>


                                <a
                                    href="internship-details.php?id=1"
                                    class="btn btn-small-primary"
                                >
                                    View
                                </a>

                            </div>

                        </div>



                        <!-- Internship Card 2 -->

                        <div class="internship-card">


                            <div class="company-logo-placeholder">

                                <i class="bi bi-building"></i>

                            </div>


                            <div class="internship-card-main">

                                <div class="internship-card-title-row">

                                    <div>

                                        <h4>
                                            Business Analyst Intern
                                        </h4>

                                        <p>
                                            Myanmar Digital Group
                                        </p>

                                    </div>


                                    <span class="match-badge">
                                        87% Match
                                    </span>

                                </div>


                                <div class="internship-meta">

                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        Yangon
                                    </span>

                                    <span>
                                        <i class="bi bi-clock"></i>
                                        6 Months
                                    </span>

                                    <span>
                                        <i class="bi bi-building"></i>
                                        On-site
                                    </span>

                                </div>


                                <div class="internship-tags">

                                    <span>
                                        Excel
                                    </span>

                                    <span>
                                        SQL
                                    </span>

                                    <span>
                                        Analysis
                                    </span>

                                </div>

                            </div>


                            <div class="internship-card-action">

                                <button
                                    class="save-button"
                                    type="button"
                                    onclick="toggleSave(this)"
                                >

                                    <i class="bi bi-bookmark"></i>

                                </button>


                                <a
                                    href="#"
                                    class="btn btn-small-primary"
                                >
                                    View
                                </a>

                            </div>

                        </div>



                        <!-- Internship Card 3 -->

                        <div class="internship-card">


                            <div class="company-logo-placeholder">

                                <i class="bi bi-building"></i>

                            </div>


                            <div class="internship-card-main">

                                <div class="internship-card-title-row">

                                    <div>

                                        <h4>
                                            UI/UX Design Intern
                                        </h4>

                                        <p>
                                            Creative Hub Myanmar
                                        </p>

                                    </div>


                                    <span class="match-badge">
                                        81% Match
                                    </span>

                                </div>


                                <div class="internship-meta">

                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        Yangon
                                    </span>

                                    <span>
                                        <i class="bi bi-clock"></i>
                                        3 Months
                                    </span>

                                    <span>
                                        <i class="bi bi-laptop"></i>
                                        Remote
                                    </span>

                                </div>


                                <div class="internship-tags">

                                    <span>
                                        Figma
                                    </span>

                                    <span>
                                        UI Design
                                    </span>

                                    <span>
                                        UX
                                    </span>

                                </div>

                            </div>


                            <div class="internship-card-action">

                                <button
                                    class="save-button"
                                    type="button"
                                    onclick="toggleSave(this)"
                                >

                                    <i class="bi bi-bookmark"></i>

                                </button>


                                <a
                                    href="#"
                                    class="btn btn-small-primary"
                                >
                                    View
                                </a>

                            </div>

                        </div>

                    </div>

                </div>



                <!-- =================================
                     RIGHT COLUMN
                ================================== -->

                <div class="col-lg-4">


                    <!-- =================================
                         APPLICATION STATUS
                    ================================== -->

                    <div class="dashboard-panel application-panel">

                        <div class="dashboard-panel-header">

                            <div>

                                <h3>
                                    Application Status
                                </h3>

                                <p>
                                    Your recent applications
                                </p>

                            </div>


                            <a href="#">
                                View All
                            </a>

                        </div>


                        <!-- Application 1 -->

                        <div class="application-item">

                            <div class="application-icon">

                                <i class="bi bi-building"></i>

                            </div>


                            <div class="application-info">

                                <h5>
                                    Web Developer Intern
                                </h5>

                                <p>
                                    TechWave Solutions
                                </p>

                            </div>


                            <span class="status-badge pending">
                                Pending
                            </span>

                        </div>


                        <!-- Application 2 -->

                        <div class="application-item">

                            <div class="application-icon">

                                <i class="bi bi-building"></i>

                            </div>


                            <div class="application-info">

                                <h5>
                                    Data Analyst Intern
                                </h5>

                                <p>
                                    Digital Myanmar
                                </p>

                            </div>


                            <span class="status-badge review">
                                Under Review
                            </span>

                        </div>


                        <!-- Application 3 -->

                        <div class="application-item">

                            <div class="application-icon">

                                <i class="bi bi-building"></i>

                            </div>


                            <div class="application-info">

                                <h5>
                                    Business Intern
                                </h5>

                                <p>
                                    ABC Company
                                </p>

                            </div>


                            <span class="status-badge shortlist">
                                Shortlisted
                            </span>

                        </div>


                    </div>



                    <!-- =================================
                         QUICK ACTIONS
                    ================================== -->

                    <div class="dashboard-panel quick-actions-panel">

                        <div class="dashboard-panel-header">

                            <div>

                                <h3>
                                    Quick Actions
                                </h3>

                            </div>

                        </div>


                        <a
                            href="#"
                            class="quick-action"
                        >

                            <div class="quick-action-icon blue">

                                <i class="bi bi-person"></i>

                            </div>

                            <div>

                                <strong>
                                    Edit Profile
                                </strong>

                                <small>
                                    Update your information
                                </small>

                            </div>

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <a
                            href="#"
                            class="quick-action"
                        >

                            <div class="quick-action-icon green">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>

                            <div>

                                <strong>
                                    Manage CV
                                </strong>

                                <small>
                                    Upload or update your CV
                                </small>

                            </div>

                            <i class="bi bi-chevron-right"></i>

                        </a>


                        <a
                            href="#"
                            class="quick-action"
                        >

                            <div class="quick-action-icon orange">

                                <i class="bi bi-bookmark"></i>

                            </div>

                            <div>

                                <strong>
                                    Saved Internships
                                </strong>

                                <small>
                                    View your saved opportunities
                                </small>

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



    <!-- Bootstrap JavaScript -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>


    <!-- Main JavaScript -->

    <script src="js/script.js"></script>


</body>

</html>