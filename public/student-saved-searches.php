<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentSavedSearchController.php';
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
            StudentSavedSearchController::save(
                $userId,
                $_POST
            );

            flash('saved_search_success', 'Search saved.');

        } elseif ($action === 'delete') {
            $id = filter_var(
                $_POST['saved_search_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($id === false) {
                throw new InvalidArgumentException(
                    'Invalid saved search.'
                );
            }

            StudentSavedSearchController::remove(
                $userId,
                $id
            );

            flash('saved_search_success', 'Search removed.');

        } else {
            throw new InvalidArgumentException(
                'Invalid action.'
            );
        }

        redirect('student-saved-searches.php');

    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();

    } catch (Throwable $exception) {
        error_log((string) $exception);

        http_response_code(500);
        $error = 'Unable to update saved searches.';
    }
}




try {
    $savedSearches = StudentSavedSearchController::all(
        $userId
    );
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Unable to load saved searches.');
}

$notice = take_flash('saved_search_success');

render_header(
    $user,
    'Saved Searches',
    'saved-searches'
);
?>





<main class="im-career-page"
      id="main-content"
      tabindex="-1">

    <div class="container py-5">

        <div class="im-career-heading">
            <div>
                <p class="dashboard-small-title">
                    INTERNSHIP DISCOVERY
                </p>

                <h1>Saved Searches</h1>

                <p>
                    Return to your favorite internship filters.
                    Searches run when you open them.
                </p>
            </div>

            <a class="btn btn-primary"
               href="<?= e(url('opportunities.php')) ?>">
                <i class="bi bi-search me-1"></i>
                Find Internships
            </a>
        </div>

        <?php if ($notice !== null): ?>
            <div class="alert alert-success" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>




        
<?php if ($savedSearches === []): ?>

    <section class="im-career-card">
        <h2>No saved searches yet</h2>

        <p>
            Choose filters on the Internships page,
            then save that search.
        </p>

        <a href="<?= e(url('opportunities.php')) ?>">
            Browse internships
        </a>
    </section>

<?php else: ?>

    <div class="im-career-grid">

        <?php foreach ($savedSearches as $search): ?>

            <article class="im-career-card">

                <span class="im-career-label">
                    SAVED SEARCH
                </span>

                <h2><?= e((string) $search['name']) ?></h2>

                <p>
                    <?= e((string) (
                        $search['keyword'] ?: 'All roles'
                    )) ?>
                    ·
                    <?= e((string) (
                        $search['location'] ?: 'All locations'
                    )) ?>
                </p>

                <p class="im-career-muted">
                    Arrangement:
                    <?= e((string) (
                        $search['internship_type'] ?: 'Any'
                    )) ?>
                </p>

                <div class="im-career-actions">

                    <a class="btn btn-primary"
                       href="<?= e(
                           StudentSavedSearchController::openUrl(
                               $search
                           )
                       ) ?>">
                        Open Search
                    </a>

                    <form method="post"
                          action="<?= e(url(
                              'student-saved-searches.php'
                          )) ?>">

                        <?= csrf_field() ?>

                        <input type="hidden"
                               name="action"
                               value="delete">

                        <input type="hidden"
                               name="saved_search_id"
                               value="<?= (int) $search['saved_search_id'] ?>">

                        <button type="submit"
                                class="btn btn-outline-danger">
                            Delete
                        </button>

                    </form>
                </div>
            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>




        <p class="im-career-muted mt-4">
            Saved searches are bookmarks,
            not automatic email or SMS alerts.
        </p>

    </div>
</main>

<?php render_footer($user); ?>
