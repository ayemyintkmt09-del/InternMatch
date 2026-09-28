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

// Fetch summary counts for platform statistics
$total_students = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_companies = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$pending_companies = $db->query("SELECT COUNT(*) FROM companies WHERE verification_status = 'pending'")->fetchColumn();
$total_internships = $db->query("SELECT COUNT(*) FROM internships")->fetchColumn();
$total_applications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();

$page_title = "Admin Dashboard";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-secondary">System Administration Dashboard</h2>
            <p class="text-muted mb-0">Monitor platform activity, verify corporate accounts, and oversee system metrics.</p>
        </div>
    </div>
</div>

<!-- Statistics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-primary border-4">
            <h6 class="text-muted small">Total Students</h6>
            <h3 class="fw-bold text-primary mb-0"><?php echo $total_students; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4">
            <h6 class="text-muted small">Total Companies</h6>
            <h3 class="fw-bold text-success mb-0"><?php echo $total_companies; ?> <span class="fs-6 text-warning">(<?php echo $pending_companies; ?> pending)</span></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-info border-4">
            <h6 class="text-muted small">Active Internships</h6>
            <h3 class="fw-bold text-info mb-0"><?php echo $total_internships; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-warning border-4">
            <h6 class="text-muted small">Total Applications</h6>
            <h3 class="fw-bold text-warning mb-0"><?php echo $total_applications; ?></h3>
        </div>
    </div>
</div>

<!-- Management Navigation Cards -->
<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-4 bg-white h-100">
            <h5 class="fw-bold text-primary mb-2">Company Verifications</h5>
            <p class="text-muted small">Review registered companies and manage trust and verification badges across the platform.</p>
            <a href="admin_verifications.php" class="btn btn-outline-primary btn-sm mt-auto">Manage Companies</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-4 bg-white h-100">
            <h5 class="fw-bold text-success mb-2">User Accounts</h5>
            <p class="text-muted small">Monitor, activate, or deactivate student and company user accounts.</p>
            <a href="ad_users.php" class="btn btn-outline-success btn-sm mt-auto">Manage Users</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 p-4 bg-white h-100">
            <h5 class="fw-bold text-info mb-2">Internships & Master Data</h5>
            <p class="text-muted small">Oversee internship listings, academic fields, and required skill categories.</p>
            <a href="ad_internships.php" class="btn btn-outline-info btn-sm mt-auto">Manage Listings</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>