<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(array $settings): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $settings['host'],
            $settings['port'],
            $settings['name']
        );

        $pdo = new PDO(
            $dsn,
            $settings['username'],
            $settings['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Store database timestamps consistently in UTC.
        $pdo->exec("SET time_zone = '+00:00'");

        self::$connection = $pdo;

        return self::$connection;
    }
}