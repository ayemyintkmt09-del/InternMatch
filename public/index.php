<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';

header('Cache-Control: no-store');

$homeFields = [];
$homeInternships = [];
$homeLoadError = false;

try {
    $homeFields = StudentInternshipController::fields();

    $homeResult = StudentInternshipController::search([
        'sort' => 'newest',
    ]);

    $homeInternships = array_slice(
        $homeResult['items'],
        0,
        4
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    $homeLoadError = true;
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InternMatch</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>

<body>
    <!--NAVBAR -->
    <?php require __DIR__ . '/../app/views/public-nav.php'; ?>


    <!--  HERO SECTION  -->

    <section class="hero-section">

        <div class="container">

            <div class="row align-items-center">

                <!-- Hero text -->
                <div class="col-lg-6">

                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        Find your next opportunity
                    </span>

                    <h1 class="hero-title">
                        Your Next
                        <span>Opportunity</span>
                        Awaits
                    </h1>

                    <p class="hero-text">
                        Discover internships that match your skills,
                        education, interests and career goals.
                    </p>


                    <!-- Search Box -->
<form
    class="search-box"
    action="<?= e(url('explore.php')) ?>"
    method="get"
    role="search"
    aria-label="Search internships">

    <div class="search-input">
        <i class="bi bi-search" aria-hidden="true"></i>

        <label class="visually-hidden" for="searchInput">
            Internship title or company
        </label>

        <input
            type="search"
            id="searchInput"
            name="q"
            maxlength="100"
            placeholder="Title or company...">
    </div>

    <label class="visually-hidden" for="fieldFilter">
        Academic field
    </label>

    <select id="fieldFilter" name="field_id">
        <option value="">All fields</option>

        <?php foreach ($homeFields as $field): ?>
            <option value="<?= (int) $field['field_id'] ?>">
                <?= e($field['field_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="visually-hidden" for="locationFilter">
        Location
    </label>

    <select id="locationFilter" name="location">
        <option value="">All locations</option>
        <option value="Yangon">Yangon</option>
        <option value="Mandalay">Mandalay</option>
    </select>

    <label class="visually-hidden" for="homeTypeFilter">
        Working arrangement
    </label>

    <select id="homeTypeFilter" name="type">
        <option value="">Any arrangement</option>
        <option value="On-site">On-site</option>
        <option value="Remote">Remote</option>
        <option value="Hybrid">Hybrid</option>
    </select>

    <button
        type="submit"
        class="search-button"
        aria-label="Search internships">
        <i class="bi bi-search" aria-hidden="true"></i>
    </button>
</form>

<p class="search-message">
    Students can sign in to view details, save internships,
    and apply.
</p>

                </div>


                <!-- Hero illustration -->
                <div class="col-lg-6 text-center">

                    <div class="hero-illustration">

                        <div class="illustration-circle"></div>

                        <div class="laptop">
                            <div class="laptop-screen">

                                <div class="screen-header"></div>

                                <div class="screen-content">

                                    <div class="screen-card"></div>
                                    <div class="screen-card"></div>
                                    <div class="screen-card"></div>

                                </div>

                            </div>

                            <div class="laptop-base"></div>
                        </div>

                        <div class="person">
                            <i class="bi bi-person-fill"></i>
                        </div>

                        <div class="floating-icon icon-one">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>

                        <div class="floating-icon icon-two">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>

                        <div class="floating-icon icon-three">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


<!-- LIVE INTERNSHIPS -->
<section class="featured-section">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-label">
                    OPPORTUNITIES
                </span>

                <h2>Latest Internships</h2>

                <p>
                    Explore current opportunities from verified companies.
                </p>
            </div>

            <a
                href="<?= e(url('explore.php')) ?>"
                class="view-all">
                View All
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if ($homeLoadError): ?>

            <div class="alert alert-warning" role="status">
                We could not load internships right now.
                Please try again shortly.
            </div>

        <?php elseif ($homeInternships === []): ?>

            <div class="text-center border rounded-4 bg-light p-5">
                <i
                    class="bi bi-briefcase fs-1 text-secondary"
                    aria-hidden="true"></i>

                <h3 class="h5 mt-3">
                    New opportunities are on the way
                </h3>

                <p class="text-muted mb-0">
                    There are no available internships right now.
                    Check back soon.
                </p>
            </div>

        <?php else: ?>

            <div class="row g-4">
                <?php foreach ($homeInternships as $internship): ?>
                    <div class="col-md-6 col-lg-3">
                        <article class="internship-card home-live-card">

                            <div class="home-company-logo">
                                <?= company_logo(
                                    (int) $internship['company_id'],
                                    (string) $internship['company_name'],
                                    (int) (
                                        $internship['company_has_logo'] ?? 0
                                    ) === 1
                                ) ?>
                            </div>

                            <h3 class="h5">
                                <?= e($internship['title']) ?>
                            </h3>

                            <p class="company-name">
                                <?= e($internship['company_name']) ?>
                            </p>

                            <div class="internship-info">
                                <span>
                                    <i
                                        class="bi bi-geo-alt"
                                        aria-hidden="true"></i>
                                    <?= e($internship['location']) ?>
                                </span>

                                <span>
                                    <i
                                        class="bi bi-laptop"
                                        aria-hidden="true"></i>
                                    <?= e($internship['internship_type']) ?>
                                </span>
                            </div>

                            <span class="category-tag">
                                <?= e(
                                    $internship['field_name']
                                    ?? 'General'
                                ) ?>
                            </span>

                            <p class="small text-muted mt-3">
                                Apply by
                                <time datetime="<?= e($internship['deadline']) ?>">
                                    <?= e($internship['deadline']) ?>
                                </time>
                            </p>

                            <a
                                href="<?= e(url(
                                    'explore.php?id='
                                    . (int) $internship['internship_id']
                                )) ?>"
                                class="btn card-button mt-auto"
                                aria-label="<?= e(
                                    'View '
                                    . $internship['title']
                                    . ' at '
                                    . $internship['company_name']
                                ) ?>">
                                View Details
                            </a>

                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>


    <!-- ================= HOW IT WORKS ================= -->

    <section class="how-section" id="how-it-works">

        <div class="container">

            <div class="text-center section-title">

                <span class="section-label">
                    SIMPLE PROCESS
                </span>

                <h2>
                    How InternMatch Works
                </h2>

                <p>
                    Find and apply for internships in a few simple steps.
                </p>

            </div>


            <div class="row g-4">

                <!-- Step 1 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            01
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>

                        <h4>
                            Create Your Profile
                        </h4>

                        <p>
                            Add your education, skills, interests,
                            availability and preferred location.
                        </p>

                    </div>

                </div>


                <!-- Step 2 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            02
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-search"></i>
                        </div>

                        <h4>
                            Find Your Match
                        </h4>

                        <p>
                            Search internships or receive recommendations
                            based on your profile.
                        </p>

                    </div>

                </div>


                <!-- Step 3 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            03
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-send-fill"></i>
                        </div>

                        <h4>
                            Apply & Track
                        </h4>

                        <p>
                            Apply for suitable internships and track
                            your application status.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- BENEFITS  -->

    <section class="benefits-section" id="about">

        <div class="container">

            <div class="row g-5 align-items-center">

                <div class="col-lg-6">

                    <span class="section-label">
                        WHY INTERNMATCH
                    </span>

                    <h2>
                        Built for Students
                        and Companies
                    </h2>

                    <p class="benefit-description">
                        InternMatch provides a centralized platform
                        where students can discover opportunities and
                        companies can manage internship recruitment.
                    </p>

                    <div class="benefit-list">

                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>For Students</h5>
                                <p>
                                    Find relevant internships, manage
                                    your CV and track applications.
                                </p>
                            </div>

                        </div>


                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>For Companies</h5>
                                <p>
                                    Post opportunities and review
                                    suitable student applications.
                                </p>
                            </div>

                        </div>


                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>Smart Matching</h5>
                                <p>
                                    Compare student profiles with
                                    internship requirements.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="stats-card">

                        <div class="stat-item">
                            <i class="bi bi-briefcase-fill"></i>
                            <strong>100+</strong>
                            <span>Internships</span>
                        </div>

                        <div class="stat-item">
                            <i class="bi bi-building-fill"></i>
                            <strong>50+</strong>
                            <span>Companies</span>
                        </div>

                        <div class="stat-item">
                            <i class="bi bi-people-fill"></i>
                            <strong>500+</strong>
                            <span>Students</span>
                        </div>

                        <div class="stat-item">
                            <i class="bi bi-graph-up-arrow"></i>
                            <strong>90%</strong>
                            <span>Match Accuracy</span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!--  CTA -->

    <section class="cta-section">

        <div class="container">

            <div class="cta-content">

                <h2>
                    Ready to Find Your Opportunity?
                </h2>

                <p>
                    Create your profile and start exploring internship
                    opportunities today.
                </p>

                <a
                    href="<?= e(url(
                        $publicUser === null
                            ? 'register.php'
                            : dashboard_path($publicUser['role'])
                    )) ?>"
                    class="btn cta-button">

                    <?= $publicUser === null
                        ? 'Create Your Account'
                        : 'Go to My Dashboard' ?>
                </a>

            </div>

        </div>

    </section>


    <!--  FOOTER  -->

    <footer class="footer">

        <div class="container">

            <div class="row g-4">

                <div class="col-lg-5">

                    <a class="navbar-brand logo footer-logo" href="index.php">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>Intern<span class="logo-green">Match</span></span>
                    </a>

                    <p>
                        Connecting students with internship opportunities
                        that match their skills, education and interests.
                    </p>

                </div>


                <div class="col-6 col-lg-2">

                    <h6>Platform</h6>

                    <a href="explore.php">Opportunities</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#about">About</a>
                    <a href="<?= e(url('help.php')) ?>">Help & Guidance</a>

                </div>


                <div class="col-6 col-lg-2">

                    <h6>Account</h6>

                    <?php if ($publicUser === null): ?>

                        <a href="<?= e(url('login.php')) ?>">Login</a>
                        <a href="<?= e(url('register.php')) ?>">Register</a>

                    <?php else: ?>

                        <a href="<?= e(url(dashboard_path($publicUser['role']))) ?>">
                            My Dashboard
                        </a>

                        <?php if ($publicUser['role'] === 'student'): ?>
                            <a href="<?= e(url('student-profile.php')) ?>">
                                My Profile
                            </a>
                        <?php elseif ($publicUser['role'] === 'company'): ?>
                            <a href="<?= e(url('company-profile.php')) ?>">
                                Company Profile
                            </a>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>


                <div class="col-lg-3">

                    <h6>Contact</h6>

                    <p>
                        <i class="bi bi-envelope"></i>
                        info@internmatch.com
                    </p>

                    <p>
                        <i class="bi bi-geo-alt"></i>
                        Myanmar
                    </p>

                </div>

            </div>


            <hr>

            <div class="footer-bottom">

                <span>
                    © 2026 InternMatch. All rights reserved.
                </span>

                <span>
                    Internship Opportunity & Student Matching System
                </span>

            </div>

        </div>

    </footer>


    <!-- Bootstrap JavaScript -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Custom JavaScript -->
    <script src="<?= e(asset_url('js/script.js')) ?>"></script>

</body>

</html>