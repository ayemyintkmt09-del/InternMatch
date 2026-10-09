<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyInternshipController.php';
require_once __DIR__ . '/../app/layout.php';


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
            || !in_array($action, ['publish'], true)
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






<?php render_header($user, 'My Internships', 'internships'); ?>

<main class="profile-page" id="main-content" tabindex="-1">
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

                                        <span class="text-muted small">
                                                Only administrators can close internships.
                                            </span>

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
<?php render_footer($user); ?>