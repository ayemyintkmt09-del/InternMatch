<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/mail.php'; // PHPMailer helper wrapper

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'company') {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

$comp_stmt = $db->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$comp_stmt->execute([$user_id]);
$company = $comp_stmt->fetch();
$company_id = $company['company_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $app_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $interview_date = $_POST['interview_date'] ?? null;
    $interview_notes = $_POST['interview_notes'] ?? null;

    $allowed_statuses = ['Pending', 'Under Review', 'Shortlisted', 'Accepted', 'Rejected', 'Withdrawn'];
    if (in_array($new_status, $allowed_statuses)) {
        $upd = $db->prepare("UPDATE applications SET status = ?, interview_date = ?, interview_notes = ? WHERE application_id = ?");
        $upd->execute([$new_status, !empty($interview_date) ? $interview_date : null, !empty($interview_notes) ? $interview_notes : null, $app_id]);

        // Fetch user, internship, and email info for notification & mail dispatch
        $fetch_info = $db->prepare("
            SELECT u.user_id, u.email, u.name as student_name, i.title as internship_title, c.company_name 
            FROM applications a 
            JOIN student_profiles sp ON a.student_id = sp.student_id 
            JOIN users u ON sp.user_id = u.user_id 
            JOIN internships i ON a.internship_id = i.internship_id 
            JOIN companies c ON i.company_id = c.company_id 
            WHERE a.application_id = ?
        ");
        $fetch_info->execute([$app_id]);
        $app_data = $fetch_info->fetch();

        if ($app_data) {
            $student_user_id = $app_data['user_id'];
            $message = "Your application for " . $app_data['internship_title'] . " at " . $app_data['company_name'] . " has been updated to: " . $new_status;
            if (!empty($interview_date)) {
                $message .= " | Interview Scheduled: " . date('M d, Y h:i A', strtotime($interview_date));
            }

            // In-App Notification entry
            $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, CURRENT_TIMESTAMP())");
            $notif_stmt->execute([$student_user_id, $message]);

            // Dispatch Email via PHPMailer
            if (!empty($app_data['email'])) {
                $subject = "Application Update: " . $app_data['internship_title'];
                $htmlContent = "<p>Hello <b>" . htmlspecialchars($app_data['student_name']) . "</b>,</p>
                                <p>Your application status for <b>" . htmlspecialchars($app_data['internship_title']) . "</b> at <b>" . htmlspecialchars($app_data['company_name']) . "</b> has been updated to: <b>" . htmlspecialchars($new_status) . "</b>.</p>";
                if (!empty($interview_date)) {
                    $htmlContent .= "<p><b>Interview Scheduled:</b> " . date('M d, Y h:i A', strtotime($interview_date)) . "</p>";
                }
                sendSystemEmail($app_data['email'], $app_data['student_name'], $subject, $htmlContent);
            }
        }
    }
}

$applications = [];
if ($company_id) {
    $query = "
        SELECT a.*, i.title as internship_title, u.name as student_name, u.email as student_email, sp.university, sp.degree 
        FROM applications a 
        JOIN internships i ON a.internship_id = i.internship_id 
        JOIN student_profiles sp ON a.student_id = sp.student_id 
        JOIN users u ON sp.user_id = u.user_id 
        WHERE i.company_id = ? 
        ORDER BY a.application_id DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([$company_id]);
    $applications = $stmt->fetchAll();
}

$page_title = "Manage Applicants";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-primary">Candidate Applications</h2>
            <p class="text-muted mb-0">Review student submissions, manage application workflows, and trigger automated alerts.</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Candidate</th>
                        <th>Position</th>
                        <th>University / Degree</th>
                        <th>CV</th>
                        <th>Status & Interview</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($applications)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No applications received yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['student_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($app['student_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($app['internship_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['university']); ?><br><small class="text-muted"><?php echo htmlspecialchars($app['degree']); ?></small></td>
                                <td>
                                    <?php if(!empty($app['cv_path'])): ?>
                                        <a href="/InternMatch/<?php echo htmlspecialchars($app['cv_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Download CV</a>
                                    <?php else: ?>
                                        <span class="text-muted small">No CV Uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $status = $app['status'];
                                        $badge = 'secondary';
                                        if($status === 'Pending') $badge = 'warning';
                                        if($status === 'Shortlisted') $badge = 'info';
                                        if($status === 'Accepted') $badge = 'primary';
                                        if($status === 'Rejected') $badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?> mb-1"><?php echo htmlspecialchars($status); ?></span>
                                    <?php if(!empty($app['interview_date'])): ?>
                                        <br><small class="text-success fw-bold">Interview: <?php echo date('M d, Y H:i', strtotime($app['interview_date'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex flex-column gap-2">
                                        <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                        <div class="d-flex gap-1">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                                <option value="Pending" <?php if($status==='Pending') echo 'selected';?>>Pending</option>
                                                <option value="Shortlisted" <?php if($status==='Shortlisted') echo 'selected';?>>Shortlisted</option>
                                                <option value="Accepted" <?php if($status==='Accepted') echo 'selected';?>>Accepted</option>
                                                <option value="Rejected" <?php if($status==='Rejected') echo 'selected';?>>Rejected</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-outline-primary">Save</button>
                                        </div>
                                        <input type="datetime-local" name="interview_date" class="form-control form-control-sm" value="<?php echo !empty($app['interview_date']) ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : ''; ?>">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>