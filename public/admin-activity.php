<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminActivityController.php';

require_role('admin');

try {
    $activities = AdminActivityController::recent(100);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('System activity could not be loaded.');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>System Activity - InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link
        href="<?= e(asset_url('css/style.css')) ?>" 
               rel="stylesheet">
</head>

<body>

<main class="container py-5">

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

            <div class="table-responsive">

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
                                <?= e($activity['event_at']) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        <?php endif; ?>

    </section>

    <p class="text-muted small mt-3">
        Times are displayed as stored in the database.
        Company reviews show the latest stored decision.
        This feed does not retain every previous edit or deleted record.
    </p>

</main>

</body>
</html>