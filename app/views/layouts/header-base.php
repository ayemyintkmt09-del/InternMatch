<?php

$navigation = [
    'guest' => [
        ['home', 'Home', 'index.php'],
        ['opportunities', 'Internships', 'opportunities.php'],
        ['companies', 'Companies', 'companies.php'],
        ['career-content', 'Career Resources', 'career-articles.php'],
        ['help', 'Help & Guidance', 'help.php'],
    ],
    'student' => [
        ['dashboard', 'Dashboard', 'student-dashboard.php'],
        ['companies', 'Companies', 'companies.php'],
        ['opportunities', 'Internships', 'opportunities.php'],
        ['applications', 'My Applications', 'my-applications.php'],
        ['saved', 'Saved', 'saved-internships.php'],
        ['career-content', 'Career Resources', 'career-articles.php'],
    ],
    'company' => [
        ['dashboard', 'Dashboard', 'company-dashboard.php'],
        ['internships', 'My Internships', 'company-internships.php'],
        ['applications', 'Applicants', 'company-applications.php'],
        ['recruitment', 'Recruitment', 'company-recruitment.php'],
        ['companies', 'Companies', 'companies.php'],
        ['opportunities', 'Browse Internships', 'opportunities.php'],
        ['career-content', 'Career Resources', 'career-articles.php'],
    ],
    'admin' => [
        ['dashboard', 'Dashboard', 'admin-dashboard.php'],
        ['insights', 'Insights', 'admin-insights.php'],
        ['users', 'Users', 'admin-users.php'],
        ['verifications', 'Verification', 'admin-verifications.php'],
        ['internships', 'Internships', 'admin-internships.php'],
        ['activity', 'Activity', 'admin-activity.php'],
        ['review-moderation', 'Reviews', 'admin-company-reviews.php'],
    ],
];


$roleMenus = [
    'student' => [
        ['dashboard', 'bi-grid-1x2', 'Overview', 'student-dashboard.php'],
        ['opportunities', 'bi-search', 'Find Internships', 'opportunities.php'],
        ['recommendations','bi-stars','Recommended Internships','student-recommendations.php'],
        ['saved-searches','bi-bookmarks','Saved Searches','student-saved-searches.php'],
        ['applications', 'bi-send-check', 'My Applications', 'my-applications.php'],
        ['saved', 'bi-bookmark-heart', 'Saved Internships', 'saved-internships.php'],
        ['companies', 'bi-buildings', 'Companies', 'companies.php'],
        ['profile', 'bi-person-badge', 'My Profile & CV', 'student-profile.php'],       
        ['portfolio', 'bi-collection', 'My Portfolio', 'student-portfolio.php'],
        ['skills', 'bi-bar-chart', 'Skill Levels', 'student-skill-levels.php'],
        ['digital-cv', 'bi-file-earmark-person', 'My Digital CV', 'student-digital-cv.php'], 
        ['cv-sharing', 'bi-share', 'CV Sharing', 'student-cv-sharing.php'],
        ['company-reviews','bi-chat-square-heart','My Company Reviews','student-company-reviews.php'],
        ['notifications', 'bi-bell', 'Notifications', 'notifications.php'],
        ['career-content','bi-journal-text','Career Resources','career-articles.php'],
        ['help', 'bi-question-circle', 'Career Guidance', 'help.php'],
    ],
    'company' => [
        ['dashboard', 'bi-grid-1x2', 'Overview', 'company-dashboard.php'],
        ['internships', 'bi-briefcase', 'My Internships', 'company-internships.php'],
        ['create', 'bi-plus-square', 'Post Internship', 'company-internship-create.php'],
        ['applications', 'bi-people', 'Applicants', 'company-applications.php'],      
        ['recruitment','bi-kanban','Recruitment Overview','company-recruitment.php'],
        ['profile', 'bi-building', 'Company Profile', 'company-profile.php'],
        ['companies', 'bi-buildings', 'Company Directory', 'companies.php'],
        ['notifications', 'bi-bell', 'Notifications', 'notifications.php'],
        ['career-content','bi-journal-text','Career Resources', 'career-articles.php'],
        ['help', 'bi-question-circle', 'Help & Guidance', 'help.php'],
    ],
    'admin' => [
        ['dashboard', 'bi-grid-1x2', 'Overview', 'admin-dashboard.php'],
        ['insights', 'bi-graph-up-arrow', 'Platform Insights', 'admin-insights.php'],
        ['users', 'bi-people', 'Manage Users', 'admin-users.php'],
        ['verifications', 'bi-patch-check', 'Company Verification', 'admin-verifications.php'],
        ['internships', 'bi-briefcase', 'Internship Moderation', 'admin-internships.php'],
        ['activity', 'bi-clock-history', 'Activity Logs', 'admin-activity.php'],
        ['review-moderation','bi-shield-check','Review Moderation','admin-company-reviews.php'],
        ['notifications', 'bi-bell', 'Notifications', 'notifications.php'],
        ['career-admin','bi-newspaper','Career Articles','admin-career-articles.php'],
        ['help', 'bi-question-circle', 'Help & Guidance', 'help.php'],
    ],
];


// The mobile menu and desktop sidebar use the same workspace routes.
if ($layoutRole !== 'guest') {
    $navigation[$layoutRole] = array_map(
        static fn(array $item): array => [
            $item[0],
            $item[2],
            $item[3],
        ],
        $roleMenus[$layoutRole]
    );
}



$profilePaths = [
    'student' => 'student-profile.php',
    'company' => 'company-profile.php',
];

$user = $user ?? null;

$displayName = trim((string) (($user['name'] ?? '') ?? ''));
$displayName = $displayName !== '' ? $displayName : 'My Account';

$accountPhoto = null;

if ($layoutRole === 'student' && $user !== null) {
    require_once __DIR__
        . '/../../controllers/StudentProfileController.php';

    try {
        $photoStatement = db()->prepare(
            'SELECT profile_photo
             FROM student_profiles
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $photoStatement->execute([
            'user_id' => (int) $user['user_id'],
        ]);

        $photoProfile = $photoStatement->fetch();

        if ($photoProfile) {
            $accountPhoto =
                StudentProfileController::photoUrl($photoProfile);
        }
    } catch (Throwable $exception) {
        error_log((string) $exception);
    }
}



$pageTitle = trim((string) ($pageTitle ?? ''));
if ($pageTitle === '') {
    $pageTitle = 'Home';
}

$isPublicLayout = $layoutRole === 'guest';

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
        href="<?= e(asset_url('css/theme.css')) ?>"
        rel="stylesheet">

    <!-- ui.css is the final shared design layer; legacy style/theme rules remain available beneath it. -->
    <link
        href="<?= e(asset_url('css/ui.css')) ?>"
        rel="stylesheet">
</head>



<body
    class="im-app im-role-<?= e($layoutRole) ?><?= $isPublicLayout ? ' im-public' : '' ?>"
    data-layout-role="<?= e($layoutRole) ?>">

<a class="im-skip-link" href="#main-content">
    Skip to main content
</a>

<?php if ($isPublicLayout): ?>
    <div class="im-announcement-bar" role="note">
        <div class="container d-flex flex-wrap justify-content-center gap-2">
            <span><i class="bi bi-stars me-1" aria-hidden="true"></i>Build experience. Meet opportunity.</span>
            <a href="<?= e(url('register.php')) ?>">Join InternMatch <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
        </div>
    </div>
<?php endif; ?>

<nav
        class="navbar navbar-expand-xl bg-white border-bottom im-main-nav im-topbar"
        aria-label="<?= e(ucfirst($layoutRole)) ?> navigation">

    <div class="container">

        <a
            class="navbar-brand logo im-brand-lockup"
            href="<?= e(url(
                $layoutRole === 'guest'
                    ? 'index.php'
                    : dashboard_path($layoutRole)
            )) ?>"
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

            <ul class="navbar-nav mx-auto im-primary-nav">
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

            <div class="d-flex align-items-center gap-3 py-2 im-account-controls">

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
                            class="btn btn-outline-secondary dropdown-toggle im-account-button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">

                            <span class="im-account-avatar" aria-hidden="true">
                                <?php if ($accountPhoto !== null): ?>
                                    <img src="<?= e($accountPhoto) ?>" alt="">
                                <?php else: ?>
                                    <?= e(mb_strtoupper(
                                        mb_substr($displayName, 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    )) ?>
                                <?php endif; ?>
                            </span>                            
                            <span class="im-account-name">
                            <?= e(mb_strimwidth(
                                $displayName,
                                0,
                                24,
                                '…',
                                'UTF-8'
                            )) ?>
                            </span>
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

<?php if ($layoutRole !== 'guest'): ?>

<aside class="im-role-sidebar" aria-label="<?= e(ucfirst($layoutRole)) ?> workspace">
    <div class="im-sidebar-title">
        <span class="im-sidebar-symbol"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></span>
        <span><?= e(ucfirst($layoutRole)) ?> workspace<small>InternMatch</small></span>
    </div>
    <div class="im-sidebar-label">WORKSPACE</div>
    <nav class="im-sidebar-links" aria-label="Workspace pages">
        <?php foreach ($roleMenus[$layoutRole] as [$key, $icon, $label, $path]): ?>
            <a class="im-sidebar-link<?= $activeNav === $key ? ' is-active' : '' ?>"
               href="<?= e(url($path)) ?>"
               <?= $activeNav === $key ? 'aria-current="page"' : '' ?>>
                <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<div class="im-sidebar-help">
    <?php if ($layoutRole === 'student'): ?>
        <div class="im-sidebar-career-art" aria-hidden="true">
            <i class="bi bi-mortarboard"></i>
            <i class="bi bi-stars"></i>
        </div>

        <strong>Unlock More Opportunities</strong>
        <p>
            Complete your profile and showcase your skills
            to improve your recommendations.
        </p>

        <a href="<?= e(url('student-profile.php')) ?>">
            Complete My Profile
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    <?php else: ?>
        <i class="bi bi-stars" aria-hidden="true"></i>
        <strong>Grow with InternMatch</strong>
        <p>Find guidance for your next step.</p>
        <a href="<?= e(url('help.php')) ?>">
            Get guidance
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</div>
</aside>
<?php endif; ?>
