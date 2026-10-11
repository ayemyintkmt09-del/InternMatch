<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/StudentProfileController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('student');

$profile = StudentProfileController::load(
    (int) $user['user_id']
);

$studentId = (int) $profile['student_id'];
$pdo = db();


$levels = [
    'Beginner',
    'Intermediate',
    'Advanced',
    'Expert'
];


$stmt = $pdo->prepare(
    'SELECT
        ss.skill_id,
        s.skill_name,
        ss.proficiency_level
     FROM student_skills AS ss
     JOIN skills AS s
       ON s.skill_id = ss.skill_id
     WHERE ss.student_id = :student_id
     ORDER BY s.skill_name'
);

$stmt->execute([
    'student_id' => $studentId
]);

$skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = null;




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();

    $submitted = $_POST['levels'] ?? null;

    try {
        if (
            !is_array($submitted)
            || count($submitted) !== count($skills)
        ) {
            throw new InvalidArgumentException(
                'Reload and try again.'
            );
        }

        $expected = array_map(
            static fn($row): string =>
                (string) $row['skill_id'],
            $skills
        );

        if (
            array_diff(array_keys($submitted), $expected)
            || array_diff($expected, array_keys($submitted))
        ) {
            throw new InvalidArgumentException(
                'Invalid skill selection.'
            );
        }

        foreach ($submitted as $id => $level) {
            if (
                !is_string($level)
                || !in_array($level, $levels, true)
            ) {
                throw new InvalidArgumentException(
                    'Invalid skill level.'
                );
            }
        }

        $pdo->beginTransaction();

        $update = $pdo->prepare(
            'UPDATE student_skills
             SET proficiency_level = :level
             WHERE student_id = :student_id
               AND skill_id = :skill_id'
        );

        foreach ($submitted as $id => $level) {
            $update->execute([
                'level' => $level,
                'student_id' => $studentId,
                'skill_id' => (int) $id,
            ]);
        }

        $pdo->commit();

        flash('levels_success', 'Skill levels updated.');
        redirect('student-skill-levels.php');

    } catch (InvalidArgumentException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(422);
        $error = $exception->getMessage();

    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log((string) $exception);
        http_response_code(500);

        $error = 'Unable to update your skill levels.';
    }
}

$notice = take_flash('levels_success');

render_header($user, 'Skill Levels', 'skills');
?>




<main class="im-portfolio-page"
      id="main-content"
      tabindex="-1">

    <div class="container">

        <div class="im-section-heading">
            <div>
                <p class="dashboard-small-title">
                    STUDENT SKILLS
                </p>

                <h1>My Skill Levels</h1>

                <p>
                    Manage your professional and technical proficiency.
                </p>
            </div>

            <a href="<?= e(url('student-profile.php')) ?>"
               class="btn btn-outline-primary">
                Add / Remove Skills
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

            <?php if (!$skills): ?>

                <p>
                    No skills added yet.
                    <a href="<?= e(url('student-profile.php')) ?>">
                        Add skills in your profile.
                    </a>
                </p>

            <?php else: ?>

                <form method="post"
                      action="<?= e(url('student-skill-levels.php')) ?>">

                    <?= csrf_field() ?>

                    <div class="im-skill-list">

                        <?php foreach ($skills as $skill): ?>

                            <div class="im-skill-row">

                                <label for="level-<?= (int) $skill['skill_id'] ?>">
                                    <?= e($skill['skill_name']) ?>
                                </label>

                                <select
                                    class="form-select"
                                    id="level-<?= (int) $skill['skill_id'] ?>"
                                    name="levels[<?= (int) $skill['skill_id'] ?>]">

                                    <?php foreach ($levels as $level): ?>

                                        <option
                                            value="<?= e($level) ?>"
                                            <?= $skill['proficiency_level'] === $level
                                                ? 'selected'
                                                : '' ?>>

                                            <?= e($level) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <p class="im-cv-note">
                        Skill levels are self-reported, not verified test scores.
                    </p>

                    <button type="submit"
                            class="btn btn-primary">
                        Save Skill Levels
                    </button>

                </form>

            <?php endif; ?>

        </section>
    </div>
</main>

<?php render_footer($user); ?>
