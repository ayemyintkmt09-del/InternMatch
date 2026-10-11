<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminVerificationController.php';
require_once __DIR__ . '/../app/layout.php';

require_once __DIR__ . '/../app/controllers/CompanyFileController.php';



$user = require_role('admin');

$verificationFilter = $_GET['status'] ?? '';

$allowedVerificationFilters = [
    '',
    'pending',
    'verified',
    'rejected',
];

if (
    !is_string($verificationFilter)
    || !in_array(
        $verificationFilter,
        $allowedVerificationFilters,
        true
    )
) {
    http_response_code(400);
    exit('Invalid company verification filter.');
}

try {
    $allCompanies = AdminVerificationController::all();

    $companies = array_values(array_filter(
        $allCompanies,
        static function (array $company) use (
            $verificationFilter
        ): bool {
            return $verificationFilter === ''
                || $company['verification_status']
                    === $verificationFilter;
        }
    ));

} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Company verification records could not be loaded.');
}
?>

<?php render_header($user, 'Company Verification', 'verifications'); ?>


<main class="container py-5" id="main-content" tabindex="-1">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Company Verification</h1>
            <p class="text-muted">
                Review company accounts before allowing publication.
            </p>
        </div>

        <a
            href="<?= e(url('admin-dashboard.php')) ?>"
            class="btn btn-outline-secondary">
            Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-body">


        <form method="get"
      action="<?= e(url('admin-verifications.php')) ?>"
      class="im-admin-filter-bar">

    <div>
        <label for="verificationStatus">
            Verification Status
        </label>

        <select name="status"
                id="verificationStatus"
                class="form-select">

            <option value="">All Companies</option>

            <?php foreach ([
                'pending' => 'Pending',
                'verified' => 'Verified',
                'rejected' => 'Rejected'
            ] as $value => $label): ?>

                <option value="<?= e($value) ?>"
                    <?= $verificationFilter === $value
                        ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>

            <?php endforeach; ?>

        </select>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-funnel me-1"></i>
        Filter
    </button>

    <a href="<?= e(url('admin-verifications.php')) ?>"
       class="btn btn-outline-secondary">
        Clear
    </a>

</form>

            <?php if ($companies === []): ?>

                <p class="text-muted mb-0">
                    No companies match the selected verification status.
                    Try another filter or show all companies.
                </p>

            <?php else: ?>

                


<p class="text-muted small mb-3">
    <?= count($companies) ?>
    company record(s) found.
</p>



<div
    class="table-responsive"
    role="region"
    aria-label="Accounts Verification results"
    tabindex="0">
                    <table class="table align-middle">

                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Account Email</th>
                                <th>Status</th>
                                <th>Document</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($companies as $company): ?>

                            <?php
                            $badge = match ($company['verification_status']) {
                                'verified' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark',
                            };
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <a class="fw-semibold text-decoration-none"
                                            href="<?= e(url(
                                                'company-view-profile.php?id='
                                                . (int) $company['company_id']
                                            )) ?>">
                                                <?= e($company['company_name']) ?>
                                        </a>
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= e($company['industry'] ?? '') ?>
                                    </small>
                                </td>

                                <td>
                                    <?= e($company['account_email']) ?>
                                </td>

                                <td>
                                    <span class="badge <?= e($badge) ?>">
                                        <?= e(ucfirst($company['verification_status'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                        $verificationDocumentAvailable =
                                            CompanyFileController::path($company, 'verification') !== null;
                                        ?>

                                        <span class="small <?= $verificationDocumentAvailable
                                            ? 'text-success'
                                            : 'text-muted' ?>">
                                            <?= $verificationDocumentAvailable
                                                ? 'Available'
                                                : (
                                                    !empty($company['verification_doc_path'])
                                                        ? 'File unavailable'
                                                        : 'Not uploaded'
                                                ) ?>
                                        </span>
                                </td>

                                <td class="text-end">
                                    <a
                                        href="<?= e(url(
                                            'admin-verification-review.php?company_id='
                                            . (int) $company['company_id']
                                        )) ?>"
                                        class="btn btn-sm btn-primary">
                                        Review
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>
    </div>

</main>

<?php render_footer($user); ?>