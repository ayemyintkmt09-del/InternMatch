<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyApplicationController.php';

$user = require_role('company');
$userId = (int) $user['user_id'];

$rawId = $_GET['internship_id'] ?? null;

if (!is_string($rawId)) {
    http_response_code(400);
    exit('Invalid internship ID.');
}

$internshipId = filter_var(
    $rawId,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($internshipId === false) {
    http_response_code(400);
    exit('Invalid internship ID.');
}

try {
    $result = CompanyApplicationController::applicants(
        $userId,
        $internshipId
    );
} catch (InvalidArgumentException $exception) {
    http_response_code(404);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Applicants could not be loaded.');
}

$pageTitle = 'Applicants';
require __DIR__ . '/../app/views/company-header.php';
?>




<main class="profile-page">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">APPLICATIONS</p>
                <h1><?= e($result['title']) ?></h1>
                <p>Review students who applied for this internship.</p>
            </div>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('company-dashboard.php')) ?>">
                Back to Dashboard
            </a>
        </div>

        <?php if ($result['items'] === []): ?>

            <section class="profile-section text-center py-5">
                <i class="bi bi-people fs-1 text-primary"></i>
                <h3 class="mt-3">No applicants yet</h3>
                <p class="text-muted">
                    Applications will appear here when students apply.
                </p>
            </section>

        <?php else: ?>

            <section class="profile-section">
                <div class="table-responsive">
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Education</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>CV</th>
                                <th>Review</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($result['items'] as $application): ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= e($application['student_name']) ?>
                                        </strong>

                                        <div class="small text-muted">
                                            <?= e($application['student_email']) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e(
                                            $application['university']
                                            ?: 'University not added'
                                        ) ?>

                                        <div class="small text-muted">
                                            <?= e(
                                                $application['degree']
                                                ?: 'Degree not added'
                                            ) ?>
                                        </div>
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
                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="<?= e(url(
                                                'company-cv-download.php?id='
                                                . (int) $application['application_id']
                                            )) ?>">
                                            <i class="bi bi-download me-1"></i>
                                            Download CV
                                        </a>
                                    </td>

                                    <td>
                                        <?php if ($application['status'] === 'Withdrawn'): ?>

                                            <span class="text-muted small">
                                                Withdrawn — no review available
                                            </span>

                                        <?php else: ?>

                                            <a
                                                class="btn btn-sm btn-primary"
                                                href="<?= e(url(
                                                    'company-application-review.php?id='
                                                    . (int) $application['application_id']
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