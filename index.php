<?php
session_start();

// If user is already logged in, redirect them to their respective role-based dashboard
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'student':
            header("Location: views/student/stu_dashboard.php");
            exit();
        case 'company':
            header("Location: views/company/com_dashboard.php");
            exit();
        case 'admin':
            header("Location: views/admin/ad_dashboard.php");
            exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InternMatch - Internship Opportunity and Student Matching System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0f5132;
            --accent-pink: #F9CEDD;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .hero-section {
            background: linear-gradient(135deg, #0f5132 0%, #198754 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">InternMatch</a>
            <div class="ms-auto">
                <a href="views/auth/login.php" class="btn btn-outline-light me-2">Login</a>
                <a href="views/auth/register.php" class="btn btn-success">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Bridging Students and Companies Through Intelligent Matching</h1>
            <p class="lead mb-4 col-lg-8 mx-auto">InternMatch is a centralized web-based platform connecting university students with suitable internship opportunities based on education, skills, interests, availability, and location.</p>
            <div>
                <a href="views/auth/register.php" class="btn btn-light text-success fw-bold btn-lg px-4 me-2">Register Now</a>
                <a href="views/auth/login.php" class="btn btn-outline-light btn-lg px-4">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Core Features Overview -->
    <section class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Platform Capabilities</h2>
            <p class="text-muted">Designed to eliminate inefficiencies in the traditional internship recruitment process.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card p-4 h-100 bg-white">
                    <h5 class="fw-bold text-success mb-3">Comprehensive Profiles</h5>
                    <p class="text-muted small">Students create detailed profiles containing academic backgrounds, technical skills, preferences, and upload CV documents.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 h-100 bg-white">
                    <h5 class="fw-bold text-success mb-3">Matching Algorithm</h5>
                    <p class="text-muted small">Calculates a compatibility score weighting skills (35%), academic fields (25%), interests (15%), availability (15%), and location (10%).</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 h-100 bg-white">
                    <h5 class="fw-bold text-success mb-3">Application Tracking</h5>
                    <p class="text-muted small">Allows students to track application progress from pending and shortlisted to accepted or rejected states.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0 small text-muted">&copy; 2026 InternMatch. Developed with PHP, MySQL, and Bootstrap.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>