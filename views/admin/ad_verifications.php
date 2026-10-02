<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (isset($_GET['action']) && isset($_GET['company_id'])) {
    $action = $_GET['action'];
    $company_id = $_GET['company_id'];
    
    $new_status = ($action === 'approve') ? 'verified' : (($action === 'reject') ? 'rejected' : 'pending');
    
    $stmt = $db->prepare("UPDATE companies SET verification_status = ? WHERE company_id = ?");
    $stmt->execute([$new_status, $company_id]);

    $usr_q = $db->prepare("SELECT user_id, company_name FROM companies WHERE company_id = ?");
    $usr_q->execute([$company_id]);
    $comp_info = $usr_q->fetch();
    if ($comp_info) {
        $msg = "Your company verification status has been updated to: " . ucfirst($new_status);
        $notif = $db->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
        $notif->execute([$comp_info['user_id'], $msg]);
    }

    header("Location: ad_verifications.php");
    exit();
}

$stmt = $db->prepare("SELECT c.*, u.email FROM companies c JOIN users u ON c.user_id = u.user_id ORDER BY c.company_id DESC");
$stmt->execute();
$companies = $stmt->fetchAll();

$page_title = "Company Verifications";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-secondary">Company Verifications</h2>
            <p class="text-muted mb-0">Review submitted business verification documents and manage platform access permissions[cite: 27].</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Industry / Location</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($companies)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No companies registered yet[cite: 27].</td></tr>
                    <?php else: ?>
                        <?php foreach($companies as $comp): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($comp['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($comp['email']); ?></td>
                                <td><?php echo htmlspecialchars($comp['industry'] ?? 'N/A'); ?><br><small class="text-muted"><?php echo htmlspecialchars($comp['location'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <?php if(!empty($comp['verification_doc_path'])): ?>
                                        <a href="/InternMatch/<?php echo htmlspecialchars($comp['verification_doc_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">View Document</a>
                                    <?php else: ?>
                                        <span class="text-muted small">No Document</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $v = $comp['verification_status'];
                                        $b = ($v === 'verified') ? 'success' : (($v === 'rejected') ? 'danger' : 'warning');
                                    ?>
                                    <span class="badge bg-<?php echo $b; ?> text-uppercase"><?php echo $v; ?></span>
                                </td>
                                <td>
                                    <a href="ad_verifications.php?action=approve&company_id=<?php echo $comp['company_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="ad_verifications.php?action=reject&company_id=<?php echo $comp['company_id']; ?>" class="btn btn-sm btn-outline-danger">Reject</a>
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