<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CareerArticleController.php';
require_once __DIR__ . '/../app/layout.php';
header('X-Robots-Tag: index, follow');
$slug = $_GET['slug'] ?? null;
if (!is_string($slug) || $slug === '') {
    http_response_code(404);
    exit('Article not found.');
}
try {
    $article = CareerArticleController::publicOne($slug);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(503);
    exit('Career article temporarily unavailable.');
}
if ($article === null) {
    http_response_code(404);
    exit('Article not found.');
}
$user = current_user();
render_header($user, $article['title'], 'career-content');
?>
<main class="im-career-content-page" id="main-content" tabindex="-1">
  <div class="container py-5">
    <article class="im-career-full-article">
      <a class="im-career-back" href="<?= e(url('career-articles.php')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> All Career Articles
      </a>
      <span class="im-career-category mt-4"><?= e($article['category']) ?></span>
      <h1><?= e($article['title']) ?></h1>
      <p class="im-career-intro"><?= e($article['excerpt']) ?></p>
      <p class="small text-muted">Published <?= e(format_utc_datetime($article['published_at'], 'd M Y')) ?></p>
      <div class="im-career-article-body"><?= nl2br(e($article['body'])) ?></div>
      <div class="im-career-article-bottom">
        <a class="btn btn-outline-primary" href="<?= e(url('career-articles.php')) ?>">More Career Resources</a>
        <a class="btn btn-primary" href="<?= e(url('opportunities.php')) ?>">Explore Internships</a>
      </div>
    </article>
  </div>
</main>
<?php render_footer($user); ?>
