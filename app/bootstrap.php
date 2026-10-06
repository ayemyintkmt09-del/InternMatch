<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set($config['app']['timezone']);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name($config['session']['name']);

    $isHttps = !empty($_SERVER['HTTPS'])
        && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $config['app']['base_url'] . '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

function db(): PDO
{
    global $config;

    return Database::connection($config['database']);
}

function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function url(string $path = ''): string
{
    global $config;

    return rtrim($config['app']['base_url'], '/')
        . '/'
        . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token())
        . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? null;
    $stored = $_SESSION['csrf_token'] ?? null;

    if (
        !is_string($submitted)
        || !is_string($stored)
        || !hash_equals($stored, $submitted)
    ) {
        http_response_code(419);
        exit('Your session has expired. Reload the page and try again.');
    }
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('This action requires a POST request.');
    }
}

function flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function take_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;

    unset($_SESSION['flash'][$key]);

    return is_string($message) ? $message : null;
}