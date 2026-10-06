<?php

declare(strict_types=1);

final class StudentProfileController
{
    public const OPTIONS = [
        'academic_year' => [
            'First Year',
            'Second Year',
            'Third Year',
            'Fourth Year',
            'Final Year',
        ],
        'preferred_location' => [
            'Yangon',
            'Mandalay',
            'Naypyidaw',
            'Remote',
            'Any Location',
        ],
        'availability' => [
            'Full-time',
            'Part-time',
            'Flexible',
        ],
        'preferred_internship_type' => [
            'On-site',
            'Remote',
            'Hybrid',
            'Any Type',
        ],
        'preferred_duration' => [
            '1 - 3 Months',
            '3 - 6 Months',
            '6 - 12 Months',
            'Any Duration',
        ],
    ];

    public static function load(int $userId): array
    {
        $statement = db()->prepare(
            'SELECT sp.*, u.name, u.email
             FROM student_profiles AS sp
             JOIN users AS u ON u.user_id = sp.user_id
             WHERE sp.user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $profile = $statement->fetch();

        if (!$profile) {
            throw new RuntimeException('Student profile not found.');
        }

        $profile['first_name'] ??= $profile['name'];
        $profile['last_name'] ??= '';

        $skills = db()->prepare(
            'SELECT s.skill_name
             FROM student_skills AS ss
             JOIN skills AS s ON s.skill_id = ss.skill_id
             WHERE ss.student_id = :student_id
             ORDER BY s.skill_name'
        );

        $skills->execute([
            'student_id' => $profile['student_id'],
        ]);

        $profile['skills_text'] = implode(
            ', ',
            $skills->fetchAll(PDO::FETCH_COLUMN)
        );

        return $profile;
    }

    public static function academicFields(): array
    {
        return db()->query(
            'SELECT field_id, field_name
            FROM academic_fields
            ORDER BY field_name'
        )->fetchAll();
    }

    public static function catalogue(): array
    {
        return db()->query(
            'SELECT skill_id, skill_name
             FROM skills
             ORDER BY skill_name'
        )->fetchAll();
    }





    public static function completion(array $profile): int
{
    $fields = [
        'first_name',
        'phone',
        'university',
        'degree',
        'academic_year',
        'field_id',
        'skills_text',
        'interests',
        'preferred_location',
        'availability',
        'available_from',
        'bio',
        'cv_path',
    ];

    $filled = 0;

    foreach ($fields as $field) {
        $value = trim((string) ($profile[$field] ?? ''));

        if ($value !== '') {
            $filled++;
        }
    }

    return (int) round(
        ($filled / count($fields)) * 100
    );
}







    public static function save(int $userId, array $input): void
    {
        $pdo = db();
        $existing = self::load($userId);
        $data = [];

        $limits = [
            'first_name' => 100,
            'last_name' => 100,
            'phone' => 30,
            'university' => 150,
            'degree' => 150,
            'academic_year' => 50,
            'preferred_location' => 150,
            'availability' => 100,
            'preferred_internship_type' => 20,
            'preferred_duration' => 30,
            'interests' => 2000,
            'career_interest' => 255,
            'bio' => 5000,
            'skills_text' => 2000,
            'graduation_year' => 4,
            'field_id' => 10,
            'location' => 150,
            'available_from' => 10,
            'available_until' => 10,
        ];

        foreach ($limits as $field => $limit) {
            $value = $input[$field] ?? '';

            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid profile input.'
                );
            }

            $value = trim($value);

            if (mb_strlen($value, 'UTF-8') > $limit) {
                throw new InvalidArgumentException(
                    str_replace('_', ' ', ucfirst($field))
                    . " must not exceed {$limit} characters."
                );
            }

            $data[$field] = $value;
        }

        if ($data['first_name'] === '') {
            throw new InvalidArgumentException(
                'Please enter your first name.'
            );
        }

        $displayName = trim(
            $data['first_name'] . ' ' . $data['last_name']
        );

        if (mb_strlen($displayName, 'UTF-8') > 100) {
            throw new InvalidArgumentException(
                'Your combined name must not exceed 100 characters.'
            );
        }

        foreach (self::OPTIONS as $field => $options) {
            $value = $data[$field];

            // Preserve older values imported from the original backend.
            $previousValue = (string) ($existing[$field] ?? '');

            if (
                $value !== ''
                && !in_array($value, $options, true)
                && $value !== $previousValue
            ) {
                throw new InvalidArgumentException(
                    'Please choose a valid '
                    . str_replace('_', ' ', $field)
                    . '.'
                );
            }
        }

        $year = $data['graduation_year'];

        if (
            $year !== ''
            && (
                !preg_match('/^[0-9]{4}$/D', $year)
                || (int) $year < 1900
                || (int) $year > (int) date('Y') + 15
            )
        ) {
            throw new InvalidArgumentException(
                'Please enter a valid graduation year.'
            );
        }
        



        // Check that the selected academic field exists.
if ($data['field_id'] !== '') {
    if (!preg_match('/^[1-9][0-9]{0,9}$/D', $data['field_id'])) {
        throw new InvalidArgumentException(
            'Please select a valid academic field.'
        );
    }

    $fieldCheck = $pdo->prepare(
        'SELECT field_id
         FROM academic_fields
         WHERE field_id = :field_id'
    );

    $fieldCheck->execute([
        'field_id' => $data['field_id'],
    ]);

    if ($fieldCheck->fetchColumn() === false) {
        throw new InvalidArgumentException(
            'The selected academic field is unavailable.'
        );
    }
}

        // Validate real calendar dates, not just their text format.
        foreach (['available_from', 'available_until'] as $dateField) {
            $value = $data[$dateField];

            if ($value === '') {
                continue;
            }

            if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)) {
                throw new InvalidArgumentException(
                    'Please enter valid availability dates.'
                );
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            if (
                $date === false
                || $date->format('Y-m-d') !== $value
                || (int) $date->format('Y') < 1900
                || (int) $date->format('Y') > 2100
            ) {
                throw new InvalidArgumentException(
                    'Please enter availability dates between 1900 and 2100.'
                );
            }
        }

        if (
            $data['available_from'] !== ''
            && $data['available_until'] !== ''
            && $data['available_until'] < $data['available_from']
        ) {
            throw new InvalidArgumentException(
                'Available Until must be on or after Available From.'
            );
        }


        // Use the existing skills catalogue for consistent matching.
        $catalogue = [];

        foreach (self::catalogue() as $skill) {
            $key = mb_strtolower($skill['skill_name'], 'UTF-8');
            $catalogue[$key] = (int) $skill['skill_id'];
        }

        $skillIds = [];

        foreach (explode(',', $data['skills_text']) as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $key = mb_strtolower($name, 'UTF-8');

            if (!isset($catalogue[$key])) {
                throw new InvalidArgumentException(
                    "Unknown skill: {$name}. "
                    . 'Please use a name from the available skills list.'
                );
            }

            $skillIds[$catalogue[$key]] = true;
        }

        if (count($skillIds) > 30) {
            throw new InvalidArgumentException(
                'Please select no more than 30 skills.'
            );
        }

        $pdo->beginTransaction();

        try {
            // Serialize profile saves for this student.
            $lock = $pdo->prepare(
                'SELECT student_id, cv_path
                 FROM student_profiles
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $lock->execute(['user_id' => $userId]);
            $lockedProfile = $lock->fetch();

            if (!$lockedProfile) {
                throw new RuntimeException('Student profile not found.');
            }

            $studentId = (int) $lockedProfile['student_id'];

            $updateUser = $pdo->prepare(
                'UPDATE users
                 SET name = :name
                 WHERE user_id = :user_id'
            );

            $updateUser->execute([
                'name' => $displayName,
                'user_id' => $userId,
            ]);

            $completionData = array_merge($existing, $data);
            $completionData['cv_path'] = $lockedProfile['cv_path'];

            $parameters = $data;
            unset($parameters['skills_text']);

            foreach ($parameters as $key => $value) {
                $parameters[$key] = $value === '' ? null : $value;
            }

            $parameters['profile_completed'] =
                self::completion($completionData) === 100 ? 1 : 0;

            $parameters['user_id'] = $userId;

            $updateProfile = $pdo->prepare(
                'UPDATE student_profiles SET
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone,
                    university = :university,
                    degree = :degree,
                    field_id = :field_id,
                    location = :location,
                    available_from = :available_from,
                    available_until = :available_until,
                    academic_year = :academic_year,
                    graduation_year = :graduation_year,
                    preferred_location = :preferred_location,
                    availability = :availability,
                    preferred_internship_type = :preferred_internship_type,
                    preferred_duration = :preferred_duration,
                    interests = :interests,
                    career_interest = :career_interest,
                    bio = :bio,
                    profile_completed = :profile_completed
                 WHERE user_id = :user_id'
            );

            $updateProfile->execute($parameters);

            // Keep existing proficiency levels for retained skills.
            $previous = $pdo->prepare(
                'SELECT skill_id, proficiency_level
                 FROM student_skills
                 WHERE student_id = :student_id'
            );

            $previous->execute(['student_id' => $studentId]);

            $levels = $previous->fetchAll(PDO::FETCH_KEY_PAIR);

            $delete = $pdo->prepare(
                'DELETE FROM student_skills
                 WHERE student_id = :student_id'
            );

            $delete->execute(['student_id' => $studentId]);

            $insert = $pdo->prepare(
                'INSERT INTO student_skills (
                    student_id,
                    skill_id,
                    proficiency_level
                 ) VALUES (
                    :student_id,
                    :skill_id,
                    :proficiency_level
                 )'
            );

            foreach (array_keys($skillIds) as $skillId) {
                $insert->execute([
                    'student_id' => $studentId,
                    'skill_id' => $skillId,
                    'proficiency_level' =>
                        $levels[$skillId] ?? 'Intermediate',
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
}