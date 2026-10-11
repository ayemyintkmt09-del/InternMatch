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


                $internshipIds = array_values(array_unique(array_map(
            static fn(array $item): int =>
                (int) $item['internship_id'],
            $items
        )));

        $skillsByInternship = [];

        // Keep each query within a manageable parameter count.
        foreach (array_chunk($internshipIds, 500) as $chunk) {
            $placeholders = implode(
                ',',
                array_fill(0, count($chunk), '?')
            );

            $skillsStatement = db()->prepare(
                'SELECT
                    isk.internship_id,
                    s.skill_id,
                    s.skill_name
                 FROM internship_skills AS isk
                 JOIN skills AS s ON s.skill_id = isk.skill_id
                 WHERE isk.internship_id IN (' . $placeholders . ')'
            );

            $skillsStatement->execute($chunk);

            foreach ($skillsStatement->fetchAll() as $skill) {
                $id = (int) $skill['internship_id'];

                $skillsByInternship[$id][] = [
                    'skill_id' => $skill['skill_id'],
                    'skill_name' => $skill['skill_name'],
                ];
            }
        }

        foreach ($items as &$item) {
            $item['match_score'] = self::score(
                $profile,
                $item,
                $skillsByInternship[(int) $item['internship_id']] ?? [],
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
        $from = self::dateValue($profile['available_from'] ?? null);
        $until = self::dateValue($profile['available_until'] ?? null);
        $start = self::dateValue($internship['start_date'] ?? null);
        $end = self::dateValue($internship['end_date'] ?? null);

        if (
            $from === null
            || $until === null
            || $start === null
            || $end === null
            || $until < $from
            || $end < $start
        ) {
            return 0;
        }

        $overlapStart = $from > $start ? $from : $start;
        $overlapEnd = $until < $end ? $until : $end;

        if ($overlapEnd < $overlapStart) {
            return 0;
        }

        $internshipDays = (int) $start->diff($end)->days + 1;
        $overlapDays =
            (int) $overlapStart->diff($overlapEnd)->days + 1;

        return (int) round(
            ($overlapDays / $internshipDays) * 100
        );
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
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        $errors = DateTimeImmutable::getLastErrors();

        if (
            !$date
            || $date->format('Y-m-d') !== $value
            || (
                $errors !== false
                && (
                    $errors['warning_count'] > 0
                    || $errors['error_count'] > 0
                )
            )
        ) {
            return null;
        }

        return $date;
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


        public static function recommendations(
        int $userId,
        ?int $limit = null
    ): array {
        require_once __DIR__ . '/StudentInternshipController.php';

        $profile = StudentProfileController::load($userId);

        $appliedStatement = db()->prepare(
            'SELECT internship_id
             FROM applications
             WHERE student_id = :student_id'
        );

        $appliedStatement->execute([
            'student_id' => (int) $profile['student_id'],
        ]);

        $appliedIds = array_fill_keys(
            $appliedStatement->fetchAll(PDO::FETCH_COLUMN),
            true
        );

        $candidates = array_values(array_filter(
            StudentInternshipController::recommendationCandidates(),
            static fn(array $item): bool =>
                !isset($appliedIds[(int) $item['internship_id']])
        ));

        $items = self::attachToList($userId, $candidates);

        usort(
            $items,
            static function (array $a, array $b): int {
                $scoreOrder =
                    ($b['match_score']['overall'] ?? 0)
                    <=>
                    ($a['match_score']['overall'] ?? 0);

                return $scoreOrder !== 0
                    ? $scoreOrder
                    : (int) $b['internship_id']
                        <=> (int) $a['internship_id'];
            }
        );

        return $limit === null
            ? $items
            : array_slice($items, 0, max(0, $limit));
    }
}