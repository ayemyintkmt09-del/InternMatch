<?php
declare(strict_types=1);

final class CompanyReviewController
{
    public static function eligible(int $studentUserId): array
    {
        $stmt = db()->prepare(
            "SELECT
                a.application_id,
                i.title AS internship_title,
                c.company_name,
                c.company_id,
                r.review_id,
                r.rating,
                r.title AS review_title,
                r.positives,
                r.improvements,
                r.review_status
             FROM applications a
             JOIN student_profiles sp
                 ON sp.student_id = a.student_id
             JOIN users su
                 ON su.user_id = sp.user_id
             JOIN internships i
                 ON i.internship_id = a.internship_id
             JOIN companies c
                 ON c.company_id = i.company_id
             JOIN users cu
                 ON cu.user_id = c.user_id
             LEFT JOIN company_reviews r
                 ON r.application_id = a.application_id
             WHERE sp.user_id = :student_id
               AND su.role = 'student'
               AND su.status = 'active'
               AND a.status = 'Accepted'
               AND DATE(a.application_date) <= i.end_date
               AND i.end_date IS NOT NULL
               AND i.end_date <= CURDATE()
               AND c.verification_status = 'verified'
               AND cu.role = 'company'
               AND cu.status = 'active'
             ORDER BY a.application_date DESC,
                      a.application_id DESC"
        );

        $stmt->execute([
            'student_id' => $studentUserId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
public static function save(
    int $studentUserId,
    array $input
): void {
    $applicationId = filter_var(
        $input['application_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $rating = filter_var(
        $input['rating'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => [
            'min_range' => 1,
            'max_range' => 5
        ]]
    );

    if ($applicationId === false || $rating === false) {
        throw new InvalidArgumentException(
            'Choose an eligible internship and a 1–5 star rating.'
        );
    }

    $fields = [
        'title' => [5, 120],
        'positives' => [20, 1500],
        'improvements' => [0, 1500],
    ];

    $values = [];

    foreach ($fields as $key => [$minimum, $maximum]) {
        $value = $input[$key] ?? null;

        if (!is_string($value)) {
            throw new InvalidArgumentException(
                'Invalid review input.'
            );
        }

        $value = trim($value);
        $length = mb_strlen($value, 'UTF-8');

        if ($length < $minimum || $length > $maximum) {
            throw new InvalidArgumentException(
                "$key must contain {$minimum}–{$maximum} characters."
            );
        }

        $values[$key] = $value;
    }


    
$pdo = db();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare(
        "SELECT c.company_id
         FROM applications a
         JOIN student_profiles sp
             ON sp.student_id = a.student_id
         JOIN users su ON su.user_id = sp.user_id
         JOIN internships i ON i.internship_id = a.internship_id
         JOIN companies c ON c.company_id = i.company_id
         JOIN users cu ON cu.user_id = c.user_id
         WHERE a.application_id = :application_id
           AND sp.user_id = :user_id
           AND su.role = 'student'
           AND su.status = 'active'
           AND a.status = 'Accepted'
           AND DATE(a.application_date) <= i.end_date
           AND i.end_date IS NOT NULL
           AND i.end_date <= CURDATE()
           AND c.verification_status = 'verified'
           AND cu.role = 'company'
           AND cu.status = 'active'
         FOR UPDATE"
    );

    $stmt->execute([
        'application_id' => $applicationId,
        'user_id' => $studentUserId
    ]);

    $companyId = $stmt->fetchColumn();

    if ($companyId === false) {
        throw new InvalidArgumentException(
            'This internship is not eligible for a review.'
        );
    }

    $old = $pdo->prepare(
        'SELECT review_id, student_user_id
         FROM company_reviews
         WHERE application_id = :application_id
         FOR UPDATE'
    );

    $old->execute([
        'application_id' => $applicationId
    ]);

    $previous = $old->fetch(PDO::FETCH_ASSOC);

    if (
        $previous
        && (int) $previous['student_user_id'] !== $studentUserId
    ) {
        throw new InvalidArgumentException(
            'Review ownership mismatch.'
        );
    }

    $params = [
        'rating' => $rating,
        'title' => $values['title'],
        'positives' => $values['positives'],
        'improvements' => $values['improvements']
    ];

    if ($previous) {
        $stmt = $pdo->prepare(
            "UPDATE company_reviews
             SET rating = :rating,
                 title = :title,
                 positives = :positives,
                 improvements = :improvements,
                 review_status = 'pending',
                 moderated_by = NULL,
                 moderation_note = NULL,
                 moderated_at = NULL
             WHERE review_id = :review_id
               AND student_user_id = :user_id"
        );

        $stmt->execute($params + [
            'review_id' => (int) $previous['review_id'],
            'user_id' => $studentUserId
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO company_reviews
             (student_user_id, application_id, company_id,
              rating, title, positives, improvements)
             VALUES
             (:user_id, :application_id, :company_id,
              :rating, :title, :positives, :improvements)'
        );

        $stmt->execute($params + [
            'user_id' => $studentUserId,
            'application_id' => $applicationId,
            'company_id' => (int) $companyId
        ]);
    }

    $pdo->commit();

} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}
}



public static function withdraw(
    int $userId,
    int $applicationId
): void {
    if ($applicationId < 1) {
        throw new InvalidArgumentException(
            'Invalid review.'
        );
    }

    $stmt = db()->prepare(
        "UPDATE company_reviews
         SET review_status = 'withdrawn',
             moderated_by = NULL,
             moderated_at = NULL,
             moderation_note = NULL
         WHERE application_id = :application_id
           AND student_user_id = :user_id"
    );

    $stmt->execute([
        'application_id' => $applicationId,
        'user_id' => $userId
    ]);
}



public static function publicSummary(int $companyId): array
{
    $stmt = db()->prepare(
        "SELECT
            COUNT(*) AS total,
            ROUND(AVG(r.rating), 1) AS average_rating
         FROM company_reviews r
         JOIN users u
             ON u.user_id = r.student_user_id
JOIN applications a
    ON a.application_id = r.application_id
JOIN student_profiles sp
    ON sp.student_id = a.student_id
JOIN internships i
    ON i.internship_id = a.internship_id
         WHERE r.company_id = :company_id      
AND r.student_user_id = sp.user_id
AND r.company_id = i.company_id
AND a.status = 'Accepted'
AND i.end_date IS NOT NULL
AND i.end_date <= CURDATE()
AND DATE(a.application_date) <= i.end_date
           AND r.review_status = 'approved'
           AND u.role = 'student'
           AND u.status = 'active'"
    );

    $stmt->execute([
        'company_id' => $companyId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC)
        ?: ['total' => 0, 'average_rating' => null];
}



public static function publicList(int $companyId): array
{
    $stmt = db()->prepare(
        "SELECT
            r.rating,
            r.title,
            r.positives,
            r.improvements,
            r.created_at
         FROM company_reviews r
         JOIN users u
             ON u.user_id = r.student_user_id      
JOIN applications a
    ON a.application_id = r.application_id
JOIN student_profiles sp
    ON sp.student_id = a.student_id
JOIN internships i
    ON i.internship_id = a.internship_id
         WHERE r.company_id = :company_id      
AND r.student_user_id = sp.user_id
AND r.company_id = i.company_id
AND a.status = 'Accepted'
AND i.end_date IS NOT NULL
AND i.end_date <= CURDATE()
AND DATE(a.application_date) <= i.end_date
           AND r.review_status = 'approved'
           AND u.role = 'student'
           AND u.status = 'active'
         ORDER BY r.moderated_at DESC,
                  r.review_id DESC
         LIMIT 20"
    );

    $stmt->execute([
        'company_id' => $companyId
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




public static function queue(): array
{
    require_role('admin');

    return db()->query(
        "SELECT
            r.review_id,
            r.rating,
            r.title,
            r.positives,
            r.improvements,
            r.created_at,
            r.review_status,
            c.company_name,
            u.name AS student_name
         FROM company_reviews r
         JOIN companies c
             ON c.company_id = r.company_id
         JOIN users u
             ON u.user_id = r.student_user_id
         WHERE r.review_status IN ('pending', 'approved')
         ORDER BY
             FIELD(r.review_status, 'pending', 'approved'),
             r.created_at ASC,
             r.review_id ASC
         LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
}




public static function moderate(
    int $adminId,
    int $reviewId,
    string $decision,
    string $note
): void {
    $admin = require_role('admin');

    if ((int) $admin['user_id'] !== $adminId) {
        throw new InvalidArgumentException(
            'Invalid administrator.'
        );
    }

    if (
        $reviewId < 1
        || !in_array(
            $decision,
            ['approved', 'rejected', 'hidden'],
            true
        )
    ) {
        throw new InvalidArgumentException(
            'Invalid review decision.'
        );
    }

    $note = trim($note);

    if (
        mb_strlen($note, 'UTF-8') > 500
        || (
            in_array($decision, ['rejected', 'hidden'], true)
            && $note === ''
        )
    ) {
        throw new InvalidArgumentException(
            'Provide a reason for rejecting or hiding a review.'
        );
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT review_status
             FROM company_reviews
             WHERE review_id = :id
             FOR UPDATE'
        );

        $stmt->execute(['id' => $reviewId]);
        $oldStatus = $stmt->fetchColumn();

        $isAllowed =
            (
                $oldStatus === 'pending'
                && in_array(
                    $decision,
                    ['approved', 'rejected'],
                    true
                )
            )
            || (
                $oldStatus === 'approved'
                && $decision === 'hidden'
            );

        if (!$isAllowed) {
            throw new InvalidArgumentException(
                'This review decision is no longer allowed.'
            );
        }


        
if ($decision === 'approved') {
    $check = $pdo->prepare(
        "SELECT COUNT(*)
         FROM company_reviews AS r
         JOIN applications AS a
           ON a.application_id = r.application_id
         JOIN internships AS i
           ON i.internship_id = a.internship_id
         JOIN student_profiles AS sp
           ON sp.student_id = a.student_id
         WHERE r.review_id = :review_id
           AND r.student_user_id = sp.user_id
           AND r.company_id = i.company_id
           AND a.status = 'Accepted'
           AND i.end_date IS NOT NULL
           AND i.end_date <= CURDATE()
           AND DATE(a.application_date) <= i.end_date"
    );

    $check->execute([
        'review_id' => $reviewId
    ]);

    if ((int) $check->fetchColumn() !== 1) {
        throw new InvalidArgumentException(
            'The application timeline is not eligible for publication.'
        );
    }
}


        $stmt = $pdo->prepare(
            'UPDATE company_reviews
             SET review_status = :decision,
                 moderation_note = :note,
                 moderated_by = :admin_id,
                 moderated_at = NOW()
             WHERE review_id = :review_id'
        );

        $stmt->execute([
            'decision' => $decision,
            'note' => $note ?: null,
            'admin_id' => $adminId,
            'review_id' => $reviewId
        ]);

        $stmt = $pdo->prepare(
            'INSERT INTO company_review_moderation_log
             (review_id, admin_user_id,
              old_status, new_status, note)
             VALUES
             (:review_id, :admin_id,
              :old_status, :new_status, :note)'
        );

        $stmt->execute([
            'review_id' => $reviewId,
            'admin_id' => $adminId,
            'old_status' => $oldStatus,
            'new_status' => $decision,
            'note' => $note ?: null
        ]);

        $pdo->commit();

    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}



}
