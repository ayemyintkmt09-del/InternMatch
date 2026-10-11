<?php
declare(strict_types=1);

final class CareerArticleController
{
    public const CATEGORIES = [
        'Career News',
        'CV & Resume',
        'Interviews',
        'Internship Tips',
        'Career Planning',
    ];

    public const STATUSES = ['Draft', 'Published', 'Archived'];

    private static function text(array $input, string $key, int $min, int $max): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid ' . $key . ' field.');
        }
        $value = trim($value);
        $length = mb_strlen($value, 'UTF-8');
        if ($length < $min || $length > $max) {
            throw new InvalidArgumentException(
                ucfirst($key) . ' must be ' . $min . '–' . $max . ' characters.'
            );
        }
        return $value;
    }

    public static function validateEditor(array $input): array
    {
        $title = self::text($input, 'title', 5, 160);
        $excerpt = self::text($input, 'excerpt', 20, 320);
        $body = self::text($input, 'body', 80, 15000);
        $category = self::text($input, 'category', 1, 40);
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new InvalidArgumentException('Choose a valid article category.');
        }
        $featured = $input['is_featured'] ?? '0';
        if (!is_string($featured) || !in_array($featured, ['0', '1'], true)) {
            throw new InvalidArgumentException('Invalid featured setting.');
        }
        return compact('title', 'excerpt', 'body', 'category') + [
            'is_featured' => $featured === '1' ? 1 : 0,
        ];
    }

    private static function slugFor(string $title): string
    {
        $ascii = strtolower($title);
        $stem = trim((string) preg_replace('/[^a-z0-9]+/', '-', $ascii), '-');
        if ($stem === '') {
            $stem = 'article';
        }
        return substr($stem, 0, 120) . '-' . bin2hex(random_bytes(5));
    }

    public static function save(int $adminId, array $input): int
    {
        $admin = require_role('admin');
        if ((int) $admin['user_id'] !== $adminId) {
            throw new InvalidArgumentException('Invalid administrator.');
        }
        $values = self::validateEditor($input);
        $rawId = $input['article_id'] ?? '0';
        if (!is_string($rawId) || !ctype_digit($rawId)) {
            throw new InvalidArgumentException('Invalid article ID.');
        }
        $id = (int) $rawId;
        if ($id < 0 || $id > 2147483647) {
            throw new InvalidArgumentException('Invalid article ID.');
        }
        $pdo = db();
        if ($id === 0) {
            $statement = $pdo->prepare(
                'INSERT INTO career_articles
                 (slug,title,excerpt,body,category,is_featured,author_user_id)
                 VALUES (:slug,:title,:excerpt,:body,:category,:is_featured,:author_user_id)'
            );
            $statement->execute($values + [
                'slug' => self::slugFor($values['title']),
                'author_user_id' => $adminId,
            ]);
            return (int) $pdo->lastInsertId();
        }
        $statement = $pdo->prepare(
            'UPDATE career_articles SET
                title=:title, excerpt=:excerpt, body=:body,
                category=:category, is_featured=:is_featured
             WHERE article_id=:article_id'
        );
        $statement->execute($values + ['article_id' => $id]);
        if ($statement->rowCount() === 0 && self::adminOne($id) === null) {
            throw new InvalidArgumentException('Article not found.');
        }
        return $id;
    }

    public static function changeStatus(int $adminId, int $articleId, string $status): void
    {
        $admin = require_role('admin');
        if ((int) $admin['user_id'] !== $adminId
            || $articleId < 1
            || !in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid publication action.');
        }
        $stmt = db()->prepare(
            'UPDATE career_articles
             SET article_status=:status,
                 published_at=CASE
                     WHEN :publish_flag=1 THEN UTC_TIMESTAMP()
                     ELSE NULL END
             WHERE article_id=:article_id'
        );
        $stmt->execute([
            'status' => $status,
            'publish_flag' => $status === 'Published' ? 1 : 0,
            'article_id' => $articleId,
        ]);
        if ($stmt->rowCount() === 0 && self::adminOne($articleId) === null) {
            throw new InvalidArgumentException('Article not found.');
        }
    }

    public static function adminOne(int $id): ?array
    {
        require_role('admin');
        if ($id < 1) return null;
        $stmt = db()->prepare('SELECT * FROM career_articles WHERE article_id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function adminAll(string $status = ''): array
    {
        require_role('admin');
        if ($status !== '' && !in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid article status.');
        }
        $sql = 'SELECT article_id,slug,title,category,article_status,is_featured,
                       published_at,updated_at FROM career_articles';
        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE article_status=:status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY updated_at DESC,article_id DESC LIMIT 100';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function publicListing(array $input): array
    {
        $category = $input['category'] ?? '';
        $keyword = $input['q'] ?? '';
        $pageRaw = $input['page'] ?? '1';
        if (!is_string($category) || ($category !== '' &&
            !in_array($category, self::CATEGORIES, true)) ||
            !is_string($keyword) || mb_strlen(trim($keyword), 'UTF-8') > 100 ||
            !is_string($pageRaw) || !ctype_digit($pageRaw) ||
            (int) $pageRaw < 1 || (int) $pageRaw > 10000) {
            throw new InvalidArgumentException('Invalid article filters.');
        }
        $keyword = trim($keyword);
        $page = (int) $pageRaw;
        $where = ["article_status='Published'", 'published_at <= UTC_TIMESTAMP()'];
        $args = [];
        if ($category !== '') {
            $where[] = 'category=:category';
            $args['category'] = $category;
        }
        if ($keyword !== '') {
            $where[] = '(LOCATE(:q_title,title)>0 OR LOCATE(:q_excerpt,excerpt)>0)';
            $args['q_title'] = $keyword;
            $args['q_excerpt'] = $keyword;
        }
        $predicate = implode(' AND ', $where);
        $pdo = db();
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM career_articles WHERE ' . $predicate);
        $countStmt->execute($args);
        $count = (int) $countStmt->fetchColumn();
        $perPage = 9;
        $lastPage = max(1, (int) ceil($count / $perPage));
        $page = min($page, $lastPage);
        $stmt = $pdo->prepare(
            'SELECT article_id,slug,title,excerpt,category,published_at,is_featured
             FROM career_articles WHERE ' . $predicate . '
             ORDER BY published_at DESC,article_id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($args as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $count,
            'page' => $page,
            'pages' => $lastPage,
            'filters' => ['category' => $category, 'q' => $keyword],
        ];
    }

    public static function publicOne(string $slug): ?array
    {
        if (strlen($slug) > 180 || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug)) {
            return null;
        }
        $stmt = db()->prepare(
            "SELECT slug,title,excerpt,body,category,published_at
             FROM career_articles
             WHERE slug=:slug AND article_status='Published'
               AND published_at <= UTC_TIMESTAMP() LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function featured(int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));
        $stmt = db()->prepare(
            "SELECT slug,title,excerpt,category,published_at
             FROM career_articles
             WHERE article_status='Published' AND is_featured=1
               AND published_at <= UTC_TIMESTAMP()
             ORDER BY published_at DESC,article_id DESC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
