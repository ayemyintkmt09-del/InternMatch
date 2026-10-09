<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/controllers/CompanyDirectoryController.php';

header('Cache-Control: no-store');

try {
    $user = current_user();
    $result = CompanyDirectoryController::search($_GET);
    $industries = CompanyDirectoryController::industries();
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(503);
    exit('Companies could not be loaded. Please try again.');
}

$filters = $result['filters'];

$pageLink = static function (int $page) use ($filters): string {
    return url(
        'companies.php?'
        . http_build_query(array_merge($filters, ['page' => $page]))
    );
};

$first = $result['total'] > 0
    ? ($result['page'] - 1) * $result['per_page'] + 1
    : 0;

$last = min(
    $result['page'] * $result['per_page'],
    $result['total']
);

render_header($user, 'Explore Companies', 'companies');

?>

<main class="im-directory-page" id="main-content" tabindex="-1">
    <div class="container py-5">

        <header class="mb-4">
            <p class="text-success fw-semibold mb-2">
                EXPLORE COMPANIES
            </p>

            <h1>Find your next workplace.</h1>

            <p class="text-secondary">
                Discover verified companies and their available internships.
            </p>
        </header>

        <form
            method="get"
            action="<?= e(url('companies.php')) ?>"
            class="im-directory-filters mb-4">

            <div>
                <label for="companySearch" class="form-label">
                    Company name
                </label>

                <input
                    type="search"
                    id="companySearch"
                    name="q"
                    class="form-control"
                    maxlength="100"
                    value="<?= e($filters['q']) ?>"
                    placeholder="Search companies">
            </div>

            <div>
                <label for="companyIndustry" class="form-label">
                    Industry
                </label>

                <select
                    id="companyIndustry"
                    name="industry"
                    class="form-select">

                    <option value="">All industries</option>

                    <?php if (
                        $filters['industry'] !== ''
                        && !in_array($filters['industry'], $industries, true)
                    ): ?>
                        <option
                            value="<?= e($filters['industry']) ?>"
                            selected>
                            <?= e($filters['industry']) ?>
                        </option>
                    <?php endif; ?>

                    <?php foreach ($industries as $industry): ?>
                        <option
                            value="<?= e($industry) ?>"
                            <?= $filters['industry'] === $industry
                                ? 'selected' : '' ?>>
                            <?= e($industry) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label for="companyLocation" class="form-label">
                    Location
                </label>

                <input
                    id="companyLocation"
                    name="location"
                    class="form-control"
                    maxlength="150"
                    value="<?= e($filters['location']) ?>"
                    placeholder="City or region">
            </div>

            <div>
                <label for="companySort" class="form-label">
                    Sort by
                </label>

                <select
                    id="companySort"
                    name="sort"
                    class="form-select">

                    <option
                        value="name"
                        <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>
                        Company name
                    </option>

                    <option
                        value="open"
                        <?= $filters['sort'] === 'open' ? 'selected' : '' ?>>
                        Most open internships
                    </option>
                </select>
            </div>

            <div class="im-directory-filter-actions">
                <label class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        name="hiring"
                        value="1"
                        <?= $filters['hiring'] === '1' ? 'checked' : '' ?>>

                    <span class="form-check-label">
                        Has open internships
                    </span>
                </label>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        Search
                    </button>

                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e(url('companies.php')) ?>">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <p class="text-secondary">
            Showing <?= $first ?>–<?= $last ?>
            of <?= (int) $result['total'] ?> companies
        </p>

        <?php if ($result['items'] === []): ?>

            <section class="im-company-card text-center p-5">
                <h2 class="h4">No companies found</h2>

                <p class="text-secondary">
                    Try another name, industry, or location.
                </p>

                <a
                    class="btn btn-outline-success"
                    href="<?= e(url('companies.php')) ?>">
                    Clear filters
                </a>
            </section>

        <?php else: ?>

            <div class="row g-4">
                <?php foreach ($result['items'] as $company): ?>
                    <div class="col-md-6 col-xl-4">
                        <article class="im-company-card">

                            <div class="im-directory-logo">
                                <?= company_logo(
                                    (int) $company['company_id'],
                                    (string) $company['company_name'],
                                    (int) $company['company_has_logo'] === 1
                                ) ?>
                            </div>

                            <span class="badge bg-success-subtle text-success mb-3">
                                Verified company
                            </span>

                            <h2 class="h5">
                                <a
                                    class="im-company-name"
                                    href="<?= e(url(
                                        'company-public-profile.php?id='
                                        . (int) $company['company_id']
                                    )) ?>">
                                    <?= e($company['company_name']) ?>
                                </a>
                            </h2>

                            <p class="text-secondary mb-2">
                                <?= e($company['industry'] ?: 'Industry not provided') ?>
                            </p>

                            <p class="text-secondary">
                                <i
                                    class="bi bi-geo-alt"
                                    aria-hidden="true"></i>
                                <?= e($company['location'] ?: 'Location not provided') ?>
                            </p>

                            <p class="fw-semibold text-success">
                                <?= (int) $company['open_count'] ?>
                                open internship<?= (int) $company['open_count'] === 1 ? '' : 's' ?>
                            </p>

                            <a
                                class="btn btn-outline-success mt-auto"
                                href="<?= e(url(
                                    'company-public-profile.php?id='
                                    . (int) $company['company_id']
                                )) ?>">
                                View Company
                            </a>

                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

        <?php if ($result['pages'] > 1): ?>
            <nav class="mt-4" aria-label="Company directory pages">
                <ul class="pagination flex-wrap gap-1">

                    <?php if ($result['page'] > 1): ?>
                        <li class="page-item">
                            <a
                                class="page-link"
                                href="<?= e($pageLink($result['page'] - 1)) ?>">
                                Previous
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $result['page'] - 2);
                    $end = min($result['pages'], $result['page'] + 2);
                    ?>

                    <?php for ($page = $start; $page <= $end; $page++): ?>
                        <li class="page-item<?= $page === $result['page'] ? ' active' : '' ?>">
                            <a
                                class="page-link"
                                href="<?= e($pageLink($page)) ?>"
                                <?= $page === $result['page']
                                    ? 'aria-current="page"' : '' ?>>
                                <?= $page ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($result['page'] < $result['pages']): ?>
                        <li class="page-item">
                            <a
                                class="page-link"
                                href="<?= e($pageLink($result['page'] + 1)) ?>">
                                Next
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </nav>
        <?php endif; ?>

    </div>
</main>

<?php render_footer($user); ?>