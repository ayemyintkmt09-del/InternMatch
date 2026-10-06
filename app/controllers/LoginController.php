<?php

declare(strict_types=1);

final class LoginController
{
    public static function login(array $input): array
    {
        $email = isset($input['email']) && is_string($input['email'])
            ? trim($input['email'])
            : '';

        $password = isset($input['password'])
            && is_string($input['password'])
                ? $input['password']
                : '';

        $invalid = [
            'success' => false,
            'message' => 'Invalid email or password, or account unavailable.',
        ];

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 150
            || $password === ''
            || strlen($password) > 72
            || str_contains($password, "\0")
        ) {
            return $invalid;
        }

        try {
            $pdo = db();

            $statement = $pdo->prepare(
                'SELECT user_id, password, role, status
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );

            $statement->execute(['email' => $email]);

            $user = $statement->fetch();

            // Valid dummy hash keeps missing-account requests from
            // skipping password verification completely.
            $hash = $user
                ? $user['password']
                : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

            $passwordMatches = password_verify($password, $hash);

            if (
                !$user
                || !$passwordMatches
                || $user['status'] !== 'active'
                || !in_array(
                    $user['role'],
                    ['student', 'company', 'admin'],
                    true
                )
            ) {
                return $invalid;
            }

            
            $update = $pdo->prepare(
                'UPDATE users
                 SET last_login_at = UTC_TIMESTAMP()
                 WHERE user_id = :user_id'
            );

            $update->execute([
                'user_id' => $user['user_id'],
            ]);

            // Remove anonymous session data and rotate the session ID.
            $_SESSION = [];
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            return [
                'success' => true,
                'role' => $user['role'],
            ];
        } catch (Throwable $exception) {
            error_log((string) $exception);

            return [
                'success' => false,
                'message' => 'Login is temporarily unavailable. Please try again.',
            ];
        }
    }
}