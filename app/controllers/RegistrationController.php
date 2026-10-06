<?php

declare(strict_types=1);

final class RegistrationController
{
    public static function register(array $input): array
    {
        $name = trim(self::text($input, 'full_name'));
        $email = trim(self::text($input, 'email'));

        // Do not trim passwords.
        $password = self::text($input, 'password');
        $confirmation = self::text($input, 'confirm_password');

        $role = self::text($input, 'account_type');
        $terms = self::text($input, 'terms');

        // Never allow public creation of administrator accounts.
        if (!in_array($role, ['student', 'company'], true)) {
            return self::failure(
                'Please select Student or Company. '
                . 'Administrator accounts are created privately.'
            );
        }

        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
            return self::failure(
                'Please enter a name containing 1 to 100 characters.'
            );
        }

        if (
            strlen($email) > 150
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            return self::failure('Please enter a valid email address.');
        }

        // Bcrypt accepts at most 72 bytes without truncation.
        if (
            mb_strlen($password, 'UTF-8') < 8
            || strlen($password) > 72
            || str_contains($password, "\0")
        ) {
            return self::failure(
                'Use a password with at least 8 characters '
                . 'and no more than 72 bytes.'
            );
        }

        if ($password !== $confirmation) {
            return self::failure('Passwords do not match.');
        }

        if ($terms !== '1') {
            return self::failure(
                'Please accept the terms before registering.'
            );
        }

        $pdo = null;

        try {
            $pdo = db();

            $check = $pdo->prepare(
                'SELECT user_id
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $check->execute(['email' => $email]);

            if ($check->fetch()) {
                return self::failure(
                    'An account already uses this email address.'
                );
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );

            $pdo->beginTransaction();

            $statement = $pdo->prepare(
                'INSERT INTO users (
                    name,
                    email,
                    password,
                    role,
                    status
                 ) VALUES (
                    :name,
                    :email,
                    :password,
                    :role,
                    :status
                 )'
            );

            $statement->execute([
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
                'role' => $role,
                'status' => 'active',
            ]);

            $userId = (int) $pdo->lastInsertId();

            if ($role === 'student') {
                $profile = $pdo->prepare(
                    'INSERT INTO student_profiles (user_id)
                     VALUES (:user_id)'
                );

                $profile->execute([
                    'user_id' => $userId,
                ]);
            } else {
                $profile = $pdo->prepare(
                    'INSERT INTO companies (
                        user_id,
                        company_name,
                        verification_status
                     ) VALUES (
                        :user_id,
                        :company_name,
                        :verification_status
                     )'
                );

                $profile->execute([
                    'user_id' => $userId,
                    'company_name' => $name,
                    'verification_status' => 'pending',
                ]);
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Account created successfully. '
                    . 'Your account is ready for the login step.',
            ];

            
        } catch (Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Also handles simultaneous registrations for the same email.
            if (
                $exception instanceof PDOException
                && (int) ($exception->errorInfo[1] ?? 0) === 1062
            ) {
                return self::failure(
                    'An account already uses this email address.'
                );
            }

            error_log((string) $exception);

            return self::failure(
                'Registration could not be completed. Please try again.'
            );
        }
    }

    private static function text(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    private static function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
}