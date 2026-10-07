<?php

require_once __DIR__ . '/../controllers/NotificationController.php';

$notificationUnreadCount = null;
$userId = null;

if (isset($user['user_id'])) {
    $userId = (int) $user['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
}

if ($userId !== null && $userId > 0) {
    try {
        $notificationUnreadCount = NotificationController::unreadCount($userId);
    } catch (Throwable $exception) {
        error_log((string) $exception);
    }
}

$notificationLabel = $notificationUnreadCount === null
    ? 'Open notifications'
    : 'Notifications: ' . $notificationUnreadCount . ' unread';
?>

<a
    href="<?= e(url('notifications.php')) ?>"
    class="notification-button text-decoration-none"
    title="<?= e($notificationLabel) ?>"
    aria-label="<?= e($notificationLabel) ?>">

    <i class="bi bi-bell"></i>

    <?php if (
        $notificationUnreadCount !== null
        && $notificationUnreadCount > 0
    ): ?>

        <span class="notification-count-badge" aria-hidden="true">
            <?= $notificationUnreadCount > 99
                ? '99+'
                : $notificationUnreadCount ?>
        </span>

    <?php endif; ?>

</a>