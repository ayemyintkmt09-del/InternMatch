<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CareerArticleController.php';
require_once __DIR__ . '/../app/layout.php';

try {
    $listing = CareerArticleController::publicListing($_GET);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    exit('Invalid article search filters.');
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(503);
    exit('Career articles are temporarily unavailable.');
}
$user = current_user();
$category = $listing['filters']['category'];
$q = $listing['filters']['q'];
$pageUrl = static function (int $page) use ($category, $q): string {
    return url('career-articles.php?' . http_build_query([
        'category' => $category, 'q' => $q, 'page' => $page,
    ]));
};
render_header($user, 'Career News & Resources', 'career-content');
?>
<main class="im-career-content-page" id="main-content" tabindex="-1">
  <div class="container py-5">
    <header class="im-career-content-hero">
      <div><p class="dashboard-small-title">CAREER DEVELOPMENT</p>
        <h1>Career News & Resources</h1>
        <p>Guidance for internships, job applications, CV writing, and interviews.</p>
      </div>
    </header>
    <form class="im-career-search" action="<?= e(url('career-articles.php')) ?>" method="get" role="search">
      <div>
        <label class="form-label" for="careerSearch">Search articles</label>
        <input id="careerSearch" type="search" class="form-control" name="q" maxlength="100"
               value="<?= e($q) ?>" placeholder="Search title or summary">
      </div>
      <div>
        <label class="form-label" for="careerCategory">Category</label>
        <select id="careerCategory" name="category" class="form-select">
          <option value="">All Categories</option>
          <?php foreach (CareerArticleController::CATEGORIES as $choice): ?>
            <option value="<?= e($choice) ?>" <?= $category === $choice ? 'selected' : '' ?>><?= e($choice) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Search</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('career-articles.php')) ?>">Reset</a>
    </form>
    <p class="text-muted mb-3"><?= (int) $listing['total'] ?> published article(s) found.</p>
    <?php if ($listing['items'] === []): ?>
      <section class="im-career-content-panel">
        <h2>No articles found</h2><p>Try a different search term or category.</p>
      </section>
    <?php else: ?>
      <div class="im-career-articles-grid">
        <?php foreach ($listing['items'] as $article): ?>
          <article class="im-career-article-card">
            <span class="im-career-category"><?= e($article['category']) ?></span>
            <h2><a href="<?= e(url('career-article.php?slug=' . rawurlencode($article['slug']))) ?>"><?= e($article['title']) ?></a></h2>
            <p><?= e($article['excerpt']) ?></p>
            <div class="im-career-article-foot">
              <small><?= e(format_utc_datetime($article['published_at'], 'd M Y')) ?></small>
              <a href="<?= e(url('career-article.php?slug=' . rawurlencode($article['slug']))) ?>">Read Article <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($listing['pages'] > 1): ?>
      <nav class="im-career-pagination" aria-label="Article pagination">
        <?php if ($listing['page'] > 1): ?>
          <a href="<?= e($pageUrl($listing['page'] - 1)) ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= (int) $listing['page'] ?> of <?= (int) $listing['pages'] ?></span>
        <?php if ($listing['page'] < $listing['pages']): ?>
          <a href="<?= e($pageUrl($listing['page'] + 1)) ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </div>
</main>
<?php render_footer($user); ?>
