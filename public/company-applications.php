<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('company');

$statusOptions = [
    'Pending',
    'Under Review',
    'Shortlisted',
    'Accepted',
    'Rejected',
    'Withdrawn',
];

$statusFilter = $_GET['status'] ?? '';

if (!is_string($statusFilter)) {
    $statusFilter = '';
}

$statusFilter = trim($statusFilter);

if (
    $statusFilter !== ''
    && !in_array($statusFilter, $statusOptions, true)
) {
    http_response_code(400);
    exit('Invalid application status.');
}


try {
    $applications = CompanyApplicationController::all(
    (int) $user['user_id'],
    $statusFilter === '' ? null : $statusFilter
);

} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Applications could not be loaded.');
}

$pageTitle = 'Applications';
$activeNav = 'applications';

render_header(
    $user,
    $pageTitle ?? 'InternMatch',
    $activeNav ?? ''
);

?>

<main class="profile-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">APPLICATION MANAGEMENT</p>
                <h1>Applications</h1>
                <p>Review applications from all your internships.</p>

                <form
                    method="get"
                    action="<?= e(url('company-applications.php')) ?>"
                    class="application-filter-bar mb-4">

                    <label
                        for="companyStatusFilter"
                        class="visually-hidden">
                        Filter applications by status
                    </label>

                    <select
                        id="companyStatusFilter"
                        name="status"
                        class="form-select"
                        onchange="this.form.submit()">

                        <option value="">All applications</option>

                        <?php foreach ($statusOptions as $statusOption): ?>
                            <option
                                value="<?= e($statusOption) ?>"
                                <?= $statusFilter === $statusOption
                                    ? 'selected'
                                    : '' ?>>
                                <?= e($statusOption) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </form>
            </div>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('company-dashboard.php')) ?>">
                Back to Dashboard
            </a>
        </div>

        <?php if (
                $applications === []
                && $statusFilter !== ''
            ): ?>

                <section class="profile-section empty-state">
                    <i class="bi bi-funnel fs-1 text-primary"></i>

                    <h3 class="mt-3">
                        No <?= e($statusFilter) ?> applications found.
                    </h3>

                    <p class="text-muted mb-3">
                        Try another status filter.
                    </p>

                    <a
                        class="btn btn-outline-primary"
                        href="<?= e(url('company-applications.php')) ?>">
                        Show All Applications
                    </a>
                </section>

            <?php elseif ($applications === []): ?>

                

            <section class="profile-section empty-state">
                <i class="bi bi-people fs-1 text-primary"></i>

                <h3 class="mt-3">No applications yet</h3>

                <p class="text-muted mb-0">
                    Student applications will appear here.
                </p>
            </section>

        <?php else: ?>

            <section class="profile-section">
<div
    class="table-responsive"
    role="region"
    aria-label="Company Applications results"
    tabindex="0">                
                >
                    <table class="table align-middle application-table">
                    <caption class="visually-hidden">
                        Applications received by the company
                    </caption>

                        <thead>
                            <tr>
                                <th scope="col">Student</th>
                                <th scope="col">Internship</th>
                                <th scope="col">Applied</th>
                                <th scope="col">Status</th>
                                <th scope="col">Review</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($applications as $application): ?>


                                <?php
                                    $statusClass = match ($application['status']) {
                                        'Accepted' =>
                                            'bg-success-subtle text-success',

                                        'Rejected' =>
                                            'bg-danger-subtle text-danger',

                                        'Withdrawn' =>
                                            'bg-secondary-subtle text-secondary',

                                        'Shortlisted' =>
                                            'bg-primary-subtle text-primary',

                                        'Under Review' =>
                                            'bg-warning-subtle text-warning-emphasis',

                                        default =>
                                            'bg-light text-dark',
                                    };
                                    ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= e(
                                                $application['student_name']
                                            ) ?>
                                        </strong>

                                        <div class="small text-muted">
                                            <?= e(
                                                $application['student_email']
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($application['title']) ?>
                                    </td>

                                    <td>
                                        <?= e(substr(
                                            $application['application_date'],
                                            0,
                                            10
                                        )) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= e($statusClass) ?>">
                                            <?= e($application['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (
                                            $application['status']
                                            === 'Withdrawn'
                                        ): ?>

                                            <span class="text-muted small">
                                                Withdrawn
                                            </span>

                                        <?php else: ?>

                                            <a
                                                class="btn btn-sm btn-primary"
                                                href="<?= e(url(
                                                    'company-application-review.php?id='
                                                    . (int) $application[
                                                        'application_id'
                                                    ]
                                                )) ?>">
                                                Review
                                            </a>

                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php render_footer($user); ?>