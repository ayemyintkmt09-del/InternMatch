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


$keyword = $_GET['q'] ?? '';
$rawInternshipFilter = $_GET['internship_id'] ?? '';

if (
    !is_string($keyword)
    || mb_strlen(trim($keyword), 'UTF-8') > 100
    || !is_string($rawInternshipFilter)
) {
    http_response_code(400);
    exit('Invalid application filters.');
}

$keyword = trim($keyword);
$filterInternshipId = null;

if ($rawInternshipFilter !== '') {
    $filterInternshipId = filter_var(
        $rawInternshipFilter,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($filterInternshipId === false) {
        http_response_code(400);
        exit('Invalid internship selection.');
    }
}




try {
    $userId = (int) $user['user_id'];

    $companyInternships =
        CompanyApplicationController::internships($userId);

    $applications = CompanyApplicationController::all(
    $userId,
    $statusFilter === '' ? null : $statusFilter,
    $keyword,
    $filterInternshipId
);

} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Applications could not be loaded.');
}

$filteredCount = count($applications);

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


                
<form method="get"
      action="<?= e(url('company-applications.php')) ?>"
      class="im-recruit-filters">

    <div>
        <label for="recruitSearch">Search Applicants</label>

        <input type="search"
               id="recruitSearch"
               name="q"
               class="form-control"
               maxlength="100"
               placeholder="Name, email, or internship"
               value="<?= e($keyword) ?>">
    </div>

    <div>
        <label for="recruitInternship">Internship</label>

        <select id="recruitInternship"
                name="internship_id"
                class="form-select">

            <option value="">All Internships</option>

            <?php foreach ($companyInternships as $job): ?>
                <option
                    value="<?= (int) $job['internship_id'] ?>"
                    <?= $filterInternshipId ===
                        (int) $job['internship_id']
                        ? 'selected' : '' ?>>

                    <?= e($job['title']) ?>

                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label for="recruitStatus">Status</label>

        <select id="recruitStatus"
                name="status"
                class="form-select">

            <option value="">All Statuses</option>

            <?php foreach ($statusOptions as $status): ?>
                <option value="<?= e($status) ?>"
                    <?= $statusFilter === $status
                        ? 'selected' : '' ?>>

                    <?= e($status) ?>

                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="im-recruit-filter-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search me-1"></i>
            Search
        </button>
    </div>
</form>



<div class="im-recruit-results">

    <p>
        <strong><?= $filteredCount ?></strong>
        application(s) found
    </p>

    <a href="<?= e(url('company-applications.php')) ?>">
        <i class="bi bi-arrow-counterclockwise"></i>
        Clear Filters
    </a>

</div>


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