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
$message = '';

// Handle Internship Deletion (Removing inappropriate or invalid content)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['internship_id'])) {
    $internship_id = $_POST['internship_id'];

    $del_stmt = $db->prepare("DELETE FROM internships WHERE internship_id = ?");
    if ($del_stmt->execute([$internship_id])) {
        $message = "Internship listing removed successfully!";
    }
}

// Fetch all internships with their company names
$stmt = $db->query("
    SELECT i.*, c.company_name, af.field_name 
    FROM internships i 
    JOIN companies c ON i.company_id = c.company_id 
    LEFT JOIN academic_fields af ON i.field_id = af.field_id 
    ORDER BY i.internship_id DESC
");
$internships = $stmt->fetchAll();

$page_title = "Admin - Internship Management";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-info">Admin Portal: Internship Oversight</h2>
            <p class="text-muted mb-0">Monitor platform internship opportunities, inspect details, and remove inappropriate or invalid listings[cite: 18].</p>
        </div>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 bg-white">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-3">All Internship Postings</h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Academic Field</th>
                        <th>Location</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($internships)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No internship listings found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($internships as $post): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo htmlspecialchars($post['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($post['field_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($post['location'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($post['deadline'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this listing?');" class="d-inline">
                                        <input type="hidden" name="internship_id" value="<?php echo $post['internship_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
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