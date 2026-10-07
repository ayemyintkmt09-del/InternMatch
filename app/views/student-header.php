<?php

$pageTitle = $pageTitle ?? 'InternMatch';
$activeNav = $activeNav ?? '';
$user = $user ?? ['name' => 'Student'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="<?= e(url('css/style.css')) ?>">
</head>

<body>

<nav class="navbar navbar-expand-lg dashboard-nav">
    <div class="container">

        <a
            class="navbar-brand logo"
            href="<?= e(url('student-dashboard.php')) ?>">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Intern<span class="logo-green">Match</span></span>
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#studentNavbar"
            aria-controls="studentNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="studentNavbar">

            <ul class="navbar-nav mx-auto">

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="<?= e(url('student-dashboard.php')) ?>">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $activeNav === 'opportunities'
                            ? 'active' : '' ?>"
                        <?= $activeNav === 'opportunities'
                            ? 'aria-current="page"' : '' ?>
                        href="<?= e(url('opportunities.php')) ?>">
                        Opportunities
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="<?= e(url('my-applications.php')) ?>">
                        My Applications
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $activeNav === 'saved' ? 'active' : '' ?>"
                        <?= $activeNav === 'saved' ? 'aria-current="page"' : '' ?>
                        href="<?= e(url('saved-internships.php')) ?>">
                        Saved
                    </a>
                </li>

            </ul>

            <div class="dashboard-nav-right">

                <button
                    type="button"
                    class="notification-button"
                    disabled
                    title="Notifications will be available after integration"
                    aria-label="Notifications are not available yet">
                    <i class="bi bi-bell"></i>
                </button>

                <div class="dropdown">
                    <button
                        type="button"
                        id="studentAccountMenu"
                        class="dashboard-user border-0 bg-transparent text-start"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Student account menu">

                        <span class="dashboard-avatar">
                            <?= e(mb_strtoupper(
                                mb_substr($user['name'], 0, 1, 'UTF-8'),
                                'UTF-8'
                            )) ?>
                        </span>

                        <span class="dashboard-user-info">
                            <strong><?= e($user['name']) ?></strong>
                            <small>Student</small>
                        </span>

                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="studentAccountMenu">

                        <li>
                            <a
                                class="dropdown-item"
                                href="<?= e(url('student-profile.php')) ?>">
                                <i class="bi bi-person me-2"></i>
                                My Profile
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
</nav>