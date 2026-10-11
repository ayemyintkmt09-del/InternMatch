<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentCvShareController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');
$userId = (int) $user['user_id'];
$error = null;

try {
    $share = StudentCvShareController::ensure($userId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_post();
        verify_csrf();

        try {
            $action = $_POST['action'] ?? null;

            if ($action === 'generate') {
                $_SESSION['cv_new_link_once'] =
                    StudentCvShareController::generateLink($userId);

                flash(
                    'cv_share_notice',
                    'A new sharing link was created. Previous links no longer work.'
                );

            } elseif ($action === 'disable') {
                StudentCvShareController::disable($userId);
                unset($_SESSION['cv_new_link_once']);

                flash(
                    'cv_share_notice',
                    'Public sharing has been disabled.'
                );

            } else {
                throw new InvalidArgumentException(
                    'Unknown sharing action.'
                );
            }

            redirect('student-cv-sharing.php');

        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
            http_response_code(422);
        }
    }

    $share = StudentCvShareController::ensure($userId);

    $oneTimeLink = $_SESSION['cv_new_link_once'] ?? null;
    unset($_SESSION['cv_new_link_once']);

    $notice = take_flash('cv_share_notice');

} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);

    exit('Digital CV sharing is temporarily unavailable.');
}

render_header($user, 'Digital CV Sharing', 'cv-sharing');
?>



<main class="im-sharing-page"
      id="main-content"
      tabindex="-1">

    <div class="container py-5">

        <section class="im-share-panel">

            <p class="text-muted mb-1">MY DIGITAL CV</p>

            <h1>Sharing & Privacy</h1>

            <p>
                Share only the information you intend
                to make public.
            </p>

            <p>
                <strong>Digital CV ID:</strong>
                <?= e($share['cv_id']) ?>
            </p>

            <p>
                <strong>Sharing:</strong>

                <?= (int) $share['is_active'] === 1
                    ? 'Enabled'
                    : 'Disabled' ?>
            </p>

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

            <div class="alert alert-warning">
                Anyone with an active link can see your
                name, degree, university, bio, career goal,
                skills, and portfolio items you marked Public.

                Never put phone numbers or private details
                in public descriptions.

                Uploaded PDF CVs, email addresses, and
                application records are not included.
            </div>



            
<?php if (is_string($oneTimeLink)): ?>

    <label for="imShareUrl"
           class="form-label fw-semibold">
        Copy your new sharing link now
    </label>

    <div class="input-group mb-3">
        <input id="imShareUrl"
               class="form-control"
               readonly
               value="<?= e($oneTimeLink) ?>">

        <button type="button"
                id="imCopyShareLink"
                class="btn btn-primary">
            Copy link
        </button>
    </div>

    <p class="small text-muted">
        For your privacy, this link appears once.
        Generate a new one if you lose it.
    </p>

<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mt-3">

    <form method="post"
          action="<?= e(url('student-cv-sharing.php')) ?>">

        <?= csrf_field() ?>

        <input type="hidden"
               name="action"
               value="generate">

        <button type="submit" class="btn btn-primary">
            Generate / Replace Share Link
        </button>
    </form>

    <?php if ((int) $share['is_active'] === 1): ?>

        <form method="post"
              action="<?= e(url('student-cv-sharing.php')) ?>">

            <?= csrf_field() ?>

            <input type="hidden"
                   name="action"
                   value="disable">

            <button type="submit"
                    class="btn btn-outline-danger">
                Disable Sharing
            </button>
        </form>

    <?php endif; ?>

    <a class="btn btn-outline-secondary"
       href="<?= e(url('student-portfolio.php')) ?>">
        Manage Portfolio Visibility
    </a>

</div>

<p class="small text-muted mt-3">
    Localhost links are only accessible on your own computer.
    Public sharing requires a properly configured HTTPS website.
</p>

        </section>
    </div>
</main>

<script>
(() => {
    const input = document.getElementById('imShareUrl');
    const button = document.getElementById('imCopyShareLink');

    if (!input || !button) return;

    button.addEventListener('click', async () => {
        const fullUrl = new URL(
            input.value,
            window.location.href
        ).href;

        try {
            await navigator.clipboard.writeText(fullUrl);
            button.textContent = 'Copied!';
        } catch (_) {
            input.value = fullUrl;
            input.select();
            button.textContent = 'Select and copy the URL';
        }
    });
})();
</script>

<?php render_footer($user); ?>
