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

// Get student's skills via student_skills and skills table
$prof_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$prof_stmt->execute([$user_id]);
$profile = $prof_stmt->fetch();
$student_id = $profile['student_id'] ?? 0;

$student_skills_array = [];
if ($student_id) {
    $skills_stmt = $db->prepare("
        SELECT s.skill_name FROM student_skills ss 
        JOIN skills s ON ss.skill_id = s.skill_id 
        WHERE ss.student_id = ?
    ");
    $skills_stmt->execute([$student_id]);
    $student_skills_array = array_map('strtolower', array_column($skills_stmt->fetchAll(), 'skill_name'));
}

$keyword = $_GET['keyword'] ?? '';
$location = $_GET['location'] ?? '';

$query = "
    SELECT i.*, c.company_name, c.industry, af.field_name 
    FROM internships i 
    JOIN companies c ON i.company_id = c.company_id 
    LEFT JOIN academic_fields af ON i.field_id = af.field_id 
    WHERE 1=1
";
$params = [];

if (!empty($keyword)) {
    $query .= " AND (i.title LIKE ? OR i.description LIKE ? OR c.company_name LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($location)) {
    $query .= " AND i.location LIKE ?";
    $params[] = "%$location%";
}

$query .= " ORDER BY i.internship_id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$internships = $stmt->fetchAll();

$page_title = "Search Internships";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Smart Internship Search</h2>
            <p class="text-muted mb-0">Browse listings matching your academic profile and skill inventory.</p>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm p-4 bg-white mb-4">
    <form method="GET" action="" class="row g-3">
        <div class="col-md-5">
            <input type="text" name="keyword" class="form-control" placeholder="Search by title, keyword, or company..." value="<?php echo htmlspecialchars($keyword); ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="location" class="form-control" placeholder="Filter by location..." value="<?php echo htmlspecialchars($location); ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-success w-100">Search Listings</button>
        </div>
    </form>
</div>

<div class="row">
    <?php if(empty($internships)): ?>
        <div class="col-md-12">
            <div class="card border-0 shadow-sm p-4 bg-white text-center text-muted">No listings found.</div>
        </div>
    <?php else: ?>
        <?php foreach($internships as $post): ?>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm h-100 bg-white p-4">
                    <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($post['title']); ?></h4>
                    <h6 class="text-muted mb-2"><?php echo htmlspecialchars($post['company_name']); ?> • <span class="small"><?php echo htmlspecialchars($post['location'] ?? 'Remote'); ?></span></h6>
                    <p class="text-secondary small mb-3"><?php echo substr(htmlspecialchars($post['description']), 0, 120); ?>...</p>
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Deadline: <?php echo htmlspecialchars($post['deadline'] ?? 'Open'); ?></span>
                        <a href="apply.php?id=<?php echo $post['internship_id']; ?>" class="btn btn-sm btn-outline-success">View & Apply</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>