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

// Fetch student_id
$s_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$s_stmt->execute([$user_id]);
$student = $s_stmt->fetch();
$student_id = $student['student_id'] ?? 0;

// Algorithm: Calculate skill match percentage
function calculateSkillMatch($db, $student_id, $internship_id) {
    $stmt = $db->prepare("SELECT skill_name FROM student_skills WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student_skills = array_column($stmt->fetchAll(), 'skill_name');

    if (empty($student_skills)) {
        $stmt_p = $db->prepare("SELECT skills FROM student_profiles WHERE student_id = ?");
        $stmt_p->execute([$student_id]);
        $profile = $stmt_p->fetch();
        if (!empty($profile['skills'])) {
            $student_skills = array_map('trim', explode(',', $profile['skills']));
        }
    }

    if (empty($student_skills)) return 0;

    $stmt2 = $db->prepare("SELECT title, description FROM internships WHERE internship_id = ?");
    $stmt2->execute([$internship_id]);
    $internship = $stmt2->fetch();
    
    if (!$internship) return 0;

    $job_text = strtolower($internship['title'] . ' ' . $internship['description']);
    $matched_count = 0;

    foreach ($student_skills as $skill) {
        if (!empty($skill) && strpos($job_text, strtolower(trim($skill))) !== false) {
            $matched_count++;
        }
    }

    $percentage = round(($matched_count / count($student_skills)) * 100);
    return min($percentage, 100);
}

$keyword = $_GET['keyword'] ?? '';
$location = $_GET['location'] ?? '';

$query = "SELECT i.*, c.company_name, c.location as comp_location 
          FROM internships i 
          JOIN companies c ON i.company_id = c.company_id 
          WHERE (i.title LIKE ? OR i.description LIKE ?) 
          AND (i.location LIKE ? OR c.location LIKE ?)
          ORDER BY i.internship_id DESC";

$stmt = $db->prepare($query);
$stmt->execute(["%$keyword%", "%$keyword%", "%$location%", "%$location%"]);
$internships = $stmt->fetchAll();

$page_title = "Search Internships";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-primary">Find Internships</h2>
            <p class="text-muted mb-0">Search through verified company listings and view your automated skill match score.</p>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-white mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <input type="text" name="keyword" class="form-control" placeholder="Search title or keywords..." value="<?php echo htmlspecialchars($keyword); ?>">
            </div>
            <div class="col-md-5">
                <input type="text" name="location" class="form-control" placeholder="Filter by location..." value="<?php echo htmlspecialchars($location); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php if(empty($internships)): ?>
        <div class="col-12"><div class="alert alert-light text-center">No matching internships found.</div></div>
    <?php else: ?>
        <?php foreach($internships as $post): ?>
            <?php 
                $match_score = calculateSkillMatch($db, $student_id, $post['internship_id']); 
                $badge_color = $match_score >= 70 ? 'success' : ($match_score >= 40 ? 'warning' : 'secondary');
            ?>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100 bg-white">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h4 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($post['title']); ?></h4>
                            <span class="badge bg-<?php echo $badge_color; ?>"><?php echo $match_score; ?>% Match</span>
                        </div>
                        <h6 class="text-primary mb-3"><?php echo htmlspecialchars($post['company_name']); ?></h6>
                        <p class="text-muted small mb-2"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($post['location']); ?></p>
                        <p class="text-secondary small flex-grow-1"><?php echo nl2br(htmlspecialchars(substr($post['description'], 0, 150))); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">Deadline: <?php echo htmlspecialchars($post['deadline']); ?></small>
                            <a href="apply.php?id=<?php echo $post['internship_id']; ?>" class="btn btn-sm btn-dark">View & Apply</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>