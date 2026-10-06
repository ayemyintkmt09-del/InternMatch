<?php

declare(strict_types=1);

final class CompanyFileController
{
    private const TYPES = [
        'logo' => [
            'folder' => 'company-logos',
            'column' => 'logo_path',
            'extension' => 'png',
            'limit' => 2 * 1024 * 1024,
        ],
        'verification' => [
            'folder' => 'verifications',
            'column' => 'verification_doc_path',
            'extension' => 'pdf',
            'limit' => 5 * 1024 * 1024,
        ],
    ];

    public static function upload(
        int $userId,
        string $kind,
        array $file
    ): void {
        $settings = self::settings($kind);

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if (!is_int($error)) {
            throw new InvalidArgumentException('Invalid upload request.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            $message = in_array(
                $error,
                [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE],
                true
            )
                ? 'The file exceeds the upload limit.'
                : 'Please select a file and try again.';

            throw new InvalidArgumentException($message);
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

        if (
            $size === false
            || $size < 1
            || $size > $settings['limit']
        ) {
            throw new InvalidArgumentException(
                $kind === 'logo'
                    ? 'Upload a non-empty logo no larger than 2 MB.'
                    : 'Upload a non-empty PDF no larger than 5 MB.'
            );
        }

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);

        $image = null;

        if ($kind === 'logo') {
            if (
                !in_array($extension, ['png', 'jpg', 'jpeg'], true)
                || !in_array($mime, ['image/png', 'image/jpeg'], true)
            ) {
                throw new InvalidArgumentException(
                    'Logo must be a PNG or JPEG image.'
                );
            }

            $dimensions = @getimagesize($temporaryPath);

            if (
                $dimensions === false
                || $dimensions[0] < 1
                || $dimensions[1] < 1
                || $dimensions[0] > 2048
                || $dimensions[1] > 2048
            ) {
                throw new InvalidArgumentException(
                    'Use a logo no larger than 2048 × 2048 pixels.'
                );
            }

            if (!function_exists('imagecreatefromstring')) {
                throw new RuntimeException('PHP GD is not enabled.');
            }

            $bytes = file_get_contents($temporaryPath);

            if ($bytes === false) {
                throw new RuntimeException('Could not read the logo.');
            }

            $image = @imagecreatefromstring($bytes);

            if ($image === false) {
                throw new InvalidArgumentException(
                    'The selected image could not be decoded.'
                );
            }

            imagesavealpha($image, true);
        } else {
            $signature = file_get_contents(
                $temporaryPath,
                false,
                null,
                0,
                5
            );

            if (
                $extension !== 'pdf'
                || $mime !== 'application/pdf'
                || $signature !== '%PDF-'
            ) {
                throw new InvalidArgumentException(
                    'Verification documents must be PDF files.'
                );
            }
        }

        $destination = null;
        $pdo = null;

        try {
            $pdo = db();
            $directory = self::directory($settings['folder']);

            if (!is_dir($directory)) {
                if (
                    !mkdir($directory, 0750, true)
                    && !is_dir($directory)
                ) {
                    throw new RuntimeException(
                        'Could not create storage directory.'
                    );
                }
            }

            if (!is_writable($directory)) {
                throw new RuntimeException('Storage is not writable.');
            }

            $filename = bin2hex(random_bytes(24))
                . '.'
                . $settings['extension'];

            $destination = $directory
                . DIRECTORY_SEPARATOR
                . $filename;

            $relativePath = 'storage/private/'
                . $settings['folder']
                . '/'
                . $filename;

            $pdo->beginTransaction();

            $lock = $pdo->prepare(
                'SELECT company_id
                 FROM companies
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $lock->execute(['user_id' => $userId]);

            if ($lock->fetchColumn() === false) {
                throw new RuntimeException('Company profile not found.');
            }

            if ($kind === 'logo') {
                if (!imagepng($image, $destination)) {
                    throw new RuntimeException('Could not save the logo.');
                }
            } elseif (
                !move_uploaded_file($temporaryPath, $destination)
            ) {
                throw new RuntimeException('Could not save the document.');
            }

            if ($kind === 'logo') {
                $sql = 'UPDATE companies
                        SET logo_path = :path
                        WHERE user_id = :user_id';
            } else {
                $sql = "UPDATE companies SET
                            verification_doc_path = :path,
                            verification_status = 'pending',
                            verification_reviewed_by = NULL,
                            verification_reviewed_at = NULL,
                            verification_notes = NULL
                        WHERE user_id = :user_id";
            }

            $statement = $pdo->prepare($sql);

            $statement->execute([
                'path' => $relativePath,
                'user_id' => $userId,
            ]);

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($destination !== null && is_file($destination)) {
                if (!unlink($destination)) {
                    error_log('Could not clean up failed company upload.');
                }
            }

            throw $exception;
        } finally {
            if ($image instanceof GdImage) {
                imagedestroy($image);
            }
        }
    }

    public static function path(array $company, string $kind): ?string
    {
        $settings = self::settings($kind);

        $stored = $company[$settings['column']] ?? null;

        if (!is_string($stored)) {
            return null;
        }

        $prefix = 'storage/private/' . $settings['folder'] . '/';

        $pattern = '~^'
            . preg_quote($prefix, '~')
            . '([a-f0-9]{48}\.'
            . $settings['extension']
            . ')$~D';

        if (!preg_match($pattern, $stored, $matches)) {
            return null;
        }

        $directory = realpath(
            self::directory($settings['folder'])
        );

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

    private static function settings(string $kind): array
    {
        if (!isset(self::TYPES[$kind])) {
            throw new InvalidArgumentException('Invalid file type.');
        }

        return self::TYPES[$kind];
    }

    private static function directory(string $folder): string
    {
        return dirname(__DIR__, 2)
            . '/storage/private/'
            . $folder;
    }
}