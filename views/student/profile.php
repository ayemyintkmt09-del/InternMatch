<?php
session_start();
require_once '../../config/database.php';
require_once '../../classes/StudentProfile.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$profileModel = new StudentProfile($db);

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'university' => $_POST['university'],
        'degree' => $_POST['degree'],
        'academic_year' => $_POST['academic_year'],
        'bio' => $_POST['bio'],
        'location' => $_POST['location'],
        'availability' => $_POST['availability'],
        'career_interest' => $_POST['career_interest']
    ];

    if ($profileModel->updateProfile($user_id, $data)) {
        $message = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_file'])) {
    $targetDir = "../../uploads/cvs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $file = $_FILES['cv_file'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($fileExtension === 'pdf') {
        $newFileName = "cv_" . $user_id . "_" . time() . ".pdf";
        $targetFilePath = $targetDir . $newFileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $message = "CV uploaded successfully!";
        } else {
            $error = "Error uploading CV file.";
        }
    } else {
        $error = "Only PDF files are allowed for CV uploads.";
    }
}

$profile = $profileModel->getProfileByUserId($user_id);

$page_title = "Manage Profile & CV";
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 p-4 bg-white">
            <h3 class="fw-bold text-success mb-3">Student Profile & CV Management</h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success py-2"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">University Name</label>
                        <input type="text" name="university" class="form-control" value="<?php echo htmlspecialchars($profile['university'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Degree / Program</label>
                        <input type="text" name="degree" class="form-control" value="<?php echo htmlspecialchars($profile['degree'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($profile['academic_year'] ?? ''); ?>" placeholder="e.g. Year 3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Preferred Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Availability</label>
                        <input type="text" name="availability" class="form-control" value="<?php echo htmlspecialchars($profile['availability'] ?? ''); ?>" placeholder="e.g. Full-time / Part-time" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Career Interests</label>
                        <input type="text" name="career_interest" class="form-control" value="<?php echo htmlspecialchars($profile['career_interest'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Biography / Summary</label>
                    <textarea name="bio" class="form-control" rows="3"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile" class="btn btn-success w-100 py-2">Save Profile Details</button>
            </form>

            <hr class="my-4">

            <h5 class="fw-bold text-dark mb-3">CV / Resume Upload</h5>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label text-muted small">Upload your CV in PDF format (Max size: 5MB)</label>
                    <input type="file" name="cv_file" class="form-control" accept=".pdf" required>
                </div>
                <button type="submit" class="btn btn-outline-success w-100 py-2">Upload / Update CV</button>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>