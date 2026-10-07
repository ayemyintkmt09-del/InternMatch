<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';
require_once __DIR__ . '/../app/controllers/MatchingController.php';

$user = require_role('student');

try {
    $result = StudentInternshipController::search($_GET);

    $result['items'] = MatchingController::attachToList(
        (int) $user['user_id'],
        $result['items']
    );

    $fields = StudentInternshipController::fields();


} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Opportunities could not be loaded. Please try again.');
}

$filters = $result['filters'];

$pageLink = static function (int $page) use ($filters): string {
    return url(
        'opportunities.php?'
        . http_build_query(
            array_merge($filters, ['page' => $page])
        )
    );
};

$pageTitle = 'Opportunities';
$activeNav = 'opportunities';

require __DIR__ . '/../app/views/student-header.php';
?>



<main class="opportunities-page">
    <div class="container">

        <div class="opportunities-header">
            <div>
                <p class="dashboard-small-title">FIND YOUR OPPORTUNITY</p>
                <h1>Explore Internships</h1>
                <p>Find open internships from verified companies.</p>
            </div>
        </div>

        <form
            id="opportunityFilters"
            method="get"
            action="<?= e(url('opportunities.php')) ?>">

            <div class="opportunity-search-card">
                <div class="opportunity-search">
                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        name="q"
                        value="<?= e($filters['q']) ?>"
                        maxlength="100"
                        aria-label="Search internship title or company"
                        placeholder="Search internship title or company">
                </div>

                <button
                    type="submit"
                    class="btn btn-primary opportunity-search-button">
                    Search
                </button>
            </div>

        </form>

        <div class="row g-4">

            <aside class="col-lg-3">
                <div class="opportunity-filter-card">

                    <div class="filter-header">
                        <h4>Filters</h4>

                        <a href="<?= e(url('opportunities.php')) ?>">
                            Reset
                        </a>
                    </div>

                    <div class="filter-group">
                        <label for="fieldFilter" class="form-label">
                            Academic Field
                        </label>

                        <select
                            id="fieldFilter"
                            name="field_id"
                            form="opportunityFilters"
                            class="form-select">

                            <option value="">All fields</option>

                            <?php foreach ($fields as $field): ?>
                                <option
                                    value="<?= (int) $field['field_id'] ?>"
                                    <?= $filters['field_id']
                                        === (string) $field['field_id']
                                        ? 'selected' : '' ?>>
                                    <?= e($field['field_name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="typeFilter" class="form-label">
                            Internship Type
                        </label>

                        <select
                            id="typeFilter"
                            name="type"
                            form="opportunityFilters"
                            class="form-select">

                            <option value="">All types</option>

                            <?php foreach (['On-site', 'Remote', 'Hybrid'] as $type): ?>
                                <option
                                    value="<?= e($type) ?>"
                                    <?= $filters['type'] === $type
                                        ? 'selected' : '' ?>>
                                    <?= e($type) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="locationFilter" class="form-label">
                            Location
                        </label>

                        <input
                            id="locationFilter"
                            name="location"
                            form="opportunityFilters"
                            class="form-control"
                            value="<?= e($filters['location']) ?>"
                            maxlength="150"
                            placeholder="e.g. Yangon">
                    </div>

                    <button
                        type="submit"
                        form="opportunityFilters"
                        class="btn btn-primary w-100">
                        Apply Filters
                    </button>

                </div>
            </aside>

            <div class="col-lg-9">

                <div class="opportunity-results-header">
                    <p class="mb-0">
                        <strong><?= (int) $result['total'] ?></strong>
                        opportunities found
                    </p>

                    <div>
                        <label for="sortOrder" class="visually-hidden">
                            Sort opportunities
                        </label>

                        <select
                            id="sortOrder"
                            name="sort"
                            form="opportunityFilters"
                            class="form-select opportunity-sort"
                            onchange="this.form.requestSubmit()">

                            <option
                                value="newest"
                                <?= $filters['sort'] === 'newest'
                                    ? 'selected' : '' ?>>
                                Newest first
                            </option>

                            <option
                                value="deadline"
                                <?= $filters['sort'] === 'deadline'
                                    ? 'selected' : '' ?>>
                                Deadline soonest
                            </option>

                        </select>
                    </div>
                </div>

                                <?php if ($result['items'] === []): ?>

                    <div class="opportunity-card text-center py-5">
                        <i class="bi bi-search fs-1 text-primary"></i>

                        <h4 class="mt-3">No opportunities found</h4>

                        <p class="text-muted">
                            Try different keywords or reset your filters.
                        </p>

                        <a
                            class="btn btn-outline-primary"
                            href="<?= e(url('opportunities.php')) ?>">
                            Reset Filters
                        </a>
                    </div>

                <?php else: ?>

                    <?php foreach ($result['items'] as $internship): ?>

                        <div class="opportunity-card">

                            <div class="opportunity-card-top">

                                <div class="company-logo-placeholder">
                                    <?= e(mb_strtoupper(
                                        mb_substr(
                                            $internship['company_name'],
                                            0,
                                            1,
                                            'UTF-8'
                                        ),
                                        'UTF-8'
                                    )) ?>
                                </div>

                                <div class="opportunity-main">

                                    <div class="opportunity-title-row">
                                        <div>
                                            <h3>
                                                <?= e($internship['title']) ?>
                                            </h3>

                                            <p>
                                                <?= e($internship['company_name']) ?>

                                                <i
                                                    class="bi bi-patch-check-fill text-success"
                                                    title="Verified company"
                                                    aria-label="Verified company"></i>
                                            </p>
                                        </div>
                                    </div>


                                    <?php if (isset($internship['match_score'])): ?>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="bi bi-stars me-1"></i>
                                            <?= number_format(
                                                (float) $internship['match_score']['overall'],
                                                1
                                            ) ?>% match
                                        </span>
                                    <?php endif; ?>


                                    <div class="opportunity-meta">

                                        <span>
                                            <i class="bi bi-geo-alt"></i>
                                            <?= e(
                                                $internship['location']
                                                ?? 'Location not specified'
                                            ) ?>
                                        </span>

                                        <span>
                                            <i class="bi bi-clock"></i>
                                            <?= e(
                                                $internship['duration']
                                                ?? 'Duration not specified'
                                            ) ?>
                                        </span>

                                        <span>
                                            <i class="bi bi-building"></i>
                                            <?= e($internship['internship_type']) ?>
                                        </span>

                                    </div>

                                    <div class="internship-tags">
                                        <span>
                                            <?= e(
                                                $internship['field_name']
                                                ?? 'General'
                                            ) ?>
                                        </span>

                                        <span>
                                            <?= (int) $internship['interns_needed'] ?>
                                            positions
                                        </span>
                                    </div>

                                </div>
                            </div>

                            <div class="opportunity-card-bottom">
                                <span class="posted-date">
                                    <i class="bi bi-calendar3"></i>
                                    Apply by <?= e($internship['deadline']) ?>
                                </span>

                                <a
                                    class="btn btn-small-primary"
                                    href="<?= e(url(
                                        'internship-details.php?id='
                                        . (int) $internship['internship_id']
                                    )) ?>">
                                    View Details
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>

                        </div>

                    <?php endforeach; ?>

                    <?php endif; ?>

                    <?php if ($result['pages'] > 1): ?>
                        <nav
                            class="d-flex justify-content-between align-items-center mt-4"
                            aria-label="Opportunity pages">

                            <div>
                                <?php if ($result['page'] > 1): ?>
                                    <a
                                        class="btn btn-outline-primary"
                                        href="<?= e($pageLink($result['page'] - 1)) ?>">
                                        Previous
                                    </a>
                                <?php endif; ?>
                            </div>

                            <span>
                                Page <?= (int) $result['page'] ?>
                                of <?= (int) $result['pages'] ?>
                            </span>

                            <div>
                                <?php if ($result['page'] < $result['pages']): ?>
                                    <a
                                        class="btn btn-outline-primary"
                                        href="<?= e($pageLink($result['page'] + 1)) ?>">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>

                        </nav>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../app/views/student-footer.php'; ?>