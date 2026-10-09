<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/RegistrationController.php';
require_once __DIR__ . '/../app/layout.php';


require_guest();

header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    $result = RegistrationController::register($_POST);

    if ($result['success']) {
        flash(
            'login_success',
            'Your account has been created. '
            . 'Sign in with your new account to continue.'
        );

        redirect('login.php');
    }

    flash('registration_error', $result['message']);

    redirect('register.php');
}

$registrationSuccess = take_flash('registration_success');
$registrationError = take_flash('registration_error');

?>


<?php render_header(null, 'Create Account'); ?>

    <!-- REGISTRATION SECTION  -->

    <main class="registration-section" id="main-content" tabindex="-1">

        <div class="container">

            <div class="registration-wrapper">

                <div class="row g-0">


                    <!--  LEFT SIDE  -->

                    <div class="col-lg-5">

                        <div class="registration-info">

                            <div class="registration-logo">

                                <i class="bi bi-mortarboard-fill"></i>

                                <span>Intern<span>Match</span></span>

                            </div>


                            <h1>Join InternMatch</h1>


                            <p class="registration-intro">
                                Create your account and start your
                                journey towards finding the right
                                internship opportunity.
                            </p>


                            <!-- Benefits -->

                            <div class="registration-benefit">

                                <div class="benefit-icon">
                                    <i class="bi bi-search"></i>
                                </div>

                                <div>
                                    <h5>Find the Right Internship</h5>

                                    <p>
                                        Discover opportunities that
                                        match your skills and interests.
                                    </p>
                                </div>

                            </div>


                            <div class="registration-benefit">

                                <div class="benefit-icon">
                                    <i class="bi bi-person-check-fill"></i>
                                </div>

                                <div>
                                    <h5>Build Your Profile</h5>

                                    <p>
                                        Add your education, skills,
                                        interests and career goals.
                                    </p>
                                </div>

                            </div>


                            <div class="registration-benefit">

                                <div class="benefit-icon">
                                    <i class="bi bi-building"></i>
                                </div>

                                <div>
                                    <h5>Connect With Companies</h5>

                                    <p>
                                        Explore internship opportunities
                                        from companies and organizations.
                                    </p>
                                </div>

                            </div>


                            <!-- Decorative illustration -->

                            <div class="registration-decoration">

                                <i class="bi bi-briefcase-fill decoration-one"></i>

                                <i class="bi bi-mortarboard-fill decoration-two"></i>

                                <i class="bi bi-star-fill decoration-three"></i>

                                <div class="decoration-circle"></div>

                            </div>

                        </div>

                    </div>



                    <!-- RIGHT SIDE  -->

                    <div class="col-lg-7">

                        <div class="registration-form-area">

                            <div class="form-heading">

                                <h2>Create Account</h2>

                                <p>Enter your information to get started.</p>

                            </div>

                            <!-- Account Type -->
                            <div class="account-type">

                                <button
                                    type="button"
                                    class="account-type-btn active"
                                    onclick="selectAccountType('student', this)">
                                    <i class="bi bi-person-fill"></i>Student
                                </button>


                                <button type="button" class="account-type-btn" onclick="selectAccountType('company', this)">
                                <i class="bi bi-building-fill"></i>
                                Company
                                </button>


                               

                            </div>


                            <!-- Registration Form -->

                            <form
                                id="registrationForm"
                                method="post"
                                action="<?= e(url('register.php')) ?>">



                                <?= csrf_field() ?>





                                <!-- Hidden account type -->
                                <input
                                    type="hidden"
                                    id="accountType"
                                    name="account_type"
                                    value="student">

                                <!-- Full Name -->
                                <div class="form-group">
                                    <label for="fullName">
                                        Full Name
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">
                                        <i class="bi bi-person"></i>
                                        <input
                                            type="text"
                                            id="fullName"
                                            name="full_name"
                                            placeholder="Enter your full name"
                                            required>
                                    </div>
                                </div>
                                
                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email">
                                        Email Address
                                        <span>*</span>
                                    </label>

                                    <div class="input-wrapper">
                                        <i class="bi bi-envelope"></i>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            placeholder="Enter your email address"
                                            required>
                                    </div>

                                </div>


                                <!-- Password Row -->

                                <div class="row">

                                    <div class="col-md-6">

                                        <div class="form-group">

                                            <label for="password">
                                                Password
                                                <span>*</span>
                                            </label>

                                            <div class="input-wrapper">

                                                <i class="bi bi-lock"></i>

                                                <input
                                                    type="password"
                                                    id="password"
                                                    name="password"
                                                    placeholder="Create a password"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="password-toggle"
                                                    onclick="togglePassword('password', this)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="col-md-6">

                                        <div class="form-group">

                                            <label for="confirmPassword">
                                                Confirm Password
                                                <span>*</span>
                                            </label>

                                            <div class="input-wrapper">

                                                <i class="bi bi-lock"></i>

                                                <input
                                                    type="password"
                                                    id="confirmPassword"
                                                    name="confirm_password"
                                                    placeholder="Confirm password"
                                                    required>

                                                <button
                                                    type="button"
                                                    class="password-toggle"
                                                    onclick="togglePassword(
                                                        'confirmPassword',
                                                        this)">
                                                    <i class="bi bi-eye"></i>
                                                </button>

                                            </div>

                                        </div>

                                    </div>

                                </div>


                                <!-- Terms -->

                                <div class="terms-check">

                                 <input
                                    type="checkbox"
                                    id="terms"
                                    name="terms"
                                    value="1"
                                    required>

                                    <label for="terms">
                                        I agree to the
                                        <a href="#">Terms and Conditions</a>and
                                        <a href="#">Privacy Policy</a>.
                                    </label>

                                </div>





                                <!-- Message -->
                                <div
                                    id="registrationMessage"
                                    class="registration-message<?= $registrationError
                                        ? ' error'
                                        : ($registrationSuccess ? ' success' : '') ?>"
                                    role="status"
                                    aria-live="polite">
                                    <?= e($registrationError ?? $registrationSuccess ?? '') ?>
                                </div>








                                <!-- Register Button -->
                                <button
                                    type="submit"
                                    class="btn register-button">
                                    Create Account
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </form>


                            <!-- Login link -->

                            <div class="login-link">

                                Already have an account?

                                <a href="<?= e(url('login.php')) ?>">Login</a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>


<?php render_footer(null); ?>