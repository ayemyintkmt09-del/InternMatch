<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminActivityController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('admin');



$activityType = $_GET['type'] ?? '';

if (
    !is_string($activityType)
    || mb_strlen($activityType, 'UTF-8') > 80
) {
    http_response_code(400);
    exit('Invalid activity category.');
}


try {
    $recentActivities = AdminActivityController::recent(100);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('System activity could not be loaded.');
}



$availableTypes = array_values(array_unique(
    array_map(
        static fn(array $item): string =>
            (string) $item['event_type'],
        $recentActivities
    )
));

sort($availableTypes);

$activities = array_values(array_filter(
    $recentActivities,
    static function (array $item) use (
        $activityType
    ): bool {
        return $activityType === ''
            || $item['event_type'] === $activityType;
    }
));



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


    
<form method="get"
      action="<?= e(url('admin-activity.php')) ?>"
      class="im-admin-filter-bar">

    <div>
        <label for="activityType">
            Activity Category
        </label>

        <select name="type"
                id="activityType"
                class="form-select">

            <option value="">All Recent Activity</option>

            <?php foreach ($availableTypes as $type): ?>

                <option value="<?= e($type) ?>"
                    <?= $activityType === $type
                        ? 'selected' : '' ?>>

                    <?= e(ucwords(
                        str_replace('_', ' ', $type)
                    )) ?>

                </option>

            <?php endforeach; ?>

        </select>
    </div>

    <button type="submit" class="btn btn-primary">
        Filter
    </button>

    <a href="<?= e(url('admin-activity.php')) ?>"
       class="btn btn-outline-secondary">
        Clear
    </a>

</form>

<p class="small text-muted">
    Showing <?= count($activities) ?>
    matching event(s) from the latest
    <?= count($recentActivities) ?> records.
</p>


    <section class="admin-panel">

        <?php if ($activities === []): ?>

            <p class="text-muted mb-0">
                No activity matches the selected category
                within the latest 100 recorded events.
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