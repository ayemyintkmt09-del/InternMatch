<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/controllers/StudentInternshipController.php';
require_once __DIR__ . '/../app/controllers/CompanyDirectoryController.php';
require_once __DIR__ . '/../app/controllers/CareerArticleController.php';
require_once __DIR__ . '/../app/layout.php';

header('Cache-Control: no-store');

$homeFields = [];
$homeInternships = [];
$homeLoadError = false;
$homeCompanies = [];
$homeCompanyError = false;

try {
    $homeFields = StudentInternshipController::fields();

    $homeResult = StudentInternshipController::search([
        'sort' => 'newest',
    ]);

    $homeInternships = array_slice(
        $homeResult['items'],
        0,
        4
    );
} catch (Throwable $exception) {
    error_log((string) $exception);
    $homeLoadError = true;
}

try {
    $companyResult = CompanyDirectoryController::search(['sort' => 'open']);
    $homeCompanies = array_slice($companyResult['items'], 0, 6);
} catch (Throwable $exception) {
    error_log((string) $exception);
    $homeCompanyError = true;
}




$homeFeaturedArticles = [];

try {
    $homeFeaturedArticles =
        CareerArticleController::featured(3);

} catch (Throwable $exception) {
    error_log((string) $exception);
}


$publicUser = current_user();



$homeStats = [
    [
        'icon' => 'bi-briefcase-fill',
        'value' => $homeLoadError
            ? '—'
            : number_format((int) ($homeResult['total'] ?? 0)),
        'label' => 'Open Internships',
    ],
    [
        'icon' => 'bi-building-fill',
        'value' => $homeCompanyError
            ? '—'
            : number_format((int) ($companyResult['total'] ?? 0)),
        'label' => 'Verified Companies',
    ],
    [
        'icon' => 'bi-grid-fill',
        'value' => $homeLoadError
            ? '—'
            : number_format(count($homeFields)),
        'label' => 'Career Fields',
    ],
];


?>



<?php render_header($publicUser, 'Home', 'home'); ?>
<main class="im-guest-home" id="main-content" tabindex="-1">  

    <!--  HERO SECTION  -->

    <section class="hero-section">

        <div class="container">

            <div class="row align-items-center g-5">

                <!-- Hero text -->
                <div class="col-lg-6">

                    <span class="hero-badge">
                        <i class="bi bi-stars"></i>
                        MYANMAR'S INTERNSHIP PLATFORM
                    </span>

                    <h1 class="hero-title">
                        Find internships that
                        <span>move your career forward</span>
                    </h1>

                    <p class="hero-text">
                        Explore internships, connect with employers, and gain
                        real-world experience to build your future in Myanmar.
                    </p>


                    <!-- Search Box -->
<form
    class="search-box"
    action="<?= e(url('opportunities.php')) ?>"
    method="get"
    role="search"
    aria-label="Search internships">

    <div class="search-input">
        <i class="bi bi-search" aria-hidden="true"></i>

        <label class="visually-hidden" for="searchInput">
            Internship title or company
        </label>

        <input
            type="search"
            id="searchInput"
            name="q"
            maxlength="100"
            placeholder="Title or company...">
    </div>

    <label class="visually-hidden" for="fieldFilter">
        Academic field
    </label>

    <select id="fieldFilter" name="field_id">
        <option value="">All fields</option>

        <?php foreach ($homeFields as $field): ?>
            <option value="<?= (int) $field['field_id'] ?>">
                <?= e($field['field_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="visually-hidden" for="locationFilter">
        Location
    </label>

    <select id="locationFilter" name="location">
        <option value="">All locations</option>
        <option value="Yangon">Yangon</option>
        <option value="Mandalay">Mandalay</option>
    </select>

    <label class="visually-hidden" for="homeTypeFilter">
        Working arrangement
    </label>

    <select id="homeTypeFilter" name="type">
        <option value="">Any arrangement</option>
        <option value="On-site">On-site</option>
        <option value="Remote">Remote</option>
        <option value="Hybrid">Hybrid</option>
    </select>

    <button
        type="submit"
        class="search-button"
        aria-label="Search internships">
        <i class="bi bi-search" aria-hidden="true"></i>
    </button>
</form>

<p class="search-message">
    Students can sign in to view details, save internships,
    and apply.
</p>

                </div>


                <!-- Hero illustration -->
                <div class="col-lg-6 text-center">

                    <div class="hero-illustration">

                        <div class="illustration-circle"></div>

                        <div class="laptop">
                            <div class="laptop-screen">

                                <div class="screen-header"></div>

                                <div class="screen-content">

                                    <div class="screen-card"></div>
                                    <div class="screen-card"></div>
                                    <div class="screen-card"></div>

                                </div>

                            </div>

                            <div class="laptop-base"></div>
                        </div>

                        <div class="person">
                            <i class="bi bi-person-fill"></i>
                        </div>

                        <div class="floating-icon icon-one">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>

                        <div class="floating-icon icon-two">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>

                        <div class="floating-icon icon-three">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


<!-- LIVE INTERNSHIPS -->
    <section class="im-home-categories" aria-labelledby="homeCategoriesTitle">
        <div class="container">
            <div class="im-section-head">
                <div><span class="section-label">EXPLORE YOUR INTERESTS</span><h2 id="homeCategoriesTitle">Popular career fields</h2></div>
                <a href="<?= e(url('opportunities.php')) ?>">All internships <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="im-category-grid">
                <?php foreach (array_slice($homeFields, 0, 8) as $index => $field): ?>
                    <?php $categoryIcons = ['bi-code-slash','bi-megaphone','bi-graph-up-arrow','bi-palette','bi-cash-coin','bi-gear','bi-briefcase','bi-lightbulb']; ?>
                    <a class="im-category-card" href="<?= e(url('explore.php?field_id=' . (int)$field['field_id'])) ?>">
                        <span class="im-category-icon"><i class="bi <?= e($categoryIcons[$index % count($categoryIcons)]) ?>" aria-hidden="true"></i></span>
                        <strong><?= e($field['field_name']) ?></strong>
                        <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($homeFields === []): ?>
                <p class="text-muted mb-0">Browse internships to discover available career fields.</p>
            <?php endif; ?>
        </div>
    </section>


    
<section class="im-home-quick-search">
    <div class="container">

        <div class="im-section-head">
            <div>
                <span class="section-label">
                    FIND YOUR WORK STYLE
                </span>
                <h2>Explore Internship Opportunities</h2>
                <p>Find opportunities that fit how you want to work.</p>
            </div>
        </div>

        <div class="im-home-quick-grid">

            <a href="<?= e(url(
                'opportunities.php?type=On-site'
            )) ?>" class="im-quick-tile">

                <i class="bi bi-building"></i>
                <strong>On-site</strong>
                <span>Work at the company</span>
            </a>

            <a href="<?= e(url(
                'opportunities.php?type=Remote'
            )) ?>" class="im-quick-tile">

                <i class="bi bi-laptop"></i>
                <strong>Remote</strong>
                <span>Work from anywhere</span>
            </a>

            <a href="<?= e(url(
                'opportunities.php?type=Hybrid'
            )) ?>" class="im-quick-tile">

                <i class="bi bi-house-gear"></i>
                <strong>Hybrid</strong>
                <span>Flexible work arrangement</span>
            </a>

        </div>
    </div>
</section>



<section class="im-home-locations">
    <div class="container">

        <div class="im-section-head">
            <div>
                <span class="section-label">
                    BROWSE BY LOCATION
                </span>
                <h2>Where would you like to intern?</h2>
            </div>
        </div>

        <div class="im-location-links">

            <?php foreach ([
                'Yangon',
                'Mandalay',
                'Naypyidaw'
            ] as $city): ?>

                <a href="<?= e(url(
                    'opportunities.php?'
                    . http_build_query(['location' => $city])
                )) ?>">
                    <i class="bi bi-geo-alt"></i>
                    <?= e($city) ?>
                    <i class="bi bi-arrow-up-right"></i>
                </a>

            <?php endforeach; ?>

            <a href="<?= e(url('opportunities.php')) ?>">
                <i class="bi bi-globe2"></i>
                All Locations
                <i class="bi bi-arrow-up-right"></i>
            </a>

        </div>
    </div>
</section>


<section class="featured-section">
    <div class="container">

        <div class="section-heading">
            <div>
                <span class="section-label">
                    OPPORTUNITIES
                </span>

                <h2>Latest Internships</h2>

                <p>
                    Explore current opportunities from verified companies.
                </p>
            </div>

            <a
                href="<?= e(url('explore.php')) ?>"
                class="view-all">
                View All
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if ($homeLoadError): ?>

            <div class="alert alert-warning" role="status">
                We could not load internships right now.
                Please try again shortly.
            </div>

        <?php elseif ($homeInternships === []): ?>

            <div class="text-center border rounded-4 bg-light p-5">
                <i
                    class="bi bi-briefcase fs-1 text-secondary"
                    aria-hidden="true"></i>

                <h3 class="h5 mt-3">
                    New opportunities are on the way
                </h3>

                <p class="text-muted mb-0">
                    There are no available internships right now.
                    Check back soon.
                </p>
            </div>

        <?php else: ?>

            <div class="row g-4">
                <?php foreach ($homeInternships as $internship): ?>
                    <div class="col-md-6 col-lg-3">
                        <article class="internship-card home-live-card">

                            <div class="home-company-logo">
                                <?= company_logo(
                                    (int) $internship['company_id'],
                                    (string) $internship['company_name'],
                                    (int) (
                                        $internship['company_has_logo'] ?? 0
                                    ) === 1
                                ) ?>
                            </div>

                            <h3 class="h5">
                                <?= e($internship['title']) ?>
                            </h3>

                            
<p class="company-name">
    <a href="<?= e(url(
        'company-public-profile.php?id='
        . (int) $internship['company_id']
    )) ?>">
        <?= e($internship['company_name']) ?>
    </a>
</p>


                            <div class="internship-info">
                                <span>
                                    <i
                                        class="bi bi-geo-alt"
                                        aria-hidden="true"></i>
                                    <?= e($internship['location']) ?>
                                </span>

                                <span>
                                    <i
                                        class="bi bi-laptop"
                                        aria-hidden="true"></i>
                                    <?= e($internship['internship_type']) ?>
                                </span>
                            </div>

                            <span class="category-tag">
                                <?= e(
                                    $internship['field_name']
                                    ?? 'General'
                                ) ?>
                            </span>

                            <p class="small text-muted mt-3">
                                Apply by
                                <time datetime="<?= e($internship['deadline']) ?>">
                                    <?= e($internship['deadline']) ?>
                                </time>
                            </p>

                            <a
                                href="<?= e(url(
                                    'explore.php?id='
                                    . (int) $internship['internship_id']
                                )) ?>"
                                class="btn card-button mt-auto"
                                aria-label="<?= e(
                                    'View '
                                    . $internship['title']
                                    . ' at '
                                    . $internship['company_name']
                                ) ?>">
                                View Details
                            </a>

                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>


    <!-- Discoverable categories and companies use existing PHP/MySQL data. -->
    <section class="im-home-companies" aria-labelledby="homeCompaniesTitle">
        <div class="container">
            <div class="im-section-head">
                <div><span class="section-label">EXPLORE EMPLOYERS</span><h2 id="homeCompaniesTitle">Companies to discover</h2></div>
                <a href="<?= e(url('companies.php')) ?>">View all companies <i class="bi bi-arrow-right" aria-hidden="true"></i></a>

                
<a class="im-hiring-link"
   href="<?= e(url('companies.php?hiring=1&sort=open')) ?>">

    <i class="bi bi-lightning-charge-fill"></i>
    Companies Hiring Now

</a>

            </div>
            <?php if ($homeCompanyError): ?>
                <div class="alert alert-light border">Company information is temporarily unavailable.</div>
            <?php elseif ($homeCompanies === []): ?>
                <p class="text-muted">Verified employer profiles will appear here as they become available.</p>
            <?php else: ?>
                <div class="im-company-showcase">
                    <?php foreach ($homeCompanies as $featuredCompany): ?>
                        <a class="im-company-tile" href="<?= e(url('company-public-profile.php?id=' . (int)$featuredCompany['company_id'])) ?>">
                            <span class="im-company-tile-logo">
                                <?= company_logo((int)$featuredCompany['company_id'], (string)$featuredCompany['company_name'], (int)$featuredCompany['company_has_logo'] === 1) ?>
                            </span>
                            <span><strong><?= e($featuredCompany['company_name']) ?></strong><small><?= (int)$featuredCompany['open_count'] ?> open internship<?= (int)$featuredCompany['open_count'] === 1 ? '' : 's' ?></small></span>
                            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="im-home-resources" aria-labelledby="homeResourcesTitle">
        <div class="container">
            <div class="im-section-head">
                <div><span class="section-label">CAREER DEVELOPMENT</span><h2 id="homeResourcesTitle">Get ready for your first internship</h2></div>

<a href="<?= e(url('career-articles.php')) ?>">
    Career articles
    <i class="bi bi-arrow-right" aria-hidden="true"></i>
</a>
            </div>
            <div class="im-resource-grid">
                <a href="<?= e(url('help.php')) ?>" class="im-resource-card"><span class="im-resource-icon"><i class="bi bi-file-earmark-person" aria-hidden="true"></i></span><strong>Build your profile</strong><p>Showcase your education, skills, interests, and experience.</p><span>Learn more <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
                <a href="<?= e(url('help.php')) ?>" class="im-resource-card"><span class="im-resource-icon"><i class="bi bi-search" aria-hidden="true"></i></span><strong>Find a suitable role</strong><p>Search by career field, location, and working arrangement.</p><span>Learn more <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
                <a href="<?= e(url('help.php')) ?>" class="im-resource-card"><span class="im-resource-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><strong>Prepare to apply</strong><p>Review opportunities carefully and keep track of applications.</p><span>Learn more <i class="bi bi-arrow-right" aria-hidden="true"></i></span></a>
            </div>




            
<?php if ($homeFeaturedArticles !== []): ?>

    <div class="im-home-article-heading">

        <h3>Featured Career Reads</h3>

        <a href="<?= e(url('career-articles.php')) ?>">
            Browse articles
            <i class="bi bi-arrow-right"></i>
        </a>

    </div>

    <div class="im-featured-articles">

        <?php foreach ($homeFeaturedArticles as $article): ?>

            <article class="im-career-article-card">

                <span class="im-career-category">
                    <?= e($article['category']) ?>
                </span>

                <h2>
                    <a href="<?= e(url(
                        'career-article.php?slug='
                        . rawurlencode($article['slug'])
                    )) ?>">
                        <?= e($article['title']) ?>
                    </a>
                </h2>

                <p><?= e($article['excerpt']) ?></p>

                <div class="im-career-article-foot">

                    <a href="<?= e(url(
                        'career-article.php?slug='
                        . rawurlencode($article['slug'])
                    )) ?>">
                        Read article
                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

        </div>
    </section>

    <!-- ================= HOW IT WORKS ================= -->

    <section class="how-section" id="how-it-works">

        <div class="container">

            <div class="text-center section-title">

                <span class="section-label">
                    SIMPLE PROCESS
                </span>

                <h2>
                    How InternMatch Works
                </h2>

                <p>
                    Find and apply for internships in a few simple steps.
                </p>

            </div>


            <div class="row g-4">

                <!-- Step 1 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            01
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>

                        <h4>
                            Create Your Profile
                        </h4>

                        <p>
                            Add your education, skills, interests,
                            availability and preferred location.
                        </p>

                    </div>

                </div>


                <!-- Step 2 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            02
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-search"></i>
                        </div>

                        <h4>
                            Find Your Match
                        </h4>

                        <p>
                            Search internships or receive recommendations
                            based on your profile.
                        </p>

                    </div>

                </div>


                <!-- Step 3 -->
                <div class="col-md-4">

                    <div class="step-card">

                        <div class="step-number">
                            03
                        </div>

                        <div class="step-icon">
                            <i class="bi bi-send-fill"></i>
                        </div>

                        <h4>
                            Apply & Track
                        </h4>

                        <p>
                            Apply for suitable internships and track
                            your application status.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- BENEFITS  -->

    <section class="benefits-section" id="about">

        <div class="container">

            <div class="row g-5 align-items-center">

                <div class="col-lg-6">

                    <span class="section-label">
                        WHY INTERNMATCH
                    </span>

                    <h2>
                        Built for Students
                        and Companies
                    </h2>

                    <p class="benefit-description">
                        InternMatch provides a centralized platform
                        where students can discover opportunities and
                        companies can manage internship recruitment.
                    </p>

                    <div class="benefit-list">

                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>For Students</h5>
                                <p>
                                    Find relevant internships, manage
                                    your CV and track applications.
                                </p>
                            </div>

                        </div>


                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>For Companies</h5>
                                <p>
                                    Post opportunities and review
                                    suitable student applications.
                                </p>
                            </div>

                        </div>


                        <div class="benefit-item">

                            <i class="bi bi-check-circle-fill"></i>

                            <div>
                                <h5>Smart Matching</h5>
                                <p>
                                    Compare student profiles with
                                    internship requirements.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="stats-card">

                        
<?php foreach ($homeStats as $stat): ?>
    <div class="stat-item">
        <i class="bi <?= e($stat['icon']) ?>"
           aria-hidden="true"></i>

        <strong><?= e($stat['value']) ?></strong>

        <span><?= e($stat['label']) ?></span>
    </div>
<?php endforeach; ?>


                    </div>

                </div>

            </div>

        </div>

    </section>


    <!--  CTA -->

    <section class="cta-section">

        <div class="container">

            <div class="cta-content">

                <h2>
                    Ready to Find Your Opportunity?
                </h2>

                <p>
                    Create your profile and start exploring internship
                    opportunities today.
                </p>

                <a
                    href="<?= e(url(
                        $publicUser === null
                            ? 'register.php'
                            : dashboard_path($publicUser['role'])
                    )) ?>"
                    class="btn cta-button">

                    <?= $publicUser === null
                        ? 'Create Your Account'
                        : 'Go to My Dashboard' ?>
                </a>

                
<a href="<?= e(url('opportunities.php')) ?>"
   class="btn im-guest-secondary-cta">

    <i class="bi bi-search me-1"></i>
    Browse Internships First

</a>


            </div>

        </div>

    </section>


    <!--  FOOTER  -->

</main>

    <?php render_footer($publicUser); ?>

