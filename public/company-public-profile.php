<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';

$user = require_role('student');

$rawId = $_GET['id'] ?? null;

$companyId = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

if ($companyId === false) {
    http_response_code(404);
    exit('Company not found.');
}

try {
    $company = CompanyProfileController::publicProfile($companyId);

    if ($company === null) {
        http_response_code(404);
        exit('Company not found.');
    }

    $internships =
        CompanyProfileController::publicInternships($companyId);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The company profile could not be loaded.');
}

$pageTitle = $company['company_name'];
$activeNav = 'opportunities';

require __DIR__ . '/../app/views/student-header.php';
?>

<main class="company-public-profile-page">
    <div class="container">

        <a
            class="back-opportunities"
            href="<?= e(url('opportunities.php')) ?>">
            <i class="bi bi-arrow-left"></i>
            Back to Opportunities
        </a>

        <section class="public-company-hero">

            <div class="public-company-logo">
                <?= company_logo(
                    (int) $company['company_id'],
                    (string) $company['company_name'],
                    !empty($company['logo_path'])
                ) ?>
            </div>

            <div>
                <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-patch-check-fill me-1"></i>
                    Verified Company
                </span>

                <h1><?= e($company['company_name']) ?></h1>

                <?php if (!empty($company['industry'])): ?>
                    <p>
                        <i class="bi bi-building me-1"></i>
                        <?= e($company['industry']) ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($company['location'])): ?>
                    <p>
                        <i class="bi bi-geo-alt me-1"></i>
                        <?= e($company['location']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <div class="row g-4 mt-2">

            <div class="col-lg-8">
                <section class="details-content-card">
                    <h2>About the Company</h2>

                    <div class="text-break">
                        <?= nl2br(e(
                            $company['description']
                            ?: 'This company has not added a description yet.'
                        )) ?>
                    </div>
                </section>

                <section class="details-content-card">
                    <h2>Open Internships</h2>

                    <?php if ($internships === []): ?>
                        <p class="text-muted mb-0">
                            This company currently has no open internships.
                        </p>
                    <?php else: ?>
                        <div class="public-company-internships">
                            <?php foreach ($internships as $internship): ?>
                                <article class="public-company-internship">
                                    <div>
                                        <h3>
                                            <?= e($internship['title']) ?>
                                        </h3>

                                        <p class="text-muted mb-1">
                                            <?= e(
                                                $internship['field_name']
                                                ?? 'General'
                                            ) ?>
                                            ·
                                            <?= e($internship['internship_type']) ?>
                                        </p>

                                        <p class="small mb-0">
                                            Deadline:
                                            <?= e($internship['deadline']) ?>
                                        </p>
                                    </div>

                                    <a
                                        class="btn btn-primary"
                                        href="<?= e(url(
                                            'internship-details.php?id='
                                            . (int) $internship['internship_id']
                                        )) ?>">
                                        View Details
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="col-lg-4">
                <section class="details-sidebar-card">
                    <h2>Company Information</h2>

                    <dl>
                        <dt>Industry</dt>
                        <dd>
                            <?= e($company['industry'] ?? 'Not specified') ?>
                        </dd>

                        <dt>Location</dt>
                        <dd>
                            <?= e($company['location'] ?? 'Not specified') ?>
                        </dd>
                    </dl>

                    <?php if (!empty($company['website'])): ?>
                        <a
                            class="btn btn-outline-primary w-100"
                            href="<?= e($company['website']) ?>"
                            target="_blank"
                            rel="noopener noreferrer">
                            <i class="bi bi-globe me-2"></i>
                            Visit Company Website
                        </a>
                    <?php endif; ?>
                </section>
            </aside>

        </div>
    </div>
</main>

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>