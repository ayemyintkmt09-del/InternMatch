<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyProfileController.php';
require_once __DIR__ . '/../app/controllers/CompanyReviewController.php';

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

        
$reviewSummary =
    CompanyReviewController::publicSummary($companyId);

$publishedReviews =
    CompanyReviewController::publicList($companyId);



} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('The company profile could not be loaded.');
}

$pageTitle = $company['company_name'];
$activeNav = 'companies';
render_header($user, $pageTitle, $activeNav);

?>

<main class="company-public-profile-page" id="main-content" tabindex="-1">
    <div class="container">

        <a
            class="back-opportunities"
            href="<?= e(url('companies.php')) ?>">
            <i class="bi bi-arrow-left"></i>
            Back to Companies
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

                
<div class="im-company-rating-summary">

    <i class="bi bi-star-fill"></i>

    <?php if ((int) $reviewSummary['total'] > 0): ?>

        <strong>
            <?= number_format(
                (float) $reviewSummary['average_rating'],
                1
            ) ?>/5
        </strong>

        <span>
            Based on
            <?= (int) $reviewSummary['total'] ?>
            approved review(s)
        </span>

    <?php else: ?>

        <span>No published reviews yet</span>

    <?php endif; ?>

</div>


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



                
<section class="details-content-card im-company-reviews">

    <div class="d-flex justify-content-between
                align-items-center flex-wrap gap-2 mb-3">

        <h2 class="mb-0">Internship Experience Reviews</h2>

        <span class="im-review-type">
            <?= (int) $reviewSummary['total'] ?> Reviews
        </span>
    </div>

    <?php if ($publishedReviews === []): ?>

        <p class="text-muted">
            This company does not have any published
            internship experience reviews yet.
        </p>

    <?php else: ?>

        <div class="im-published-review-list">

            <?php foreach ($publishedReviews as $review): ?>

                <article class="im-published-review">

                    <div class="im-review-stars"
                         aria-label="<?= (int) $review['rating'] ?> out of 5 stars">

                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi <?= $i <= (int) $review['rating']
                                ? 'bi-star-fill'
                                : 'bi-star' ?>"
                               aria-hidden="true"></i>
                        <?php endfor; ?>
                    </div>

                    <h3><?= e($review['title']) ?></h3>

                    <p class="im-review-author">
                        InternMatch Student · Application-linked review
                    </p>

                    <p>
                        <?= nl2br(e($review['positives'])) ?>
                    </p>

                    <?php if ($review['improvements']): ?>
                        <p>
                            <strong>Suggested Improvements:</strong>
                            <?= nl2br(e($review['improvements'])) ?>
                        </p>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <p class="small text-muted mt-3">
        Reviews are submitted by students with eligible
        application records and moderated before publication.
        Internship completion is not independently verified.
    </p>

</section>



                <section class="details-content-card">
                    <h2>Open Internships</h2>

                    

<a class="im-company-jobs-link"
   href="<?= e(url(
       'opportunities.php?company_id='
       . (int) $company['company_id']
   )) ?>">

    Browse all internships from
    <?= e($company['company_name']) ?>

    <i class="bi bi-arrow-right"></i>
</a>



<CodeBlock language="text"> 

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

<?php render_footer($user); ?>