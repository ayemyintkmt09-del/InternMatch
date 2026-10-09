<?php

$pageTitle = $pageTitle ?? 'InternMatch';
$activeNav = $activeNav ?? '';

$studentName = trim((string) ($user['name'] ?? 'Student'));

if ($studentName === '') {
    $studentName = 'Student';
}

$studentInitial = mb_strtoupper(
    mb_substr($studentName, 0, 1, 'UTF-8'),
    'UTF-8'
);

$studentNavigation = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'path' => 'student-dashboard.php',
    ],
    [
        'key' => 'opportunities',
        'label' => 'Opportunities',
        'path' => 'opportunities.php',
    ],
    [
        'key' => 'applications',
        'label' => 'My Applications',
        'path' => 'my-applications.php',
    ],
    [
        'key' => 'saved',
        'label' => 'Saved',
        'path' => 'saved-internships.php',
    ],
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | InternMatch</title>

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

<nav
    class="navbar navbar-expand-lg dashboard-nav"
    aria-label="Student navigation">

    <div class="container">

        <a
            class="navbar-brand logo"
            href="<?= e(url('student-dashboard.php')) ?>">
            <i
                class="bi bi-mortarboard-fill"
                aria-hidden="true"></i>

            <span>
                Intern<span class="logo-green">Match</span>
            </span>
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

        <div
            class="collapse navbar-collapse"
            id="studentNavbar">

            <ul class="navbar-nav mx-auto">
                <?php foreach ($studentNavigation as $item): ?>
                    <?php
                    $isActive = $activeNav === $item['key'];
                    ?>

                    <li class="nav-item">
                        <a
                            class="nav-link<?= $isActive ? ' active' : '' ?>"
                            href="<?= e(url($item['path'])) ?>"
                            <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="dashboard-nav-right">

                <?php require __DIR__ . '/notification-link.php'; ?>

                <div class="dropdown">
                    <button
                        type="button"
                        id="studentAccountMenu"
                        class="dashboard-user border-0 bg-transparent text-start"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Student account menu">

                        <span
                            class="dashboard-avatar"
                            aria-hidden="true">
                            <?= e($studentInitial) ?>
                        </span>

                        <span class="dashboard-user-info">
                            <strong><?= e($studentName) ?></strong>
                            <small>Student</small>
                        </span>

                        <i
                            class="bi bi-chevron-down"
                            aria-hidden="true"></i>
                    </button>

                    <ul
                        class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="studentAccountMenu">

                        <li>
                            <a
                                class="dropdown-item"
                                href="<?= e(url('student-profile.php')) ?>">
                                <i
                                    class="bi bi-person me-2"
                                    aria-hidden="true"></i>
                                My Profile
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="<?= e(url('help.php')) ?>">
                                <i
                                    class="bi bi-question-circle me-2"
                                    aria-hidden="true"></i>
                                Help & Guidance
                            </a>
                        </li>

                        <li>
                            <a
                                class="dropdown-item"
                                href="<?= e(url('index.php')) ?>">
                                <i
                                    class="bi bi-house me-2"
                                    aria-hidden="true"></i>
                                Homepage
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <form
                                method="post"
                                action="<?= e(url('logout.php')) ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <button
                                    type="submit"
                                    class="dropdown-item">
                                    <i
                                        class="bi bi-box-arrow-right me-2"
                                        aria-hidden="true"></i>
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