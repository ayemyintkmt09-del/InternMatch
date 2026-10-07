<?php

declare(strict_types=1);

final class CompanyInternshipController
{
    public static function companyId(int $userId): int
    {
        $statement = db()->prepare(
            'SELECT company_id
             FROM companies
             WHERE user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $companyId = $statement->fetchColumn();

        if ($companyId === false) {
            throw new RuntimeException('Company profile not found.');
        }

        return (int) $companyId;
    }

    public static function fields(): array
    {
        return db()->query(
            'SELECT field_id, field_name
             FROM academic_fields
             ORDER BY field_name'
        )->fetchAll();
    }

    public static function listing(int $userId): array
    {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            'SELECT
                i.*,
                af.field_name
             FROM internships AS i
             LEFT JOIN academic_fields AS af
                ON af.field_id = i.field_id
             WHERE i.company_id = :company_id
             ORDER BY i.created_at DESC, i.internship_id DESC'
        );

        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }

    private static function text(
        array $input,
        string $key,
        int $limit
    ): string {
        $value = $input[$key] ?? '';

        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid form input.');
        }

        $value = trim($value);

        if (mb_strlen($value, 'UTF-8') > $limit) {
            $label = ucfirst(str_replace('_', ' ', $key));

            throw new InvalidArgumentException(
                "{$label} must not exceed {$limit} characters."
            );
        }

        return $value;
    }

    private static function date(
        array $input,
        string $key
    ): ?string {
        $value = self::text($input, $key, 10);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (
            !$date
            || $date->format('Y-m-d') !== $value
            || (int) $date->format('Y') < 1000
        ) {
            throw new InvalidArgumentException(
                'Please enter a valid '
                . str_replace('_', ' ', $key)
                . '.'
            );
        }

        return $value;
    }

    public static function saveDraft(
            int $userId,
            array $input,
            ?int $internshipId = null
        ): void {
        $companyId = self::companyId($userId);

        $title = self::text($input, 'title', 150);
        $description = self::text($input, 'description', 10000);
        $location = self::text($input, 'location', 150);
        $duration = self::text($input, 'duration', 100);
        $type = self::text($input, 'internship_type', 20);

        if ($title === '' || $description === '') {
            throw new InvalidArgumentException(
                'Please enter an internship title and description.'
            );
        }

        if (!in_array($type, ['On-site', 'Remote', 'Hybrid'], true)) {
            throw new InvalidArgumentException(
                'Please select a valid internship type.'
            );
        }

        $internsValue = self::text($input, 'interns_needed', 10);

        $internsNeeded = filter_var(
            $internsValue,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 10000,
                ],
            ]
        );

        if ($internsNeeded === false) {
            throw new InvalidArgumentException(
                'Interns needed must be a whole number from 1 to 10,000.'
            );
        }

        $fieldValue = self::text($input, 'field_id', 10);
        $fieldId = null;

        if ($fieldValue !== '') {
            $fieldId = filter_var(
                $fieldValue,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($fieldId === false) {
                throw new InvalidArgumentException(
                    'Please select a valid academic field.'
                );
            }

            $check = db()->prepare(
                'SELECT field_id
                 FROM academic_fields
                 WHERE field_id = :field_id'
            );

            $check->execute(['field_id' => $fieldId]);

            if ($check->fetchColumn() === false) {
                throw new InvalidArgumentException(
                    'The selected academic field is unavailable.'
                );
            }
        }

        $startDate = self::date($input, 'start_date');
        $endDate = self::date($input, 'end_date');
        $deadline = self::date($input, 'deadline');

        if (
            $startDate !== null
            && $endDate !== null
            && $endDate < $startDate
        ) {
            throw new InvalidArgumentException(
                'End date cannot be earlier than start date.'
            );
        }

        if (
            $deadline !== null
            && $startDate !== null
            && $deadline > $startDate
        ) {
            throw new InvalidArgumentException(
                'Application deadline cannot be later than start date.'
            );
        }

        $skillIds = self::validateSkills($input['skills'] ?? []);

        $parameters = [
            'company_id' => $companyId,
            'title' => $title,
            'description' => $description,
            'field_id' => $fieldId,
            'location' => $location === '' ? null : $location,
            'internship_type' => $type,
            'duration' => $duration === '' ? null : $duration,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'deadline' => $deadline,
            'interns_needed' => $internsNeeded,
        ];

        $pdo = db();
        $pdo->beginTransaction();

        try {
            if ($internshipId === null) {
                $statement = $pdo->prepare(
                    "INSERT INTO internships (
                        company_id,
                        title,
                        description,
                        field_id,
                        location,
                        internship_type,
                        duration,
                        start_date,
                        end_date,
                        deadline,
                        interns_needed,
                        status
                     ) VALUES (
                        :company_id,
                        :title,
                        :description,
                        :field_id,
                        :location,
                        :internship_type,
                        :duration,
                        :start_date,
                        :end_date,
                        :deadline,
                        :interns_needed,
                        'Draft'
                     )"
                );

                $statement->execute($parameters);
                $internshipId = (int) $pdo->lastInsertId();
            } else {
                $lock = $pdo->prepare(
                    'SELECT status
                     FROM internships
                     WHERE internship_id = :internship_id
                       AND company_id = :company_id
                     FOR UPDATE'
                );

                $lock->execute([
                    'internship_id' => $internshipId,
                    'company_id' => $companyId,
                ]);

                $status = $lock->fetchColumn();

                if ($status === false) {
                    throw new InvalidArgumentException(
                        'Internship not found.'
                    );
                }

                if ($status !== 'Draft') {
                    throw new InvalidArgumentException(
                        'Only draft internships can be edited here.'
                    );
                }

                $parameters['internship_id'] = $internshipId;

                $statement = $pdo->prepare(
                    "UPDATE internships SET
                        title = :title,
                        description = :description,
                        field_id = :field_id,
                        location = :location,
                        internship_type = :internship_type,
                        duration = :duration,
                        start_date = :start_date,
                        end_date = :end_date,
                        deadline = :deadline,
                        interns_needed = :interns_needed
                     WHERE internship_id = :internship_id
                       AND company_id = :company_id
                       AND status = 'Draft'"
                );

                $statement->execute($parameters);
            }

            $delete = $pdo->prepare(
                'DELETE FROM internship_skills
                 WHERE internship_id = :internship_id'
            );

            $delete->execute(['internship_id' => $internshipId]);

            $insertSkill = $pdo->prepare(
                'INSERT INTO internship_skills (
                    internship_id,
                    skill_id
                 ) VALUES (
                    :internship_id,
                    :skill_id
                 )'
            );

            foreach ($skillIds as $skillId) {
                $insertSkill->execute([
                    'internship_id' => $internshipId,
                    'skill_id' => $skillId,
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



        public static function skills(): array
    {
        return db()->query(
            'SELECT skill_id, skill_name
             FROM skills
             ORDER BY skill_name'
        )->fetchAll();
    }

    private static function validateSkills(mixed $input): array
    {
        if (!is_array($input) || count($input) > 100) {
            throw new InvalidArgumentException(
                'Please select a valid list of skills.'
            );
        }

        $ids = [];

        foreach ($input as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid skill selection.'
                );
            }

            $id = filter_var(
                $value,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($id === false) {
                throw new InvalidArgumentException(
                    'Invalid skill selection.'
                );
            }

            $ids[] = $id;
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($ids), '?')
        );

        $statement = db()->prepare(
            "SELECT skill_id
             FROM skills
             WHERE skill_id IN ($placeholders)"
        );

        $statement->execute($ids);

        if (count($statement->fetchAll(PDO::FETCH_COLUMN)) !== count($ids)) {
            throw new InvalidArgumentException(
                'One or more selected skills are unavailable.'
            );
        }

        return $ids;
    }

    public static function loadDraft(
        int $userId,
        int $internshipId
    ): array {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            'SELECT *
             FROM internships
             WHERE internship_id = :internship_id
               AND company_id = :company_id
             LIMIT 1'
        );

        $statement->execute([
            'internship_id' => $internshipId,
            'company_id' => $companyId,
        ]);

        $internship = $statement->fetch();

        if (!$internship) {
            throw new InvalidArgumentException(
                'Internship not found.'
            );
        }

        if ($internship['status'] !== 'Draft') {
            throw new InvalidArgumentException(
                'Only draft internships can be edited here.'
            );
        }

        $skills = db()->prepare(
            'SELECT skill_id
             FROM internship_skills
             WHERE internship_id = :internship_id'
        );

        $skills->execute(['internship_id' => $internshipId]);

        $internship['skills'] = array_map(
            'strval',
            $skills->fetchAll(PDO::FETCH_COLUMN)
        );

        return $internship;
    }



        public static function publish(
        int $userId,
        int $internshipId
    ): void {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $companyStatement = $pdo->prepare(
                'SELECT company_id, verification_status
                 FROM companies
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $companyStatement->execute(['user_id' => $userId]);
            $company = $companyStatement->fetch();

            if (!$company) {
                throw new RuntimeException('Company profile not found.');
            }

            if ($company['verification_status'] !== 'verified') {
                throw new InvalidArgumentException(
                    'Your company must be verified before publishing. '
                    . 'You can continue saving and editing drafts.'
                );
            }

            $statement = $pdo->prepare(
                'SELECT *
                 FROM internships
                 WHERE internship_id = :internship_id
                   AND company_id = :company_id
                 FOR UPDATE'
            );

            $statement->execute([
                'internship_id' => $internshipId,
                'company_id' => (int) $company['company_id'],
            ]);

            $internship = $statement->fetch();

            if (!$internship) {
                throw new InvalidArgumentException(
                    'Internship not found.'
                );
            }

            if ($internship['status'] !== 'Draft') {
                throw new InvalidArgumentException(
                    'Only draft internships can be published.'
                );
            }

            if (
                trim((string) $internship['title']) === ''
                || trim((string) $internship['description']) === ''
                || empty($internship['field_id'])
                || trim((string) $internship['duration']) === ''
                || (int) $internship['interns_needed'] < 1
            ) {
                throw new InvalidArgumentException(
                    'Add a title, description, academic field, duration '
                    . 'and at least one position before publishing.'
                );
            }

            if (
                $internship['internship_type'] !== 'Remote'
                && trim((string) $internship['location']) === ''
            ) {
                throw new InvalidArgumentException(
                    'Add a location for an on-site or hybrid internship.'
                );
            }

            $startDate = self::date($internship, 'start_date');
            $endDate = self::date($internship, 'end_date');
            $deadline = self::date($internship, 'deadline');

            if (
                $startDate === null
                || $endDate === null
                || $deadline === null
            ) {
                throw new InvalidArgumentException(
                    'Add the start date, end date and application deadline.'
                );
            }

            if ($deadline < date('Y-m-d')) {
                throw new InvalidArgumentException(
                    'The application deadline cannot be in the past.'
                );
            }

            if ($deadline > $startDate || $endDate < $startDate) {
                throw new InvalidArgumentException(
                    'Check your dates: deadline must be on or before '
                    . 'start date, and end date must be on or after start date.'
                );
            }

            $skills = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM internship_skills
                 WHERE internship_id = :internship_id'
            );

            $skills->execute(['internship_id' => $internshipId]);

            if ((int) $skills->fetchColumn() < 1) {
                throw new InvalidArgumentException(
                    'Select at least one required skill before publishing.'
                );
            }

            $update = $pdo->prepare(
                "UPDATE internships SET
                    status = 'Published',
                    published_at = CURRENT_TIMESTAMP
                 WHERE internship_id = :internship_id
                   AND company_id = :company_id
                   AND status = 'Draft'"
            );

            $update->execute([
                'internship_id' => $internshipId,
                'company_id' => (int) $company['company_id'],
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }




        public static function dashboard(int $userId): array
        {
            $pdo = db();
            $companyId = self::companyId($userId);

            $activeStatement = $pdo->prepare(
                "SELECT COUNT(*)
                FROM internships AS i
                INNER JOIN companies AS c
                    ON c.company_id = i.company_id
                INNER JOIN users AS u
                    ON u.user_id = c.user_id
                WHERE i.company_id = :company_id
                AND i.status = 'Published'
                AND i.deadline >= :today
                AND c.verification_status = 'verified'
                AND u.status = 'active'
                AND u.role = 'company'"
            );

            $activeStatement->execute([
                'company_id' => $companyId,
                'today' => date('Y-m-d'),
            ]);

            $statusCounts = [
                'Pending' => 0,
                'Under Review' => 0,
                'Shortlisted' => 0,
                'Accepted' => 0,
                'Rejected' => 0,
                'Withdrawn' => 0,
            ];

            $statusStatement = $pdo->prepare(
                'SELECT a.status, COUNT(*) AS total
                FROM applications AS a
                INNER JOIN internships AS i
                    ON i.internship_id = a.internship_id
                WHERE i.company_id = :company_id
                GROUP BY a.status'
            );

            $statusStatement->execute([
                'company_id' => $companyId,
            ]);

            foreach ($statusStatement->fetchAll() as $row) {
                $statusCounts[$row['status']] = (int) $row['total'];
            }

            $latestStatement = $pdo->prepare(
                'SELECT
                    i.*,
                    (
                        SELECT COUNT(*)
                        FROM applications AS a
                        WHERE a.internship_id = i.internship_id
                    ) AS application_count
                FROM internships AS i
                WHERE i.company_id = :company_id
                ORDER BY i.created_at DESC, i.internship_id DESC
                LIMIT 5'
            );

            $latestStatement->execute([
                'company_id' => $companyId,
            ]);

            return [
                'active_internships' =>
                    (int) $activeStatement->fetchColumn(),
                'total_applications' => array_sum($statusCounts),
                'under_review' => $statusCounts['Under Review'],
                'shortlisted' => $statusCounts['Shortlisted'],
                'status_counts' => $statusCounts,
                'latest_internships' => $latestStatement->fetchAll(),
            ];
        }


        

        public static function close(
        int $userId,
        int $internshipId
    ): void {
        $companyId = self::companyId($userId);

        $statement = db()->prepare(
            "UPDATE internships
             SET status = 'Closed'
             WHERE internship_id = :internship_id
               AND company_id = :company_id
               AND status = 'Published'"
        );

        $statement->execute([
            'internship_id' => $internshipId,
            'company_id' => $companyId,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException(
                'The internship was not found or is no longer published.'
            );
        }
    }
}