<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentProfileController.php';

require_once __DIR__ . '/../app/controllers/CvController.php';



$user = require_role('student');
$userId = (int) $user['user_id'];

$profileError = null;
$profileSuccess = take_flash('profile_success');

$cvSuccess = take_flash('cv_success');
$cvError = take_flash('cv_error');

try {
    $profile = StudentProfileController::load($userId);
    $hasDownloadableCv = CvController::downloadPath($profile) !== null;

    $availableSkills = StudentProfileController::catalogue();

    $academicFields = StudentProfileController::academicFields();





    // This reflects saved data, even if a submitted form is invalid.
    $completion = StudentProfileController::completion($profile);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post();
        verify_csrf();

        try {
            StudentProfileController::save($userId, $_POST);

            flash('profile_success', 'Your profile has been saved.');

            redirect('student-profile.php');
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $profileError = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log((string) $exception);
            http_response_code(500);

            $profileError =
                'Your changes could not be saved. Please try again.';
        }

        // Preserve editable form values after a failed submission.
        $editableFields = [
            'first_name',
            'last_name',
            'phone',
            'university',
            'degree',
            'academic_year',
            'graduation_year',
            'skills_text',
            'interests',
            'career_interest',
            'preferred_location',
            'availability',
            'preferred_internship_type',
            'preferred_duration',
            'bio',
            'field_id',
            'location',
            'available_from',
            'available_until',
        ];

        foreach ($editableFields as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field])) {
                $profile[$field] = $_POST[$field];
            }
        }
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('The profile could not be loaded. Please try again later.');
}

function profile_options(
    string $field,
    array $profile,
    string $placeholder
): void {
    $current = (string) ($profile[$field] ?? '');

    echo '<option value="">'
        . e($placeholder)
        . '</option>';

    $options = StudentProfileController::OPTIONS[$field];

    // Display existing legacy values without silently replacing them.
    if ($current !== '' && !in_array($current, $options, true)) {
        $options[] = $current;
    }

    foreach ($options as $option) {
        $selected = $current === $option ? ' selected' : '';

        echo '<option value="'
            . e($option)
            . '"'
            . $selected
            . '>'
            . e($option)
            . '</option>';
    }
}
?>









<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | InternMatch</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>

<body>

<!-- =========================================
     NAVBAR
========================================= -->

<nav class="navbar navbar-expand-lg dashboard-nav">
    <div class="container">

        <!-- Logo -->
        <a
            class="navbar-brand logo"
            href="<?= e(url('student-dashboard.php')) ?>">
            <i class="bi bi-mortarboard-fill"></i>
            <span>Intern<span class="logo-green">Match</span></span>
        </a>

        <!-- Mobile Menu Button -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="student-dashboard.php">
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="opportunities.php">
                        Opportunities
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="my-applications.php">
                        My Applications
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="saved-internships.php">
                        Saved
                    </a>
                </li>

                <!-- Notification -->
                <?php require __DIR__ . '/../app/views/notification-link.php'; ?>

                <!-- User -->
                <li class="nav-item dropdown ms-lg-3">

                    <a class="nav-link dropdown-toggle dashboard-user"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown">

                        <div class="dashboard-avatar">
                            <?= e(mb_strtoupper(
                                mb_substr($user['name'], 0, 1, 'UTF-8'),
                                'UTF-8'
                            )) ?>
                        </div>

                        <div class="dashboard-user-info">
                            <strong><?= e($user['name']) ?></strong>
                            <small>Student</small>
                        </div>

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">

                        <li>
                            <a class="dropdown-item active" href="student-profile.php">
                                <i class="bi bi-person me-2"></i>
                                My Profile
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="#cv-section">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                My CV
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

                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </button>
                            </form>
                        </li>

                    </ul>

                </li>

            </ul>

        </div>
    </div>
</nav>


<!-- =========================================
     PROFILE PAGE
========================================= -->

<main class="profile-page">

    <div class="container">

        <!-- Page Header -->
        <div class="profile-page-header">

            <div>
                <p class="dashboard-small-title">
                    STUDENT PROFILE
                </p>

                <h1>My Profile</h1>

                <p>
                    Keep your profile updated to receive better internship matches.
                </p>
            </div>

            <button
                class="btn btn-primary profile-save-top"
                type="submit"
                form="studentProfileForm">
                <i class="bi bi-check2-circle me-2"></i>
                Save Changes
            </button>

        </div>


        <!-- =====================================
             PROFILE COMPLETION
        ====================================== -->

        <div class="profile-completion-card mb-4">

            <div class="profile-completion-content">

                <div class="profile-completion-icon">
                    <i class="bi bi-person-check"></i>
                </div>

                <div>

                    <h5>Profile Completion</h5>

                    <p>
                        Complete your profile to improve your internship matches.
                    </p>

                </div>

                <div class="profile-percentage">
                    <?= $completion ?>%
                </div>

            </div>

            <div class="progress profile-progress">

               <div
                    class="progress-bar"
                    role="progressbar"
                    style="width: <?= $completion ?>%;"
                    aria-valuenow="<?= $completion ?>"
                    aria-valuemin="0"
                    aria-valuemax="100">
                </div>

            </div>

        </div>


        <?php if ($profileError !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($profileError) ?>
            </div>
        <?php endif; ?>

        <?php if ($profileSuccess !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($profileSuccess) ?>
            </div>
        <?php endif; ?>

        <form
            id="studentProfileForm"
            method="post"
            action="<?= e(url('student-profile.php')) ?>">

            <?= csrf_field() ?>

        <!-- PERSONAL INFORMATION -->

        <section class="profile-section">

            <div class="profile-section-header">

                <div>
                    <h3>Personal Information</h3>

                    <p>
                        Your basic contact information.
                    </p>
                </div>

                <div class="profile-section-icon blue">
                    <i class="bi bi-person"></i>
                </div>

            </div>


            <div class="row g-4">

                <!-- First Name -->
                <div class="col-md-6">

                    <label class="profile-label">
                        First Name
                    </label>

                    <input
                        type="text"
                        name="first_name"
                        class="form-control profile-input"
                        value="<?= e($profile['first_name'] ?? '') ?>"
                        maxlength="100"
                        autocomplete="given-name"
                        required>

                </div>


                <!-- Last Name -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Last Name
                    </label>

                    <input
                        type="text"
                        name="last_name"
                        class="form-control profile-input"
                        value="<?= e($profile['last_name'] ?? '') ?>"
                        maxlength="100"
                        autocomplete="family-name">

                </div>


                <!-- Email -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Email Address
                    </label>

                    <input
                        type="email"
                        class="form-control profile-input"
                        value="<?= e($profile['email']) ?>"
                        autocomplete="email"
                        readonly>

                </div>


                <!-- Phone -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        class="form-control profile-input"
                        value="<?= e($profile['phone'] ?? '') ?>"
                        placeholder="+95 9 xxx xxx xxx"
                        maxlength="30"
                        autocomplete="tel">

                </div>





                <div class="col-md-6">
                    <label class="profile-label" for="currentLocation">
                        Current Location
                    </label>

                    <input
                        type="text"
                        id="currentLocation"
                        name="location"
                        class="form-control profile-input"
                        value="<?= e($profile['location'] ?? '') ?>"
                        placeholder="e.g. Yangon"
                        maxlength="150"
                        autocomplete="address-level2">

                    <small class="profile-help-text">
                        Your current city or town.
                    </small>
                </div>






            </div>

        </section>

        <!-- EDUCATION -->

        <section class="profile-section">

            <div class="profile-section-header">

                <div>
                    <h3>Education</h3>

                    <p>
                        Tell companies about your academic background.
                    </p>
                </div>

                <div class="profile-section-icon green">
                    <i class="bi bi-mortarboard"></i>
                </div>

            </div>


            <div class="row g-4">

                <!-- University -->
                <div class="col-md-6">

                    <label class="profile-label">
                        University
                    </label>

                    <input
                        type="text"
                        name="university"
                        class="form-control profile-input"
                        value="<?= e($profile['university'] ?? '') ?>"
                        placeholder="Enter your university"
                        maxlength="150">

                </div>


                <!-- Degree -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Degree / Programme
                    </label>

                    <input
                        type="text"
                        name="degree"
                        class="form-control profile-input"
                        value="<?= e($profile['degree'] ?? '') ?>"
                        placeholder="e.g. Computing with Business Management"
                        maxlength="150">
                </div>


                <!-- Academic Year -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Academic Year
                    </label>

                    <select
                        name="academic_year"
                        class="form-select profile-input">
                        <?php
                        profile_options(
                            'academic_year',
                            $profile,
                            'Select academic year'
                        );
                        ?>
                    </select>

                </div>


                <!-- Graduation -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Expected Graduation
                    </label>

                    <input
                        type="number"
                        name="graduation_year"
                        class="form-control profile-input"
                        value="<?= e((string) ($profile['graduation_year'] ?? '')) ?>"
                        placeholder="e.g. 2028"
                        min="1900"
                        max="<?= (int) date('Y') + 15 ?>"
                        step="1">

                </div>

                <div class="col-md-6">
                    <label class="profile-label" for="academicField">
                        Academic Field
                    </label>

                    <select
                        id="academicField"
                        name="field_id"
                        class="form-select profile-input">

                        <option value="">Select academic field</option>

                        <?php foreach ($academicFields as $field): ?>
                            <option
                                value="<?= (int) $field['field_id'] ?>"
                                <?= (string) ($profile['field_id'] ?? '')
                                    === (string) $field['field_id']
                                        ? 'selected'
                                        : '' ?>>
                                <?= e($field['field_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <small class="profile-help-text">
                        Choose the field closest to your degree or programme.
                    </small>
                </div>





            </div>

        </section>


        <!-- SKILLS & INTERESTS -->

        <section class="profile-section">

            <div class="profile-section-header">

                <div>
                    <h3>Skills & Interests</h3>

                    <p>
                        Add your skills and areas of interest.
                    </p>
                </div>

                <div class="profile-section-icon orange">
                    <i class="bi bi-stars"></i>
                </div>

            </div>


            <div class="row g-4">

                <!-- Skills -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Skills
                    </label>

                    <textarea
                        name="skills_text"
                        class="form-control profile-input profile-textarea"
                        rows="4"
                        maxlength="2000"
                        placeholder="e.g. HTML, CSS, JavaScript, PHP, MySQL"><?= e($profile['skills_text'] ?? '') ?></textarea>

                    <small class="profile-help-text">
                        Separate multiple skills with commas.
                    </small>

                    <details class="mt-2">
                        <summary class="profile-help-text">
                            Available skills
                        </summary>

                        <p class="profile-help-text mt-2">
                            <?= e(implode(', ', array_column(
                                $availableSkills,
                                'skill_name'
                            ))) ?>
                        </p>
                    </details>

                </div>


                <!-- Interests -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Interests
                    </label>

                    <textarea
                        name="interests"
                        class="form-control profile-input profile-textarea"
                        rows="4"
                        maxlength="2000"
                        placeholder="e.g. Web Development, Data Analytics, UI/UX"><?= e($profile['interests'] ?? '') ?></textarea>


                    <small class="profile-help-text">
                        Add areas that you are interested in.
                    </small>

                </div>


                <!-- Career Interests -->
                <div class="col-12">

                    <label class="profile-label">
                        Career Interests
                    </label>

                <textarea
                    name="career_interest"
                    class="form-control profile-input profile-textarea"
                    rows="3"
                    maxlength="255"
                    placeholder="Describe the type of career you would like to explore."><?= e($profile['career_interest'] ?? '') ?></textarea>                </div>

            </div>

        </section>


        <!-- INTERNSHIP PREFERENCES -->

        <section class="profile-section">

            <div class="profile-section-header">

                <div>
                    <h3>Internship Preferences</h3>

                    <p>
                        Tell us what type of internship you are looking for.
                    </p>
                </div>

                <div class="profile-section-icon purple">
                    <i class="bi bi-briefcase"></i>
                </div>

            </div>


            <div class="row g-4">

                <!-- Preferred Location -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Preferred Location
                    </label>

                    <select
                        name="preferred_location"
                        class="form-select profile-input">
                        <?php
                        profile_options(
                            'preferred_location',
                            $profile,
                            'Select preferred location'
                        );
                        ?>
                    </select>

                </div>


                <!-- Availability -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Availability
                    </label>

                    <select
                        name="availability"
                        class="form-select profile-input">
                        <?php
                        profile_options(
                            'availability',
                            $profile,
                            'Select availability'
                        );
                        ?>
                    </select>

                </div>

                <!-- Internship Type -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Preferred Internship Type
                    </label>

                    <select
                        name="preferred_internship_type"
                        class="form-select profile-input">
                        <?php
                        profile_options(
                            'preferred_internship_type',
                            $profile,
                            'Select internship type'
                        );
                        ?>
                    </select>

                </div>


                <!-- Duration -->
                <div class="col-md-6">

                    <label class="profile-label">
                        Preferred Duration
                    </label>

                    <select
                        name="preferred_duration"
                        class="form-select profile-input">
                        <?php
                        profile_options(
                            'preferred_duration',
                            $profile,
                            'Select duration'
                        );
                        ?>
                    </select>

                </div>




                <div class="col-md-6">
                    <label class="profile-label" for="availableFrom">
                        Available From
                    </label>

                    <input
                        type="date"
                        id="availableFrom"
                        name="available_from"
                        class="form-control profile-input"
                        value="<?= e($profile['available_from'] ?? '') ?>"
                        min="1900-01-01"
                        max="2100-12-31">

                    <small class="profile-help-text">
                        The earliest date you can start.
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="profile-label" for="availableUntil">
                        Available Until
                    </label>

                    <input
                        type="date"
                        id="availableUntil"
                        name="available_until"
                        class="form-control profile-input"
                        value="<?= e($profile['available_until'] ?? '') ?>"
                        min="1900-01-01"
                        max="2100-12-31">

                    <small class="profile-help-text">
                        The latest date you can continue. Leave blank if undecided.
                    </small>
                </div>




                

            </div>

        </section>


        <!--BIOGRAPHY -->

        <section class="profile-section">

            <div class="profile-section-header">

                <div>
                    <h3>About Me</h3>

                    <p>
                        Give companies a short introduction about yourself.
                    </p>
                </div>

                <div class="profile-section-icon blue">
                    <i class="bi bi-chat-left-text"></i>
                </div>

            </div>


            <label class="profile-label">
                Biography
            </label>

            <textarea
                    name="bio"
                    class="form-control profile-input profile-textarea"
                    rows="5"
                    maxlength="5000"
                    placeholder="Write a short introduction about yourself, your interests and your career goals."><?= e($profile['bio'] ?? '') ?></textarea>

        </section>
        



    </form>



        <!-- CV -->

        <section class="profile-section" id="cv-section">

            <div class="profile-section-header">
                <div>
                    <h3>CV / Resume</h3>
                    <p>
                        Upload your latest CV for internship applications.
                    </p>
                </div>

                <div class="profile-section-icon green">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </div>

            <?php if ($cvError !== null): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($cvError) ?>
                </div>
            <?php endif; ?>

            <?php if ($cvSuccess !== null): ?>
                <div class="alert alert-success" role="status">
                    <?= e($cvSuccess) ?>
                </div>
            <?php endif; ?>

            <form
                id="cvUploadForm"
                method="post"
                enctype="multipart/form-data"
                action="<?= e(url('cv-upload.php')) ?>">

                <?= csrf_field() ?>

                <div class="cv-upload-box">

                    <div class="cv-icon">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <div class="cv-information">
                        <h5 id="cvFileTitle">
                            <?php if ($hasDownloadableCv): ?>
                                <?= e($profile['cv_original_name'] ?: 'My CV') ?>
                            <?php elseif (!empty($profile['cv_path'])): ?>
                                Please re-upload your previous CV
                            <?php else: ?>
                                No CV uploaded
                            <?php endif; ?>
                        </h5>

                        <p id="cvFileDescription" aria-live="polite">
                            Choose a PDF, then click Upload CV.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <label
                            class="btn btn-outline-primary cv-upload-button"
                            for="cvFile">

                            <i class="bi bi-folder2-open me-2"></i>
                            Choose PDF
                        </label>

                        <input
                            type="file"
                            id="cvFile"
                            name="cv"
                            accept=".pdf,application/pdf"
                            class="visually-hidden"
                            required>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>
                            <?= $hasDownloadableCv ? 'Replace CV' : 'Upload CV' ?>
                        </button>
                    </div>

                </div>
            </form>

            <small class="profile-help-text">
                PDF only. Maximum file size: 5 MB.
                CV uploads are separate from Save Changes.
            </small>

            <?php if (!empty($profile['cv_path'])): ?>
                <div class="d-flex flex-wrap gap-2 mt-3">

                    <?php if ($hasDownloadableCv): ?>
                        <a
                            class="btn btn-outline-primary"
                            href="<?= e(url('cv-download.php')) ?>">
                            <i class="bi bi-download me-2"></i>
                            Download CV
                        </a>
                    <?php endif; ?>

                    <form
                        method="post"
                        action="<?= e(url('cv-delete.php')) ?>"
                        class="m-0"
                        onsubmit="return confirm('Remove the CV from your profile?');">

                        <?= csrf_field() ?>

                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>
                            Remove CV
                        </button>
                    </form>
                </div>

                <small class="profile-help-text d-block mt-2">
                    Replacing or removing your profile CV does not change
                    CVs already submitted with applications.
                </small>
            <?php endif; ?>

        </section>



        <!-- BOTTOM ACTIONS -->

        <div class="profile-bottom-actions">

            <a href="student-dashboard.php"
               class="btn btn-outline-secondary">
                Cancel
            </a>

            <button
                class="btn btn-primary"
                type="submit"
                form="studentProfileForm">
                <i class="bi bi-check2-circle me-2"></i>
                Save Changes
            </button>

        </div>

    </div>

</main>


<!-- FOOTER -->

<footer class="dashboard-footer">

    <div class="container">

        <p>
            © 2026 InternMatch
            <span>•</span>
            Internship Opportunity & Student Matching System
        </p>

    </div>

</footer>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Main JS -->
<script src="<?= e(asset_url('js/script.js')) ?>"></script>


<script>
document.getElementById("cvFile").addEventListener("change", function () {
    const file = this.files[0];
    const description = document.getElementById("cvFileDescription");

    this.setCustomValidity("");

    if (!file) {
        description.textContent = "Choose a PDF, then click Upload CV.";
        return;
    }

    if (!file.name.toLowerCase().endsWith(".pdf")) {
        this.setCustomValidity("Please choose a PDF file.");
    } else if (file.size > 5 * 1024 * 1024) {
        this.setCustomValidity("The PDF must not exceed 5 MB.");
    } else if (file.size === 0) {
        this.setCustomValidity("The selected file is empty.");
    }

    if (!this.checkValidity()) {
        description.textContent = this.validationMessage;
        this.reportValidity();
        return;
    }

    description.textContent =
        "Selected: " + file.name + ". Click Upload CV or Replace CV to save.";
});
</script>

</body>
</html>