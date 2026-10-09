<?php

$navigation = [
    'guest' => [
        ['home', 'Home', 'index.php'],
        ['opportunities', 'Internships', 'opportunities.php'],
        ['companies', 'Companies', 'companies.php'],
        ['help', 'Help & Guidance', 'help.php'],
    ],
    'student' => [
        ['dashboard', 'Dashboard', 'student-dashboard.php'],
        ['companies', 'Companies', 'companies.php'],
        ['opportunities', 'Internships', 'opportunities.php'],
        ['applications', 'My Applications', 'my-applications.php'],
        ['saved', 'Saved', 'saved-internships.php'],
    ],
    'company' => [
        ['dashboard', 'Dashboard', 'company-dashboard.php'],
        ['internships', 'My Internships', 'company-internships.php'],
        ['applications', 'Applicants', 'company-applications.php'],
        ['companies', 'Companies', 'companies.php'],
        ['opportunities', 'Browse Internships', 'opportunities.php'],
    ],
    'admin' => [
        ['dashboard', 'Dashboard', 'admin-dashboard.php'],
        ['users', 'Users', 'admin-users.php'],
        ['verifications', 'Verification', 'admin-verifications.php'],
        ['internships', 'Internships', 'admin-internships.php'],
        ['activity', 'Activity', 'admin-activity.php'],
    ],
];

$profilePaths = [
    'student' => 'student-profile.php',
    'company' => 'company-profile.php',
];

$displayName = trim((string) ($user['name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : 'My Account';

$pageTitle = trim((string) ($pageTitle ?? ''));
if ($pageTitle === '') {
    $pageTitle = 'Home';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title><?= e($pageTitle) ?> | InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link
        href="<?= e(asset_url('css/style.css')) ?>"
        rel="stylesheet">

    <link
        href="<?= e(asset_url('css/ui.css')) ?>"
        rel="stylesheet">
</head>



<body class="im-app im-role-<?= e($layoutRole) ?>">

<a class="im-skip-link" href="#main-content">
    Skip to main content
</a>


<nav
        class="navbar navbar-expand-xl bg-white border-bottom im-main-nav" 
        aria-label="<?= e(ucfirst($layoutRole)) ?> navigation">

    <div class="container">

        <a
            class="navbar-brand logo"
            href="<?= e(url('index.php')) ?>">
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
            data-bs-target="#roleNavbar"
            aria-controls="roleNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="roleNavbar">

            <ul class="navbar-nav mx-auto">
                <?php foreach ($navigation[$layoutRole] as [$key, $label, $path]): ?>
                    <li class="nav-item">
                        <a
                            class="nav-link<?= $activeNav === $key ? ' active' : '' ?>"
                            href="<?= e(url($path)) ?>"
                            <?= $activeNav === $key
                                ? 'aria-current="page"'
                                : '' ?>>
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-3 py-2">

                <?php if ($layoutRole === 'guest'): ?>

                    <a
                        class="btn btn-outline-success"
                        href="<?= e(url('login.php')) ?>">
                        Login
                    </a>

                    <a
                        class="btn btn-success"
                        href="<?= e(url('register.php')) ?>">
                        Register
                    </a>

                <?php else: ?>

                    <?php require __DIR__ . '/../notification-link.php'; ?>

                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            <?= e(mb_strimwidth(
                                $displayName,
                                0,
                                24,
                                '…',
                                'UTF-8'
                            )) ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a
                                    class="dropdown-item"
                                    href="<?= e(url(dashboard_path($layoutRole))) ?>">
                                    My Dashboard
                                </a>
                            </li>

                            <?php if (isset($profilePaths[$layoutRole])): ?>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="<?= e(url($profilePaths[$layoutRole])) ?>">
                                        My Profile
                                    </a>
                                </li>
                            <?php endif; ?>

                            <li>
                                <a
                                    class="dropdown-item"
                                    href="<?= e(url('help.php')) ?>">
                                    Help & Guidance
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
                                        Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>