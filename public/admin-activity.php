<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminActivityController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('admin');
try {
    $activities = AdminActivityController::recent(100);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('System activity could not be loaded.');
}
?>

<?php render_header($user, 'Activity', 'activity'); ?>


<main class="container py-5" id="main-content" tabindex="-1">

    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">

        <div>
            <h1 class="h2">System Activity</h1>
            <p class="text-muted mb-0">
                Latest 100 recorded events, newest first.
            </p>
        </div>

        <a
            class="btn btn-outline-secondary"
            href="<?= e(url('admin-dashboard.php')) ?>">
            Back to Dashboard
        </a>

    </div>

    <section class="admin-panel">

        <?php if ($activities === []): ?>

            <p class="text-muted mb-0">
                No activity has been recorded yet.
            </p>

        <?php else: ?>

            <div
    class="table-responsive"
    role="region"
    aria-label="System activity"
    tabindex="0">

                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Activity</th>
                            <th scope="col">Details</th>
                            <th scope="col">Recorded at</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($activities as $activity): ?>

                        <?php
                        $appearance = AdminActivityController::appearance(
                            $activity['event_type']
                        );
                        ?>

                        <tr>
                            <td>
                                <i
                                    class="bi <?= e(
                                        $appearance['icon']
                                    ) ?> me-2"
                                    aria-hidden="true">
                                </i>

                                <?php if (
                                    $activity['company_id'] !== null
                                ): ?>

                                    <a
                                        href="<?= e(url(
                                            'admin-verification-review.php?company_id='
                                            . (int) $activity['company_id']
                                        )) ?>">
                                        <?= e($activity['title']) ?>
                                    </a>

                                <?php else: ?>

                                    <?= e($activity['title']) ?>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($activity['detail']) ?>
                            </td>

                            <td class="text-nowrap">
                                <?= e(format_utc_datetime(
                                    (string) $activity['event_at'],
                                    'd M Y, H:i'
                                )) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </section>

    <p class="text-muted small mt-3">
Times are displayed in the application timezone.
Recorded events include the actor, action, target, and timestamp.
    </p>

</main>

<?php render_footer($user); ?>