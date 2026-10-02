<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $update = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Fetch user notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$page_title = "My Notifications";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-success">Notifications & Alerts</h2>
            <p class="text-muted mb-0">Stay updated on your application status changes and hiring updates.</p>
        </div>
        <?php if(!empty($notifications)): ?>
            <a href="notifications.php?mark_read=true" class="btn btn-outline-secondary btn-sm">Mark All as Read</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="list-group list-group-flush">
            <?php if(empty($notifications)): ?>
                <p class="text-center text-muted py-4 mb-0">You have no notifications right now.</p>
            <?php else: ?>
                <?php foreach($notifications as $notif): ?>
                    <div class="list-group-item px-3 py-3 d-flex justify-content-between align-items-center <?php echo $notif['is_read'] == 0 ? 'bg-light fw-bold' : ''; ?>">
                        <div>
                            <p class="mb-1 text-dark"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($notif['created_at'])); ?></small>
                        </div>
                        <?php if($notif['is_read'] == 0): ?>
                            <span class="badge bg-success rounded-pill">New</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>