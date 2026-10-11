<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentCvShareController.php';
require_once __DIR__ . '/../app/layout.php';

header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');

$token = $_GET['token'] ?? null;

if (
    !is_string($token)
    || !preg_match('/\A[a-f0-9]{64}\z/D', $token)
) {
    http_response_code(404);
    exit('This Digital CV link is unavailable.');
}

try {
    $profile = StudentCvShareController::publicProfile($token);

    if ($profile === null) {
        http_response_code(404);
        exit('This Digital CV link is unavailable.');
    }

    $skills = StudentCvShareController::publicSkills(
        (int) $profile['student_id']
    );

    $portfolio = StudentCvShareController::publicPortfolio(
        (int) $profile['user_id']
    );

} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(503);

    exit('This Digital CV is temporarily unavailable.');
}

$user = current_user();

render_header($user, 'Digital CV', '');
?>

<main class="im-sharing-page"
      id="main-content"
      tabindex="-1">

    <div class="container py-5">

        <article class="im-share-panel">

            <div class="im-public-cv-heading">

                <p>
                    INTERNMATCH DIGITAL CV ·
                    <?= e($profile['cv_id']) ?>
                </p>

                <h1><?= e($profile['name']) ?></h1>

                <p>
                    <?= e((string) ($profile['degree'] ?? '')) ?>
                </p>

                <p>
                    <?= e((string) ($profile['university'] ?? '')) ?>
                </p>

            </div>

            <?php if (trim((string) ($profile['bio'] ?? '')) !== ''): ?>

                <section class="im-public-cv-section">
                    <h2>About Me</h2>
                    <p><?= nl2br(e($profile['bio'])) ?></p>
                </section>

            <?php endif; ?>

            <?php if (
                trim((string) ($profile['career_interest'] ?? '')) !== ''
            ): ?>

                <section class="im-public-cv-section">
                    <h2>Career Interests</h2>
                    <p><?= e($profile['career_interest']) ?></p>
                </section>

            <?php endif; ?>

<section class="im-public-cv-section">

    <h2>Skills</h2>

    <?php if (!$skills): ?>

        <p>No skills listed.</p>

    <?php else: ?>

        <div class="im-public-cv-skills">

            <?php foreach ($skills as $skill): ?>

                <span class="im-public-skill">

                    <?= e($skill['skill_name']) ?>

                    <small>
                        <?= e((string) $skill['proficiency_level']) ?>
                        · self-reported
                    </small>

                </span>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<section class="im-public-cv-section">

    <h2>Selected Portfolio</h2>

    <?php if (!$portfolio): ?>

        <p>No publicly shared portfolio entries.</p>

    <?php else: ?>

        <div class="im-public-cv-cards">

            <?php foreach ($portfolio as $entry): ?>

                <article class="im-public-cv-item">

                    <span class="im-portfolio-type">
                        <?= e(ucfirst((string) $entry['item_type'])) ?>
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
                            Open portfolio link
                        </a>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<p class="small text-muted mt-4">
    Self-reported profile information.
    InternMatch has not verified qualifications or skills.
</p>

        </article>
    </div>
</main>

<?php render_footer($user); ?>
