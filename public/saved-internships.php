<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/SavedInternshipController.php';

$user = require_role('student');

$savedQuery = $_GET['q'] ?? '';
$savedAvailability = $_GET['availability'] ?? '';
$savedSort = $_GET['sort'] ?? 'newest';

if (
    !is_string($savedQuery)
    || !is_string($savedAvailability)
    || !is_string($savedSort)
) {
    http_response_code(400);
    exit('Invalid saved-internship filters.');
}

$savedQuery = trim($savedQuery);

if (
    mb_strlen($savedQuery, 'UTF-8') > 100
    || !in_array(
        $savedAvailability,
        ['', 'available', 'unavailable'],
        true
    )
    || !in_array(
        $savedSort,
        ['newest', 'oldest', 'deadline'],
        true
    )
) {
    http_response_code(400);
    exit('Invalid saved-internship filters.');
}

try {
    $allSavedInternships = SavedInternshipController::listing(
        (int) $user['user_id']
    );

    $totalSaved = count($allSavedInternships);
    $availableSaved = 0;

    foreach ($allSavedInternships as $item) {
        if ((int) $item['is_available'] === 1) {
            $availableSaved++;
        }
    }

    $unavailableSaved = $totalSaved - $availableSaved;

    $savedInternships = array_values(array_filter(
        $allSavedInternships,
        static function (array $item) use (
            $savedQuery,
            $savedAvailability
        ): bool {
            $available = (int) $item['is_available'] === 1;

            if ($savedAvailability === 'available' && !$available) {
                return false;
            }

            if ($savedAvailability === 'unavailable' && $available) {
                return false;
            }

            if ($savedQuery !== '') {
                // Unavailable cards do not display their original details.
                if (!$available) {
                    return false;
                }

                $searchText = implode(' ', [
                    (string) $item['title'],
                    (string) $item['company_name'],
                    (string) ($item['location'] ?? ''),
                ]);

                return mb_stripos(
                    $searchText,
                    $savedQuery,
                    0,
                    'UTF-8'
                ) !== false;
            }

            return true;
        }
    ));

    usort(
        $savedInternships,
        static function (array $left, array $right) use (
            $savedSort
        ): int {
            if ($savedSort === 'deadline') {
                // Available internships appear before unavailable ones.
                $availabilityOrder =
                    (int) $right['is_available']
                    <=> (int) $left['is_available'];

                if ($availabilityOrder !== 0) {
                    return $availabilityOrder;
                }

                if ((int) $left['is_available'] === 1) {
                    $deadlineOrder = strcmp(
                        (string) ($left['deadline'] ?? '9999-12-31'),
                        (string) ($right['deadline'] ?? '9999-12-31')
                    );

                    if ($deadlineOrder !== 0) {
                        return $deadlineOrder;
                    }
                }
            }

            if ($savedSort === 'oldest') {
                return strcmp(
                    (string) $left['saved_at'],
                    (string) $right['saved_at']
                ) ?: (
                    (int) $left['saved_id']
                    <=> (int) $right['saved_id']
                );
            }

            return strcmp(
                (string) $right['saved_at'],
                (string) $left['saved_at']
            ) ?: (
                (int) $right['saved_id']
                <=> (int) $left['saved_id']
            );
        }
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Your saved internships could not be loaded.');
}

$success = take_flash('saved_internship_success');
$error = take_flash('saved_internship_error');

$pageTitle = 'Saved Internships';
$activeNav = 'saved';

require __DIR__ . '/../app/views/student-header.php';
?>

<main class="opportunities-page">
    <div class="container">

        <div class="opportunities-header">
            <div>
                <p class="dashboard-small-title">YOUR SHORTLIST</p>
                <h1>Saved Internships</h1>
                <p>Keep track of opportunities you want to revisit.</p>
            </div>
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



        <div class="saved-overview" aria-label="Saved internship summary">
            <div class="saved-overview-item">
                <span>Total saved</span>
                <strong><?= $totalSaved ?></strong>
            </div>

            <div class="saved-overview-item">
                <span>Available</span>
                <strong><?= $availableSaved ?></strong>
            </div>

            <div class="saved-overview-item">
                <span>Unavailable</span>
                <strong><?= $unavailableSaved ?></strong>
            </div>
        </div>


        <form
    method="get"
    action="<?= e(url('saved-internships.php')) ?>"
    class="saved-controls">

    <div class="saved-controls-search">
        <label for="savedKeyword" class="form-label">
            Search saved internships
        </label>

        <input
            id="savedKeyword"
            type="search"
            name="q"
            class="form-control"
            maxlength="100"
            value="<?= e($savedQuery) ?>"
            placeholder="Title, company, or location"
            aria-describedby="savedSearchHelp">
    </div>

    <div>
        <label for="savedAvailability" class="form-label">
            Availability
        </label>

        <select
            id="savedAvailability"
            name="availability"
            class="form-select">

            <option value="">All saved</option>

            <option
                value="available"
                <?= $savedAvailability === 'available'
                    ? 'selected' : '' ?>>
                Available
            </option>

            <option
                value="unavailable"
                <?= $savedAvailability === 'unavailable'
                    ? 'selected' : '' ?>>
                Unavailable
            </option>
        </select>
    </div>

    <div>
        <label for="savedOrder" class="form-label">
            Sort by
        </label>

        <select
            id="savedOrder"
            name="sort"
            class="form-select">

            <option
                value="newest"
                <?= $savedSort === 'newest' ? 'selected' : '' ?>>
                Recently saved
            </option>

            <option
                value="oldest"
                <?= $savedSort === 'oldest' ? 'selected' : '' ?>>
                Oldest saved
            </option>

            <option
                value="deadline"
                <?= $savedSort === 'deadline' ? 'selected' : '' ?>>
                Deadline soonest
            </option>
        </select>
    </div>

    <div class="saved-controls-actions">
        <button type="submit" class="btn btn-primary">
            Apply
        </button>

        <a
            class="btn btn-outline-secondary"
            href="<?= e(url('saved-internships.php')) ?>">
            Reset
        </a>
    </div>

    <p id="savedSearchHelp" class="small text-muted mb-0">
        Keyword search covers available internships.
        Clear the keyword to include unavailable saved items.
    </p>
</form>

<div class="opportunity-results-header">
    <p class="mb-0">
        Showing
                <strong><?= count($savedInternships) ?></strong>
                of
                <strong><?= $totalSaved ?></strong>
                saved internships
            </p>

            <a
                class="btn btn-outline-primary"
                href="<?= e(url('opportunities.php')) ?>">
                Browse Opportunities
            </a>
        </div>

        <?php if ($totalSaved > 0 && $savedInternships === []): ?>

            <div class="opportunity-card empty-state">
                <i
                    class="bi bi-funnel fs-1 text-primary"
                    aria-hidden="true"></i>

                <h2 class="h4 mt-3">
                    No saved internships match these filters
                </h2>

                <p class="text-muted">
                    Try another keyword or availability option.
                    Your saved internships are still in your list.
                </p>

                <a
                    class="btn btn-outline-primary"
                    href="<?= e(url('saved-internships.php')) ?>">
                    Show all saved internships
                </a>
            </div>

        <?php elseif ($totalSaved === 0): ?>

            <div class="opportunity-card empty-state">
                <i class="bi bi-bookmark fs-1 text-primary"></i>

                <h3 class="mt-3">You have not saved any internships yet.</h3>

                <p class="text-muted">
                    Save interesting opportunities to compare them later.

                </p>

                <a
                    class="btn btn-primary"
                    href="<?= e(url('opportunities.php')) ?>">
                    Find Internships
                </a>
            </div>

        <?php else: ?>

        <?php foreach ($savedInternships as $internship): ?>
            <?php
            $available = (int) $internship['is_available'] === 1;

            $deadlineLabel = $available
                ? 'Deadline unavailable'
                : 'No longer available';

            $deadlineTone = $available
                ? 'deadline-badge--neutral'
                : 'deadline-badge--expired';

            if ($available && !empty($internship['deadline'])) {
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
                    $deadlineTone = 'deadline-badge--neutral';
                }
            }
            ?>

                <div class="opportunity-card">

                    <div class="opportunity-card-top">
                        <div class="company-logo-placeholder">
                            <i class="bi bi-bookmark-fill"></i>
                        </div>

                        <div class="opportunity-main">

                            <?php if ($available): ?>

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
                                            <?= e($internship['company_name']) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="opportunity-meta">
                                    <span>
                                        <i class="bi bi-geo-alt"></i>
                                        <?= e(
                                            $internship['location']
                                            ?? 'Location not specified'
                                        ) ?>
                                    </span>

                                    <span>
                                        <i class="bi bi-building"></i>
                                        <?= e($internship['internship_type']) ?>
                                    </span>

                                    <span class="deadline-badge <?= e($deadlineTone) ?>">
                                        <i
                                            class="bi bi-calendar3"
                                            aria-hidden="true"></i>

                                        <?= e($deadlineLabel) ?>
                                    </span>
                                </div>

                            <?php else: ?>

                                <h3>Opportunity unavailable</h3>

                                <p class="text-muted mb-0">
                                    This saved internship is no longer
                                    available to browse. You can remove it
                                    from your saved list.
                                </p>

                                <span class="deadline-badge deadline-badge--expired mt-3">
                                    <i
                                        class="bi bi-exclamation-circle"
                                        aria-hidden="true"></i>

                                    No longer available
                                </span>

                            <?php endif; ?>

                        </div>
                    </div>

                    <div class="opportunity-card-bottom">

                        <span class="posted-date">
                            Saved <?= e(substr(
                                $internship['saved_at'],
                                0,
                                10
                            )) ?>
                        </span>

                        <div class="d-flex flex-wrap gap-2">

                            <?php if ($available): ?>
                                <a
                                    class="btn btn-outline-primary"
                                    href="<?= e(url(
                                        'internship-details.php?id='
                                        . (int) $internship['internship_id']
                                    )) ?>">
                                    View Details
                                </a>
                            <?php endif; ?>

                            <form
                                method="post"
                                action="<?= e(url('saved-internship-action.php')) ?>"
                                class="m-0">

                                <?= csrf_field() ?>

                                <input
                                    type="hidden"
                                    name="return_to"
                                    value="saved-internships.php">

                                <input
                                    type="hidden"
                                    name="return_filters[q]"
                                    value="<?= e($savedQuery) ?>">

                                <input
                                    type="hidden"
                                    name="return_filters[availability]"
                                    value="<?= e($savedAvailability) ?>">

                                <input
                                    type="hidden"
                                    name="return_filters[sort]"
                                    value="<?= e($savedSort) ?>">

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove">

                                <input
                                    type="hidden"
                                    name="internship_id"
                                    value="<?= (int) $internship['internship_id'] ?>">

                                <button
                                    type="submit"
                                    class="btn btn-outline-danger">
                                    <i class="bi bi-bookmark-dash me-1"></i>
                                    Remove
                                </button>

                            </form>

                        </div>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . '/../app/views/student-footer.php'; ?>