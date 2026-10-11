<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyRecruitmentController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('company');
$userId = (int) $user['user_id'];

try {
    $summary = CompanyRecruitmentController::summary($userId);

    $interviews =
        CompanyRecruitmentController::upcomingInterviews($userId);

} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Recruitment information could not be loaded.');
}

render_header(
    $user,
    'Recruitment Overview',
    'recruitment'
);
?>




<main class="im-recruit-page"
      id="main-content"
      tabindex="-1">

    <div class="container">

        <div class="im-recruit-hero">

            <div>
                <p class="dashboard-small-title">
                    COMPANY RECRUITMENT
                </p>

                <h1>Recruitment Overview</h1>

                <p>
                    Track applications, review candidates,
                    and manage upcoming interviews.
                </p>
            </div>

            <a href="<?= e(url('company-applications.php')) ?>"
               class="btn btn-primary">
                <i class="bi bi-people me-1"></i>
                Manage Applicants
            </a>

        </div>


        
<section class="im-recruit-panel">

    <h2>Application Pipeline</h2>

    <div class="im-recruit-stat-grid">

        <?php foreach ([
            'Total Applications' => 'total',
            'Pending' => 'pending',
            'Under Review' => 'under_review',
            'Shortlisted' => 'shortlisted',
            'Accepted' => 'accepted',
            'Rejected' => 'rejected'
        ] as $label => $key): ?>

            <div class="im-recruit-stat">

                <span><?= e($label) ?></span>

                <strong>
                    <?= (int) ($summary[$key] ?? 0) ?>
                </strong>

            </div>

        <?php endforeach; ?>

    </div>

</section>




<section class="im-recruit-panel">

    <h2>Recruitment Stages</h2>

    <div class="im-recruit-stages">

        <?php foreach ([
            'Pending' => 'pending',
            'Under Review' => 'under_review',
            'Shortlisted' => 'shortlisted',
            'Accepted' => 'accepted',
            'Rejected' => 'rejected',
            'Withdrawn' => 'withdrawn'
        ] as $status => $key): ?>

            <a href="<?= e(url(
    'company-applications.php?status='
    . rawurlencode($status)
)) ?>"
               class="im-recruit-stage">

                <div>
                    <strong><?= e($status) ?></strong>
                    <small>View applications</small>
                </div>

                <span>
                    <?= (int) ($summary[$key] ?? 0) ?>
                </span>

                <i class="bi bi-arrow-right"></i>

            </a>

        <?php endforeach; ?>

    </div>

</section>




<section class="im-recruit-panel">

    <div class="d-flex justify-content-between
                align-items-center flex-wrap gap-2 mb-3">

        <h2 class="mb-0">Upcoming Interviews</h2>

        <span class="text-muted small">
            Next 10 scheduled interviews
        </span>
    </div>

    <?php if ($interviews === []): ?>

        <div class="im-recruit-empty">
            <i class="bi bi-calendar-check"></i>

            <h3>No upcoming interviews</h3>

            <p>
                Interviews scheduled for shortlisted
                or under-review applicants will appear here.
            </p>
        </div>

    <?php else: ?>

        <div class="im-recruit-interviews">

            <?php foreach ($interviews as $interview): ?>

                <div class="im-recruit-interview">

                    <div class="im-interview-date">
                        <i class="bi bi-calendar-event"></i>

                        <strong>
                            <?= e(
                                (new DateTimeImmutable(
                                    $interview['interview_date']
                                ))->format('d M Y')
                            ) ?>
                        </strong>

                        <span>
                            <?= e(
                                (new DateTimeImmutable(
                                    $interview['interview_date']
                                ))->format('g:i A')
                            ) ?>
                        </span>
                    </div>

                    <div class="im-interview-info">
                        <strong>
                            <?= e($interview['student_name']) ?>
                        </strong>

                        <span>
                            <?= e($interview['title']) ?>
                        </span>

                        <small>
                            <?= e($interview['status']) ?>
                        </small>
                    </div>

                    <a class="btn btn-outline-primary btn-sm"
                       href="<?= e(url(
                           'company-application-review.php?id='
                           . (int) $interview['application_id']
                       )) ?>">
                        Review
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>



    </div>
</main>

<?php render_footer($user); ?>
