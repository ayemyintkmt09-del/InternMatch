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

$stmt = $db->prepare("SELECT company_id FROM companies WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
$company_id = $company['company_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $upd = $db->prepare("UPDATE applications SET status = ? WHERE application_id = ?");
    $upd->execute([$new_status, $app_id]);
}

$query = "SELECT a.*, i.title as internship_title, sp.*, u.name as student_name, u.email as student_email 
          FROM applications a 
          JOIN internships i ON a.internship_id = i.internship_id 
          JOIN student_profiles sp ON a.student_id = sp.student_id 
          JOIN users u ON sp.user_id = u.user_id 
          WHERE i.company_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$company_id]);
$applications = $stmt->fetchAll();

$page_title = "Manage Applicants";
include '../../includes/header.php';
?>

<h3 class="fw-bold mb-4">Student Applications</h3>
<div class="card shadow-sm border-0 p-4 bg-white">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Student</th>
                <th>Internship</th>
                <th>University / Degree</th>
                <th>CV</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($applications as $app): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br><small><?php echo htmlspecialchars($app['student_email']); ?></small></td>
                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                <td><?php echo htmlspecialchars($app['university']); ?><br><small><?php echo htmlspecialchars($app['degree']); ?></small></td>
                <td><a href="<?php echo htmlspecialchars($app['cv_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Download CV</a></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($app['status']); ?></span></td>
                <td>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                        <select name="status" class="form-select form-select-sm">
                            <option value="Pending" <?php if($app['status']=='Pending') echo 'selected';?>>Pending</option>
                            <option value="Shortlisted" <?php if($app['status']=='Shortlisted') echo 'selected';?>>Shortlisted</option>
                            <option value="Accepted" <?php if($app['status']=='Accepted') echo 'selected';?>>Accepted</option>
                            <option value="Rejected" <?php if($app['status']=='Rejected') echo 'selected';?>>Rejected</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm btn-dark">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>