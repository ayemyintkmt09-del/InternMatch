<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/layout.php';


header('Cache-Control: no-store');

$user = current_user();

$dashboardPath = $user !== null
    ? dashboard_path($user['role'])
    : 'login.php';

$dashboardLabel = $user !== null
    ? 'My Dashboard'
    : 'Sign In';

$helpGroups = [
    [
        'title' => 'For students',
        'icon' => 'bi-mortarboard',
        'items' => [
            [
                'question' => 'How do I get started?',
                'answer' => 'Create a student account, complete your profile, '
                    . 'add your skills, and upload your CV. Then browse '
                    . 'opportunities and open an internship to review its requirements.',
            ],
            [
                'question' => 'What does the matching percentage mean?',
                'answer' => 'It is a rule-based comparison of your profile '
                    . 'with an internship, using skills, academic field, interests, '
                    . 'availability, and location. It is not a hiring probability '
                    . 'or a guarantee of acceptance. Read the requirements '
                    . 'before deciding whether to apply.',
            ],
            [
                'question' => 'Does saving an internship submit an application?',
                'answer' => 'No. Saving adds an internship to your saved list. '
                    . 'You must open its details and complete the application '
                    . 'process separately.',
            ],
            [
                'question' => 'Why does a saved internship show Unavailable?',
                'answer' => 'An internship can become unavailable when its '
                    . 'deadline passes, it is no longer Published, or its company '
                    . 'no longer meets the platform’s visibility requirements. '
                    . 'You can remove it from your saved list.',
            ],
            [
                'question' => 'Can I withdraw an application?',
                'answer' => 'You can withdraw an application while its status '
                    . 'is Pending or Under Review. Withdrawal is final in the '
                    . 'current system: you cannot submit another application '
                    . 'for that same internship.',
            ],
            [
                'question' => 'Will uploading a new CV change an old application?',
                'answer' => 'Submitted applications retain the CV selected '
                    . 'when you applied. Updating your profile CV affects '
                    . 'future applications.',
            ],
            [
                'question' => 'Which CV format can I upload?',
                'answer' => 'Upload a PDF no larger than 5 MB. '
                    . 'Make sure the document is readable and contains '
                    . 'your current contact information.',
            ],
        ],
    ],
    [
        'title' => 'For companies',
        'icon' => 'bi-buildings',
        'items' => [
            [
                'question' => 'Why can I save a draft but not publish?',
                'answer' => 'Company verification is required before '
                    . 'publishing. Complete your company profile and provide '
                    . 'the required verification document for administrator review.',
            ],
            [
                'question' => 'Why has my company returned to pending verification?',
                'answer' => 'Changing the company name resets verification '
                    . 'so an administrator can review the updated identity.',
            ],
            [
                'question' => 'What should I check before publishing?',
                'answer' => 'Review the title, description, academic field, '
                    . 'skills, location, working arrangement, dates, duration, '
                    . 'and number of positions. Resolve any validation messages '
                    . 'shown by the publishing form.',
            ],
            [
                'question' => 'Can I review a withdrawn application?',
                'answer' => 'Withdrawn applications remain part of the '
                    . 'application record, but their review status cannot '
                    . 'be changed.',
            ],
        ],
    ],
    [
        'title' => 'Troubleshooting',
        'icon' => 'bi-tools',
        'items' => [
            [
                'question' => 'Why was I asked to reload the page?',
                'answer' => 'Your session or form security token may have '
                    . 'expired. Reload the page, sign in again if requested, '
                    . 'and retry the action.',
            ],
            [
                'question' => 'Why does a company show a letter instead of a logo?',
                'answer' => 'The company may not have uploaded a logo, '
                    . 'or its image could not be loaded. The initial keeps '
                    . 'the page readable when an image is unavailable.',
            ],
            [
                'question' => 'Where should I check application updates?',
                'answer' => 'Check My Applications and the notification bell '
                    . 'while signed in. Do not rely on external email or SMS '
                    . 'messages for updates in the current version.',
            ],
        ],
    ],
];

?>

<?php render_header($user, 'Help & Guidance', 'help'); ?>



<main
    id="main-content"
    class="im-help-page"
    tabindex="-1">

    <section class="im-help-hero">
        <div class="container">
            <p class="text-uppercase fw-semibold text-success mb-2">
                Help & Guidance
            </p>

            <h1>Find your next step.</h1>

            <p class="text-secondary mb-4">
                Learn how profiles, applications, saved internships,
                and company verification work.
            </p>

            <nav
                class="d-flex flex-wrap gap-2"
                aria-label="Help topics">

                <?php foreach ($helpGroups as $index => $group): ?>
                    <a
                        class="btn btn-light border"
                        href="#help-group-<?= $index ?>">
                        <?= e($group['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </section>

    <div class="container pb-5">
        <div class="im-help-content">

            <?php foreach ($helpGroups as $index => $group): ?>
                <section
                    id="help-group-<?= $index ?>"
                    class="im-help-group"
                    aria-labelledby="help-title-<?= $index ?>">

                    <h2
                        id="help-title-<?= $index ?>"
                        class="h4 mb-3">

                        <i
                            class="bi <?= e($group['icon']) ?> me-2 text-success"
                            aria-hidden="true"></i>

                        <?= e($group['title']) ?>
                    </h2>

                    <?php foreach ($group['items'] as $item): ?>
                        <details class="im-help-item">
                            <summary>
                                <?= e($item['question']) ?>
                            </summary>

                            <div class="im-help-answer">
                                <p class="mb-0">
                                    <?= e($item['answer']) ?>
                                </p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <aside class="im-help-next">
                <h2 class="h5">Ready to continue?</h2>

                <p class="text-secondary">
                    Return to your account, or explore the latest
                    available internships.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a
                        class="btn btn-success"
                        href="<?= e(url($dashboardPath)) ?>">
                        <?= e($dashboardLabel) ?>
                    </a>

                    <a
                        class="btn btn-outline-success"
                        href="<?= e(url('index.php')) ?>">
                        View Homepage
                    </a>
                </div>
            </aside>

        </div>
    </div>
</main>
<?php render_footer($user); ?>