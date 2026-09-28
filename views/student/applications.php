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

$stu_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$stu_stmt->execute([$user_id]);
$student_row = $stu_stmt->fetch();
$real_student_id = $student_row['student_id'] ?? 0;

$stmt = $db->prepare("
    SELECT a.*, i.title as internship_title, i.location, c.company_name 
    FROM applications a 
    JOIN internships i ON a.internship_id = i.internship_id 
    JOIN companies c ON i.company_id = c.company_id 
    WHERE a.student_id = ? 
    ORDER BY a.application_id DESC
");
$stmt->execute([$real_student_id]);
$applications = $stmt->fetchAll();

$page_title = "My Applications";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">My Internship Applications</h2>
            <p class="text-muted mb-0">Monitor the real-time review progress of all submitted applications.</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Internship Title</th>
                        <th>Location</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($applications)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No applications submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($app['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['location'] ?? 'Remote'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                                <td>
                                    <?php 
                                        $status = $app['status'];
                                        $badge = 'warning';
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