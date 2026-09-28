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

// Fixed: Querying 'student_profiles' instead of non-existent 'students' table[cite: 14, 15]
$prof_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$prof_stmt->execute([$user_id]);
$profile = $prof_stmt->fetch();
$student_id = $profile['student_id'] ?? 0;

// Fetch metrics using exact columns from schema[cite: 14, 15]
$total_applications = 0;
$pending_reviews = 0;
$accepted_offers = 0;
$rejected_count = 0;

if ($student_id) {
    $total_apps_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ?");
    $total_apps_stmt->execute([$student_id]);
    $total_applications = $total_apps_stmt->fetchColumn();

    $pending_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'Pending'");
    $pending_stmt->execute([$student_id]);
    $pending_reviews = $pending_stmt->fetchColumn();

    $accepted_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'Accepted'");
    $accepted_stmt->execute([$student_id]);
    $accepted_offers = $accepted_stmt->fetchColumn();

    $rejected_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'Rejected'");
    $rejected_stmt->execute([$student_id]);
    $rejected_count = $rejected_stmt->fetchColumn();
}

$page_title = "Student Dashboard";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0 border-start border-success border-4">
            <h2 class="fw-bold text-success">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p class="text-muted mb-0">Track your internship applications, update your profile, and discover matches tailored for your career path.</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4 h-100">
            <h6 class="text-muted small">Total Applications</h6>
            <h2 class="fw-bold text-success mb-0"><?php echo $total_applications; ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-warning border-4 h-100">
            <h6 class="text-muted small">Pending Review</h6>
            <h2 class="fw-bold text-warning mb-0"><?php echo $pending_reviews; ?></h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-primary border-4 h-100">
            <h6 class="text-muted small">Accepted Offers</h6>
            <h2 class="fw-bold text-primary mb-0"><?php echo $accepted_offers; ?></h2>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-dark mb-3">Application Status Distribution</h5>
            <div style="height: 250px; position: relative;">
                <canvas id="applicationChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-dark mb-3">Career Readiness</h5>
            <p class="text-muted small">Keep your profile updated and configure your profile skills to improve visibility.</p>
            <a href="profile.php" class="btn btn-success btn-sm w-100 mt-auto">Update Profile & CV</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('applicationChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Accepted', 'Rejected'],
            datasets: [{
                data: [<?php echo $pending_reviews; ?>, <?php echo $accepted_offers; ?>, <?php echo $rejected_count; ?>],
                backgroundColor: ['#ffc107', '#0d6efd', '#dc3545']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>

<?php include '../../includes/footer.php'; ?>