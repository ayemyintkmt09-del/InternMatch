<?php

declare(strict_types=1);

require_once __DIR__ . '/StudentProfileController.php';

final class MatchingController
{
    public static function forInternship(
        int $userId,
        array $internship
    ): array {
        $profile = StudentProfileController::load($userId);

        return self::score(
            $profile,
            $internship,
            self::requiredSkills(
                (int) $internship['internship_id']
            ),
            self::studentSkillIds(
                (int) $profile['student_id']
            )
        );
    }

    public static function attachToList(
        int $userId,
        array $items
    ): array {
        if ($items === []) {
            return $items;
        }

        $profile = StudentProfileController::load($userId);

        $studentSkillIds = self::studentSkillIds(
            (int) $profile['student_id']
        );

        foreach ($items as &$item) {
            $item['match_score'] = self::score(
                $profile,
                $item,
                self::requiredSkills(
                    (int) $item['internship_id']
                ),
                $studentSkillIds
            );
        }

        unset($item);

        return $items;
    }

    private static function score(
        array $profile,
        array $internship,
        array $requiredSkills,
        array $studentSkillIds
    ): array {
        $skill = self::skillScore(
            $requiredSkills,
            $studentSkillIds
        );

        $academic = self::academicScore(
            $profile,
            $internship
        );

        $interest = self::interestScore(
            $profile,
            $internship
        );

        $availability = self::availabilityScore(
            $profile,
            $internship
        );

        $location = self::locationScore(
            $profile,
            $internship
        );

        $overall = round(
            ($skill * 0.35)
            + ($academic * 0.25)
            + ($interest * 0.15)
            + ($availability * 0.15)
            + ($location * 0.10),
            1
        );

        return [
            'overall' => $overall,
            'skill' => $skill,
            'academic_field' => $academic,
            'interest' => $interest,
            'availability' => $availability,
            'location' => $location,
            'required_skill_count' => count($requiredSkills),
            'matched_skill_count' => self::matchedSkillCount(
                $requiredSkills,
                $studentSkillIds
            ),
        ];
    }

    private static function requiredSkills(
        int $internshipId
    ): array {
        $statement = db()->prepare(
            'SELECT s.skill_id, s.skill_name
             FROM internship_skills AS isk
             JOIN skills AS s
               ON s.skill_id = isk.skill_id
             WHERE isk.internship_id = :internship_id'
        );

        $statement->execute([
            'internship_id' => $internshipId,
        ]);

        return $statement->fetchAll();
    }

    private static function studentSkillIds(
        int $studentId
    ): array {
        $statement = db()->prepare(
            'SELECT skill_id
             FROM student_skills
             WHERE student_id = :student_id'
        );

        $statement->execute([
            'student_id' => $studentId,
        ]);

        return array_map(
            'intval',
            $statement->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    private static function skillScore(
        array $requiredSkills,
        array $studentSkillIds
    ): int {
        if ($requiredSkills === []) {
            return 100;
        }

        if ($studentSkillIds === []) {
            return 0;
        }

        return (int) round(
            self::matchedSkillCount(
                $requiredSkills,
                $studentSkillIds
            )
            / count($requiredSkills)
            * 100
        );
    }

    private static function matchedSkillCount(
        array $requiredSkills,
        array $studentSkillIds
    ): int {
        $studentSkillIds = array_fill_keys(
            $studentSkillIds,
            true
        );

        $matched = 0;

        foreach ($requiredSkills as $skill) {
            if (isset(
                $studentSkillIds[(int) $skill['skill_id']]
            )) {
                $matched++;
            }
        }

        return $matched;
    }

    private static function academicScore(
        array $profile,
        array $internship
    ): int {
        if (empty($internship['field_id'])) {
            return 100;
        }

        if (empty($profile['field_id'])) {
            return 0;
        }

        return (int) $profile['field_id']
            === (int) $internship['field_id']
            ? 100
            : 0;
    }

    private static function interestScore(
        array $profile,
        array $internship
    ): int {
        $profileTokens = self::tokens(
            (string) ($profile['interests'] ?? '')
            . ' '
            . (string) ($profile['career_interest'] ?? '')
        );

        $internshipTokens = self::tokens(
            (string) ($internship['title'] ?? '')
            . ' '
            . (string) ($internship['description'] ?? '')
        );

        if (
            $profileTokens === []
            || $internshipTokens === []
        ) {
            return 0;
        }

        $matched = count(
            array_intersect(
                $profileTokens,
                $internshipTokens
            )
        );

        return (int) round(
            $matched / count($profileTokens) * 100
        );
    }

    private static function availabilityScore(
        array $profile,
        array $internship
    ): int {
        $availableFrom = self::dateValue(
            $profile['available_from'] ?? null
        );

        $availableUntil = self::dateValue(
            $profile['available_until'] ?? null
        );

        $internshipStart = self::dateValue(
            $internship['start_date'] ?? null
        );

        $internshipEnd = self::dateValue(
            $internship['end_date'] ?? null
        );

        if (
            $availableFrom === null
            || $availableUntil === null
            || $internshipStart === null
            || $internshipEnd === null
        ) {
            return 100;
        }

        return $availableFrom <= $internshipEnd
            && $availableUntil >= $internshipStart
            ? 100
            : 0;
    }

    private static function locationScore(
        array $profile,
        array $internship
    ): int {
        $preferred = mb_strtolower(
            trim((string) (
                $profile['preferred_location'] ?? ''
            )),
            'UTF-8'
        );

        $location = mb_strtolower(
            trim((string) (
                $internship['location'] ?? ''
            )),
            'UTF-8'
        );

        $type = mb_strtolower(
            trim((string) (
                $internship['internship_type'] ?? ''
            )),
            'UTF-8'
        );

        if (
            $preferred === ''
            || $preferred === 'any location'
            || $location === ''
        ) {
            return 100;
        }

        if ($preferred === 'remote') {
            return $type === 'remote'
                || str_contains($location, 'remote')
                ? 100
                : 0;
        }

        return str_contains($location, $preferred)
            || str_contains($preferred, $location)
            ? 100
            : 0;
    }

    private static function dateValue(
        mixed $value
    ): ?DateTimeImmutable {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date instanceof DateTimeImmutable
            ? $date
            : null;
    }

    private static function tokens(string $text): array
    {
        $stopWords = array_fill_keys([
            'and',
            'the',
            'for',
            'with',
            'from',
            'that',
            'this',
            'are',
            'you',
            'your',
            'our',
            'will',
            'have',
            'has',
            'into',
            'about',
            'intern',
            'internship',
            'company',
            'work',
            'team',
            'role',
        ], true);

        if (!preg_match_all(
            '/[\p{L}\p{N}]{3,}/u',
            mb_strtolower($text, 'UTF-8'),
            $matches
        )) {
            return [];
        }

        $tokens = [];

        foreach ($matches[0] as $token) {
            if (!isset($stopWords[$token])) {
                $tokens[$token] = true;
            }
        }

        return array_keys($tokens);
    }
}