<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';
require_once __DIR__ . '/../app/controllers/MatchingController.php';
require_once __DIR__ . '/../app/controllers/ApplicationController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');
$userId = (int) $user['user_id'];




try {
    $items = MatchingController::recommendations($userId);
    
} catch (Throwable $exception) {
    error_log((string) $exception);

    http_response_code(500);
    exit('Recommendations could not be loaded.');
}

render_header(
    $user,
    'Recommended Internships',
    'recommendations'
);
?>





<main class="im-career-page"
      id="main-content"
      tabindex="-1">

    <div class="container py-5">

        <div class="im-career-heading">

            <div>
                <p class="dashboard-small-title">
                    YOUR CAREER MATCHES
                </p>

                <h1>Recommended Internships</h1>

                <p>
                    Personalized matches across currently open internships,
                    excluding internships you already applied to.
                </p>
            </div>

            <a href="<?= e(url('opportunities.php')) ?>"
               class="btn btn-outline-primary">
                Browse All Internships
            </a>
        </div>

        <p class="im-career-muted">
            Match percentages are estimates based on
            your profile, not predictions of hiring success.
        </p>



        
<?php if ($items === []): ?>

    <div class="im-career-card">

        <h2>No new matches in this group</h2>

        <p>
            Browse all internships or update
            your interests and skills.
        </p>

        <a href="<?= e(url('student-profile.php')) ?>">
            Update Your Profile
        </a>

    </div>

<?php else: ?>

    <div class="im-career-grid">

        <?php foreach ($items as $item): ?>

            <article class="im-career-card">

                <div class="im-career-score">

                    <?= e(number_format(
                        (float) (
                            $item['match_score']['overall'] ?? 0
                        ),
                        1
                    )) ?>%

                    Profile Match

                </div>

                <h2><?= e((string) $item['title']) ?></h2>

                <p>
                    <?= e((string) $item['company_name']) ?>
                </p>

                <p class="im-career-muted">
                    <?= e((string) (
                        $item['location'] ?: 'Location not specified'
                    )) ?>
                    ·
                    <?= e((string) $item['internship_type']) ?>
                </p>

                <p class="im-career-muted">
                    Skills matched:

                    <?= (int) (
                        $item['match_score']['matched_skill_count'] ?? 0
                    ) ?>

                    /

                    <?= (int) (
                        $item['match_score']['required_skill_count'] ?? 0
                    ) ?>
                </p>

                <a class="btn btn-primary"
                   href="<?= e(url(
                       'internship-details.php?id='
                       . (int) $item['internship_id']
                   )) ?>">

                    View Internship

                </a>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>



    </div>
</main>

<?php render_footer($user); ?>
