<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';

$user = require_role('student');
$userId = (int) $user['user_id'];

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
    $applications = ApplicationController::listing($userId);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Your applications could not be loaded.');
}

$success = take_flash('application_success');
$error = take_flash('application_error');

$pageTitle = 'My Applications';
$activeNav = '';

require __DIR__ . '/../app/views/student-header.php';
?>

<main class="opportunities-page">
    <div class="container">

        <div class="opportunities-header">
            <div>
                <p class="dashboard-small-title">APPLICATION TRACKER</p>
                <h1>My Applications</h1>
                <p>Track the internships you have applied for.</p>
            </div>
        </div>

        <?php if ($success !== null): ?>
            <div class="alert alert-success">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($applications === []): ?>

            <div class="opportunity-card text-center py-5">
                <i class="bi bi-send fs-1 text-primary"></i>

                <h3 class="mt-3">No applications yet</h3>

                <p class="text-muted">
                    Browse opportunities and submit your first application.
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
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Internship</th>
                                <th>Company</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>Action</th>
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
                                    default =>
                                        'bg-warning-subtle text-warning-emphasis',
                                };
                                ?>

                                <tr>
                                    <td>
                                        <?= e($application['title']) ?>
                                    </td>

                                    <td>
                                        <?= e($application['company_name']) ?>
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
                                            in_array(
                                                $application['status'],
                                                ['Pending', 'Under Review'],
                                                true
                                            )
                                        ): ?>

                                            <form
                                                method="post"
                                                action="<?= e(url(
                                                    'my-applications.php'
                                                )) ?>"
                                                class="m-0"
                                                onsubmit="return confirm('Withdraw this application?');">

                                                <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="application_id"
                                                    value="<?= (int) $application['application_id'] ?>">

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

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>