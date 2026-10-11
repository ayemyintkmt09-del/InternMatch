<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentProfileController.php';
require_once __DIR__ . '/../app/controllers/StudentCvShareController.php';
require_once __DIR__ . '/../app/controllers/StudentPortfolioController.php';
require_once __DIR__ . '/../app/controllers/CvController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');

try {
    $profile = StudentProfileController::load(
        (int) $user['user_id']
    );

    
$cvSharing = StudentCvShareController::ensure(
    (int) $user['user_id']
);


    $completion = StudentProfileController::completion($profile);
    $hasUploadedCv = CvController::downloadPath($profile) !== null;

$statement = db()->prepare(
    'SELECT s.skill_name, ss.proficiency_level
     FROM student_skills AS ss
     JOIN skills AS s ON s.skill_id = ss.skill_id
     WHERE ss.student_id = :student_id
     ORDER BY s.skill_name'
);

$statement->execute([
    'student_id' => (int) $profile['student_id'],
]);


$skillRows = $statement->fetchAll();


$portfolioItems = StudentPortfolioController::all(
    (int) $user['user_id']
);



} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Unable to load your Digital CV.');
}

function im_cv_display(mixed $value): string
{
    $text = trim((string) $value);

    return e($text !== '' ? $text : 'Not added yet');
}


render_header($user, 'My Digital CV', 'digital-cv');
?>


<main class="im-cv-page" id="main-content" tabindex="-1">
    <div class="container">

        <div class="im-cv-actions">
            <div>
                <p class="dashboard-small-title">STUDENT DIGITAL CV</p>
                <h1>My Digital CV</h1>
                <p>Preview your professional information.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= e(url('student-profile.php')) ?>"
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>
                    Edit Profile
                </a>

                
<a href="<?= e(url('student-cv-sharing.php')) ?>"
   class="btn btn-outline-primary">

    <i class="bi bi-share me-1"></i>
    Share Digital CV

</a>


                <button type="button"
                        class="btn btn-primary"
                        onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    Print / Save PDF
                </button>
                
<?php if ($hasUploadedCv): ?>
    <a href="<?= e(url('cv-download.php')) ?>"
       class="btn btn-outline-primary">

        <i class="bi bi-file-earmark-arrow-down me-1"></i>
        Download Uploaded CV

    </a>
<?php endif; ?>

            </div>
        </div>

        <article class="im-cv-sheet">
            <header class="im-cv-hero">
                <span class="im-cv-avatar" aria-hidden="true">
                    <i class="bi bi-person-fill"></i>
                </span>

        <div>
            <h2><?= im_cv_display($profile['name'] ?? '') ?></h2>

            <!-- Digital CV ID -->
            <p class="im-cv-id">
                <i class="bi bi-patch-check"></i>
                Digital CV ID:
                <?= e($cvSharing['cv_id']) ?>
            </p>

            <p><?= im_cv_display($profile['degree'] ?? '') ?></p>

            <span>
                <i class="bi bi-geo-alt"></i>
                <?= im_cv_display($profile['location'] ?? '') ?>
            </span>
                    
<div class="im-cv-completion">
    <i class="bi bi-check-circle-fill"></i>

    <span>
        Profile <?= (int) $completion ?>% Complete
    </span>
</div>

                </div>
            </header>

            
<div class="im-cv-content">

    <section class="im-cv-section">
        <h3><i class="bi bi-person-lines-fill"></i> About Me</h3>

        <p class="im-cv-description">
            <?= nl2br(e(
                trim((string) ($profile['bio'] ?? ''))
                ?: 'Add an introduction in your profile.'
            )) ?>
        </p>
    </section>

    <section class="im-cv-section">
        <h3><i class="bi bi-mortarboard"></i> Education</h3>

        <div class="im-cv-details">
            <div>
                <strong>University</strong>
                <span><?= im_cv_display($profile['university'] ?? '') ?></span>
            </div>

            <div>
                <strong>Degree</strong>
                <span><?= im_cv_display($profile['degree'] ?? '') ?></span>
            </div>

            <div>
                <strong>Academic Year</strong>
                <span><?= im_cv_display($profile['academic_year'] ?? '') ?></span>
            </div>

            <div>
                <strong>Graduation Year</strong>
                <span><?= im_cv_display($profile['graduation_year'] ?? '') ?></span>
            </div>
        </div>
    </section>


    
<section class="im-cv-section">
    <h3><i class="bi bi-code-slash"></i> Technologies & Skills</h3>

    <?php if ($skillRows === []): ?>
        <p>No skills added yet.</p>
    <?php else: ?>
        <div class="im-cv-skills">
            <?php foreach ($skillRows as $skill): ?>
                <?php
                $level = (string) ($skill['proficiency_level'] ?? '');
                
$width = [
    'Beginner' => 25,
    'Intermediate' => 50,
    'Advanced' => 75,
    'Expert' => 100
][$level] ?? 0;

                ?>

                <div class="im-cv-skill">
                    <div class="im-cv-skill-label">
                        <strong><?= e($skill['skill_name']) ?></strong>
                        <span><?= e($level ?: 'Not rated') ?></span>
                    </div>

                    <div class="im-cv-skill-track"
                         role="img"
                         aria-label="<?= e($skill['skill_name'] . ': ' . ($level ?: 'Not rated')) ?>">
                        <div style="width: <?= $width ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="im-cv-note">Skill levels are self-reported, not verified assessment scores.</p>
    <?php endif; ?>
</section>

<section class="im-cv-section">
    <h3><i class="bi bi-bullseye"></i> Career Goals</h3>
    <p><?= nl2br(e((string) ($profile['career_interest'] ?? ''))) ?></p>

    <h4>Areas of Interest</h4>
    <p><?= im_cv_display($profile['interests'] ?? '') ?></p>
</section>



<section class="im-cv-section">
    <h3>
        <i class="bi bi-briefcase"></i>
        Internship Preferences
    </h3>

    <div class="im-cv-details">
        <div>
            <strong>Preferred Location</strong>
            <span>
                <?= im_cv_display($profile['preferred_location'] ?? '') ?>
            </span>
        </div>

        <div>
            <strong>Work Arrangement</strong>
            <span>
                <?= im_cv_display($profile['preferred_internship_type'] ?? '') ?>
            </span>
        </div>

        <div>
            <strong>Availability</strong>
            <span>
                <?= im_cv_display($profile['availability'] ?? '') ?>
            </span>
        </div>

        <div>
            <strong>Preferred Duration</strong>
            <span>
                <?= im_cv_display($profile['preferred_duration'] ?? '') ?>
            </span>
        </div>
    </div>
</section>



<section class="im-cv-section im-cv-portfolio">

    <h3>
        <i class="bi bi-collection"></i>
        Projects, Experience & Certificates
    </h3>

    <?php if ($portfolioItems === []): ?>

        <p>
            No portfolio entries added yet.
        </p>

    <?php else: ?>

        <?php foreach ($portfolioItems as $item): ?>

            <div class="im-cv-portfolio-item">

                <span class="im-portfolio-type">
                    <?= e(
                        StudentPortfolioController::TYPES[
                            $item['item_type']
                        ] ?? 'Entry'
                    ) ?>
                </span>

                <h4><?= e($item['title']) ?></h4>

                <?php if ($item['organization']): ?>
                    <p><?= e($item['organization']) ?></p>
                <?php endif; ?>

                <?php if ($item['description']): ?>
                    <p><?= nl2br(e($item['description'])) ?></p>
                <?php endif; ?>

                <?php if ($item['link_url']): ?>
                    <a href="<?= e($item['link_url']) ?>"
                       target="_blank"
                       rel="noopener noreferrer">
                        View Portfolio Link
                    </a>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</section>


<section class="im-cv-section">
    <h3><i class="bi bi-envelope"></i> Contact</h3>

    <div class="im-cv-details">
        <div>
            <strong>Email</strong>
            <span><?= im_cv_display($profile['email'] ?? '') ?></span>
        </div>

        <div>
            <strong>Phone</strong>
            <span><?= im_cv_display($profile['phone'] ?? '') ?></span>
        </div>
    </div>
</section>

        </div>
    </article>

    <p class="im-cv-privacy-note">
        <i class="bi bi-lock"></i>
        This Digital CV preview is private to your account.
        Review your contact details before exporting it.
    </p>
    </div>
</main>

<?php render_footer($user); ?>
