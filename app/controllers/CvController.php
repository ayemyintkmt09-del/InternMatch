<?php

declare(strict_types=1);

require_once __DIR__ . '/StudentProfileController.php';

final class CvController
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const PATH_PREFIX = 'storage/private/cvs/';

    public static function upload(int $userId, array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if (!is_int($error)) {
            throw new InvalidArgumentException('Invalid upload request.');
        }

        if (
            $error === UPLOAD_ERR_INI_SIZE
            || $error === UPLOAD_ERR_FORM_SIZE
        ) {
            throw new InvalidArgumentException(
                'The CV must not exceed 5 MB.'
            );
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                'Please select a PDF and wait for the upload to finish.'
            );
        }

        $temporaryPath = $file['tmp_name'] ?? null;
        $originalName = $file['name'] ?? null;

        if (
            !is_string($temporaryPath)
            || !is_string($originalName)
            || !is_uploaded_file($temporaryPath)
        ) {
            throw new InvalidArgumentException('Invalid uploaded file.');
        }

        $size = filesize($temporaryPath);

        if ($size === false || $size < 1 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException(
                'Upload a non-empty PDF no larger than 5 MB.'
            );
        }

        $originalName = basename(
            str_replace('\\', '/', $originalName)
        );

        if (
            !mb_check_encoding($originalName, 'UTF-8')
            || mb_strlen($originalName, 'UTF-8') > 255
            || preg_match('/[\x00-\x1F\x7F]/', $originalName)
            || strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            ) !== 'pdf'
        ) {
            throw new InvalidArgumentException(
                'Please upload a PDF with a valid filename.'
            );
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);

        $signature = file_get_contents(
            $temporaryPath,
            false,
            null,
            0,
            5
        );

        if ($mime !== 'application/pdf' || $signature !== '%PDF-') {
            throw new InvalidArgumentException(
                'The selected file is not a recognized PDF.'
            );
        }

        $directory = self::directory();

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0750, true) && !is_dir($directory)) {
                throw new RuntimeException(
                    'Could not create the CV storage directory.'
                );
            }
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('CV storage is not writable.');
        }

        $filename = bin2hex(random_bytes(24)) . '.pdf';
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
        $relativePath = self::PATH_PREFIX . $filename;

        $pdo = db();
        $moved = false;

        $pdo->beginTransaction();

        try {
            self::lockProfile($pdo, $userId);

            if (!move_uploaded_file($temporaryPath, $destination)) {
                throw new RuntimeException('Could not store the CV.');
            }

            $moved = true;

            $statement = $pdo->prepare(
                'UPDATE student_profiles
                 SET cv_path = :cv_path,
                     cv_original_name = :original_name,
                     cv_uploaded_at = UTC_TIMESTAMP()
                 WHERE user_id = :user_id'
            );

            $statement->execute([
                'cv_path' => $relativePath,
                'original_name' => $originalName,
                'user_id' => $userId,
            ]);

            self::updateCompletion($pdo, $userId);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Remove only a new file that was not successfully committed.
            if ($moved && is_file($destination)) {
                if (!unlink($destination)) {
                    error_log('Could not clean up an unsuccessful CV upload.');
                }
            }

            throw $exception;
        }

        // Previous CV files are retained for application history.
        // A new upload never overwrites an existing file.
    }

    public static function remove(int $userId): void
    {
        $pdo = db();

        $pdo->beginTransaction();

        try {
            self::lockProfile($pdo, $userId);

            $statement = $pdo->prepare(
                'UPDATE student_profiles
                 SET cv_path = NULL,
                     cv_original_name = NULL,
                     cv_uploaded_at = NULL
                 WHERE user_id = :user_id'
            );

            $statement->execute(['user_id' => $userId]);

            self::updateCompletion($pdo, $userId);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        // Removing the current CV does not erase submitted application CVs.
    }

    public static function downloadPath(array $profile): ?string
    {
        $storedPath = $profile['cv_path'] ?? '';

        if (!is_string($storedPath)) {
            return null;
        }

        // Accept only filenames generated by this controller.
        if (!preg_match(
            '~^storage/private/cvs/([a-f0-9]{48}\.pdf)$~D',
            $storedPath,
            $matches
        )) {
            return null;
        }

        $directory = realpath(self::directory());

        if ($directory === false) {
            return null;
        }

        $path = realpath(
            $directory . DIRECTORY_SEPARATOR . $matches[1]
        );

        if (
            $path === false
            || dirname($path) !== $directory
            || !is_file($path)
            || !is_readable($path)
        ) {
            return null;
        }

        return $path;
    }

    private static function directory(): string
    {
        return dirname(__DIR__, 2)
            . '/storage/private/cvs';
    }

    private static function lockProfile(PDO $pdo, int $userId): void
    {
        $statement = $pdo->prepare(
            'SELECT student_id
             FROM student_profiles
             WHERE user_id = :user_id
             FOR UPDATE'
        );

        $statement->execute(['user_id' => $userId]);

        if ($statement->fetchColumn() === false) {
            throw new RuntimeException('Student profile not found.');
        }
    }

    private static function updateCompletion(
        PDO $pdo,
        int $userId
    ): void {
        $profile = StudentProfileController::load($userId);

        $complete =
            StudentProfileController::completion($profile) === 100;

        $statement = $pdo->prepare(
            'UPDATE student_profiles
             SET profile_completed = :completed
             WHERE user_id = :user_id'
        );

        $statement->execute([
            'completed' => $complete ? 1 : 0,
            'user_id' => $userId,
        ]);
    }
}