<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

require_post();
verify_csrf();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $settings = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $settings['path'],
        'domain' => $settings['domain'],
        'secure' => $settings['secure'],
        'httponly' => $settings['httponly'],
        'samesite' => $settings['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

redirect('login.php');