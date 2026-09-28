<?php
session_start();
require_once '../../config/database.php';

// Ensure user is logged in and has an admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = '';

// Handle Status Toggle (Active / Suspended)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $target_user_id = $_POST['user_id'];
    $new_status = $_POST['status']; // 'active' or 'suspended'

    // Prevent admin from suspending themselves
    if ($target_user_id == $_SESSION['user_id']) {
        $message = "Error: You cannot modify your own administrator account status.";
    } else {
        $update_stmt = $db->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        if ($update_stmt->execute([$new_status, $target_user_id])) {
            $message = "User account status updated successfully!";
        }
    }
}

// Fetch all registered users
$stmt = $db->query("SELECT * FROM users ORDER BY user_id DESC");
$users = $stmt->fetchAll();

$page_title = "Admin - User Management";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Admin Portal: User Management</h2>
            <p class="text-muted mb-0">Monitor platform accounts, review user roles, and manage account statuses (active, pending, or suspended).</p>
        </div>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-3">All System Users</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($users as $usr): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($usr['name']); ?></td>
                                <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $usr['role'] === 'admin' ? 'secondary' : ($usr['role'] === 'company' ? 'dark' : 'success'); ?>">
                                        <?php echo ucfirst($usr['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $usr['status'] === 'active' ? 'success' : ($usr['status'] === 'suspended' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($usr['status'] ?? 'active'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                                <td>
                                    <?php if($usr['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $usr['user_id']; ?>">
                                            <?php if(($usr['status'] ?? 'active') === 'active'): ?>
                                                <input type="hidden" name="status" value="suspended">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Suspend</button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-sm btn-outline-success">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Current Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>