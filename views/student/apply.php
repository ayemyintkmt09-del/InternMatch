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
$internship_id = $_GET['id'] ?? null;
$message = '';
$error = '';

if (!$internship_id) {
    header("Location: search.php");
    exit();
}

// Get student_id from student_profiles
$stu_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$stu_stmt->execute([$user_id]);
$student_row = $stu_stmt->fetch();
$real_student_id = $student_row['student_id'] ?? null;

// Handle application posting and file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_letter = $_POST['cover_letter'] ?? '';
    $cv_path = '';

    // Check if already applied
    $check = $db->prepare("SELECT * FROM applications WHERE student_id = ? AND internship_id = ?");
    $check->execute([$real_student_id, $internship_id]);
    
    if ($check->rowCount() > 0) {
        $error = "You have already applied for this internship position.";
    } else {
        // Handle CV File Upload
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['cv_file']['tmp_name'];
            $file_name = $_FILES['cv_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            if (in_array($file_ext, $allowed_extensions)) {
                $upload_dir = '../../uploads/cvs/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_file_name = 'cv_' . $real_student_id . '_' . time() . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $destination)) {
                    // Store relative path from project root
                    $cv_path = 'uploads/cvs/' . $new_file_name;
                } else {
                    $error = "Failed to move uploaded CV file.";
                }
            } else {
                $error = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
            }
        } else {
            $error = "Please upload your CV document.";
        }

        // Insert application if no errors
        if (empty($error)) {
            $ins = $db->prepare("INSERT INTO applications (student_id, internship_id, cv_path, message, status, application_date) VALUES (?, ?, ?, ?, 'Pending', CURRENT_TIMESTAMP())");
            if ($ins->execute([$real_student_id, $internship_id, $cv_path, $cover_letter])) {
                $message = "Application submitted successfully!";
            } else {
                $error = "Failed to submit application to database. Please try again.";
            }
        }
    }
}

// Fetch internship details
$stmt = $db->prepare("
    SELECT i.*, c.company_name, c.industry, c.location as comp_location 
    FROM internships i 
    JOIN companies c ON i.company_id = c.company_id 
    WHERE i.internship_id = ?
");
$stmt->execute([$internship_id]);
$internship = $stmt->fetch();

if (!$internship) {
    header("Location: search.php");
    exit();
}

$page_title = "Apply for Internship";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Application Portal</h2>
            <p class="text-muted mb-0">Review job details and submit your application to the hiring organization.</p>
        </div>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm p-4 bg-white mb-4">
            <h3 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($internship['title']); ?></h3>
            <h5 class="text-muted mb-3"><?php echo htmlspecialchars($internship['company_name']); ?></h5>
            <hr>
            <h6 class="fw-bold text-success">Job Description</h6>
            <p class="text-secondary"><?php echo nl2br(htmlspecialchars($internship['description'])); ?></p>
            
            <div class="mt-3 text-muted small">
                <span><strong>Location:</strong> <?php echo htmlspecialchars($internship['location'] ?? 'Remote'); ?></span> | 
                <span><strong>Deadline:</strong> <?php echo htmlspecialchars($internship['deadline'] ?? 'Open'); ?></span>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm p-4 bg-white">
            <h4 class="fw-bold text-dark mb-3">Submit Your Application</h4>
            <!-- Added enctype to support file uploads -->
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Upload CV / Resume (PDF, DOC, DOCX)</label>
                    <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cover Note / Remarks for Employer</label>
                    <textarea name="cover_letter" rows="5" class="form-control" placeholder="Introduce yourself and explain why you're a great fit..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success w-100">Confirm & Submit Application</button>
            </form>
            <div class="mt-3 text-center">
                <a href="search.php" class="text-muted small text-decoration-none">← Back to Search Results</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>