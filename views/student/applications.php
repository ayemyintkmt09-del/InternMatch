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

// Fetch student_id
$s_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$s_stmt->execute([$user_id]);
$student = $s_stmt->fetch();
$student_id = $student['student_id'] ?? 0;

$applications = [];
if ($student_id) {
    $query = "
        SELECT a.*, i.title as internship_title, i.location, c.company_name 
        FROM applications a 
        JOIN internships i ON a.internship_id = i.internship_id 
        JOIN companies c ON i.company_id = c.company_id 
        WHERE a.student_id = ? 
        ORDER BY a.application_id DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $applications = $stmt->fetchAll();
}

$page_title = "My Applications";
include '../../includes/header.php';
?>

<!-- Print-specific styles to make exported/printed report look clean -->
<style>
@media print {
    body { background: white !important; }
    .navbar, .btn, footer, .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .table { width: 100% !important; border-collapse: collapse !important; }
    .table th, .table td { border: 1px solid #dee2e6 !important; padding: 8px !important; }
}
</style>

<div class="row mb-4 no-print">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold text-primary">My Internship Applications</h2>
            <p class="text-muted mb-0">Track your submission statuses, interview schedules, and export reports.</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-dark"><i class="bi bi-file-earmark-pdf"></i> Export / Print Report</button>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="d-none d-print-block mb-4">
            <h3 class="fw-bold">InternMatch - Official Application Report</h3>
            <p class="text-muted small">Generated on: <?php echo date('F d, Y'); ?></p>
            <hr>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company & Position</th>
                        <th>Location</th>
                        <th>Applied Date</th>
                        <th>Interview Schedule</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($applications)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">You have not applied to any internships yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['internship_title']); ?></strong><br>
                                    <span class="text-primary small"><?php echo htmlspecialchars($app['company_name']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($app['location']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['created_at'] ?? 'now')); ?></td>
                                <td>
                                    <?php if(!empty($app['interview_date'])): ?>
                                        <span class="text-success fw-bold"><?php echo date('M d, Y H:i', strtotime($app['interview_date'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not Scheduled</span>
                                    <?php endif; ?>
                                </td>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>