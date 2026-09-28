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
$message = '';

// Fetch or create company record
$stmt = $db->prepare("SELECT * FROM companies WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    $stmt = $db->prepare("INSERT INTO companies (user_id, company_name, verification_status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, $_SESSION['name']]);
    $stmt = $db->prepare("SELECT * FROM companies WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $description = $_POST['description'];
    $industry = $_POST['industry'];
    $location = $_POST['location'];
    $website = $_POST['website'];

    $update = $db->prepare("UPDATE companies SET company_name = ?, description = ?, industry = ?, location = ?, website = ? WHERE user_id = ?");
    if ($update->execute([$company_name, $description, $industry, $location, $website, $user_id])) {
        $message = "Company profile updated successfully!";
        $stmt = $db->prepare("SELECT * FROM companies WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $company = $stmt->fetch();
    }
}

$page_title = "Company Profile";
include '../../includes/header.php';
?>

<div class="card shadow-sm border-0 p-4 bg-white col-md-8 mx-auto">
    <h3 class="fw-bold mb-3">Company Profile & Verification Status</h3>
    <p>Verification Status: <span class="badge bg-<?php echo $company['verification_status'] === 'verified' ? 'success' : 'warning'; ?>"><?php echo ucfirst($company['verification_status']); ?></span></p>
    <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Industry</label>
            <input type="text" name="industry" class="form-control" value="<?php echo htmlspecialchars($company['industry'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Website</label>
            <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-dark w-100">Save Profile</button>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>