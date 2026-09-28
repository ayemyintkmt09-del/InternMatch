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
$message = '';
$error = '';

// Get student_id
$prof_stmt = $db->prepare("SELECT student_id FROM student_profiles WHERE user_id = ?");
$prof_stmt->execute([$user_id]);
$profile = $prof_stmt->fetch();
$student_id = $profile['student_id'] ?? 0;

// Handle skill addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skill_name = trim($_POST['skill_name'] ?? '');
    
    if (!empty($skill_name) && $student_id) {
        // Check if skill exists in master list
        $skill_check = $db->prepare("SELECT skill_id FROM skills WHERE skill_name = ?");
        $skill_check->execute([$skill_name]);
        $skill_row = $skill_check->fetch();

        if ($skill_row) {
            $skill_id = $skill_row['skill_id'];
        } else {
            // Insert new skill into master table
            $ins_skill = $db->prepare("INSERT INTO skills (skill_name) VALUES (?)");
            $ins_skill->execute([$skill_name]);
            $skill_id = $db->lastInsertId();
        }

        // Link skill to student if not already linked
        $link_check = $db->prepare("SELECT * FROM student_skills WHERE student_id = ? AND skill_id = ?");
        $link_check->execute([$student_id, $skill_id]);

        if ($link_check->rowCount() === 0) {
            $ins_link = $db->prepare("INSERT INTO student_skills (student_id, skill_id) VALUES (?, ?)");
            $ins_link->execute([$student_id, $skill_id]);
            $message = "Skill added successfully!";
        } else {
            $error = "You already have this skill in your profile.";
        }
    }
}

// Handle skill deletion
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['skill_id'])) {
    $skill_id_to_remove = $_GET['skill_id'];
    $del = $db->prepare("DELETE FROM student_skills WHERE student_id = ? AND skill_id = ?");
    if ($del->execute([$student_id, $skill_id_to_remove])) {
        $message = "Skill removed from your profile.";
    }
}

// Fetch current student skills
$student_skills = [];
if ($student_id) {
    $skills_query = "
        SELECT s.skill_id, s.skill_name 
        FROM student_skills ss 
        JOIN skills s ON ss.skill_id = s.skill_id 
        WHERE ss.student_id = ?
    ";
    $s_stmt = $db->prepare($skills_query);
    $s_stmt->execute([$student_id]);
    $student_skills = $s_stmt->fetchAll();
}

$page_title = "Manage Skills";
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="p-4 bg-white rounded shadow-sm border-0">
            <h2 class="fw-bold text-success">Skill Inventory</h2>
            <p class="text-muted mb-0">Add your core technical competencies and software skills to improve matching accuracy.</p>
        </div>
    </div>
</div>

<?php if($message): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-5 mb-4">
        <div class="card border-0 shadow-sm p-4 bg-white">
            <h4 class="fw-bold text-dark mb-3">Add New Skill</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Skill Name (e.g. PHP, React, Python)</label>
                    <input type="text" name="skill_name" class="form-control" placeholder="Enter skill..." required>
                </div>
                <button type="submit" name="add_skill" class="btn btn-success w-100">Add to Profile</button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm p-4 bg-white">
            <h4 class="fw-bold text-dark mb-3">Your Tagged Skills</h4>
            <?php if(empty($student_skills)): ?>
                <p class="text-muted">No skills added yet. Use the form to tag your competencies.</p>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach($student_skills as $sk): ?>
                        <span class="badge bg-light text-success border border-success p-2 d-flex align-items-center gap-2">
                            <?php echo htmlspecialchars($sk['skill_name']); ?>
                            <a href="skills.php?action=remove&skill_id=<?php echo $sk['skill_id']; ?>" class="text-danger text-decoration-none fw-bold">&times;</a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>