<?php

require_once __DIR__ . '/../middleware/auth.php';

$publicUser = current_user();

$publicLinks = [
    ['Home', 'index.php'],
    ['Opportunities', 'explore.php'],
    ['How It Works', 'index.php#how-it-works'],
    ['Help', 'help.php'],
];

?>

<nav
    class="navbar navbar-expand-lg bg-white sticky-top shadow-sm"
    aria-label="Main navigation">

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
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav mx-auto">
                <?php foreach ($publicLinks as [$label, $path]): ?>
                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="<?= e(url($path)) ?>">
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex flex-wrap align-items-center gap-2">

                <?php if ($publicUser === null): ?>

                    <a
                        href="<?= e(url('login.php')) ?>"
                        class="btn btn-login">
                        Login
                    </a>

                    <a
                        href="<?= e(url('register.php')) ?>"
                        class="btn btn-primary-custom">
                        Register
                    </a>

                <?php else: ?>

                    <a
                        href="<?= e(url(
                            dashboard_path($publicUser['role'])
                        )) ?>"
                        class="btn btn-primary-custom">
                        My Dashboard
                    </a>

                    <form
                        method="post"
                        action="<?= e(url('logout.php')) ?>"
                        class="m-0">

                        <?= csrf_field() ?>

                        <button
                            type="submit"
                            class="btn btn-outline-secondary">
                            Logout
                        </button>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>