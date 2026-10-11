<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminDashboardController.php';
require_once __DIR__ . '/../app/controllers/AdminInsightsController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('admin');

try {
    $totals = AdminDashboardController::load();
    $panels = AdminDashboardController::panels();

    $monthly = AdminInsightsController::monthlyActivity();
    $companies = AdminInsightsController::topCompanies();

} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('Platform analytics could not be loaded.');
}

$chartMaximum = 1;

foreach ($monthly as $row) {
    $chartMaximum = max(
        $chartMaximum,
        $row['registrations'],
        $row['applications']
    );
}

render_header($user, 'Platform Insights', 'insights');
?>




<main class="im-admin-insights"
      id="main-content"
      tabindex="-1">

    <div class="container">

        <header class="im-insights-header">

            <div>
                <p class="dashboard-small-title">
                    PLATFORM MANAGEMENT
                </p>

                <h1>Platform Insights</h1>

                <p>
                    Monitor platform activity, internship
                    publishing, and company engagement.
                </p>
            </div>

            <a href="<?= e(url('admin-dashboard.php')) ?>"
               class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i>
                Admin Dashboard
            </a>

        </header>


        
<section class="im-insights-stats">

    <?php foreach ([
        [
            'label' => 'Total Students',
            'value' => $totals['total_students'],
            'icon' => 'bi-mortarboard'
        ],
        [
            'label' => 'Total Companies',
            'value' => $totals['total_companies'],
            'icon' => 'bi-buildings'
        ],
        [
            'label' => 'Active Internships',
            'value' => $totals['active_internships'],
            'icon' => 'bi-briefcase'
        ],
        [
            'label' => 'Total Applications',
            'value' => $totals['total_applications'],
            'icon' => 'bi-file-earmark-text'
        ],
    ] as $stat): ?>

        <article class="im-insight-stat">
            <i class="bi <?= e($stat['icon']) ?>"
               aria-hidden="true"></i>

            <span><?= e($stat['label']) ?></span>

            <strong>
                <?= number_format((int) $stat['value']) ?>
            </strong>
        </article>

    <?php endforeach; ?>

</section>




<section class="im-insights-panel">

    <h2>Platform Activity — Last 6 Months</h2>

    <p class="text-muted">
        Monthly registrations and internship applications.
    </p>

    <div class="im-insights-legend">
        <span><i class="im-legend-dot registrations"></i> Registrations</span>
        <span><i class="im-legend-dot applications"></i> Applications</span>
    </div>

    <div class="im-monthly-chart">

        <?php foreach ($monthly as $row): ?>

            <?php
            $registrationWidth = 100
                * $row['registrations'] / $chartMaximum;

            $applicationWidth = 100
                * $row['applications'] / $chartMaximum;
            ?>

            <div class="im-month-row">
                <strong><?= e($row['month']) ?></strong>

                <div class="im-month-bars">
                    <div class="im-chart-track">
                        <div class="registrations"
                             style="width: <?= (float) $registrationWidth ?>%">
                        </div>
                    </div>

                    <small>
                        <?= $row['registrations'] ?> registrations
                    </small>

                    <div class="im-chart-track">
                        <div class="applications"
                             style="width: <?= (float) $applicationWidth ?>%">
                        </div>
                    </div>

                    <small>
                        <?= $row['applications'] ?> applications
                    </small>
                </div>
            </div>

        <?php endforeach; ?>

    </div>
</section>




<section class="im-insights-panel">

    <div class="im-insights-section-head">
        <h2>Application Status Distribution</h2>

        <a href="<?= e(url('admin-dashboard.php#applications')) ?>">
            Dashboard Overview
        </a>
    </div>

    <div class="im-insights-status-grid">

        <?php foreach (
            $panels['applications'] as $status => $count
        ): ?>

            <div class="im-insights-status-card">
                <span><?= e($status) ?></span>

                <strong>
                    <?= number_format((int) $count) ?>
                </strong>
            </div>

        <?php endforeach; ?>

    </div>

</section>



<section class="im-insights-panel">

    <div class="im-insights-section-head">
        <h2>Internship Publishing Overview</h2>

        <a href="<?= e(url('admin-internships.php')) ?>">
            Manage Internships
        </a>
    </div>

    <div class="im-insights-status-grid">

        <?php foreach (
            $panels['internships'] as $status => $count
        ): ?>

            <a class="im-insights-status-card"
               href="<?= e(url(
                   'admin-internships.php?status='
                   . rawurlencode($status)
               )) ?>">

                <span><?= e($status) ?></span>

                <strong>
                    <?= number_format((int) $count) ?>
                </strong>

            </a>

        <?php endforeach; ?>

    </div>

    <p class="small text-muted mt-3">
        These counts reflect stored internship statuses.
        Actual visibility also depends on company verification,
        account status, and application deadlines.
    </p>

</section>




<section class="im-insights-panel">

    <div class="im-insights-section-head">
        <h2>Companies by Application Volume</h2>

        <a href="<?= e(url('admin-verifications.php')) ?>">
            View Companies
        </a>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">

            <thead>
                <tr>
                    <th>Company</th>
                    <th>Verification</th>
                    <th>Postings</th>
                    <th>Applications</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($companies as $company): ?>

                    <tr>
                        <td>
                            <a href="<?= e(url(
                                'admin-verification-review.php?company_id='
                                . (int) $company['company_id']
                            )) ?>">
                                <?= e($company['company_name']) ?>
                            </a>
                        </td>

                        <td>
                            <?= e(ucfirst(
                                $company['verification_status']
                            )) ?>
                        </td>

                        <td>
                            <?= (int) $company['total_postings'] ?>
                        </td>

                        <td>
                            <?= (int) $company['total_applications'] ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</section>


    </div>
</main>

<?php render_footer($user); ?>
