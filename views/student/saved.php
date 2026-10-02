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

// Get student_id
$prof_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$prof_stmt->execute([$user_id]);
$profile = $prof_stmt->fetch();
$student_id = $profile['student_id'] ?? 0;

// Handle removal
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['saved_id'])) {
    $saved_id = $_GET['saved_id'];
    $del = $db->prepare("DELETE FROM saved_internships WHERE saved_id = ? AND student_id = ?");
    $del->execute([$saved_id, $student_id]);
    header("Location: saved.php");
    exit();
}

// Fetch saved internships
$saved_jobs = [];
if ($student_id) {
    $query = "
        SELECT s.saved_id, i.*, c.company_name 
        FROM saved_internships s 
        JOIN internships i ON s.internship_id = i.internship_id 
        JOIN companies c ON i.company_id = c.company_id 
        WHERE s.student_id = ? 
        ORDER BY s.saved_at DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$student_id]);
    $saved_jobs = $stmt->fetchAll();
}

$page_title = "Saved Internships";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Saved Opportunities</h2>
            <p class="text-muted mb-0">Quickly access and review the internship positions you have bookmarked.</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Position</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Deadline</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($saved_jobs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No bookmarked internships found.</td></tr>
                    <?php else: ?>
                        <?php foreach($saved_jobs as $job): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($job['location'] ?? 'Remote'); ?></td>
                                <td><?php echo htmlspecialchars($job['deadline'] ?? 'Open'); ?></td>
                                <td>
                                    <a href="apply.php?id=<?php echo $job['internship_id']; ?>" class="btn btn-sm btn-success">Apply Now</a>
                                    <a href="saved.php?action=remove&saved_id=<?php echo $job['saved_id']; ?>" class="btn btn-sm btn-outline-danger">Remove</a>
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