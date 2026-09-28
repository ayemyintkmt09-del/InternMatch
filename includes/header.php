<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = $page_title ?? 'InternMatch';
$user_role = $_SESSION['role'] ?? 'guest';

// Dynamic background color: Success for student, Dark for company, Secondary for admin, Primary for guest/default
$navbar_bg = ($user_role === 'student') ? 'bg-success' : (($user_role === 'company') ? 'bg-dark' : (($user_role === 'admin') ? 'bg-secondary' : 'bg-primary'));
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
            <?php if($user_role === 'student'): ?>../student/stu_dashboard.php
            <?php elseif($user_role === 'company'): ?>../company/com_dashboard.php
            <?php elseif($user_role === 'admin'): ?>../admin/ad_dashboard.php
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
                        <a class="nav-link" href="stu_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">Search Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="applications.php">My Applications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile & CV</a>
                    </li>
                <?php elseif($user_role === 'company'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="com_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="post_internship.php">Post Internship</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_applications.php">Manage Applicants</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Company Profile</a>
                    </li>
                <?php elseif($user_role === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="ad_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ad_verifications.php">Company Verifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ad_users.php">Manage Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ad_internships.php">Manage Internships</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- User Welcome & Logout Action -->
            <div class="ms-auto d-flex align-items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></span>
                    <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <main class="container my-4 flex-fill">