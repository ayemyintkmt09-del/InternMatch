<?php

declare(strict_types=1);

require_once __DIR__ . '/middleware/auth.php';

function render_header(
    ?array $user,
    string $pageTitle,
    string $activeNav = ''
): void {
    $role = $user['role'] ?? 'guest';

    $headers = [
        'guest' => 'guest-header.php',
        'student' => 'student-header.php',
        'company' => 'company-header.php',
        'admin' => 'admin-header.php',
    ];

    if (!isset($headers[$role])) {
        throw new LogicException('Unknown layout role.');
    }

    require __DIR__ . '/views/layouts/' . $headers[$role];
}

function render_footer(?array $user): void
{
    $role = $user['role'] ?? 'guest';

    $footers = [
        'guest' => 'guest-footer.php',
        'student' => 'student-footer.php',
        'company' => 'company-footer.php',
        'admin' => 'admin-footer.php',
    ];

    if (!isset($footers[$role])) {
        throw new LogicException('Unknown layout role.');
    }

    require __DIR__ . '/views/layouts/' . $footers[$role];
}