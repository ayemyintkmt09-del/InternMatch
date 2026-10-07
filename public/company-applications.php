<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';

$user = require_role('company');

try {
    $applications = CompanyApplicationController::all(
        (int) $user['user_id']
    );
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Applications could not be loaded.');
}

$pageTitle = 'Applications';
$activeNav = 'applications';

require __DIR__ . '/../app/views/company-header.php';
?>

<main class="profile-page">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">APPLICATION MANAGEMENT</p>
                <h1>Applications</h1>
                <p>Review applications from all your internships.</p>
            </div>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('company-dashboard.php')) ?>">
                Back to Dashboard
            </a>
        </div>

        <?php if ($applications === []): ?>

            <section class="profile-section text-center py-5">
                <i class="bi bi-people fs-1 text-primary"></i>

                <h3 class="mt-3">No applications yet</h3>

                <p class="text-muted mb-0">
                    Student applications will appear here.
                </p>
            </section>

        <?php else: ?>

            <section class="profile-section">
                <div class="table-responsive">
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Internship</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>Review</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($applications as $application): ?>

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
                                        <?= e($application['status']) ?>
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

<?php require __DIR__ . '/../app/views/company-footer.php'; ?>