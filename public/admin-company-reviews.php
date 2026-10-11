<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyReviewController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('admin');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $reviewId = filter_var(
            $_POST['review_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($reviewId === false) {
            throw new InvalidArgumentException(
                'Invalid review.'
            );
        }

        $decision = $_POST['decision'] ?? null;
        $note = $_POST['note'] ?? null;

        if (!is_string($decision) || !is_string($note)) {
            throw new InvalidArgumentException(
                'Invalid moderation action.'
            );
        }

        CompanyReviewController::moderate(
            (int) $user['user_id'],
            $reviewId,
            $decision,
            $note
        );

        flash('moderation_success', 'Review updated.');

        redirect('admin-company-reviews.php');

    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();

    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        $error = 'Review moderation failed.';
    }
}



try {
    $reviews = CompanyReviewController::queue();
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Unable to load review moderation.');
}

$notice = take_flash('moderation_success');

render_header(
    $user,
    'Company Review Moderation',
    'review-moderation'
);
?>



<main class="im-reviews-page" id="main-content" tabindex="-1">
    <div class="container">

        <header class="im-reviews-hero">
            <div>
                <p class="dashboard-small-title">
                    ADMIN MODERATION
                </p>

                <h1>Company Reviews</h1>

                <p>
                    Approve new reviews, reject inappropriate
                    submissions, or hide published content.
                </p>
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($reviews === []): ?>

            <div class="im-review-card">
                <h2>No reviews awaiting management</h2>
            </div>

        <?php else: ?>

            <div class="im-review-grid">

                <?php foreach ($reviews as $review): ?>

                    <article class="im-review-card">

                        <span class="im-review-type">
                            <?= e($review['company_name']) ?>
                        </span>

                        <h2><?= e($review['title']) ?></h2>

                        <p>
                            Reviewer:
                            <?= e($review['student_name']) ?>
                        </p>

                        <p>
                            Rating:
                            <strong>
                                <?= (int) $review['rating'] ?>/5
                            </strong>
                        </p>

                        <p>
                            Status:
                            <?= e($review['review_status']) ?>
                        </p>

                        <h3>Positive Experience</h3>
                        <p>
                            <?= nl2br(e($review['positives'])) ?>
                        </p>

                        <h3>Suggested Improvements</h3>
                        <p>
                            <?= nl2br(e(
                                $review['improvements'] ?: 'None'
                            )) ?>
                        </p>


                        
                        <form method="post"
                              action="<?= e(url(
                                  'admin-company-reviews.php'
                              )) ?>">

                            <?= csrf_field() ?>

                            <input type="hidden"
                                   name="review_id"
                                   value="<?= (int) $review['review_id'] ?>">

                            <label class="form-label">
                                Moderation Notes
                            </label>

                            <textarea name="note"
                                      class="form-control mb-3"
                                      maxlength="500"
                                      rows="3"></textarea>

                            <?php if (
                                $review['review_status'] === 'pending'
                            ): ?>

                                <button type="submit"
                                        name="decision"
                                        value="approved"
                                        class="btn btn-primary">
                                    Approve
                                </button>

                                <button type="submit"
                                        name="decision"
                                        value="rejected"
                                        class="btn btn-outline-danger">
                                    Reject
                                </button>

                            <?php else: ?>

                                <button type="submit"
                                        name="decision"
                                        value="hidden"
                                        class="btn btn-outline-danger">
                                    Hide Published Review
                                </button>

                            <?php endif; ?>

                        </form>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</main>

<?php render_footer($user); ?>
