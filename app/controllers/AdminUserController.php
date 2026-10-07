<?php

declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

final class AdminUserController
{
    public static function search(
        string $query,
        string $role,
        string $status,
        int $page
    ): array {
        require_role('admin');

        $query = trim($query);

        if (mb_strlen($query, 'UTF-8') > 100) {
            throw new InvalidArgumentException(
                'Search must not exceed 100 characters.'
            );
        }

        if (!in_array($role, ['', 'student', 'company', 'admin'], true)) {
            throw new InvalidArgumentException('Invalid role filter.');
        }

        if (!in_array($status, ['', 'active', 'pending', 'suspended'], true)) {
            throw new InvalidArgumentException('Invalid status filter.');
        }

        $where = [];
        $parameters = [];

        if ($query !== '') {
            $where[] = '(LOCATE(:name_query, name) > 0
                         OR LOCATE(:email_query, email) > 0)';

            $parameters['name_query'] = $query;
            $parameters['email_query'] = $query;
        }

        if ($role !== '') {
            $where[] = 'role = :role';
            $parameters['role'] = $role;
        }

        if ($status !== '') {
            $where[] = 'status = :status';
            $parameters['status'] = $status;
        }

        $whereSql = $where === []
            ? ''
            : ' WHERE ' . implode(' AND ', $where);

        $pdo = db();

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM users' . $whereSql
        );

        $count->execute($parameters);
        $total = (int) $count->fetchColumn();

        $pageSize = 20;
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $pageSize;

        $statement = $pdo->prepare(
            'SELECT user_id, name, email, role, status, created_at
             FROM users'
            . $whereSql
            . ' ORDER BY user_id DESC
                LIMIT :page_size OFFSET :page_offset'
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }

        $statement->bindValue(':page_size', $pageSize, PDO::PARAM_INT);
        $statement->bindValue(':page_offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public static function changeStatus(
        int $userId,
        string $expectedStatus,
        string $newStatus
    ): void {
        $admin = require_role('admin');

        if ($userId < 1) {
            throw new InvalidArgumentException('Invalid user ID.');
        }

        if (!in_array($newStatus, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException('Invalid account status.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'SELECT user_id, role, status
                 FROM users
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $statement->execute(['user_id' => $userId]);
            $target = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                throw new InvalidArgumentException('User not found.');
            }

            if (
                $userId === (int) $admin['user_id']
                || $target['role'] === 'admin'
            ) {
                throw new InvalidArgumentException(
                    'Administrator accounts cannot be changed here.'
                );
            }

            if ($target['status'] !== $expectedStatus) {
                throw new InvalidArgumentException(
                    'This account changed since the page loaded. Refresh and try again.'
                );
            }

            $allowed = (
                $target['status'] === 'active'
                && $newStatus === 'suspended'
            ) || (
                $target['status'] === 'suspended'
                && $newStatus === 'active'
            );

            if (!$allowed) {
                throw new InvalidArgumentException(
                    'Only active accounts can be suspended and suspended accounts reactivated.'
                );
            }

            $statement = $pdo->prepare(
                'UPDATE users
                 SET status = :status
                 WHERE user_id = :user_id'
            );

            $statement->execute([
                'status' => $newStatus,
                'user_id' => $userId,
            ]);


                        $history = $pdo->prepare(
                'INSERT INTO user_status_history (
                    user_id,
                    changed_by,
                    old_status,
                    new_status
                 ) VALUES (
                    :user_id,
                    :changed_by,
                    :old_status,
                    :new_status
                 )'
            );

            $history->execute([
                'user_id' => $userId,
                'changed_by' => (int) $admin['user_id'],
                'old_status' => $target['status'],
                'new_status' => $newStatus,
            ]);


            $pdo->commit();

            
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}