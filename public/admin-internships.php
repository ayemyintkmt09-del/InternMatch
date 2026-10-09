<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminInternshipController.php';

require_role('admin');

$query = is_string($_GET['q'] ?? null)
    ? trim($_GET['q'])
    : '';

$status = is_string($_GET['status'] ?? null)
    ? $_GET['status']
    : '';

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = is_int($page) && $page > 0 ? $page : 1;

$pageUrl = static function (int $number) use (
    $query,
    $status
): string {
    return url('admin-internships.php?' . http_build_query([
        'q' => $query,
        'status' => $status,
        'page' => $number,
    ]));
};

try {
    $result = AdminInternshipController::search(
        $query,
        $status,
        $page
    );
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Internships could not be loaded.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Internship Management - InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
       href="<?= e(asset_url('css/style.css')) ?>"
        rel="stylesheet">
</head>

<body>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">

        <div>
            <h1 class="h2">Internship Management</h1>
            <p class="text-muted mb-0">
                Search posts and inspect their details and availability.
            </p>
        </div>

        <a
            class="btn btn-outline-secondary"
            href="<?= e(url('admin-dashboard.php')) ?>">
            Back to Dashboard
        </a>

    </div>

    <div class="card mb-4">
        <div class="card-body">

            <form
                method="get"
                action="<?= e(url('admin-internships.php')) ?>"
                class="row g-3 align-items-end">

                <div class="col-md-6">
                    <label for="q" class="form-label">
                        Internship title or company
                    </label>

                    <input
                        id="q"
                        name="q"
                        type="search"
                        class="form-control"
                        maxlength="100"
                        value="<?= e($query) ?>">
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">
                        Post status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select">

                        <option value="">All statuses</option>

                        <?php foreach (
                            ['Draft', 'Published', 'Closed', 'Expired']
                            as $option
                        ): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= $status === $option
                                    ? 'selected' : '' ?>>
                                <?= e($option) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>

                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e(url('admin-internships.php')) ?>">
                        Reset
                    </a>
                </div>

            </form>

        </div>
    </div>

    <p>
        <?= number_format($result['total']) ?> matching internship(s)
    </p>

    <div class="card">
        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead>
                    <tr>
                        <th scope="col">Internship</th>
                        <th scope="col">Company</th>
                        <th scope="col">Status</th>
                        <th scope="col">Deadline</th>
                        <th scope="col">Applications</th>
                        <th scope="col">Student availability</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result['items'] === []): ?>
                    <tr>
                        <td colspan="6" class="empty-table-cell">
                            No internships match your search.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($result['items'] as $internship): ?>

                    <?php
                    $badgeClass = match ($internship['status']) {
                        'Published' => 'bg-success',
                        'Closed' => 'bg-secondary',
                        'Expired' => 'bg-danger',
                        default => 'bg-warning text-dark',
                    };

                    $available =
                        (int) $internship['available_to_students'] === 1;
                    ?>

                    <tr>
                        <td>
                            <strong>
                                <?= e($internship['title']) ?>
                            </strong>

                            <div class="small text-muted">
                                #<?= (int) $internship['internship_id'] ?>
                                ·
                                <?= e(
                                    $internship['field_name']
                                    ?: 'No academic field'
                                ) ?>
                            </div>

                            <details class="mt-2">
                                <summary class="text-primary">
                                    View details
                                </summary>

                                <div class="py-3 text-break">
                                    <p>
                                        <strong>Location:</strong>
                                        <?= e(
                                            $internship['location']
                                            ?: 'Not provided'
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Type:</strong>
                                        <?= e($internship['internship_type']) ?>
                                    </p>

                                    <p>
                                        <strong>Duration:</strong>
                                        <?= e(
                                            $internship['duration']
                                            ?: 'Not provided'
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Positions:</strong>
                                        <?= (int) $internship['interns_needed'] ?>
                                    </p>

                                    <p>
                                        <strong>Start date:</strong>
                                        <?= e(
                                            $internship['start_date']
                                            ?: 'Not provided'
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>End date:</strong>
                                        <?= e(
                                            $internship['end_date']
                                            ?: 'Not provided'
                                        ) ?>
                                    </p>

                                    <p class="mb-1">
                                        <strong>Description:</strong>
                                    </p>

                                    <div>
                                        <?= nl2br(e(
                                            $internship['description']
                                        )) ?>
                                    </div>
                                </div>
                            </details>
                        </td>

                        <td>
                            <a
                                href="<?= e(url(
                                    'admin-verification-review.php?company_id='
                                    . (int) $internship['company_id']
                                )) ?>">
                                <?= e($internship['company_name']) ?>
                            </a>

                            <div class="small text-muted">
                                Verification:
                                <?= e(ucfirst(
                                    $internship['verification_status']
                                )) ?>
                            </div>

                            <div class="small text-muted">
                                Account:
                                <?= e(ucfirst(
                                    $internship['company_account_status']
                                )) ?>
                            </div>
                        </td>

                        <td>
                            <span class="badge <?= e($badgeClass) ?>">
                                <?= e($internship['status']) ?>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <?= e(
                                $internship['deadline'] ?: 'Not provided'
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (int) $internship['application_count']
                            ) ?>
                        </td>

                        <td>
                            <span class="badge <?= $available
                                ? 'bg-success'
                                : 'bg-secondary' ?>">
                                <?= $available
                                    ? 'Available'
                                    : 'Unavailable' ?>
                            </span>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        </div>
    </div>

    <nav
        class="d-flex justify-content-between align-items-center mt-4"
        aria-label="Internship result pages">

        <div>
            <?php if ($result['page'] > 1): ?>
                <a
                    class="btn btn-outline-primary"
                    href="<?= e($pageUrl($result['page'] - 1)) ?>">
                    Previous
                </a>
            <?php endif; ?>
        </div>

        <span>
            Page <?= $result['page'] ?> of <?= $result['pages'] ?>
        </span>

        <div>
            <?php if ($result['page'] < $result['pages']): ?>
                <a
                    class="btn btn-outline-primary"
                    href="<?= e($pageUrl($result['page'] + 1)) ?>">
                    Next
                </a>
            <?php endif; ?>
        </div>

    </nav>

    <p class="text-muted small mt-4">
        Available internships are published by verified companies
        with active company accounts and deadlines that have not passed.
        Application counts include withdrawn applications.
    </p>

</main>

</body>
</html>