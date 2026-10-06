<?php

    declare(strict_types=1);

    require_once __DIR__ . '/../app/middleware/auth.php';
    require_once __DIR__ . '/../app/controllers/CompanyInternshipController.php';

    $user = require_role('company');
    $userId = (int) $user['user_id'];

    $error = null;
    $internshipId = null;

    if (array_key_exists('id', $_GET)) {
        $rawId = $_GET['id'];

        if (!is_string($rawId)) {
            http_response_code(400);
            exit('Invalid internship ID.');
        }

        $parsedId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($parsedId === false) {
            http_response_code(400);
            exit('Invalid internship ID.');
        }

        $internshipId = $parsedId;
    }

    $values = [
        'title' => '',
        'description' => '',
        'field_id' => '',
        'location' => '',
        'internship_type' => 'On-site',
        'duration' => '',
        'start_date' => '',
        'end_date' => '',
        'deadline' => '',
        'interns_needed' => '1',
    ];

    $selectedSkills = [];

    try {
        CompanyInternshipController::companyId($userId);

        $fields = CompanyInternshipController::fields();
        $skills = CompanyInternshipController::skills();

        if ($internshipId !== null) {
            $draft = CompanyInternshipController::loadDraft(
                $userId,
                $internshipId
            );

            foreach ($values as $key => $default) {
                $values[$key] = (string) ($draft[$key] ?? '');
            }

            $selectedSkills = $draft['skills'];
        }
    } catch (InvalidArgumentException $exception) {
        http_response_code(404);
        exit(e($exception->getMessage()));
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        exit('The internship form could not be loaded.');
    }

    $formPath = 'company-internship-create.php';

    if ($internshipId !== null) {
        $formPath .= '?id=' . $internshipId;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post();
        verify_csrf();

        foreach ($values as $key => $default) {
            if (isset($_POST[$key]) && is_string($_POST[$key])) {
                $values[$key] = $_POST[$key];
            }
        }

        $postedSkills = $_POST['skills'] ?? [];

        $selectedSkills = is_array($postedSkills)
            ? array_values(array_filter($postedSkills, 'is_string'))
            : [];

        try {
            CompanyInternshipController::saveDraft(
                $userId,
                $_POST,
                $internshipId
            );

            flash(
                'company_internship_success',
                $internshipId === null
                    ? 'Your internship draft has been saved.'
                    : 'Your internship draft has been updated.'
            );

            redirect('company-internships.php');
        } catch (InvalidArgumentException $exception) {
            http_response_code(422);
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            error_log((string) $exception);
            http_response_code(500);

            $error = 'Your draft could not be saved. Please try again.';
        }
    }

    $pageHeading = $internshipId === null
        ? 'Create Internship'
        : 'Edit Internship';
    ?>








<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageHeading) ?> | InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
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
                            class="nav-link"
                            href="company-dashboard.php"
                        >
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link active"
                            href="<?= e(url('company-internships.php')) ?>"
                        >
                            Internships
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            class="nav-link"
                            href="#applications"
                        >
                            Applications
                        </a>
                    </li>

                </ul>


                <!-- Right Side -->
                <div class="dashboard-nav-right">

                    <!-- Notification -->
                    <a
                        href="<?= e(url('notification.html')) ?>"
                        class="notification-button"
                        title="Notifications"
                    >

                        <i class="bi bi-bell"></i>

                        <span class="notification-dot"></span>

                    </a>


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
                                    <?= e(mb_strtoupper(
                                        mb_substr($user['name'], 0, 1, 'UTF-8'),
                                        'UTF-8'
                                    )) ?>
                                </div>

                                <div class="dashboard-user-info">
                                    <strong><?= e($user['name']) ?></strong>
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


<main class="profile-page">
    <div class="container">

        <div class="profile-page-header">
            <div>
                <p class="dashboard-small-title">COMPANY INTERNSHIPS</p>
               <h1><?= e($pageHeading) ?></h1>
                <p>Save a draft before publishing your opportunity.</p>
            </div>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>





        <form
            method="post"
            action="<?= e(url($formPath)) ?>" >


            <?= csrf_field() ?>

            <section class="profile-section">
                <div class="profile-section-header">
                    <div>
                        <h3>Internship Details</h3>
                        <p>Describe the role and what students will learn.</p>
                    </div>
                </div>

                <div class="row g-4">

                    <div class="col-12">
                        <label class="profile-label" for="title">
                            Internship Title
                        </label>

                        <input
                            id="title"
                            name="title"
                            class="form-control profile-input"
                            value="<?= e($values['title']) ?>"
                            maxlength="150"
                            placeholder="e.g. Junior Web Developer Intern"
                            required>
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="field_id">
                            Academic Field
                        </label>

                        <select
                            id="field_id"
                            name="field_id"
                            class="form-select profile-input">

                            <option value="">Select a field (optional)</option>

                            <?php foreach ($fields as $field): ?>
                                <option
                                    value="<?= (int) $field['field_id'] ?>"
                                    <?= (string) $field['field_id']
                                        === $values['field_id']
                                        ? 'selected' : '' ?>>
                                    <?= e($field['field_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="internship_type">
                            Internship Type
                        </label>

                        <select
                            id="internship_type"
                            name="internship_type"
                            class="form-select profile-input"
                            required>

                            <?php foreach (['On-site', 'Remote', 'Hybrid'] as $type): ?>
                                <option
                                    value="<?= e($type) ?>"
                                    <?= $values['internship_type'] === $type
                                        ? 'selected' : '' ?>>
                                    <?= e($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="location">
                            Location
                        </label>

                        <input
                            id="location"
                            name="location"
                            class="form-control profile-input"
                            value="<?= e($values['location']) ?>"
                            maxlength="150"
                            placeholder="e.g. Yangon, Myanmar">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="duration">
                            Duration
                        </label>

                        <input
                            id="duration"
                            name="duration"
                            class="form-control profile-input"
                            value="<?= e($values['duration']) ?>"
                            maxlength="100"
                            placeholder="e.g. 3 months">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="start_date">
                            Start Date
                        </label>

                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            class="form-control profile-input"
                            value="<?= e($values['start_date']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="end_date">
                            End Date
                        </label>

                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            class="form-control profile-input"
                            value="<?= e($values['end_date']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="deadline">
                            Application Deadline
                        </label>

                        <input
                            type="date"
                            id="deadline"
                            name="deadline"
                            class="form-control profile-input"
                            value="<?= e($values['deadline']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="profile-label" for="interns_needed">
                            Interns Needed
                        </label>

                        <input
                            type="number"
                            id="interns_needed"
                            name="interns_needed"
                            class="form-control profile-input"
                            value="<?= e($values['interns_needed']) ?>"
                            min="1"
                            max="10000"
                            step="1"
                            required>
                    </div>

                    <div class="col-12">
                        <label class="profile-label" for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            class="form-control profile-input profile-textarea"
                            rows="8"
                            maxlength="10000"
                            placeholder="Describe responsibilities, learning opportunities and requirements."
                            required><?= e($values['description']) ?></textarea>
                    </div>

                </div>
            </section>

            <section class="profile-section">

            <div class="profile-section-header">
                <div>
                    <h3>Required Skills</h3>
                    <p>
                        Select the skills applicants should have.
                        At least one is required before publishing.
                    </p>
                </div>
            </div>

            <?php if ($skills === []): ?>
                <p class="text-muted mb-0">
                    No skills are available yet.
                    You can still save your draft.
                </p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($skills as $skill): ?>
                        <?php
                        $skillId = (string) $skill['skill_id'];
                        ?>

                        <div class="col-sm-6 col-lg-4">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="skill-<?= (int) $skill['skill_id'] ?>"
                                    name="skills[]"
                                    value="<?= (int) $skill['skill_id'] ?>"
                                    <?= in_array(
                                        $skillId,
                                        $selectedSkills,
                                        true
                                    ) ? 'checked' : '' ?>>

                                <label
                                    class="form-check-label"
                                    for="skill-<?= (int) $skill['skill_id'] ?>">
                                    <?= e($skill['skill_name']) ?>
                                </label>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </section>
                    
            <div class="profile-bottom-actions">
                <a
                    class="btn btn-outline-secondary"
                    href="<?= e(url('company-internships.php')) ?>">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>
                    Save Draft
                </button>
            </div>

        </form>


    </div>
</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>
