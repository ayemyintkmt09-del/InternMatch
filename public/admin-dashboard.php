<?php

    declare(strict_types=1);

    require_once __DIR__ . '/../app/middleware/auth.php';
    require_once __DIR__ . '/../app/controllers/AdminDashboardController.php';

    require_once __DIR__ . '/../app/controllers/AdminActivityController.php';

    require_once __DIR__ . '/../app/layout.php';


    $user = require_role('admin');

    try {
        $adminDashboard = AdminDashboardController::load();
        $adminPanels = AdminDashboardController::panels();
        $recentActivity = AdminActivityController::recent(8);
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);

        exit('The admin dashboard could not be loaded.');
    }
?>






<?php render_header($user, 'Admin Dashboard', 'dashboard'); ?>


    <!--  MAIN CONTENT  -->

    <main class="admin-dashboard-page" id="main-content" tabindex="-1">

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

                <a
                    class="btn btn-primary admin-action-button"
                    href="<?= e(url('admin-users.php')) ?>">

                    <i class="bi bi-people me-2"></i>
                    Manage Users
                </a>

            </div>


        <!-- STATISTICS -->

        <div class="admin-stat-grid">

            <div class="admin-stat-card">
                <div class="admin-stat-icon blue">
                    <i class="bi bi-people"></i>
                </div>

                <div>
                    <p>Total Students</p>

                    <h3>
                        <?= number_format($adminDashboard['total_students']) ?>
                    </h3>

                    <span class="text-muted small">
                        Registered student accounts
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-icon green">
                    <i class="bi bi-building"></i>
                </div>

                <div>
                    <p>Total Companies</p>

                    <h3>
                        <?= number_format($adminDashboard['total_companies']) ?>
                    </h3>

                    <span class="text-muted small">
                        Registered company profiles
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-icon orange">
                    <i class="bi bi-briefcase"></i>
                </div>

                <div>
                    <p>Active Internships</p>

                    <h3>
                        <?= number_format($adminDashboard['active_internships']) ?>
                    </h3>

                    <span class="text-muted small">
                        Published and within deadline
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-icon purple">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div>
                    <p>Total Applications</p>

                    <h3>
                        <?= number_format($adminDashboard['total_applications']) ?>
                    </h3>

                    <span class="text-muted small">
                        All submitted applications
                    </span>
                </div>
            </div>

        </div>


            <!-- PENDING ACTIONS  -->

            <section class="admin-panel">

                <div class="admin-panel-header">

                    <div>
                        <h2>Company Verification</h2>
                        <p>Company accounts awaiting an administrator decision.</p>
                    </div>

                    <span class="admin-pending-count">
                        <?= number_format($adminDashboard['pending_companies']) ?> Pending
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
                                <?= number_format($adminDashboard['pending_companies']) ?>
                                company account(s) awaiting verification.
                            </p>

                            <a
                                class="admin-text-button text-decoration-none"
                                href="<?= e(url('admin-verifications.php')) ?>">
                                Review Companies
                                <i class="bi bi-arrow-right"></i>
                            </a>

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
                                Internship review tools are not connected yet.
                            </p>

                            <a
                                class="admin-text-button text-decoration-none"
                                href="<?= e(url('admin-internships.php')) ?>">
                                Review Internships
                                <i class="bi bi-arrow-right"></i>
                            </a>

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
                                Report management is not connected yet.
                            </p>

                            <button
                                type="button"
                                class="admin-text-button"
                                disabled>

                                Review Reports
                                <i class="bi bi-arrow-right"></i>

                            </button>

                        </div>

                    </div>

                </div>

            </section>


            <!-- MANAGEMENT -->

            <section class="admin-management-grid">

                <!-- USER SUMMARY -->
                <div class="admin-panel" id="users">

                    <div class="admin-panel-header">
                        <div>
                            <h2>User Accounts</h2>
                            <p>Registered accounts by role and account status.</p>
                        </div>


                        <a
                            class="btn btn-outline-primary admin-small-button"
                            href="<?= e(url('admin-users.php')) ?>">
                            Manage Users
                        </a>
                    </div>

                    <div class="admin-user-summary">

                        <?php foreach ($adminPanels['user_roles'] as $row): ?>
                            <div class="admin-user-row">

                                <div class="admin-user-icon blue">
                                    <i class="bi bi-person"></i>
                                </div>

                                <div class="admin-user-info">
                                    <strong>
                                        <?= e(ucfirst((string) $row['role'])) ?>
                                    </strong>
                                    <span>Registered accounts</span>
                                </div>

                                <span class="admin-user-number">
                                    <?= number_format((int) $row['total']) ?>
                                </span>

                            </div>
                        <?php endforeach; ?>

                        <hr>

                        <?php foreach ($adminPanels['user_statuses'] as $row): ?>
                            <div class="admin-user-row">

                                <div class="admin-user-icon green">
                                    <i class="bi bi-person-check"></i>
                                </div>

                                <div class="admin-user-info">
                                    <strong>
                                        <?= e(ucfirst(
                                            (string) ($row['status'] ?? 'Unspecified')
                                        )) ?>
                                    </strong>
                                    <span>Across all account roles</span>
                                </div>

                                <span class="admin-user-number">
                                    <?= number_format((int) $row['total']) ?>
                                </span>

                            </div>
                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- COMPANY MANAGEMENT -->
                <div class="admin-panel" id="companies">

                    <div class="admin-panel-header">
                        <div>
                            <h2>Company Management</h2>
                            <p>Five most recently created company profiles.</p>
                        </div>

                        <a
                            class="btn btn-outline-primary admin-small-button"
                            href="<?= e(url('admin-verifications.php')) ?>">
                            Manage Companies
                        </a>
                    </div>

                    <div class="admin-company-list">

                        <?php if ($adminPanels['companies'] === []): ?>

                            <p class="text-muted py-3">
                                No company profiles have been created yet.
                            </p>

                        <?php else: ?>

                            <?php foreach ($adminPanels['companies'] as $company): ?>

                                <?php
                                $badgeClass = match (
                                    $company['verification_status']
                                ) {
                                    'verified' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-warning text-dark',
                                };

                                $initial = mb_strtoupper(
                                    mb_substr(
                                        $company['company_name'],
                                        0,
                                        1,
                                        'UTF-8'
                                    ),
                                    'UTF-8'
                                );
                                ?>

                                <div class="admin-company-row">

                                    <div class="admin-company-logo blue">
                                        <?= e($initial) ?>
                                    </div>

                                    <div class="admin-company-info">
                                        <strong>
                                            <a
                                                class="text-decoration-none text-reset"
                                                href="<?= e(url(
                                                    'admin-verification-review.php?company_id='
                                                    . (int) $company['company_id']
                                                )) ?>">
                                                <?= e($company['company_name']) ?>
                                            </a>
                                        </strong>

                                        <span>
                                            <?= e(
                                                $company['industry']
                                                ?: 'Industry not provided'
                                            ) ?>
                                        </span>
                                    </div>

                                    <span class="badge <?= e($badgeClass) ?>">
                                        <?= e(ucfirst(
                                            $company['verification_status']
                                        )) ?>
                                    </span>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </section>

            <!-- INTERNSHIPS & APPLICATIONS -->

            <section class="admin-management-grid">

                <!-- INTERNSHIP SUMMARY -->
                <div class="admin-panel" id="internships">

                    <div class="admin-panel-header">
                        
                        <div>
                            <h2>Internship Status</h2>
                            <p>All internship posts, grouped by saved status.</p>
                        </div>

                        <a
                            class="btn btn-outline-primary admin-small-button"
                            href="<?= e(url('admin-internships.php')) ?>">
                            Manage Internships
                        </a>
                    </div>

                    <div class="admin-internship-summary">

                        <?php foreach (
                            $adminPanels['internships'] as $status => $total
                        ): ?>

                            <?php
                            $dotClass = match ($status) {
                                'Published' => 'active',
                                'Draft' => 'pending',
                                default => 'closed',
                            };
                            ?>

                            <div class="admin-status-item">

                                <span
                                    class="admin-status-dot <?= e($dotClass) ?>">
                                </span>

                                <div>
                                    <strong><?= e($status) ?></strong>

                                    <small>
                                        <?= number_format($total) ?> internships
                                    </small>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        Published is the saved post status. The Active Internships
                        total above also checks company verification and deadlines.
                    </p>

                </div>

                <!-- APPLICATION SUMMARY -->
                <div class="admin-panel" id="applications">

                    <div class="admin-panel-header">
                        <div>
                            <h2>Application Monitoring</h2>
                            <p>Current application statuses across the system.</p>
                        </div>
                    </div>

                    <div class="admin-application-summary">

                        <?php foreach (
                            $adminPanels['applications'] as $status => $total
                        ): ?>

                            <?php
                            $iconClass = match ($status) {
                                'Pending' => 'bi-hourglass-split',
                                'Under Review' => 'bi-search',
                                'Shortlisted' => 'bi-person-check',
                                'Accepted' => 'bi-check-circle',
                                'Rejected' => 'bi-x-circle',
                                'Withdrawn' => 'bi-arrow-return-left',
                                default => 'bi-file-earmark-text',
                            };

                            $colorClass = match ($status) {
                                'Shortlisted', 'Accepted' => 'green',
                                'Under Review', 'Rejected' => 'orange',
                                default => 'blue',
                            };
                            ?>

                            <div class="admin-application-item">

                                <div
                                    class="admin-application-icon <?= e($colorClass) ?>">
                                    <i class="bi <?= e($iconClass) ?>"></i>
                                </div>

                                <div>
                                    <strong><?= e($status) ?></strong>
                                    <span><?= number_format($total) ?></span>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            </section>


            <!-- RECENT ACTIVITY -->

            <section class="admin-panel">

                <div class="admin-panel-header">
                    <div>
                        <h2>Recent System Activity</h2>
                        <p>Latest recorded activity across InternMatch.</p>
                    </div>

                    <a
                        class="admin-text-button text-decoration-none"
                        href="<?= e(url('admin-activity.php')) ?>">
                        View More
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="admin-activity-list">

                    <?php if ($recentActivity === []): ?>

                        <p class="text-muted py-3">
                            No activity has been recorded yet.
                        </p>

                    <?php else: ?>

                        <?php foreach ($recentActivity as $activity): ?>

                            <?php
                            $appearance = AdminActivityController::appearance(
                                $activity['event_type']
                            );
                            ?>

                            <div class="admin-activity-row">

                                <div
                                    class="admin-activity-icon <?= e(
                                        $appearance['color']
                                    ) ?>">
                                    <i class="bi <?= e($appearance['icon']) ?>"></i>
                                </div>

                                <div class="admin-activity-info">

                                    <strong>
                                        <?php if ($activity['company_id'] !== null): ?>

                                            <a
                                                class="text-decoration-none text-reset"
                                                href="<?= e(url(
                                                    'admin-verification-review.php?company_id='
                                                    . (int) $activity['company_id']
                                                )) ?>">
                                                <?= e($activity['title']) ?>
                                            </a>

                                        <?php else: ?>

                                            <?= e($activity['title']) ?>

                                        <?php endif; ?>
                                    </strong>

                                    <span><?= e($activity['detail']) ?></span>

                                </div>

                                <span class="admin-activity-time">
                                    <?= e($activity['event_at']) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>



            <!--QUICK ACTIONS -->

            <section class="admin-panel">

            <a
                href="<?= e(url('admin-verifications.php')) ?>"
                class="btn btn-primary">
                <i class="bi bi-shield-check me-1"></i>
                Company Verification
            </a>

                <div class="admin-panel-header">

                    <div>
                        <h2>Quick Actions</h2>
                        <p>Frequently used administration tools.</p>
                    </div>

                </div>


                <div class="admin-quick-actions">

                    <a
                        class="admin-quick-action text-decoration-none text-reset"
                        href="<?= e(url('admin-users.php?role=student')) ?>">

                        <div class="admin-quick-icon blue">
                            <i class="bi bi-people"></i>
                        </div>

                        <span>Manage Students</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>


                    <a
                        class="admin-quick-action text-decoration-none text-reset"
                        href="<?= e(url('admin-verifications.php')) ?>">

                        <div class="admin-quick-icon green">
                            <i class="bi bi-building-check"></i>
                        </div>

                        <span>Verify Companies</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>



                    

                    <a
                        class="admin-quick-action text-decoration-none text-reset"
                        href="<?= e(url('admin-internships.php')) ?>">

                        <div class="admin-quick-icon orange">
                            <i class="bi bi-briefcase"></i>
                        </div>

                        <span>Manage Internships</span>
                        <i class="bi bi-arrow-right"></i>

                    </a>


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

<?php render_footer($user); ?>

