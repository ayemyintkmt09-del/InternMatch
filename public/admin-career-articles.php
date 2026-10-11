<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/controllers/CareerArticleController.php';
require_once __DIR__ . '/../app/layout.php';

$user = require_role('admin');
$adminId = (int) $user['user_id'];
$error = null;
$editId = 0;
$form = [
    'title' => '', 'excerpt' => '', 'body' => '',
    'category' => 'Internship Tips', 'is_featured' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    verify_csrf();
    try {
        $action = $_POST['action'] ?? null;
        if ($action === 'save') {
            $id = CareerArticleController::save($adminId, $_POST);
            flash('career_admin_notice', 'Article saved. Publish it separately when ready.');
            redirect('admin-career-articles.php?edit=' . $id);
        }
        if ($action === 'status') {
            $id = filter_var($_POST['article_id'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]);
            $status = $_POST['article_status'] ?? null;
            if ($id === false || !is_string($status)) {
                throw new InvalidArgumentException('Invalid publication request.');
            }
            CareerArticleController::changeStatus($adminId, $id, $status);
            flash('career_admin_notice', 'Publication status updated.');
            redirect('admin-career-articles.php?edit=' . $id);
        }
        throw new InvalidArgumentException('Invalid article action.');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        http_response_code(422);
        foreach (['title', 'excerpt', 'body', 'category'] as $key) {
            if (is_string($_POST[$key] ?? null)) {
                $form[$key] = $_POST[$key];
            }
        }
        $form['is_featured'] = ($_POST['is_featured'] ?? '0') === '1' ? 1 : 0;
    } catch (Throwable $exception) {
        error_log((string) $exception);
        http_response_code(500);
        $error = 'Unable to save article. Check the server error log.';
    }
}

$rawEdit = $_GET['edit'] ?? ($_POST['article_id'] ?? '0');
if (!is_string($rawEdit) || !ctype_digit($rawEdit) || (int) $rawEdit > 2147483647) {
    http_response_code(400);
    exit('Invalid article ID.');
}
$editId = (int) $rawEdit;
$filter = $_GET['status'] ?? '';
if (!is_string($filter) || ($filter !== '' && !in_array($filter, CareerArticleController::STATUSES, true))) {
    http_response_code(400);
    exit('Invalid status filter.');
}
try {
    $selected = $editId > 0 ? CareerArticleController::adminOne($editId) : null;
    if ($editId > 0 && $selected === null) {
        http_response_code(404);
        exit('Article not found.');
    }
    if ($error === null && $selected) {
        $form = $selected;
    }
    $articles = CareerArticleController::adminAll($filter);
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    exit('Unable to load career content.');
}
$notice = take_flash('career_admin_notice');
render_header($user, 'Manage Career Articles', 'career-admin');
?>

<main class="im-career-content-page" id="main-content" tabindex="-1">
  <div class="container py-5">
    <header class="im-career-content-hero">
      <div>
        <p class="dashboard-small-title">ADMIN CONTENT MANAGEMENT</p>
        <h1>Career News & Resources</h1>
        <p>Draft articles, review content, and control public publication.</p>
      </div>
      <a class="btn btn-outline-primary" href="<?= e(url('career-articles.php')) ?>">View Public Library</a>
    </header>

    <?php if ($notice !== null): ?>
      <div class="alert alert-success" role="status"><?= e($notice) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
      <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="im-career-content-panel">
      <h2><?= $editId > 0 ? 'Edit Article #' . $editId : 'Create Article' ?></h2>
      <?php if ($selected): ?>
        <p class="text-muted small">Status: <strong><?= e($selected['article_status']) ?></strong> ·
          Changes to an already published article are visible publicly as soon as you save.
        </p>
      <?php else: ?>
        <p class="text-muted small">New articles are saved as Draft until published explicitly.</p>
      <?php endif; ?>
      <form method="post" action="<?= e(url('admin-career-articles.php' . ($editId ? '?edit=' . $editId : ''))) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="article_id" value="<?= $editId ?>">
        <div class="mb-3">
          <label class="form-label" for="articleTitle">Title</label>
          <input class="form-control" id="articleTitle" name="title" maxlength="160" minlength="5" required
                 value="<?= e((string) $form['title']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="articleCategory">Category</label>
          <select class="form-select" id="articleCategory" name="category" required>
            <?php foreach (CareerArticleController::CATEGORIES as $category): ?>
              <option value="<?= e($category) ?>" <?= $form['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="articleExcerpt">Short summary</label>
          <textarea class="form-control" id="articleExcerpt" name="excerpt" minlength="20" maxlength="320" rows="3" required><?= e((string) $form['excerpt']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label" for="articleBody">Article body (plain text)</label>
          <textarea class="form-control" id="articleBody" name="body" minlength="80" maxlength="15000" rows="12" required><?= e((string) $form['body']) ?></textarea>
          <div class="form-text">Plain text only. Line breaks will be kept. HTML is never executed.</div>
        </div>
        <div class="form-check mb-3">
          <input type="hidden" name="is_featured" value="0">
          <input class="form-check-input" type="checkbox" id="articleFeatured" name="is_featured" value="1"
                 <?= (int) $form['is_featured'] === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="articleFeatured">Feature on homepage when published</label>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button class="btn btn-primary" type="submit">Save Article</button>
          <a class="btn btn-outline-secondary" href="<?= e(url('admin-career-articles.php')) ?>">Create Another</a>
        </div>
      </form>

      <?php if ($selected): ?>
        <div class="im-career-publication-actions mt-4">
          <?php foreach (CareerArticleController::STATUSES as $status): ?>
            <?php if ($selected['article_status'] !== $status): ?>
              <form method="post" action="<?= e(url('admin-career-articles.php?edit=' . $editId)) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="article_id" value="<?= $editId ?>">
                <input type="hidden" name="article_status" value="<?= e($status) ?>">
                <button type="submit" class="btn <?= $status === 'Published' ? 'btn-success' : 'btn-outline-secondary' ?>">
                  <?= $status === 'Draft' ? 'Unpublish to Draft' : ($status === 'Published' ? 'Publish Now' : 'Archive') ?>
                </button>
              </form>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php if ($selected['article_status'] === 'Published'): ?>
            <a class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer"
               href="<?= e(url('career-article.php?slug=' . rawurlencode($selected['slug']))) ?>">Open Published Article</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="im-career-content-panel">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="mb-0">Manage Articles</h2>
        <form method="get" action="<?= e(url('admin-career-articles.php')) ?>">
          <label for="articleFilter" class="visually-hidden">Article status</label>
          <select id="articleFilter" name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (CareerArticleController::STATUSES as $status): ?>
              <option value="<?= e($status) ?>" <?= $filter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <p class="small text-muted">Showing up to 100 most recently updated articles<?= $filter !== '' ? ' (' . e($filter) . ')' : '' ?>.</p>
      <?php if ($articles === []): ?>
        <p>No articles match this filter.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Featured</th><th>Action</th></tr></thead>
            <tbody>
              <?php foreach ($articles as $article): ?>
                <tr>
                  <td><?= e($article['title']) ?></td>
                  <td><?= e($article['category']) ?></td>
                  <td><?= e($article['article_status']) ?></td>
                  <td><?= (int) $article['is_featured'] === 1 ? 'Yes' : 'No' ?></td>
                  <td><a href="<?= e(url('admin-career-articles.php?edit=' . (int) $article['article_id'])) ?>">Edit</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php render_footer($user); ?>
