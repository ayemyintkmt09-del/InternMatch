<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = $page_title ?? 'InternMatch';
$user_role = $_SESSION['role'] ?? 'guest';

// Dynamic background color: Success for student, Dark for company, Secondary for admin, Primary for guest/default
$navbar_bg = ($user_role === 'student') ? 'bg-success' : (($user_role === 'company') ? 'bg-dark' : (($user_role === 'admin') ? 'bg-secondary' : 'bg-primary'));

// Fetch unread notifications count if user is a student
$unread_count = 0;
if ($user_role === 'student' && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db_conn = $database->getConnection();
    $notif_q = $db_conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $notif_q->execute([$_SESSION['user_id']]);
    $unread_count = $notif_q->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - InternMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark <?php echo $navbar_bg; ?> px-4 shadow-sm">
        <a class="navbar-brand fw-bold" href="
            <?php if($user_role === 'student'): ?>/InternMatch/views/student/stu_dashboard.php
            <?php elseif($user_role === 'company'): ?>/InternMatch/views/company/com_dashboard.php
            <?php elseif($user_role === 'admin'): ?>/InternMatch/views/admin/ad_dashboard.php
            <?php else: ?>#<?php endif; ?>">
            InternMatch 
            <?php if($user_role === 'student'): ?>[Student Portal]
            <?php elseif($user_role === 'company'): ?>[Company Portal]
            <?php elseif($user_role === 'admin'): ?>[Admin Portal]
            <?php endif; ?>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Collapsible Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Portal-Specific Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if($user_role === 'student'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stu_dashboard.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/stu_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/search.php">Search Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'saved.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/saved.php">Saved Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/applications.php">My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/notifications.php">
                            Notifications
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-25 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/student/profile.php">Profile & CV</a>
                    </li>
                <?php elseif($user_role === 'company'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'com_dashboard.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/company/com_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'post_internship.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/company/post_internship.php">Post Internship</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_applications.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/company/manage_applications.php">Manage Applicants</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/company/profile.php">Company Profile</a>
                    </li>
                <?php elseif($user_role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ad_dashboard.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/admin/ad_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ad_verifications.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/admin/ad_verifications.php">Company Verifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ad_users.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/admin/ad_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ad_internships.php' ? 'active text-white fw-bold' : ''; ?>" href="/InternMatch/views/admin/ad_internships.php">Manage Internships</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- User Welcome & Logout Action -->
            <div class="ms-auto d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
                    <a href="/InternMatch/views/auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container my-4 flex-fill">