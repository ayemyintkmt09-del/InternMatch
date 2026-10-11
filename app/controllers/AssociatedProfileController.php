<?php

declare(strict_types=1);

final class AssociatedProfileController
{
    public static function student(
        array $viewer,
        int $studentId
    ): ?array {
        $stmt = db()->prepare(
            'SELECT
                sp.student_id,
                sp.user_id,
                u.name,
                u.status,
                sp.university,
                sp.degree,
                sp.academic_year,
                sp.bio,
                sp.location,
                sp.career_interest,
                sp.profile_photo,
                sp.available_from,
                sp.available_until,
                af.field_name
             FROM student_profiles AS sp
             JOIN users AS u
                ON u.user_id = sp.user_id
               AND u.role = \'student\'
             LEFT JOIN academic_fields AS af
                ON af.field_id = sp.field_id
             WHERE sp.student_id = :student_id
             LIMIT 1'
        );

        $stmt->execute(['student_id' => $studentId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            return null;
        }

        $isAdmin = $viewer['role'] === 'admin';
        $isOwner =
            $viewer['role'] === 'student'
            && (int) $viewer['user_id'] === (int) $profile['user_id'];

        if (!$isAdmin && $profile['status'] !== 'active') {
            return null;
        }

        if (!$isAdmin && !$isOwner) {
            if ($viewer['role'] !== 'company') {
                return null;
            }

            $relation = db()->prepare(
                'SELECT 1
                 FROM applications AS a
                 JOIN internships AS i
                    ON i.internship_id = a.internship_id
                 JOIN companies AS c
                    ON c.company_id = i.company_id
                 WHERE a.student_id = :student_id
                   AND c.user_id = :viewer_id
                 LIMIT 1'
            );

            $relation->execute([
                'student_id' => $studentId,
                'viewer_id' => (int) $viewer['user_id'],
            ]);

            if ($relation->fetchColumn() === false) {
                return null;
            }
        }

        $skills = db()->prepare(
            'SELECT s.skill_name, ss.proficiency_level
             FROM student_skills AS ss
             JOIN skills AS s ON s.skill_id = ss.skill_id
             WHERE ss.student_id = :student_id
             ORDER BY s.skill_name'
        );

        $skills->execute(['student_id' => $studentId]);
        $profile['skills'] = $skills->fetchAll();

        $portfolio = db()->prepare(
            'SELECT title, item_type, organization, description
             FROM student_portfolio_items
             WHERE user_id = :user_id
               AND is_public = 1
             ORDER BY created_at DESC, portfolio_id DESC'
        );

        $portfolio->execute([
            'user_id' => (int) $profile['user_id'],
        ]);

        $profile['portfolio'] = $portfolio->fetchAll();

        return $profile;
    }
}