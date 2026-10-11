<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentPortfolioController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');
$userId = (int) $user['user_id'];

$error = null;

$blank = [
    'portfolio_id' => '0',
    'item_type' => 'project',
    'title' => '',
    'organization' => '',
    'description' => '',
    'link_url' => '',
    'start_date' => '',
    'end_date' => '',
    'is_public' => 0,
];

$form = $blank;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    try {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'delete') {
            $deleteId = filter_var(
                $_POST['portfolio_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$deleteId || $deleteId < 1) {
                throw new InvalidArgumentException(
                    'Invalid entry.'
                );
            }

            StudentPortfolioController::delete(
                $userId,
                $deleteId
            );

            flash('portfolio_success', 'Entry deleted.');
        } elseif ($action === 'save') {
            StudentPortfolioController::save(
                $userId,
                $_POST
            );

            flash('portfolio_success', 'Portfolio saved.');
        } else {
            throw new InvalidArgumentException(
                'Invalid action.'
            );
        }

        redirect('student-portfolio.php');

    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();

        foreach ($blank as $key => $default) {
            $form[$key] = is_string($_POST[$key] ?? null)
                ? $_POST[$key]
                : $default;
        }

        
$form['is_public'] =
    ($_POST['is_public'] ?? '0') === '1' ? 1 : 0;


    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);

        $error = 'Unable to save portfolio right now.';
    }
}


if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    && isset($_GET['edit'])
) {
    $editId = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {
        $form = StudentPortfolioController::one(
            $userId,
            $editId
        ) ?? $blank;
    }
}

$entries = StudentPortfolioController::all($userId);
$notice = take_flash('portfolio_success');

render_header($user, 'My Portfolio', 'portfolio');
?>




<main class="im-portfolio-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="im-section-heading">
            <div>
                <p class="dashboard-small-title">
                    STUDENT CAREER PROFILE
                </p>

                <h1>My Portfolio</h1>

                <p>
                    Showcase your projects, work experience,
                    and professional certificates.
                </p>
            </div>

            <a class="btn btn-outline-primary"
               href="<?= e(url('student-digital-cv.php')) ?>">
                <i class="bi bi-file-earmark-person"></i>
                View Digital CV
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($notice): ?>
            <div class="alert alert-success" role="status">
                <?= e($notice) ?>
            </div>
        <?php endif; ?>


        
<section class="im-portfolio-panel">
    <h2>
        <?= (int) ($form['portfolio_id'] ?? 0) > 0
            ? 'Edit Entry'
            : 'Add New Entry' ?>
    </h2>

    <form action="<?= e(url('student-portfolio.php')) ?>"
          method="post"
          class="im-portfolio-form">

        <?= csrf_field() ?>

        <input type="hidden" name="action" value="save">

        <input type="hidden"
               name="portfolio_id"
               value="<?= (int) ($form['portfolio_id'] ?? 0) ?>">

        <div>
            <label for="portfolioType">Entry Type</label>
            
<span class="im-visibility-label">

    <i class="bi <?= (int) ($form['is_public'] ?? 0) === 1
        ? 'bi-globe2'
        : 'bi-lock-fill' ?>"></i>

    <?= (int) ($form['is_public'] ?? 0) === 1
        ? 'Public'
        : 'Private' ?>

</span>


            <select id="portfolioType"
                    name="item_type"
                    class="form-select"
                    required>

                <?php foreach (
                    StudentPortfolioController::TYPES as $type => $label
                ): ?>
                    <option value="<?= e($type) ?>"
                        <?= ($form['item_type'] ?? '') === $type
                            ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="portfolioTitle">Title</label>
            <input type="text"
                   class="form-control"
                   id="portfolioTitle"
                   name="title"
                   maxlength="150"
                   required
                   value="<?= e((string) ($form['title'] ?? '')) ?>">
        </div>

        <div>
            <label for="portfolioOrg">Company / Institution</label>
            <input type="text"
                   class="form-control"
                   id="portfolioOrg"
                   name="organization"
                   maxlength="150"
                   value="<?= e((string) ($form['organization'] ?? '')) ?>">
        </div>

        <div>
            <label for="portfolioUrl">Link (Optional)</label>
            <input type="url"
                   class="form-control"
                   id="portfolioUrl"
                   name="link_url"
                   maxlength="500"
                   value="<?= e((string) ($form['link_url'] ?? '')) ?>">
        </div>

        <div>
            <label for="portfolioStart">Start Date</label>
            <input type="date"
                   class="form-control"
                   id="portfolioStart"
                   name="start_date"
                   value="<?= e((string) ($form['start_date'] ?? '')) ?>">
        </div>

        <div>
            <label for="portfolioEnd">End Date</label>
            <input type="date"
                   class="form-control"
                   id="portfolioEnd"
                   name="end_date"
                   value="<?= e((string) ($form['end_date'] ?? '')) ?>">
        </div>

        <div class="im-portfolio-wide">
            <label for="portfolioDesc">Description</label>
            <textarea id="portfolioDesc"
                      name="description"
                      maxlength="3000"
                      rows="4"
                      class="form-control"><?= e((string) ($form['description'] ?? '')) ?></textarea>
        </div>

        
<div class="im-portfolio-wide">

    <div class="form-check">

        <input class="form-check-input"
               type="checkbox"
               name="is_public"
               id="portfolioPublic"
               value="1"
               <?= (int) ($form['is_public'] ?? 0) === 1
                   ? 'checked'
                   : '' ?>>

        <label class="form-check-label"
               for="portfolioPublic">

            Show this entry on my public Digital CV

        </label>
    </div>

    <p class="small text-muted mt-2">
        Private entries are visible only in your
        logged-in portfolio and Digital CV preview.
    </p>

</div>


        <div class="im-portfolio-wide">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i>
                Save Entry
            </button>

            <a class="btn btn-outline-secondary"
               href="<?= e(url('student-portfolio.php')) ?>">
                Clear
            </a>
        </div>
    </form>
</section>



<section class="im-portfolio-panel">
    <h2>My Entries</h2>

    <?php if (!$entries): ?>
        <p>
            No portfolio entries yet.
            Add your first project, experience, or certificate.
        </p>
    <?php else: ?>

        <div class="im-portfolio-grid">
            <?php foreach ($entries as $entry): ?>

                <article class="im-portfolio-card">

                    <span class="im-portfolio-type">
                        <?= e(
                            StudentPortfolioController::TYPES[
                                $entry['item_type']
                            ] ?? 'Entry'
                        ) ?>
                    </span>

                    <h3><?= e($entry['title']) ?></h3>

                    <?php if ($entry['organization']): ?>
                        <p><?= e($entry['organization']) ?></p>
                    <?php endif; ?>

                    <?php if ($entry['description']): ?>
                        <p>
                            <?= nl2br(e($entry['description'])) ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($entry['link_url']): ?>
                        <a href="<?= e($entry['link_url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer">
                            View Link
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php endif; ?>

                    <div class="im-portfolio-card-actions">

                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= e(url(
                               'student-portfolio.php?edit='
                               . (int) $entry['portfolio_id']
                           )) ?>">
                            Edit
                        </a>

                        <form method="post"
                              action="<?= e(url('student-portfolio.php')) ?>">

                            <?= csrf_field() ?>

                            <input type="hidden"
                                   name="action"
                                   value="delete">

                            <input type="hidden"
                                   name="portfolio_id"
                                   value="<?= (int) $entry['portfolio_id'] ?>">

                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                Delete
                            </button>
                        </form>

                    </div>
                </article>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>


    </div>
</main>

<?php render_footer($user); ?>
