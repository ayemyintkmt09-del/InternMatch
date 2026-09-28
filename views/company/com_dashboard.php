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

// Get company_id
$comp_stmt = $db->prepare("SELECT company_id, company_name FROM companies WHERE user_id = ?");
$comp_stmt->execute([$user_id]);
$company = $comp_stmt->fetch();
$company_id = $company['company_id'] ?? 0;

$total_postings = 0;
$total_applicants = 0;

if ($company_id) {
    $p_stmt = $db->prepare("SELECT COUNT(*) FROM internships WHERE company_id = ?");
    $p_stmt->execute([$company_id]);
    $total_postings = $p_stmt->fetchColumn();

    $a_stmt = $db->prepare("
        SELECT COUNT(*) FROM applications a 
        JOIN internships i ON a.internship_id = i.internship_id 
        WHERE i.company_id = ?
    ");
    $a_stmt->execute([$company_id]);
    $total_applicants = $a_stmt->fetchColumn();
}

$page_title = "Company Dashboard";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0 border-start border-primary border-4">
            <h2 class="fw-bold text-primary">Welcome, <?php echo htmlspecialchars($company['company_name'] ?? 'Employer'); ?>!</h2>
            <p class="text-muted mb-0">Manage your active internship openings and review incoming candidate portfolios.</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-primary border-4 h-100">
            <h6 class="text-muted small">Active Internship Postings</h6>
            <h2 class="fw-bold text-primary mb-0"><?php echo $total_postings; ?></h2>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4 h-100">
            <h6 class="text-muted small">Total Candidates Applied</h6>
            <h2 class="fw-bold text-success mb-0"><?php echo $total_applicants; ?></h2>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm p-4 bg-white d-flex flex-row justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-1">Looking for top talent?</h5>
                <p class="text-muted small mb-0">Post new internship opportunities instantly to attract qualified students.</p>
            </div>
            <a href="post_internship.php" class="btn btn-primary">Post New Internship</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>