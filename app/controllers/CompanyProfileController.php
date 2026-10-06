<?php

declare(strict_types=1);

final class CompanyProfileController
{
    public static function load(int $userId): array
    {
        $statement = db()->prepare(
            'SELECT
                c.*,
                u.name AS account_name,
                u.email AS login_email
             FROM companies AS c
             JOIN users AS u ON u.user_id = c.user_id
             WHERE c.user_id = :user_id
             LIMIT 1'
        );

        $statement->execute(['user_id' => $userId]);

        $company = $statement->fetch();

        if (!$company) {
            throw new RuntimeException('Company profile not found.');
        }

        return $company;
    }

    public static function verificationLabel(string $status): string
    {
        return match ($status) {
            'verified' => 'Verified Company',
            'rejected' => 'Verification Rejected',
            default => 'Verification Pending',
        };
    }

    public static function verificationClass(string $status): string
    {
        return match ($status) {
            'verified' => 'bg-success-subtle text-success',
            'rejected' => 'bg-danger-subtle text-danger',
            default => 'bg-warning-subtle text-warning-emphasis',
        };
    }

    public static function save(int $userId, array $input): void
    {
        $limits = [
            'company_name' => 150,
            'description' => 5000,
            'industry' => 100,
            'location' => 150,
            'phone' => 30,
            'contact_email' => 150,
            'website' => 255,
        ];

        $data = [];

        foreach ($limits as $field => $limit) {
            $value = $input[$field] ?? '';

            if (!is_string($value)) {
                throw new InvalidArgumentException(
                    'Invalid company profile input.'
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

        if ($data['company_name'] === '') {
            throw new InvalidArgumentException(
                'Please enter your company name.'
            );
        }

        if (
            $data['contact_email'] !== ''
            && !filter_var(
                $data['contact_email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new InvalidArgumentException(
                'Please enter a valid contact email.'
            );
        }

        if ($data['website'] !== '') {
            $website = $data['website'];
            $parts = parse_url($website);

            if (
                !filter_var($website, FILTER_VALIDATE_URL)
                || !is_array($parts)
                || empty($parts['host'])
                || !in_array(
                    strtolower($parts['scheme'] ?? ''),
                    ['http', 'https'],
                    true
                )
                || isset($parts['user'])
                || isset($parts['pass'])
            ) {
                throw new InvalidArgumentException(
                    'Enter a website beginning with https:// or http:// '
                    . 'without a username or password.'
                );
            }
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare(
                'SELECT company_id, company_name, verification_status
                 FROM companies
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $lock->execute(['user_id' => $userId]);
            $existing = $lock->fetch();

            if (!$existing) {
                throw new RuntimeException('Company profile not found.');
            }

            $parameters = $data;

            foreach ($parameters as $field => $value) {
                $parameters[$field] = $value === '' ? null : $value;
            }

            $parameters['user_id'] = $userId;

            // File paths and verification fields are not accepted
            // from the public form.
            $statement = $pdo->prepare(
                'UPDATE companies SET
                    company_name = :company_name,
                    description = :description,
                    industry = :industry,
                    location = :location,
                    phone = :phone,
                    contact_email = :contact_email,
                    website = :website
                 WHERE user_id = :user_id'
            );

            $statement->execute($parameters);

            // A previously verified company changing its name
            // must be reviewed again.
            $nameChanged =
                $data['company_name'] !== $existing['company_name'];

            if (
                $nameChanged
                && $existing['verification_status'] === 'verified'
            ) {
                $reset = $pdo->prepare(
                    "UPDATE companies SET
                        verification_status = 'pending',
                        verification_reviewed_by = NULL,
                        verification_reviewed_at = NULL,
                        verification_notes = NULL
                     WHERE user_id = :user_id"
                );

                $reset->execute(['user_id' => $userId]);
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