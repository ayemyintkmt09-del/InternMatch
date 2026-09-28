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
$error = '';

$comp_stmt = $db->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$comp_stmt->execute([$user_id]);
$company = $comp_stmt->fetch();
$company_id = $company['company_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    if (!empty($title) && !empty($description) && $company_id) {
        $stmt = $db->prepare("INSERT INTO internships (company_id, title, description, location, deadline) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$company_id, $title, $description, $location, $deadline])) {
            $message = "Internship posted successfully!";
        } else {
            $error = "Failed to post internship.";
        }
    } else {
        $error = "Please fill in all mandatory fields.";
    }
}

$page_title = "Post Internship";
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 p-4 bg-white">
            <h3 class="fw-bold text-primary mb-3">Create Internship Listing</h3>

            <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Internship Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Junior Software Developer Intern" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="e.g. Yangon / Remote" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Application Deadline</label>
                        <input type="date" name="deadline" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Job Description & Requirements</label>
                    <textarea name="description" rows="6" class="form-control" placeholder="Describe roles, expectations, and technical stack..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Publish Listing</button>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>