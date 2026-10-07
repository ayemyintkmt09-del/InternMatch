<?php
    $companyHeaderName = $user['name'] ?? 'Company';
    $companyHeaderInitial = mb_strtoupper(
        mb_substr($companyHeaderName, 0, 1, 'UTF-8'),
        'UTF-8'
    );

    $activeNav = $activeNav ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle ?? 'Company') ?> - InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link
        href="<?= e(url('css/style.css')) ?>"
        rel="stylesheet">
</head>

<body>



<nav class="navbar navbar-expand-lg main-navbar">

        <div class="container">

            <!-- Logo -->
            <a class="navbar-brand logo" href="index.html">
                <i class="bi bi-mortarboard-fill"></i>
                <span>Intern<span class="logo-green">Match</span></span>
            </a>


            <!-- Mobile Menu -->
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#companyNavbar"
            >
                <i class="bi bi-list"></i>
            </button>


            <div
                class="collapse navbar-collapse"
                id="companyNavbar"
            >

                <!-- Navigation -->
                <ul class="navbar-nav mx-auto">

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $activeNav === 'dashboard'
                                ? 'active' : '' ?>"
                            href="<?= e(url('company-dashboard.php')) ?>">
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $activeNav === 'internships'
                                ? 'active' : '' ?>"
                            href="<?= e(url('company-internships.php')) ?>"
                        >
                            Internships
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link <?= $activeNav === 'applications'
                                ? 'active' : '' ?>"
                            href="<?= e(url('company-applications.php')) ?>"
                        >
                            Applications
                        </a>
                    </li>

                </ul>


                <!-- Right Side -->
                <div class="dashboard-nav-right">

                    <!-- Notification -->
                   <?php require __DIR__ . '/notification-link.php'; ?>


                    <!-- Company User -->
                    <div class="dashboard-user">

                        <div class="dropdown">
                            <button
                                type="button"
                                class="dashboard-user border-0 bg-transparent text-start"
                                id="companyAccountMenu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Company account menu">

                                <div class="dashboard-avatar company-avatar">
                                    <?= e($companyHeaderInitial) ?>
                                </div>

                                <div class="dashboard-user-info">
                                    <strong><?= e($companyHeaderName) ?></strong>
                                    <span>Company</span>
                                </div>

                                <i class="bi bi-chevron-down dashboard-chevron"></i>
                            </button>

                            <ul
                                class="dropdown-menu dropdown-menu-end"
                                aria-labelledby="companyAccountMenu">



                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="<?= e(url('company-profile.php')) ?>">
                                        <i class="bi bi-building me-2"></i>
                                        Company Profile
                                    </a>
                                </li>

                                <li>
                                    <form
                                        method="post"
                                        action="<?= e(url('logout.php')) ?>"
                                        class="m-0">

                                        <?= csrf_field() ?>

                                        <button type="submit" class="dropdown-item">
                                            <i class="bi bi-box-arrow-right me-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </nav>
