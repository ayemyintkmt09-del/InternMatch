<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/NotificationController.php';
require_once __DIR__ . '/../app/layout.php';


header('Cache-Control: no-store');

$user = current_user();

if ($user === null) {
    flash('login_error', 'Please log in to view notifications.');
    redirect('login.php');
}

$userId = (int) $user['user_id'];

$filter = is_string($_GET['filter'] ?? null)
    ? $_GET['filter']
    : 'all';

if (!in_array(
    $filter,
    ['all', 'unread', 'applications', 'system'],
    true
)) {
    http_response_code(400);
    exit('Invalid notification filter.');
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = is_int($page) && $page > 0 ? $page : 1;

$pagePath = static function (int $number) use ($filter): string {
    return 'notifications.php?' . http_build_query([
        'filter' => $filter,
        'page' => $number,
    ]);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $action = $_POST['action'] ?? null;

        if ($action === 'read_all') {
            NotificationController::markAllRead($userId);
        } elseif ($action === 'read_one') {
            $rawId = $_POST['notification_id'] ?? null;

            $notificationId = is_string($rawId)
                ? filter_var(
                    $rawId,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                )
                : false;

            if ($notificationId === false) {
                throw new InvalidArgumentException(
                    'Invalid notification ID.'
                );
            }

            NotificationController::markRead(
                $userId,
                $notificationId
            );
        } else {
            throw new InvalidArgumentException(
                'Invalid notification action.'
            );
        }

        flash('notification_success', 'Read status updated.');
    } catch (InvalidArgumentException $exception) {
        flash('notification_error', $exception->getMessage());
    } catch (Throwable $exception) {
        error_log((string) $exception);

        flash(
            'notification_error',
            'Notifications could not be updated. Please try again.'
        );
    }

    redirect($pagePath($page));
}

try {
    $result = NotificationController::listing(
        $userId,
        $filter,
        $page
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('Notifications could not be loaded.');
}

$success = take_flash('notification_success');
$error = take_flash('notification_error');

$filterLabels = [
    'all' => 'All',
    'unread' => 'Unread',
    'applications' => 'Applications',
    'system' => 'System',
];

$displayTime = static function (string $value): string {
    return (new DateTimeImmutable(
        $value,
        new DateTimeZone('UTC')
    ))->setTimezone(
        new DateTimeZone(date_default_timezone_get())
    )->format('d M Y, g:i A');
};
?>

<?php render_header($user, 'Notifications', 'notifications'); ?>


<main class="notifications-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="notifications-page-header">
            <div>
                <p class="dashboard-small-title">YOUR INBOX</p>
                <h1>Notifications</h1>
                <p>Updates for your InternMatch account.</p>
            </div>

            <form
                method="post"
                action="<?= e(url($pagePath($result['page']))) ?>">

                <?= csrf_field() ?>
                <input type="hidden" name="action" value="read_all">

                <button
                    type="submit"
                    class="btn btn-primary notification-read-all"
                    <?= $result['unread_count'] === 0 ? 'disabled' : '' ?>>
                    Mark All as Read
                </button>
            </form>
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

        <div class="notification-summary-row">

            <?php foreach ([
                ['Unread', $result['unread_count'], 'blue', 'bi-bell'],
                ['Read', $result['read_count'], 'green', 'bi-check2-all'],
                ['Total', $result['all_count'], 'orange', 'bi-inbox'],
            ] as [$label, $count, $color, $icon]): ?>

                <div class="notification-summary-card">
                    <div class="notification-summary-icon <?= e($color) ?>">
                        <i class="bi <?= e($icon) ?>"></i>
                    </div>

                    <div>
                        <span><?= e($label) ?></span>
                        <strong><?= number_format($count) ?></strong>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>

        <div class="notifications-toolbar">
            <div class="notification-tabs">

                <?php foreach ($filterLabels as $value => $label): ?>
                    <a
                        class="notification-tab <?= $filter === $value
                            ? 'active' : '' ?>"
                        <?= $filter === $value
                            ? 'aria-current="page"' : '' ?>
                        href="<?= e(url(
                            'notifications.php?'
                            . http_build_query(['filter' => $value])
                        )) ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>

            </div>
        </div>

        <p class="small text-muted">
            <?= number_format($result['total']) ?> notification(s)
            · Times shown in Myanmar time
        </p>

        <?php if ($result['items'] === []): ?>

            <div class="no-notifications">
                <div class="no-notifications-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>

                <h4>No notifications found</h4>
                <p>New account updates will appear here.</p>
            </div>

        <?php else: ?>

            <?php foreach ($result['items'] as $notification): ?>

                <?php
                $unread = (int) $notification['is_read'] === 0;

                $destination = NotificationController::destination(
                    $notification,
                    $user['role']
                );
                ?>

                <article class="notification-card <?= $unread ? 'unread' : '' ?>">

                    <div class="notification-icon blue">
                        <i class="bi bi-bell"></i>
                    </div>

                    <div class="notification-content">
                        <div class="notification-title-row">
                            <h5><?= e($notification['notification_type']) ?></h5>

                            <?php if ($unread): ?>
                                <span class="badge bg-primary">Unread</span>
                            <?php endif; ?>
                        </div>

                        <p><?= e($notification['message']) ?></p>

                        <span class="notification-time">
                            <?= e($displayTime(
                                $notification['created_at']
                            )) ?>
                        </span>

                        <?php if ($destination !== null): ?>
                            <div class="mt-2">
                                <a href="<?= e(url($destination)) ?>">
                                    View related information
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($unread): ?>
                        <form
                            method="post"
                            action="<?= e(url(
                                $pagePath($result['page'])
                            )) ?>">

                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="read_one">

                            <input
                                type="hidden"
                                name="notification_id"
                                value="<?= (int) $notification['notification_id'] ?>">

                            <button
                                type="submit"
                                class="notification-action"
                                title="Mark as read"
                                aria-label="Mark this notification as read">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center gap-3 mt-4">

            <div>
                <?php if ($result['page'] > 1): ?>
                    <a
                        class="btn btn-outline-primary"
                        href="<?= e(url(
                            $pagePath($result['page'] - 1)
                        )) ?>">
                        Previous
                    </a>
                <?php endif; ?>
            </div>

            <span class="small text-muted">
                Page <?= $result['page'] ?> of <?= $result['pages'] ?>
            </span>

            <div>
                <?php if ($result['page'] < $result['pages']): ?>
                    <a
                        class="btn btn-outline-primary"
                        href="<?= e(url(
                            $pagePath($result['page'] + 1)
                        )) ?>">
                        Next
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</main>

<?php render_footer($user); ?>