<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch summary metrics
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_internships = $db->query("SELECT COUNT(*) FROM internships")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$total_applications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// Fetch application status counts for Chart.js
$status_counts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM applications 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statuses = ['Pending', 'Shortlisted', 'Accepted', 'Rejected'];
$data_counts = [];
foreach($statuses as $s) {
    $data_counts[] = $status_counts[$s] ?? 0;
}

$page_title = "Admin Control Center";
include '../../includes/header.php';
?>

<!-- Include Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-dark">Admin Control Center</h2>
            <p class="text-muted mb-0">Platform overview, governance controls, and application analytics.</p>
        </div>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h6 class="text-muted">Total Users</h6>
            <h3 class="fw-bold text-primary mb-0"><?php echo $total_users; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h6 class="text-muted">Registered Companies</h6>
            <h3 class="fw-bold text-success mb-0"><?php echo $total_companies; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h6 class="text-muted">Active Internships</h6>
            <h3 class="fw-bold text-info mb-0"><?php echo $total_internships; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white">
            <h6 class="text-muted">Total Applications</h6>
            <h3 class="fw-bold text-warning mb-0"><?php echo $total_applications; ?></h3>
        </div>
    </div>
</div>

<!-- Analytics Chart & Governance Panel -->
<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h5 class="fw-bold text-dark mb-3">Application Status Overview</h5>
            <canvas id="applicationsChart" height="140"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-4 bg-white h-100 d-flex flex-column">
            <h5 class="fw-bold text-dark mb-3">Governance Quick Actions</h5>
            <p class="text-muted small">Manage system entities securely using administrative shortcuts.</p>
            <div class="d-grid gap-2 mt-auto">
                <a href="ad_verifications.php" class="btn btn-outline-primary btn-sm">Review Company Verifications</a>
                <a href="ad_users.php" class="btn btn-outline-secondary btn-sm">Manage User Accounts</a>
                <a href="ad_internships.php" class="btn btn-outline-dark btn-sm">Moderate Internship Listings</a>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('applicationsChart').getContext('2d');
const applicationsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'Shortlisted', 'Accepted', 'Rejected'],
        datasets: [{
            label: '# of Applications',
            data: <?php echo json_encode($data_counts); ?>,
            backgroundColor: [
                'rgba(255, 193, 7, 0.7)',
                'rgba(13, 202, 240, 0.7)',
                'rgba(25, 135, 84, 0.7)',
                'rgba(220, 53, 69, 0.7)'
            ],
            borderColor: [
                'rgb(255, 193, 7)',
                'rgb(13, 202, 240)',
                'rgb(25, 135, 84)',
                'rgb(220, 53, 69)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>