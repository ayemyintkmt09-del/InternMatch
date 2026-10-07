<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyInternshipController.php';

$user = require_role('company');
$userId = (int) $user['user_id'];




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $action = $_POST['action'] ?? null;
        $rawId = $_POST['internship_id'] ?? null;

        if (
            !is_string($action)
            || !in_array($action, ['publish', 'close'], true)
            || !is_string($rawId)
        ) {
            throw new InvalidArgumentException(
                'Invalid internship action.'
            );
        }

        $internshipId = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($internshipId === false) {
            throw new InvalidArgumentException(
                'Invalid internship ID.'
            );
        }

        if ($action === 'publish') {
            CompanyInternshipController::publish(
                $userId,
                $internshipId
            );

            $message = 'Your internship has been published.';
        } else {
            CompanyInternshipController::close(
                $userId,
                $internshipId
            );

            $message = 'Your internship has been closed.';
        }

        flash('company_internship_success', $message);
    } catch (InvalidArgumentException $exception) {
        flash(
            'company_internship_error',
            $exception->getMessage()
        );
    } catch (Throwable $exception) {
        error_log((string) $exception);

        flash(
            'company_internship_error',
            'The internship could not be updated. Please try again.'
        );
    }

    redirect('company-internships.php');
}






try {
    $internships = CompanyInternshipController::listing($userId);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Your internships could not be loaded.');
}

$success = take_flash('company_internship_success');
$error = take_flash('company_internship_error');
?>






<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Internships | InternMatch</title>

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
                            href="<?= e(url('company-applications.php')) ?>"
                        >
                            Applications
                        </a>
                    </li>

                </ul>


                <!-- Right Side -->
                <div class="dashboard-nav-right">

                    <!-- Notification -->
                    <button
                        type="button"
                        class="notification-button"
                        disabled
                        title="Notifications will be available after integration"
                        aria-label="Notifications are not available yet">
                        <i class="bi bi-bell"></i>
                    </button>

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
                <h1>My Internships</h1>
                <p>Your company's saved internship opportunities.</p>
            </div>

            <a
                class="btn btn-primary"
                href="<?= e(url('company-internship-create.php')) ?>">
                <i class="bi bi-plus-lg me-2"></i>
                Create Internship
            </a>
        </div>

        <?php if ($success !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <section class="profile-section">

            <?php if ($internships === []): ?>

                <div class="text-center py-5">
                    <i class="bi bi-briefcase fs-1 text-primary"></i>
                    <h3 class="mt-3">No internships yet</h3>
                    <p class="text-muted">
                        Create your first internship draft to get started.
                    </p>

                    <a
                        class="btn btn-primary"
                        href="<?= e(url('company-internship-create.php')) ?>">
                        Create First Draft
                    </a>
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Internship</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Positions</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($internships as $internship): ?>
                                <?php
                                $badgeClass = match ($internship['status']) {
                                    'Published' => 'bg-success-subtle text-success',
                                    'Closed' => 'bg-secondary-subtle text-secondary',
                                    'Expired' => 'bg-danger-subtle text-danger',
                                    default => 'bg-warning-subtle text-warning-emphasis',
                                };
                                ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= e($internship['title']) ?>
                                        </strong>

                                        <div class="small text-muted">
                                            <?= e(
                                                $internship['field_name']
                                                ?? 'Field not selected'
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($internship['internship_type']) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $internship['location']
                                            ?? 'Not specified'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int) $internship['interns_needed'] ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $internship['deadline']
                                            ?? 'Not set'
                                        ) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= e($badgeClass) ?>">
                                            <?= e($internship['status']) ?>
                                        </span>
                                    </td>
                                





                                <td>
                                    <?php if ($internship['status'] === 'Draft'): ?>

                                        <div class="d-flex flex-wrap gap-2">
                                            <a
                                                class="btn btn-sm btn-outline-primary"
                                                href="<?= e(url(
                                                    'company-internship-create.php?id='
                                                    . (int) $internship['internship_id']
                                                )) ?>">
                                                <i class="bi bi-pencil me-1"></i>
                                                Edit
                                            </a>

                                            <form
                                                method="post"
                                                action="<?= e(url('company-internships.php')) ?>"
                                                class="m-0">

                                                <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="publish">

                                                <input
                                                    type="hidden"
                                                    name="internship_id"
                                                    value="<?= (int) $internship['internship_id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-success">
                                                    <i class="bi bi-send me-1"></i>
                                                    Publish
                                                </button>
                                            </form>
                                        </div>

                                    <?php elseif ($internship['status'] === 'Published'): ?>

                                        <form
                                            method="post"
                                            action="<?= e(url('company-internships.php')) ?>"
                                            class="m-0"
                                            onsubmit="return confirm('Close this internship? Existing applications will be kept.');">

                                            <?= csrf_field() ?>

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="close">

                                            <input
                                                type="hidden"
                                                name="internship_id"
                                                value="<?= (int) $internship['internship_id'] ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-stop-circle me-1"></i>
                                                Close
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span class="text-muted small">
                                            No actions available
                                        </span>

                                    <?php endif; ?>


                                    <a
                                        class="btn btn-sm btn-outline-primary mt-2"
                                        href="<?= e(url(
                                            'company-applicants.php?internship_id='
                                            . (int) $internship['internship_id']
                                        )) ?>">
                                        Applicants
                                    </a>
                                </td>

                            </tr>
                                
                            <?php endforeach; ?>


                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>
        
    </div>
</main>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>