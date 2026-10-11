<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CompanyReviewController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');
$userId = (int) $user['user_id'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            CompanyReviewController::save($userId, $_POST);

            flash(
                'review_success',
                'Review submitted for moderation.'
            );
        } elseif ($action === 'withdraw') {
            $id = filter_var(
                $_POST['application_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($id === false) {
                throw new InvalidArgumentException(
                    'Invalid review.'
                );
            }

            CompanyReviewController::withdraw($userId, $id);

            flash('review_success', 'Review withdrawn.');
        } else {
            throw new InvalidArgumentException(
                'Invalid action.'
            );
        }

        redirect('student-company-reviews.php');

    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();

    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        $error = 'Unable to update your review.';
    }
}



try {
    $eligibleReviews = CompanyReviewController::eligible(
        $userId
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Reviews are temporarily unavailable.');
}

$notice = take_flash('review_success');

render_header(
    $user,
    'My Company Reviews',
    'company-reviews'
);
?>



<main class="im-reviews-page" id="main-content" tabindex="-1">
    <div class="container">

        <header class="im-reviews-hero">
            <div>
                <p class="dashboard-small-title">
                    INTERNSHIP EXPERIENCE
                </p>

                <h1>My Company Reviews</h1>

                <p>
                    Share constructive feedback about
                    eligible internship experiences.
                </p>
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="alert alert-success" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-info">

Reviews are available for Accepted applications where
the application was submitted before the internship ended
and the scheduled end date has passed.

An Accepted application does not independently verify
that the internship was completed.

All reviews require administrator approval before
publication.
        </div>



        
<?php if ($eligibleReviews === []): ?>

    <section class="im-review-card">
        <h2>No eligible internships yet</h2>

        <p>
            Accepted applications for internships whose
            scheduled end dates have passed will appear here.
        </p>

        <a href="<?= e(url('my-applications.php')) ?>"
           class="btn btn-outline-primary">
            View My Applications
        </a>
    </section>

<?php else: ?>

    <div class="im-review-grid">

        <?php foreach ($eligibleReviews as $review): ?>

            <section class="im-review-card">

                <span class="im-review-type">
                    <?= e($review['company_name']) ?>
                </span>

                <h2><?= e($review['internship_title']) ?></h2>

                <?php if ($review['review_id']): ?>
                    <p>
                        Status:
                        <strong>
                            <?= e(ucfirst(
                                $review['review_status']
                            )) ?>
                        </strong>
                    </p>
                <?php endif; ?>

                <form method="post"
                      action="<?= e(url(
                          'student-company-reviews.php'
                      )) ?>">

                    <?= csrf_field() ?>

                    <input type="hidden"
                           name="action"
                           value="save">

                    <input type="hidden"
                           name="application_id"
                           value="<?= (int) $review['application_id'] ?>">

                    <div class="mb-3">
                        <label class="form-label">
                            Overall Rating
                        </label>

                        <select name="rating"
                                class="form-select"
                                required>

                            <?php for ($rating = 5; $rating >= 1; $rating--): ?>

                                <option value="<?= $rating ?>"
                                    <?= (int) ($review['rating'] ?? 5)
                                        === $rating
                                        ? 'selected' : '' ?>>

                                    <?= $rating ?> Stars
                                </option>

                            <?php endfor; ?>

                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Review Title
                        </label>

                        <input type="text"
                               name="title"
                               class="form-control"
                               minlength="5"
                               maxlength="120"
                               required
                               value="<?= e(
                                   $review['review_title'] ?? ''
                               ) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            What went well?
                        </label>

                        <textarea name="positives"
                                  class="form-control"
                                  minlength="20"
                                  maxlength="1500"
                                  rows="4"
                                  required><?= e(
                                      $review['positives'] ?? ''
                                  ) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            What could improve?
                        </label>

                        <textarea name="improvements"
                                  class="form-control"
                                  maxlength="1500"
                                  rows="3"><?= e(
                                      $review['improvements'] ?? ''
                                  ) ?></textarea>
                    </div>

                    <button type="submit"
                            class="btn btn-primary">
                        <?= $review['review_id']
                            ? 'Resubmit Review'
                            : 'Submit Review' ?>
                    </button>
                </form>


                
                <?php if (
                    $review['review_id']
                    && $review['review_status'] !== 'withdrawn'
                ): ?>

                    <form method="post"
                          action="<?= e(url(
                              'student-company-reviews.php'
                          )) ?>"
                          class="mt-3">

                        <?= csrf_field() ?>

                        <input type="hidden"
                               name="action"
                               value="withdraw">

                        <input type="hidden"
                               name="application_id"
                               value="<?= (int) $review['application_id'] ?>">

                        <button type="submit"
                                class="btn btn-outline-danger">
                            Withdraw Review
                        </button>
                    </form>

                <?php endif; ?>

            </section>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

    </div>
</main>

<?php render_footer($user); ?>
