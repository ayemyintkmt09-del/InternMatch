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

// Handle Verification Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_id'], $_POST['status'])) {
    $company_id = $_POST['company_id'];
    $new_status = $_POST['status']; // 'verified' or 'pending'

    $update_stmt = $db->prepare("UPDATE companies SET verification_status = ? WHERE company_id = ?");
    if ($update_stmt->execute([$new_status, $company_id])) {
        $message = "Company verification status updated successfully!";
    }
}

// Fetch all registered companies joined with user emails
$stmt = $db->query("SELECT c.*, u.email FROM companies c JOIN users u ON c.user_id = u.user_id ORDER BY c.company_id DESC");
$companies = $stmt->fetchAll();

$page_title = "Admin - Company Verifications";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-primary">Admin Portal: Company Verifications</h2>
            <p class="text-muted mb-0">Review registered companies and manage trust and verification badges across the platform.</p>
        </div>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-3">All Registered Companies</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Industry</th>
                        <th>Location</th>
                        <th>Current Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($companies)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No companies found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($companies as $comp): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($comp['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($comp['email']); ?></td>
                                <td><?php echo htmlspecialchars($comp['industry'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($comp['location'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $comp['verification_status'] === 'verified' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($comp['verification_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="company_id" value="<?php echo $comp['company_id']; ?>">
                                        <?php if($comp['verification_status'] !== 'verified'): ?>
                                            <input type="hidden" name="status" value="verified">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Verify</button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="pending">
                                            <button type="submit" class="btn btn-sm btn-outline-warning">Unverify</button>
                                        <?php endif; ?>
                                    </form>
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