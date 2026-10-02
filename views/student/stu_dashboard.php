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

// Get student_id[cite: 12]
$prof_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$prof_stmt->execute([$user_id]);
$profile = $prof_stmt->fetch();
$student_id = $profile['student_id'] ?? 0;

// Fetch application metrics counts[cite: 12]
$metrics = [
    'total' => 0,
    'pending' => 0,
    'shortlisted' => 0,
    'accepted' => 0,
    'rejected' => 0
];
$saved_count = 0;
$internships = [];

if ($student_id) {
    // Applications counts by status[cite: 12]
    $cnt_stmt = $db->prepare("SELECT status, COUNT(*) as count FROM applications WHERE student_id = ? GROUP BY status");
    $cnt_stmt->execute([$student_id]);
    while ($row = $cnt_stmt->fetch()) {
        $metrics['total'] += $row['count'];
        if (isset($metrics[strtolower($row['status'])])) {
            $metrics[strtolower($row['status'])] = $row['count'];
        }
    }

    // Saved internships count[cite: 12]
    $sav_stmt = $db->prepare("SELECT COUNT(*) FROM saved_internships WHERE student_id = ?");
    $sav_stmt->execute([$student_id]);
    $saved_count = $sav_stmt->fetchColumn();

    // Fetch internships with Smart Skill-Based Match Score calculation
    $match_query = "
        SELECT 
            i.*,
            c.company_name,
            c.industry,
            COALESCE(
                (SUM(CASE WHEN ss.student_id IS NOT NULL THEN 1 ELSE 0 END) / 
                NULLIF((SELECT COUNT(*) FROM internship_skills WHERE internship_id = i.internship_id), 0)) * 100, 
            0) AS match_score
        FROM internships i
        JOIN companies c ON i.company_id = c.company_id
        LEFT JOIN internship_skills ins ON i.internship_id = ins.internship_id
        LEFT JOIN student_skills ss ON ins.skill_id = ss.skill_id AND ss.student_id = ?
        GROUP BY i.internship_id
        ORDER BY match_score DESC
        LIMIT 6
    ";
    $int_stmt = $db->prepare($match_query);
    $int_stmt->execute([$student_id]);
    $internships = $int_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Student Dashboard";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Welcome Back, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>!</h2>
            <p class="text-muted mb-0">Here is an analytical overview of your internship search progress and application tracking[cite: 12].</p>
        </div>
    </div>
</div>

<!-- Metrics Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white text-center">
            <h6 class="text-muted mb-1">Total Applications</h6>
            <h3 class="fw-bold text-success mb-0"><?php echo $metrics['total']; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white text-center">
            <h6 class="text-muted mb-1">Shortlisted / Interviews</h6>
            <h3 class="fw-bold text-info mb-0"><?php echo $metrics['shortlisted']; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white text-center">
            <h6 class="text-muted mb-1">Accepted Offers</h6>
            <h3 class="fw-bold text-primary mb-0"><?php echo $metrics['accepted']; ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white text-center">
            <h6 class="text-muted mb-1">Saved Opportunities</h6>
            <h3 class="fw-bold text-secondary mb-0"><?php echo $saved_count; ?></h3>
        </div>
    </div>
</div>

<!-- Quick Navigation Actions -->
<div class="row mb-4">
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h4 class="fw-bold text-dark mb-3">Explore & Search</h4>
            <p class="text-muted">Find matching internships based on your university major, computer science background, and technical skills[cite: 12].</p>
            <a href="search.php" class="btn btn-success mt-auto w-100">Browse Listings</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-4 bg-white h-100">
            <h4 class="fw-bold text-dark mb-3">Manage Portfolio & CV</h4>
            <p class="text-muted">Keep your academic standing, skill inventory, and downloadable resume up to date for hiring managers[cite: 12].</p>
            <a href="profile.php" class="btn btn-outline-success mt-auto w-100">Update Profile</a>
        </div>
    </div>
</div>

<!-- Smart Skill-Based Recommendations Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark mb-0">Top Recommended Internships For You</h4>
            <a href="search.php" class="text-success text-decoration-none fw-semibold">View All &rarr;</a>
        </div>
        <div class="row">
            <?php if (empty($internships)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-4 text-center bg-white">
                        <p class="text-muted mb-0">No internships available yet. Add skills to your profile to improve matching accuracy!</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($internships as $row): ?>
                    <?php 
                        $score = round($row['match_score']);
                        $badgeColor = $score >= 75 ? 'bg-success' : ($score >= 40 ? 'bg-warning text-dark' : 'bg-secondary');
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-0 shadow-sm h-100 bg-white">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge <?= $badgeColor ?>"><?= $score ?>% Match</span>
                                    <small class="text-muted"><?= htmlspecialchars($row['industry'] ?? 'General') ?></small>
                                </div>
                                <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($row['company_name']) ?></h6>
                                <p class="card-text text-muted small flex-grow-1"><?= substr(htmlspecialchars($row['description']), 0, 80) ?>...</p>
                                <a href="view_internship.php?id=<?= $row['internship_id'] ?>" class="btn btn-outline-success btn-sm w-100 mt-2">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>