<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';

$user = require_role('student');
$userId = (int) $user['user_id'];

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

$selectedStatus = $statusFilter === ''
    ? null
    : $statusFilter;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $rawId = $_POST['application_id'] ?? null;

        if (!is_string($rawId)) {
            throw new InvalidArgumentException(
                'Invalid application ID.'
            );
        }

        $applicationId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($applicationId === false) {
            throw new InvalidArgumentException(
                'Invalid application ID.'
            );
        }

        ApplicationController::withdraw(
            $userId,
            $applicationId
        );

        flash(
            'application_success',
            'Your application has been withdrawn.'
        );
    } catch (InvalidArgumentException $exception) {
        flash(
            'application_error',
            $exception->getMessage()
        );
    } catch (Throwable $exception) {
        error_log((string) $exception);

        flash(
            'application_error',
            'The application could not be updated.'
        );
    }

    redirect('my-applications.php');
}

try {
    $applications = ApplicationController::listing(
    $userId,
    $selectedStatus
); 

    $applicationHistory = [];

    foreach ($applications as $application) {
        $applicationHistory[
            (int) $application['application_id']
        ] = ApplicationController::history(
            $userId,
            (int) $application['application_id']
        );
    }
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Your applications could not be loaded.');
}

$success = take_flash('application_success');
$error = take_flash('application_error');

$pageTitle = 'My Applications';
$activeNav = 'applications';


require __DIR__ . '/../app/views/student-header.php';
?>

<main class="opportunities-page">
    <div class="container">

        <div class="opportunities-header">
            <div>
                <p class="dashboard-small-title">
                    APPLICATION TRACKER
                </p>

                <h1>My Applications</h1>

                <p>
                    Track the internships you have applied for.
                </p>
            </div>
        </div>

        <?php if ($success !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>


        <div class="application-list-toolbar">

    <p class="mb-0 text-muted">
        Showing
        <strong><?= count($applications) ?></strong>
        <?= $statusFilter === ''
            ? 'applications'
            : e($statusFilter . ' applications') ?>
    </p>

    <form
        method="get"
        action="<?= e(url('my-applications.php')) ?>"
        class="application-filter-bar">

        <label
            for="applicationStatusFilter"
            class="visually-hidden">
            Filter applications by status
        </label>

        <select
            id="applicationStatusFilter"
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

        <?php if (
            $applications === []
            && $statusFilter !== ''
        ): ?>

            <div class="opportunity-card empty-state">
                <i class="bi bi-funnel fs-1 text-primary"></i>

                <h3 class="mt-3">
                    No <?= e($statusFilter) ?> applications found.
                </h3>

                <p class="text-muted">
                    Try another application-status filter.
                </p>

                <a
                    class="btn btn-outline-primary"
                    href="<?= e(url('my-applications.php')) ?>">
                    Show All Applications
                </a>
            </div>

        <?php elseif ($applications === []): ?>

           <div class="opportunity-card empty-state">
                <i class="bi bi-send fs-1 text-primary"></i>

                <h3 class="mt-3">You have not submitted any applications yet.</h3>

                <p class="text-muted">
                    Browse current opportunities and apply when you find a good fit.
                </p>

                <a
                    class="btn btn-primary"
                    href="<?= e(url('opportunities.php')) ?>">
                    Browse Opportunities
                </a>
            </div>

        <?php else: ?>

            <section class="profile-section">
                <div class="table-responsive">
                    <table class="table align-middle application-table">
                        <caption class="visually-hidden">
                            Applications submitted by the current student
                        </caption>

                        <thead>
                            <tr>
                                <th scope="col">Internship</th>
                                <th scope="col">Company</th>
                                <th scope="col">Applied</th>
                                <th scope="col">Status</th>
                                <th scope="col">Interview</th>
                                <th scope="col">Interview Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($applications as $application): ?>

                                <?php
                                $statusClass = match (
                                    $application['status']
                                ) {
                                    'Accepted' =>
                                        'bg-success-subtle text-success',

                                    'Rejected' =>
                                        'bg-danger-subtle text-danger',

                                    'Withdrawn' =>
                                        'bg-secondary-subtle text-secondary',

                                    'Shortlisted' =>
                                        'bg-primary-subtle text-primary',

                                    default =>
                                        'bg-warning-subtle text-warning-emphasis',
                                };
                                ?>

                                <tr>
                                    <td>
                                        <?= e($application['title']) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $application['company_name']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(format_utc_datetime(
                                            $application['application_date'],
                                            'd M Y'
                                        )) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="badge <?= e(
                                                $statusClass
                                            ) ?>">
                                            <?= e(
                                                $application['status']
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (
                                            !empty(
                                                $application['interview_date']
                                            )
                                        ): ?>

                                            <span
                                                class="small text-primary">
                                                <i
                                                    class="bi bi-calendar-event me-1"></i>

                                                <?= e(
                                                    $application[
                                                        'interview_date'
                                                    ]
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="small text-muted">
                                                Not scheduled
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            !empty(
                                                $application[
                                                    'interview_notes'
                                                ]
                                            )
                                        ): ?>

                                            <span class="small text-muted">
                                                <?= e(
                                                    $application[
                                                        'interview_notes'
                                                    ]
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="small text-muted">
                                                No notes
                                            </span>

                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (
                                            in_array(
                                                $application['status'],
                                                [
                                                    'Pending',
                                                    'Under Review',
                                                ],
                                                true
                                            )
                                        ): ?>

                                            <form
                                                method="post"
                                                action="<?= e(url(
                                                    'my-applications.php'
                                                )) ?>"
                                                class="m-0"
                                                onsubmit="return confirm(
                                                    'Withdraw this application?'
                                                );">

                                                <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="application_id"
                                                    value="<?= (int) $application[
                                                        'application_id'
                                                    ] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Withdraw
                                                </button>
                                            </form>

                                        <?php else: ?>

                                            <span class="text-muted small">
                                                No action
                                            </span>

                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="7">
                                        <details class="application-history">
                                            <summary
                                                class="small text-primary">
                                                View status history
                                            </summary>

                                            <div class="application-history-list mt-3">
                                                <?php foreach (
                                                    $applicationHistory[
                                                        (int) $application[
                                                            'application_id'
                                                        ]
                                                    ] as $history
                                                ): ?>

                                                    <div
                                                        class="border-start border-primary ps-3 mb-3">

                                                        <strong>
                                                            <?= e(
                                                                $history[
                                                                    'new_status'
                                                                ]
                                                            ) ?>
                                                        </strong>

                                                        <div
                                                            class="small text-muted">
                                                            <?= e(
                                                                $history[
                                                                    'changed_at'
                                                                ]
                                                            ) ?>
                                                        </div>

                                                        <?php if (
                                                            !empty(
                                                                $history[
                                                                    'notes'
                                                                ]
                                                            )
                                                        ): ?>

                                                            <div
                                                                class="small mt-1">
                                                                <?= e(
                                                                    $history[
                                                                        'notes'
                                                                    ]
                                                                ) ?>
                                                            </div>

                                                        <?php endif; ?>

                                                    </div>

                                                <?php endforeach; ?>
                                            </div>
                                        </details>
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

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>