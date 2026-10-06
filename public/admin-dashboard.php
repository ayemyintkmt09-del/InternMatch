<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';

$user = require_role('admin');
?>






<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | InternMatch</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Shared CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!--  NAVBAR -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">

            <a class="navbar-brand logo" href="index.html">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Intern<span class="logo-green">Match</span></span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <li class="nav-item">
                        <a class="nav-link active" href="admin-dashboard.html">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#users">
                            Users
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#companies">
                            Companies
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#internships">
                            Internships
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="#applications">
                            Applications
                        </a>
                    </li>

                </ul>

                <div class="d-flex align-items-center gap-3">

                    <!-- Notification -->
                    <a href="notifications.html"
                       class="dashboard-notification"
                       title="Notifications">
                       <i class="bi bi-bell"></i>
                       <span class="notification-badge">3</span>
                    </a>

                    <!-- Admin Profile -->
                    <div class="dropdown">
                        
                        <button
                            class="btn admin-profile-button dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown">
                            <span class="admin-avatar">
                                <?= e(mb_strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                            </span>

                            <span class="d-none d-md-inline">
                                <?= e($user['name']) ?>
                            </span>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">

                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-person me-2"></i>
                                    Admin Profile
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-gear me-2"></i>
                                    Settings
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
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
    </nav>


    <!--  MAIN CONTENT  -->

    <main class="admin-dashboard-page">

        <div class="container">

            <!-- Dashboard Header -->

            <div class="admin-dashboard-header">

                <div>
                    <span class="section-label">
                        ADMIN DASHBOARD
                    </span>

                    <h1>Welcome back, <?= e($user['name']) ?>! 👋</h1>

                    <p>
                        Manage users, companies, internships and
                        applications from one place.
                    </p>
                </div>

                <button
                    class="btn btn-primary admin-action-button"
                    onclick="showAdminSettings()">

                    <i class="bi bi-gear me-2"></i>
                    System Settings
                </button>

            </div>


            <!--STATISTICS  -->
            <div class="admin-stat-grid">

                <!-- Students -->
                <div class="admin-stat-card">

                    <div class="admin-stat-icon blue">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <p>Total Students</p>
                        <h3>1,248</h3>
                        <span class="admin-stat-change positive">
                            <i class="bi bi-arrow-up"></i>
                            8.4% this month
                        </span>
                    </div>

                </div>


                <!-- Companies -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon green">
                        <i class="bi bi-building"></i>
                    </div>

                    <div>
                        <p>Total Companies</p>
                        <h3>186</h3>
                        <span class="admin-stat-change positive">
                            <i class="bi bi-arrow-up"></i>
                            5.2% this month
                        </span>
                    </div>

                </div>

                <!-- Internships -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon orange">
                        <i class="bi bi-briefcase"></i>
                    </div>

                    <div>
                        <p>Active Internships</p>
                        <h3>324</h3>
                        <span class="admin-stat-change positive">
                            <i class="bi bi-arrow-up"></i>
                            12.1% this month
                        </span>
                    </div>

                </div>


                <!-- Applications -->

                <div class="admin-stat-card">

                    <div class="admin-stat-icon purple">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div>
                        <p>Total Applications</p>
                        <h3>3,842</h3>
                        <span class="admin-stat-change positive">
                            <i class="bi bi-arrow-up"></i>
                            15.7% this month
                        </span>
                    </div>

                </div>

            </div>


            <!-- PENDING ACTIONS  -->

            <section class="admin-panel">

                <div class="admin-panel-header">

                    <div>
                        <h2>Pending Actions</h2>
                        <p>Items that require administrator attention.</p>
                    </div>

                    <span class="admin-pending-count">
                        12 Pending
                    </span>

                </div>


                <div class="admin-pending-grid">

                    <!-- Company Verification -->

                    <div class="admin-pending-card">

                        <div class="admin-pending-icon green">
                            <i class="bi bi-building-check"></i>
                        </div>

                        <div class="admin-pending-content">

                            <h4>Company Verifications</h4>

                            <p>
                                5 companies are waiting for verification.
                            </p>

                            <button
                                class="admin-text-button"
                                onclick="showManagementMessage('Company Verification')">
                                Review Companies
                                <i class="bi bi-arrow-right"></i>
                            </button>

                        </div>

                    </div>


                    <!-- Internship Review -->

                    <div class="admin-pending-card">

                        <div class="admin-pending-icon orange">
                            <i class="bi bi-briefcase"></i>
                        </div>

                        <div class="admin-pending-content">

                            <h4>Internship Reviews</h4>

                            <p>
                                4 internship posts require review.
                            </p>

                            <button
                                class="admin-text-button"
                                onclick="showManagementMessage('Internship Review')">
                                Review Internships
                                <i class="bi bi-arrow-right"></i>
                            </button>

                        </div>

                    </div>


                    <!-- Reported Content -->

                    <div class="admin-pending-card">

                        <div class="admin-pending-icon red">
                            <i class="bi bi-flag"></i>
                        </div>

                        <div class="admin-pending-content">

                            <h4>Reported Content</h4>

                            <p>
                                3 reports need administrator review.
                            </p>

                            <button
                                class="admin-text-button"
                                onclick="showManagementMessage('Reported Content')">

                                Review Reports
                                <i class="bi bi-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </div>

            </section>


            <!-- MANAGEMENT-->

            <section class="admin-management-grid">


                <!-- User Management -->

                <div class="admin-panel" id="users">

                    <div class="admin-panel-header">

                        <div>
                            <h2>User Management</h2>
                            <p>Manage student and administrator accounts.</p>
                        </div>

                        <button
                            class="btn btn-outline-primary admin-small-button"
                            onclick="showManagementMessage('User Management')">

                            Manage Users

                        </button>

                    </div>


                    <div class="admin-user-summary">

                        <div class="admin-user-row">

                            <div class="admin-user-icon blue">
                                <i class="bi bi-mortarboard"></i>
                            </div>

                            <div class="admin-user-info">
                                <strong>Students</strong>
                                <span>1,248 registered users</span>
                            </div>

                            <span class="admin-user-number">
                                1,248
                            </span>

                        </div>


                        <div class="admin-user-row">

                            <div class="admin-user-icon green">
                                <i class="bi bi-person-check"></i>
                            </div>

                            <div class="admin-user-info">
                                <strong>Active Accounts</strong>
                                <span>Currently active users</span>
                            </div>

                            <span class="admin-user-number">
                                1,182
                            </span>

                        </div>


                        <div class="admin-user-row">

                            <div class="admin-user-icon orange">
                                <i class="bi bi-person-x"></i>
                            </div>

                            <div class="admin-user-info">
                                <strong>Inactive Accounts</strong>
                                <span>Deactivated user accounts</span>
                            </div>

                            <span class="admin-user-number">
                                66
                            </span>

                        </div>

                    </div>

                </div>


                <!-- Company Management -->

                <div class="admin-panel" id="companies">

                    <div class="admin-panel-header">

                        <div>
                            <h2>Company Management</h2>
                            <p>Monitor and verify registered companies.</p>
                        </div>

                        <button
                            class="btn btn-outline-primary admin-small-button"
                            onclick="showManagementMessage('Company Management')">

                            Manage Companies

                        </button>

                    </div>


                    <div class="admin-company-list">

                        <div class="admin-company-row">

                            <div class="admin-company-logo blue">
                                TW
                            </div>

                            <div class="admin-company-info">

                                <strong>
                                    TechWave Solutions
                                </strong>

                                <span>
                                    Technology & Software
                                </span>

                            </div>

                            <span class="admin-verification verified">
                                Verified
                            </span>

                        </div>


                        <div class="admin-company-row">

                            <div class="admin-company-logo green">
                                MD
                            </div>

                            <div class="admin-company-info">
                                <strong>Myanmar Digital Group</strong>
                                <span>Digital Services</span>
                            </div>

                            <span class="admin-verification verified">
                                Verified
                            </span>

                        </div>

                        <div class="admin-company-row">

                            <div class="admin-company-logo orange">
                                FG
                            </div>

                            <div class="admin-company-info">
                                <strong>Future Growth Co.</strong>
                                <span>Marketing & Business</span>
                            </div>

                            <span class="admin-verification pending">
                                Pending
                            </span>

                        </div>

                    </div>

                </div>

            </section>


            <!--  INTERNSHIPS & APPLICATIONS  -->

            <section class="admin-management-grid">


                <!-- Internship Management -->

                <div class="admin-panel" id="internships">

                    <div class="admin-panel-header">

                        <div>
                            <h2>Internship Management</h2>
                            <p>Monitor internship opportunities.</p>
                        </div>

                        <button
                            class="btn btn-outline-primary admin-small-button"
                            onclick="showManagementMessage('Internship Management')">

                            Manage Internships

                        </button>

                    </div>


                    <div class="admin-internship-summary">

                        <div class="admin-status-item">

                            <span class="admin-status-dot active"></span>

                            <div>
                                <strong>Active</strong>
                                <small>324 internships</small>
                            </div>

                        </div>


                        <div class="admin-status-item">

                            <span class="admin-status-dot pending"></span>

                            <div>
                                <strong>Pending Review</strong>
                                <small>4 internships</small>
                            </div>

                        </div>


                        <div class="admin-status-item">

                            <span class="admin-status-dot closed"></span>

                            <div>
                                <strong>Closed</strong>
                                <small>86 internships</small>
                            </div>

                        </div>

                    </div>

                </div>


                <!-- Application Monitoring -->

                <div class="admin-panel" id="applications">

                    <div class="admin-panel-header">

                        <div>
                            <h2>Application Monitoring</h2>
                            <p>Monitor application activity across the system.</p>
                        </div>

                        <button
                            class="btn btn-outline-primary admin-small-button"
                            onclick="showManagementMessage('Application Monitoring')">

                            View Applications

                        </button>

                    </div>


                    <div class="admin-application-summary">

                        <div class="admin-application-item">

                            <div class="admin-application-icon blue">
                                <i class="bi bi-hourglass-split"></i>
                            </div>

                            <div>
                                <strong>Pending</strong>
                                <span>1,126</span>
                            </div>

                        </div>


                        <div class="admin-application-item">

                            <div class="admin-application-icon orange">
                                <i class="bi bi-search"></i>
                            </div>

                            <div>
                                <strong>Under Review</strong>
                                <span>864</span>
                            </div>

                        </div>


                        <div class="admin-application-item">

                            <div class="admin-application-icon green">
                                <i class="bi bi-check-circle"></i>
                            </div>

                            <div>
                                <strong>Accepted</strong>
                                <span>438</span>
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            <!--  RECENT ACTIVITY -->

            <section class="admin-panel">

                <div class="admin-panel-header">

                    <div>
                        <h2>Recent System Activity</h2>
                        <p>Latest activity across InternMatch.</p>
                    </div>

                    <button
                        class="admin-text-button"
                        onclick="showManagementMessage('System Activity')">

                        View All
                        <i class="bi bi-arrow-right"></i>

                    </button>

                </div>


                <div class="admin-activity-list">

                    <div class="admin-activity-row">

                        <div class="admin-activity-icon green">
                            <i class="bi bi-building-check"></i>
                        </div>

                        <div class="admin-activity-info">

                            <strong>
                                TechWave Solutions was verified
                            </strong>

                            <span>
                                Company verification completed
                            </span>

                        </div>

                        <span class="admin-activity-time">
                            10 min ago
                        </span>

                    </div>


                    <div class="admin-activity-row">

                        <div class="admin-activity-icon blue">
                            <i class="bi bi-person-plus"></i>
                        </div>

                        <div class="admin-activity-info">

                            <strong>
                                12 new students registered
                            </strong>

                            <span>
                                New student accounts created
                            </span>

                        </div>

                        <span class="admin-activity-time">
                            1 hour ago
                        </span>

                    </div>


                    <div class="admin-activity-row">

                        <div class="admin-activity-icon orange">
                            <i class="bi bi-briefcase"></i>
                        </div>

                        <div class="admin-activity-info">

                            <strong>
                                4 new internship opportunities posted
                            </strong>

                            <span>
                                Internship posts waiting for review
                            </span>

                        </div>

                        <span class="admin-activity-time">
                            2 hours ago
                        </span>

                    </div>


                    <div class="admin-activity-row">

                        <div class="admin-activity-icon purple">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>

                        <div class="admin-activity-info">

                            <strong>
                                37 new applications submitted
                            </strong>

                            <span>
                                Students submitted applications
                            </span>

                        </div>

                        <span class="admin-activity-time">
                            3 hours ago
                        </span>

                    </div>

                </div>

            </section>


            <!--QUICK ACTIONS -->

            <section class="admin-panel">

                <div class="admin-panel-header">

                    <div>
                        <h2>Quick Actions</h2>
                        <p>Frequently used administration tools.</p>
                    </div>

                </div>


                <div class="admin-quick-actions">

                    <button
                        class="admin-quick-action"
                        onclick="showManagementMessage('Student Management')">

                        <div class="admin-quick-icon blue">
                            <i class="bi bi-people"></i>
                        </div>

                        <span>Manage Students</span>

                        <i class="bi bi-arrow-right"></i>

                    </button>


                    <button
                        class="admin-quick-action"
                        onclick="showManagementMessage('Company Verification')">

                        <div class="admin-quick-icon green">
                            <i class="bi bi-building-check"></i>
                        </div>

                        <span>Verify Companies</span>

                        <i class="bi bi-arrow-right"></i>

                    </button>


                    <button
                        class="admin-quick-action"
                        onclick="showManagementMessage('Internship Management')">

                        <div class="admin-quick-icon orange">
                            <i class="bi bi-briefcase"></i>
                        </div>

                        <span>Manage Internships</span>

                        <i class="bi bi-arrow-right"></i>

                    </button>


                    <button
                        class="admin-quick-action"
                        onclick="showManagementMessage('Skills & Academic Fields')">

                        <div class="admin-quick-icon purple">
                            <i class="bi bi-tags"></i>
                        </div>

                        <span>Skills & Academic Fields</span>

                        <i class="bi bi-arrow-right"></i>

                    </button>

                </div>

            </section>

        </div>

    </main>


    <!--  FOOTER  -->

    <footer class="dashboard-footer">

        <div class="container">

            <div class="footer-content">

                <div>
                    <strong>InternMatch</strong>
                    <p>
                        Connecting students with the right
                        internship opportunities.
                    </p>
                </div>

                <div>
                    <p class="mb-0">
                        © 2026 InternMatch. All rights reserved.
                    </p>
                </div>

            </div>

        </div>

    </footer>


    <!-- Bootstrap JS -->

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Shared JavaScript -->

    <script src="js/script.js"></script>

</body>

</html>
```
