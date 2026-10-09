<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/layout.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';
require_once __DIR__ . '/../app/controllers/MatchingController.php';
require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

header('Cache-Control: no-store');

$user = current_user();
$isStudent = ($user['role'] ?? null) === 'student';

$savedInternshipIds = [];
$savedSuccess = take_flash('saved_internship_success');
$savedError = take_flash('saved_internship_error');

try {
    $result = StudentInternshipController::search($_GET);
    $fields = StudentInternshipController::fields();

    if ($isStudent) {
        $result['items'] = MatchingController::attachToList(
            (int) $user['user_id'],
            $result['items']
        );

        $savedInternshipIds = SavedInternshipController::savedIds(
            (int) $user['user_id']
        );
    }
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

$pageTitle = 'Internships';
$activeNav = 'opportunities';

render_header($user, $pageTitle, $activeNav);

?>


<main class="opportunities-page" id="main-content" tabindex="-1">
    <div class="container">


        <?php if ($savedSuccess !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($savedSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if ($savedError !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($savedError) ?>
            </div>
        <?php endif; ?>

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



        <button
            type="button"
            id="opportunityFilterToggle"
            class="btn btn-outline-primary d-lg-none mb-3"
            data-bs-toggle="collapse"
            data-bs-target="#opportunityFiltersPanel"
            aria-expanded="false"
            aria-controls="opportunityFiltersPanel">

            <i class="bi bi-sliders me-2" aria-hidden="true"></i>
            <span data-filter-toggle-label>Show filters</span>
        </button>

        <div class="row g-4">

            <aside
                id="opportunityFiltersPanel"
                class="col-lg-3 collapse d-lg-block">


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
                    <?php
                        $totalResults = (int) $result['total'];

                        $firstResult = $totalResults > 0
                            ? (((int) $result['page'] - 1) * (int) $result['per_page']) + 1
                            : 0;

                        $lastResult = $totalResults > 0
                            ? $firstResult + count($result['items']) - 1
                            : 0;
                        ?>

                        <p class="mb-0">
                            <?php if ($totalResults > 0): ?>
                                Showing
                                <strong><?= $firstResult ?>–<?= $lastResult ?></strong>
                                of
                                <strong><?= $totalResults ?></strong>
                                <?= $totalResults === 1 ? 'opportunity' : 'opportunities' ?>
                            <?php else: ?>
                                No opportunities found
                            <?php endif; ?>
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


                <?php
                    $activeFilters = [];

                    if ($filters['q'] !== '') {
                        $activeFilters['q'] = 'Search: ' . $filters['q'];
                    }

                    if ($filters['location'] !== '') {
                        $activeFilters['location'] =
                            'Location: ' . $filters['location'];
                    }

                    if ($filters['type'] !== '') {
                        $activeFilters['type'] = 'Type: ' . $filters['type'];
                    }

                    if ($filters['field_id'] !== '') {
                        $fieldLabel = 'Selected academic field';

                        foreach ($fields as $field) {
                            if ((string) $field['field_id'] === $filters['field_id']) {
                                $fieldLabel = (string) $field['field_name'];
                                break;
                            }
                        }

                        $activeFilters['field_id'] = 'Field: ' . $fieldLabel;
                    }
                    ?>

                    <?php if ($activeFilters !== []): ?>
                        <div
                            class="active-filter-list"
                            role="group"
                            aria-label="Remove individual search filters">

                            <?php foreach ($activeFilters as $filterKey => $filterLabel): ?>
                                <?php
                                $remainingFilters = $filters;
                                unset($remainingFilters[$filterKey]);

                                $removeFilterUrl = url(
                                    'opportunities.php?'
                                    . http_build_query($remainingFilters)
                                );
                                ?>

                                <a
                                    class="active-filter-chip filter-remove-link"
                                    href="<?= e($removeFilterUrl) ?>"
                                    aria-label="<?= e('Remove ' . $filterLabel) ?>">
                                    <?= e($filterLabel) ?>
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </a>
                            <?php endforeach; ?>

                            <a
                                class="btn btn-sm btn-link"
                                href="<?= e(url('opportunities.php')) ?>">
                                Clear all
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($activeFilters !== []): ?>
                        <div
                            class="active-filter-list"
                            role="group"
                            aria-label="Active filters">

                            <?php foreach ($activeFilters as $activeFilter): ?>
                                <span class="active-filter-chip">
                                    <?= e($activeFilter) ?>
                                </span>
                            <?php endforeach; ?>

                            <a
                                class="btn btn-sm btn-link p-0"
                                href="<?= e(url('opportunities.php')) ?>">
                                Clear all
                            </a>
                        </div>
                <?php endif; ?>

                <?php if ($result['items'] === []): ?>

                    <div class="opportunity-card empty-state">
                        <i class="bi bi-search fs-1 text-primary" aria-hidden="true"></i>

                        <?php if ($activeFilters !== []): ?>
                            <h2 class="h4 mt-3">
                                No opportunities match these filters
                            </h2>

                            <p class="text-muted">
                                Remove a filter or try a broader keyword or location.
                            </p>

                            <a
                                class="btn btn-outline-primary"
                                href="<?= e(url('opportunities.php')) ?>">
                                Clear filters
                            </a>
                        <?php else: ?>
                            <h2 class="h4 mt-3">
                                No open internships right now
                            </h2>

                            <p class="text-muted">
                                Check back for new opportunities from verified companies.
                                You can update your profile while you wait.
                            </p>

                            <a
                                class="btn btn-outline-primary"
                                href="<?= e(url('student-profile.php')) ?>">
                                Update my profile
                            </a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <?php foreach ($result['items'] as $internship): ?>



                        <?php
                            $deadlineLabel = 'Deadline not specified';
                            $deadlineTone = 'deadline-badge--neutral';

                            if (!empty($internship['deadline'])) {
                                try {
                                    $deadlineDate = new DateTimeImmutable(
                                        (string) $internship['deadline']
                                    );

                                    $todayDate = new DateTimeImmutable('today');

                                    $daysRemaining = (int) $todayDate
                                        ->diff($deadlineDate)
                                        ->format('%r%a');

                                    if ($daysRemaining < 0) {
                                        $deadlineLabel = 'Expired';
                                        $deadlineTone = 'deadline-badge--expired';
                                    } elseif ($daysRemaining <= 3) {
                                        $deadlineLabel = 'Closing soon';
                                        $deadlineTone = 'deadline-badge--soon';
                                    } else {
                                        $deadlineLabel = 'Deadline: '
                                            . $deadlineDate->format('M j, Y');
                                        $deadlineTone = 'deadline-badge--normal';
                                    }
                                } catch (Throwable $exception) {
                                    $deadlineLabel = 'Deadline unavailable';
                                }
                            }
                            ?>



                            <?php
                            $isSaved = in_array(
                                (int) $internship['internship_id'],
                                $savedInternshipIds,
                                true
                            );
                            ?>
                                                    
                            <article class="opportunity-card">

                            <div class="opportunity-card-top">

                                <div class="company-logo-placeholder">

<div class="company-logo-placeholder">
    <?= company_logo(
        (int) $internship['company_id'],
        (string) $internship['company_name'],
        (int) ($internship['company_has_logo'] ?? 0) === 1,
        'opportunity-company-logo-image'
    ) ?>
</div>
                                </div>

                                <div class="opportunity-main">

                                    <div class="opportunity-title-row">
                                        <div>
                                            <h3>
                                                <a
                                                    class="opportunity-title-link"
                                                    href="<?= e(url(
                                                        'internship-details.php?id='
                                                        . (int) $internship['internship_id']
                                                    )) ?>">
                                                    <?= e($internship['title']) ?>
                                                </a>
                                            </h3>



                                            <p>
                                                <a
                                                    class="opportunity-company-link"
                                                    href="<?= e(url(
                                                        'company-public-profile.php?id='
                                                        . (int) $internship['company_id']
                                                    )) ?>">
                                                    <?= e($internship['company_name']) ?>
                                                </a>

                                                <span
                                                    class="verified-badge"
                                                    title="This company has been verified by InternMatch">

                                                    <i
                                                        class="bi bi-patch-check-fill"
                                                        aria-hidden="true"></i>

                                                    <span>Verified</span>
                                                </span>
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

                                <span class="deadline-badge <?= e($deadlineTone) ?>">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <?= e($deadlineLabel) ?>
                                </span>

<div class="opportunity-card-actions">

    <?php if ($isStudent): ?>

        <form
            method="post"
            action="<?= e(url('saved-internship-action.php')) ?>"
            class="opportunity-save-form">

            <?= csrf_field() ?>

            <input
                type="hidden"
                name="internship_id"
                value="<?= (int) $internship['internship_id'] ?>">

            <input
                type="hidden"
                name="action"
                value="<?= $isSaved ? 'remove' : 'save' ?>">

            <input
                type="hidden"
                name="return_to"
                value="opportunities.php">

            <?php foreach ($filters as $filterName => $filterValue): ?>
                <input
                    type="hidden"
                    name="return_filters[<?= e($filterName) ?>]"
                    value="<?= e((string) $filterValue) ?>">
            <?php endforeach; ?>

            <input
                type="hidden"
                name="return_filters[page]"
                value="<?= (int) $result['page'] ?>">

            <button
                type="submit"
                class="btn btn-sm opportunity-save-button<?= $isSaved ? ' is-saved' : '' ?>"
                aria-pressed="<?= $isSaved ? 'true' : 'false' ?>"
                title="<?= $isSaved
                    ? 'Remove from saved internships'
                    : 'Save internship' ?>">

                <i
                    class="bi <?= $isSaved
                        ? 'bi-bookmark-fill'
                        : 'bi-bookmark' ?>"
                    aria-hidden="true"></i>

                <?= $isSaved ? 'Saved' : 'Save' ?>
            </button>
        </form>

    <?php elseif ($user === null): ?>

        <a
            class="btn btn-sm btn-outline-secondary"
            href="<?= e(url(
                'student-access.php?id='
                . (int) $internship['internship_id']
            )) ?>">
            Sign in to save
        </a>

    <?php endif; ?>

    <a
        class="btn btn-small-primary"
        href="<?= e(url(
            'internship-details.php?id='
            . (int) $internship['internship_id']
        )) ?>">
        View Details
        <i
            class="bi bi-arrow-right ms-1"
            aria-hidden="true"></i>
    </a>

</div>
                            </div>

                        </article>

                    <?php endforeach; ?>

                    <?php endif; ?>




                    <?php if ($result['pages'] > 1): ?>
                        <?php
                        $currentPage = (int) $result['page'];
                        $totalPages = (int) $result['pages'];

                        $visiblePages = [1, $totalPages];

                        for (
                            $number = max(1, $currentPage - 2);
                            $number <= min($totalPages, $currentPage + 2);
                            $number++
                        ) {
                            $visiblePages[] = $number;
                        }

                        $visiblePages = array_values(array_unique($visiblePages));
                        sort($visiblePages);

                        $previousNumber = 0;
                        ?>

                        <nav
                            class="opportunity-pagination"
                            aria-label="Opportunity result pages">

                            <?php if ($currentPage > 1): ?>
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= e($pageLink($currentPage - 1)) ?>"
                                    rel="prev">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <div class="opportunity-page-numbers">
                                <?php foreach ($visiblePages as $number): ?>

                                    <?php if (
                                        $previousNumber > 0
                                        && $number > $previousNumber + 1
                                    ): ?>
                                        <span class="pagination-gap" aria-hidden="true">
                                            …
                                        </span>
                                    <?php endif; ?>

                                    <a
                                        class="btn <?= $number === $currentPage
                                            ? 'btn-primary'
                                            : 'btn-outline-primary' ?>"
                                        href="<?= e($pageLink($number)) ?>"
                                        aria-label="Page <?= $number ?>"
                                        <?= $number === $currentPage
                                            ? 'aria-current="page"'
                                            : '' ?>>
                                        <?= $number ?>
                                    </a>

                                    <?php $previousNumber = $number; ?>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($currentPage < $totalPages): ?>
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= e($pageLink($currentPage + 1)) ?>"
                                    rel="next">
                                    Next
                                </a>
                            <?php endif; ?>

                        </nav>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

<?php render_footer($user); ?>