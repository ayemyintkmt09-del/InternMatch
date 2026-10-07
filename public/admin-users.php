<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminUserController.php';

require_role('admin');

$getText = static function (string $key): string {
    return is_string($_GET[$key] ?? null)
        ? trim($_GET[$key])
        : '';
};

$query = $getText('q');
$role = $getText('role');
$status = $getText('status');

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = is_int($page) && $page > 0 ? $page : 1;

$pageUrl = static function (int $number) use (
    $query,
    $role,
    $status
): string {
    return url('admin-users.php?' . http_build_query([
        'q' => $query,
        'role' => $role,
        'status' => $status,
        'page' => $number,
    ]));
};

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        $targetId = filter_input(
            INPUT_POST,
            'user_id',
            FILTER_VALIDATE_INT
        );

        $expected = is_string($_POST['expected_status'] ?? null)
            ? $_POST['expected_status']
            : '';

        $newStatus = is_string($_POST['new_status'] ?? null)
            ? $_POST['new_status']
            : '';

        try {
            AdminUserController::changeStatus(
                $targetId ?: 0,
                $expected,
                $newStatus
            );

            flash(
                'admin_users_success',
                $newStatus === 'suspended'
                    ? 'Account suspended.'
                    : 'Account reactivated.'
            );
        } catch (InvalidArgumentException $exception) {
            flash('admin_users_error', $exception->getMessage());
        }

        header('Location: ' . $pageUrl($page), true, 303);
        exit;
    }

    $result = AdminUserController::search(
        $query,
        $role,
        $status,
        $page
    );

    $success = take_flash('admin_users_success');
    $error = take_flash('admin_users_error');
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit(e($exception->getMessage()));
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('User management could not be loaded.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>User Management - InternMatch</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        href="<?= e(url('css/style.css')) ?>"
        rel="stylesheet">
</head>

<body>

<main class="container py-5">

    <div class="d-flex flex-wrap justify-content-between
                align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2">User Management</h1>
            <p class="text-muted mb-0">
                Search accounts and manage login access.
            </p>
        </div>

        <a
            href="<?= e(url('admin-dashboard.php')) ?>"
            class="btn btn-outline-secondary">
            Back to Dashboard
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

    <div class="card mb-4">
        <div class="card-body">
            <form
                method="get"
                action="<?= e(url('admin-users.php')) ?>"
                class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label for="q" class="form-label">
                        Name or email
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
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">All roles</option>
                        <?php foreach (
                            ['student', 'company', 'admin'] as $option
                        ): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= $role === $option ? 'selected' : '' ?>>
                                <?= e(ucfirst($option)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="form-select">
                        <option value="">All statuses</option>
                        <?php foreach (
                            ['active', 'pending', 'suspended'] as $option
                        ): ?>
                            <option
                                value="<?= e($option) ?>"
                                <?= $status === $option ? 'selected' : '' ?>>
                                <?= e(ucfirst($option)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        Search
                    </button>
                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e(url('admin-users.php')) ?>">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <p>
        <?= number_format($result['total']) ?> matching account(s)
    </p>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($result['items'] === []): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            No accounts match your search.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($result['items'] as $account): ?>

                    <?php
                    $badgeClass = match ($account['status']) {
                        'active' => 'bg-success',
                        'suspended' => 'bg-danger',
                        default => 'bg-warning text-dark',
                    };

                    $canChange = $account['role'] !== 'admin'
                        && in_array(
                            $account['status'],
                            ['active', 'suspended'],
                            true
                        );

                    $nextStatus = $account['status'] === 'active'
                        ? 'suspended'
                        : 'active';

                    $actionLabel = $nextStatus === 'suspended'
                        ? 'Suspend'
                        : 'Reactivate';
                    ?>

                    <tr>
                        <td><?= e($account['name']) ?></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e(ucfirst($account['role'])) ?></td>
                        <td>
                            <span class="badge <?= e($badgeClass) ?>">
                                <?= e(ucfirst($account['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($canChange): ?>

                                <form
                                    method="post"
                                    action="<?= e($pageUrl($result['page'])) ?>"
                                    onsubmit="return confirm('Apply this account status change?');">

                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $account['user_id'] ?>">

                                    <input
                                        type="hidden"
                                        name="expected_status"
                                        value="<?= e($account['status']) ?>">

                                    <input
                                        type="hidden"
                                        name="new_status"
                                        value="<?= e($nextStatus) ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-sm <?= $nextStatus === 'suspended'
                                            ? 'btn-outline-danger'
                                            : 'btn-outline-success' ?>"
                                        aria-label="<?= e(
                                            $actionLabel . ' ' . $account['email']
                                        ) ?>">
                                        <?= e($actionLabel) ?>
                                    </button>
                                </form>

                            <?php else: ?>
                                <span class="text-muted small">
                                    <?= $account['role'] === 'admin'
                                        ? 'Protected admin'
                                        : 'Pending account' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav
        class="d-flex justify-content-between align-items-center mt-4"
        aria-label="User results pages">

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
        Suspension blocks login and access to protected pages.
        Reactivation restores login access. Company verification
        is managed separately.
    </p>

</main>

</body>
</html>