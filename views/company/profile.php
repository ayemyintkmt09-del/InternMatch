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
$success = "";
$error = "";

// Handle profile update & document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name']);
    $description = trim($_POST['description']);
    $industry = trim($_POST['industry']);
    $location = trim($_POST['location']);
    $website = trim($_POST['website']);

    // Handle file upload for verification document
    $doc_path = null;
    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['verification_doc']['tmp_name'];
        $file_name = $_FILES['verification_doc']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg'];
        if (in_array($file_ext, $allowed_exts)) {
            $upload_dir = '../../uploads/verifications/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = 'ver_comp_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $doc_path = 'uploads/verifications/' . $new_filename;
            }
        } else {
            $error = "Invalid document format. Allowed: PDF, JPG, PNG.";
        }
    }

    if (empty($error)) {
        if ($doc_path) {
            $stmt = $db->prepare("UPDATE companies SET company_name = ?, description = ?, industry = ?, location = ?, website = ?, verification_doc_path = ?, verification_status = 'pending' WHERE user_id = ?");
            $stmt->execute([$company_name, $description, $industry, $location, $website, $doc_path, $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE companies SET company_name = ?, description = ?, industry = ?, location = ?, website = ? WHERE user_id = ?");
            $stmt->execute([$company_name, $description, $industry, $location, $website, $user_id]);
        }
        $success = "Company profile updated successfully!";
    }
}

// Fetch current company profile
$stmt = $db->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

$page_title = "Company Profile";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-dark">Company Profile & Verification</h2>
            <p class="text-muted mb-0">Manage your business profile details and upload documents to get verified by administrators.</p>
        </div>
    </div>
</div>

<?php if($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label fw-bold">Verification Status:</label>
            <?php 
                $v_status = $company['verification_status'] ?? 'pending';
                $badge = ($v_status === 'verified') ? 'success' : (($v_status === 'rejected') ? 'danger' : 'warning');
            ?>
            <span class="badge bg-<?php echo $badge; ?> text-uppercase px-3 py-2"><?php echo $v_status; ?></span>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Industry</label>
                    <input type="text" name="industry" class="form-control" value="<?php echo htmlspecialchars($company['industry'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Website URL</label>
                <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Company Description</label>
                <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-4">
                <label class="form-label">Upload Verification Document (Business License / Registration PDF or Image)</label>
                <input type="file" name="verification_doc" class="form-control">
                <?php if(!empty($company['verification_doc_path'])): ?>
                    <small class="text-muted d-mt-2">Current Document: <a href="/InternMatch/<?php echo $company['verification_doc_path']; ?>" target="_blank">View File</a></small>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-dark w-100">Save Changes</button>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>