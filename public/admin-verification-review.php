<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminVerificationController.php';
require_once __DIR__ . '/../app/controllers/CompanyFileController.php';
require_once __DIR__ . '/../app/layout.php';


$user = require_role('admin');

$companyId = filter_input(
    INPUT_GET,
    'company_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$companyId) {
    http_response_code(400);
    exit('Invalid company ID.');
}

$error = null;
$decision = '';
$notes = '';

try {
    $company = AdminVerificationController::find($companyId);

    if (!$company) {
        http_response_code(404);
        exit('Company not found.');
    }

    $notes = (string) ($company['verification_notes'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $decision = is_string($_POST['status'] ?? null)
            ? $_POST['status']
            : '';

        $notes = is_string($_POST['notes'] ?? null)
            ? trim($_POST['notes'])
            : '';

        try {
            AdminVerificationController::review(
                (int) $user['user_id'],
                $companyId,
                $decision,
                $notes,
                is_string($_POST['review_token'] ?? null)
                    ? $_POST['review_token']
                    : ''
            );

            flash(
                'verification_success',
                $decision === 'verified'
                    ? 'Company approved successfully.'
                    : 'Company rejected. Your review notes have been saved.'
            );

            header(
                'Location: ' . url(
                    'admin-verification-review.php?company_id='
                    . $companyId
                ),
                true,
                303
            );
            exit;
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }
    }

    $hasDocument =
        CompanyFileController::path($company, 'verification') !== null;

    $success = take_flash('verification_success');
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The company review page could not be loaded.');
}

$statusClass = match ($company['verification_status']) {
    'verified' => 'bg-success',
    'rejected' => 'bg-danger',
    default => 'bg-warning text-dark',
};
?>

<?php render_header($user, 'Verification Review', 'verifications'); ?>


<main class="container py-5" id="main-content" tabindex="-1">

    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2">Review Company</h1>
            <p class="text-muted mb-0">
                Check the company information and verification document.
            </p>
        </div>

        <a
            class="btn btn-outline-secondary"
            href="<?= e(url('admin-verifications.php')) ?>">
            Back to Companies
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

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card">
                <div class="card-body p-4">

                    <div class="d-flex flex-wrap justify-content-between
                                align-items-center gap-2 mb-4">
                        <h2 class="h4 mb-0">
                            <?= e($company['company_name']) ?>
                        </h2>

                        <span class="badge <?= e($statusClass) ?>">
                            <?= e(ucfirst(
                                $company['verification_status']
                            )) ?>
                        </span>
                    </div>

                    <dl class="row">
                        <dt class="col-sm-4">Account owner</dt>
                        <dd class="col-sm-8">
                            <?= e($company['account_name']) ?>
                        </dd>

                        <dt class="col-sm-4">Login email</dt>
                        <dd class="col-sm-8">
                            <?= e($company['account_email']) ?>
                        </dd>

                        <dt class="col-sm-4">Industry</dt>
                        <dd class="col-sm-8">
                            <?= e($company['industry'] ?: 'Not provided') ?>
                        </dd>

                        <dt class="col-sm-4">Location</dt>
                        <dd class="col-sm-8">
                            <?= e($company['location'] ?: 'Not provided') ?>
                        </dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">
                            <?= e($company['phone'] ?: 'Not provided') ?>
                        </dd>

                        <dt class="col-sm-4">Contact email</dt>
                        <dd class="col-sm-8">
                            <?= e(
                                $company['contact_email'] ?: 'Not provided'
                            ) ?>
                        </dd>

                        <dt class="col-sm-4">Website</dt>
                        <dd class="col-sm-8 text-break">
                            <?= e($company['website'] ?: 'Not provided') ?>
                        </dd>
                    </dl>

                    <h3 class="h6">Company description</h3>

                    <p class="text-break">
                        <?= nl2br(e(
                            $company['description'] ?: 'Not provided'
                        )) ?>
                    </p>

                    <hr>

                    <h3 class="h6">Verification document</h3>

                    <?php if ($hasDocument): ?>
                        <p class="text-muted">
                            Download and review the document before approval.
                        </p>

                        <a
                            class="btn btn-outline-primary"
                            href="<?= e(url(
                                'admin-verification-file.php?company_id='
                                . $companyId
                            )) ?>">
                            Download Document
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            No readable verification document is available.
                            Approval requires a document.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-body p-4">

                    <h2 class="h4 mb-3">Verification Decision</h2>

                    <?php if (!empty(
                        $company['verification_reviewed_at']
                    )): ?>
                        <p class="text-muted small">
                            Last reviewed:
                            <?= e($company['verification_reviewed_at']) ?>
                        </p>
                    <?php endif; ?>

                    <form
                        method="post"
                        action="<?= e(url(
                            'admin-verification-review.php?company_id='
                            . $companyId
                        )) ?>">

                        <?= csrf_field() ?>


                        <input
                            type="hidden"
                            name="review_token"
                            value="<?= e(
                                $_SERVER['REQUEST_METHOD'] === 'POST'
                                    ? (
                                        is_string($_POST['review_token'] ?? null)
                                            ? $_POST['review_token']
                                            : ''
                                    )
                                    : AdminVerificationController::reviewToken($company)
                            ) ?>">




                        <div class="mb-3">
                            <label for="status" class="form-label">
                                Decision
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="form-select"
                                required>

                                <option value="">Choose a decision</option>

                                <option
                                    value="verified"
                                    <?= $decision === 'verified'
                                        ? 'selected' : '' ?>
                                    <?= !$hasDocument ? 'disabled' : '' ?>>
                                    Approve company
                                </option>

                                <option
                                    value="rejected"
                                    <?= $decision === 'rejected'
                                        ? 'selected' : '' ?>>
                                    Reject company
                                </option>

                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                Review notes
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                class="form-control"
                                rows="6"
                                maxlength="2000"
                                aria-describedby="notesHelp"
                            ><?= e($notes) ?></textarea>

                            <div id="notesHelp" class="form-text">
                                A reason is required when rejecting.
                                Maximum 2,000 characters.
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100">
                            Save Decision
                        </button>

                    </form>

                </div>
            </div>
        </div>

    </div>

</main>
<?php render_footer($user); ?>