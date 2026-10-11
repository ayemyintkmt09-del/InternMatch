<?php
declare(strict_types=1);

final class StudentCvShareController
{
    public static function ensure(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT cv_id, is_active
             FROM student_cv_shares
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            return $row;
        }

        $cvId = 'IMCV-' . strtoupper(
            bin2hex(random_bytes(8))
        );

        $stmt = db()->prepare(
            'INSERT INTO student_cv_shares
             (user_id, cv_id)
             VALUES (:user_id, :cv_id)
             ON DUPLICATE KEY UPDATE user_id = user_id'
        );

        $stmt->execute([
            'user_id' => $userId,
            'cv_id' => $cvId,
        ]);

                $read = db()->prepare(
            'SELECT cv_id, is_active
             FROM student_cv_shares
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $read->execute(['user_id' => $userId]);
        $created = $read->fetch(PDO::FETCH_ASSOC);

        if ($created === false) {
            throw new RuntimeException(
                'The CV-sharing record could not be initialized.'
            );
        }

        return $created;
    }


    
public static function generateLink(int $userId): string
{

    self::ensure($userId);
    
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $stmt = db()->prepare(
        'UPDATE student_cv_shares
         SET token_hash = :token_hash,
             is_active = 1
         WHERE user_id = :user_id'
    );

    $stmt->execute([
        'token_hash' => $hash,
        'user_id' => $userId,
    ]);

    return url('public-student-cv.php?token=' . $token);
}


public static function disable(int $userId): void
{
    $stmt = db()->prepare(
        'UPDATE student_cv_shares
         SET is_active = 0,
             token_hash = NULL
         WHERE user_id = :user_id'
    );

    $stmt->execute([
        'user_id' => $userId,
    ]);
}



public static function publicProfile(
    string $token
): ?array {
    if (!preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT
            cvs.cv_id,
            u.name,
            sp.student_id,
            sp.user_id,
            sp.university,
            sp.degree,
            sp.bio,
            sp.career_interest
         FROM student_cv_shares AS cvs
         JOIN users AS u
           ON u.user_id = cvs.user_id
         JOIN student_profiles AS sp
           ON sp.user_id = u.user_id
         WHERE cvs.token_hash = :token_hash
           AND cvs.is_active = 1
           AND u.role = \'student\'
           AND u.status = \'active\'
         LIMIT 1'
    );

    $stmt->execute([
        'token_hash' => hash('sha256', $token),
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}



    
public static function publicSkills(
    int $studentId
): array {
    $stmt = db()->prepare(
        'SELECT s.skill_name, ss.proficiency_level
         FROM student_skills AS ss
         JOIN skills AS s
           ON s.skill_id = ss.skill_id
         WHERE ss.student_id = :student_id
         ORDER BY s.skill_name'
    );

    $stmt->execute([
        'student_id' => $studentId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



        
public static function publicPortfolio(
    int $userId
): array {
    $stmt = db()->prepare(
        'SELECT item_type, title, organization,
                description, link_url,
                start_date, end_date
         FROM student_portfolio_items
         WHERE user_id = :user_id
           AND is_public = 1
         ORDER BY portfolio_id DESC
         LIMIT 40'
    );

    $stmt->execute([
        'user_id' => $userId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



}
