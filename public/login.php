<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';

require_once __DIR__ . '/../app/controllers/LoginController.php';

require_once __DIR__ . '/../app/student-entry.php';

require_guest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    // Read before login clears anonymous session data.
$studentEntry = $_SESSION['student_entry'] ?? null;

$result = LoginController::login($_POST);

if ($result['success']) {
    unset($_SESSION['student_entry']);

    if ($result['role'] === 'student') {
        $destination = student_entry_path($studentEntry);

        if ($destination !== null) {
            redirect($destination);
        }
    }

    redirect(dashboard_path($result['role']));
}

    flash('login_error', $result['message']);

    redirect('login.php');
}

$loginError = take_flash('login_error');
$loginSuccess = take_flash('login_success');
?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - InternMatch</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">

</head>

<body>


    <!-- NAVBAR -->
        <?php require __DIR__ . '/../app/views/public-nav.php'; ?>

        
    <!-- LOGIN SECTION -->

    <main class="login-section">

        <div class="container">

            <div class="login-wrapper">

                <div class="row g-0">

                <!-- LEFT SIDE -->
                <div class="col-lg-6">

                        <div class="login-info">

                            <!-- Logo -->
                            <div class="registration-logo">
                                <i class="bi bi-mortarboard-fill"></i>
                                <span>Intern<span>Match</span></span>
                            </div>

                            <h1>Welcome Back</h1>
                            <p class="login-intro">
                                Log in to continue your internship
                                journey and discover opportunities
                                that match your goals.
                            </p>


                            <!-- Login benefits -->
                            <div class="login-benefit">
                            <div class="benefit-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                            <div>
                                
                                <h5>Discover Opportunities</h5>
                                    <p>
                                        Find internships based on your
                                        skills, education and interests.
                                    </p>
                            </div>
                            </div>


                            <div class="login-benefit">
                                <div class="benefit-icon">
                                    <i class="bi bi-stars"></i>
                                </div>

                                <div>
                                    <h5>Get Matched</h5>
                                    <p>
                                        Receive internship recommendations
                                        based on your profile.
                                    </p>
                                </div>
                            </div>


                            <div class="login-benefit">
                                <div class="benefit-icon">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>

                                <div>
                                    <h5>Track Applications</h5>
                                    <p>
                                        Keep track of your internship
                                        applications and their status.
                                    </p>
                                </div>
                            </div>

                            <!-- Decoration -->
                            <div class="login-decoration">
                                <div class="login-circle"></div>
                                    <i class="bi bi-briefcase-fill login-icon-one"></i>
                                    <i class="bi bi-mortarboard-fill login-icon-two"></i>
                                    <i class="bi bi-check-circle-fill login-icon-three"></i>
                            </div>
                        </div>
                    </div>

                    <!--  RIGHT SIDE -->

                    <div class="col-lg-6">

                        <div class="login-form-area">


                            <!-- Heading -->

                            <div class="form-heading">

                                <h2>
                                    Login to Your Account
                                </h2>

                                <p>
                                    Enter your details to continue.
                                </p>

                            </div>


                            

                            


                            <!-- Login Form -->

                            <form
                                id="loginForm"
                                method="post"
                                action="<?= e(url('login.php')) ?>">

                                <?= csrf_field() ?>


                               


                                <!-- Email -->

                                <div class="form-group">

                                    <label for="loginEmail">
                                        Email Address
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">

                                        <i class="bi bi-envelope"></i>

                                        <input
                                            type="email"
                                            id="loginEmail"
                                            name="email"
                                            placeholder="Enter your email address"
                                            required
                                        >

                                    </div>

                                </div>


                                <!-- Password -->

                                <div class="form-group">

                                    <div class="password-label-row">

                                        <label for="loginPassword">
                                            Password
                                            <span>*</span>
                                        </label>

                                        <a href="#">
                                            Forgot Password?
                                        </a>

                                    </div>


                                    <div class="input-wrapper">

                                        <i class="bi bi-lock"></i>

                                        <input
                                            type="password"
                                            id="loginPassword"
                                            name="password"
                                            placeholder="Enter your password"
                                            required
                                        >

                                        <button
                                            type="button"
                                            class="password-toggle"
                                            onclick="togglePassword(
                                                'loginPassword',
                                                this
                                            )"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </button>

                                    </div>

                                </div>


                                <!-- Remember me -->

                                <div class="remember-row">

                                    <div class="remember-check">

                                       <input
                                            type="checkbox"
                                            id="rememberMe"
                                            disabled
                                            title="Persistent login will be added later">

                                        <label for="rememberMe">
                                            Remember me
                                        </label>

                                    </div>

                                </div>


                                <!-- Login message -->

                                <div
                                    id="loginMessage"
                                    class="registration-message<?= $loginError
                                        ? ' error'
                                        : ($loginSuccess ? ' success' : '') ?>"
                                    role="status"
                                    aria-live="polite">
                                    <?= e($loginError ?? $loginSuccess ?? '') ?>
                                </div>


                                <!-- Login button -->

                                <button
                                    type="submit"
                                    class="btn register-button"
                                >

                                    Login

                                    <i class="bi bi-arrow-right"></i>

                                </button>

                            </form>


                            <!-- Register -->

                            <div class="login-link">

                                Don't have an account?

                                <a href="<?= e(url('register.php')) ?>">
                                    Register
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>



    <!-- FOOTER -->

    <footer class="simple-footer">

        <div class="container">

            <div class="simple-footer-content">

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