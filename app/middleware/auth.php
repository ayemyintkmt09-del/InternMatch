<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function dashboard_path(string $role): string
{
    return match ($role) {
        'student' => 'student-dashboard.php',
        'company' => 'company-dashboard.php',
        'admin' => 'admin-dashboard.php',
        default => throw new LogicException('Unknown account role.'),
    };
}

function current_user(): ?array
{
    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId) || $userId < 1) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT user_id, name, email, role, status
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $statement->execute(['user_id' => $userId]);

    $user = $statement->fetch();

    if (
        !$user
        || $user['status'] !== 'active'
        || !in_array(
            $user['role'],
            ['student', 'company', 'admin'],
            true
        )
    ) {
        $_SESSION = [];
        session_regenerate_id(true);

        return null;
    }

    return $user;
}

function require_role(string $requiredRole): array
{
    header('Cache-Control: no-store');

    $user = current_user();

    if ($user === null) {
        flash('login_error', 'Please log in to continue.');
        redirect('login.php');
    }

    if ($user['role'] !== $requiredRole) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }

    return $user;
}

function require_guest(): void
{
    header('Cache-Control: no-store');

    $user = current_user();

    if ($user !== null) {
        redirect(dashboard_path($user['role']));
    }
}