<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/layout.php';

header('Cache-Control: no-store');

$user = current_user();
$rawId = $_GET['id'] ?? null;

$companyId = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

$company = null;

try {
    if ($companyId !== false) {
        $stmt = db()->prepare(
            'SELECT
                c.company_id,
                c.user_id,
                c.company_name,
                c.description,
                c.industry,
                c.location,
                c.verification_status,
                u.status AS account_status
             FROM companies AS c
             JOIN users AS u
                ON u.user_id = c.user_id
               AND u.role = \'company\'
             WHERE c.company_id = :company_id
             LIMIT 1'
        );

        $stmt->execute(['company_id' => $companyId]);
        $company = $stmt->fetch() ?: null;
    }
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The company profile could not be loaded.');
}

$isAdmin = $user !== null && $user['role'] === 'admin';

$isOwner =
    $company !== null
    && $user !== null
    && $user['role'] === 'company'
    && (int) $user['user_id'] === (int) $company['user_id'];

$isPublic =
    $company !== null
    && $company['account_status'] === 'active'
    && $company['verification_status'] === 'verified';

if ($company === null || (!$isAdmin && !$isOwner && !$isPublic)) {
    http_response_code(404);
    render_header($user, 'Company unavailable');
    ?>
    <main id="main-content" class="container py-5" tabindex="-1">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h1 class="h3">Company unavailable</h1>
            <p>This company is unavailable or you do not have access.</p>
            <a class="btn btn-outline-primary"
               href="<?= e(url('companies.php')) ?>">
                Browse Companies
            </a>
        </div>
    </main>
    <?php
    render_footer($user);
    exit;
}

if (!$isAdmin && !$isOwner) {
    redirect(
        'company-public-profile.php?id=' . (int) $company['company_id']
    );
}

render_header($user, $company['company_name'] . ' — Profile');
?>

<main id="main-content" class="container py-4 py-lg-5" tabindex="-1">
    <a class="btn btn-outline-primary mb-4"
       href="<?= e(url(dashboard_path($user['role']))) ?>">
        Back to Dashboard
    </a>

    <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <p class="small text-uppercase text-muted">Company Profile</p>
        <h1 class="h3"><?= e($company['company_name']) ?></h1>
        <p>
            <?= e($company['industry'] ?: 'Industry not provided') ?>
            · <?= e($company['location'] ?: 'Location not provided') ?>
        </p>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-light">
                Verification: <?= e(ucfirst($company['verification_status'])) ?>
            </span>
            <span class="badge text-bg-light">
                Account: <?= e(ucfirst($company['account_status'])) ?>
            </span>
        </div>
    </section>

    <section class="card border-0 shadow-sm rounded-4 p-4">
        <h2 class="h5">About the Company</h2>
        <p><?= nl2br(e(
            $company['description'] ?: 'No company introduction added yet.'
        )) ?></p>

        <?php if ($isOwner): ?>
            <a class="btn btn-primary"
               href="<?= e(url('company-profile.php')) ?>">
                Edit Company Profile
            </a>
        <?php endif; ?>

        <?php if ($isPublic): ?>
            <a class="btn btn-outline-primary"
               href="<?= e(url(
                   'company-public-profile.php?id='
                   . (int) $company['company_id']
               )) ?>">
                View Public Profile
            </a>
        <?php endif; ?>
    </section>
</main>

<?php render_footer($user); ?>