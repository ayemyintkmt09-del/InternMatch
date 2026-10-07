<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/AdminVerificationController.php';

require_role('admin');

$companies = AdminVerificationController::all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Company Verification</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">
</head>

<body>

<main class="container py-5">

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

            <?php if ($companies === []): ?>

                <p class="text-muted mb-0">
                    No company accounts found.
                </p>

            <?php else: ?>

                <div class="table-responsive">

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
                                        <?= e($company['company_name']) ?>
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
                                    <?= $company['verification_doc_path']
                                        ? 'Uploaded'
                                        : 'Not uploaded' ?>
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

</body>
</html>