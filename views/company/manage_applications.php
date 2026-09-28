<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$comp_stmt = $db->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$comp_stmt->execute([$user_id]);
$company = $comp_stmt->fetch();
$company_id = $company['company_id'] ?? 0;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['status'];

    $allowed_statuses = ['Pending', 'Under Review', 'Shortlisted', 'Accepted', 'Rejected', 'Withdrawn'];
    if (in_array($new_status, $allowed_statuses)) {
        $upd = $db->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
        $upd->execute([$new_status, $app_id]);
    }
}

// Fetch applicants for this company's postings
$applications = [];
if ($company_id) {
    $query = "
        SELECT a.*, i.title as internship_title, u.name as student_name, u.email as student_email, sp.university, sp.degree 
        FROM applications a 
        JOIN internships i ON a.internship_id = i.internship_id 
        JOIN student_profiles sp ON a.student_id = sp.student_id 
        JOIN users u ON sp.user_id = u.user_id 
        WHERE i.company_id = ? 
        ORDER BY a.application_id DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$company_id]);
    $applications = $stmt->fetchAll();
}

$page_title = "Manage Applicants";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-primary">Candidate Applications</h2>
            <p class="text-muted mb-0">Review student submissions, download CVs, and update application statuses.</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Candidate</th>
                        <th>Position</th>
                        <th>University / Degree</th>
                        <th>CV</th>
                        <th>Cover Note</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($applications)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No applications received yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($app['student_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['university']); ?><br><small class="text-muted"><?php echo htmlspecialchars($app['degree']); ?></small></td>
                                <td>
                                    <?php if(!empty($app['cv_path'])): ?>
                                        <!-- Fixed path mapping to ensure correct file retrieval from project root -->
                                        <a href="/InternMatch/<?php echo htmlspecialchars($app['cv_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Download CV</a>                                    <?php else: ?>
                                        <span class="text-muted small">No CV Uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($app['message'] ?? 'No cover note provided.'); ?></small></td>
                                <td>
                                    <?php 
                                        $status = $app['status'];
                                        $badge = 'secondary';
                                        if($status === 'Pending') $badge = 'warning';
                                        if($status === 'Shortlisted') $badge = 'info';
                                        if($status === 'Accepted') $badge = 'primary';
                                        if($status === 'Rejected') $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                            <option value="Pending" <?php if($status==='Pending') echo 'selected';?>>Pending</option>
                                            <option value="Shortlisted" <?php if($status==='Shortlisted') echo 'selected';?>>Shortlisted</option>
                                            <option value="Accepted" <?php if($status==='Accepted') echo 'selected';?>>Accepted</option>
                                            <option value="Rejected" <?php if($status==='Rejected') echo 'selected';?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-primary">Save</button>
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