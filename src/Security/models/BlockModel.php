<?php

declare(strict_types=1);

namespace Jb\Security\models;

class BlockModel extends SecurityModel
{
    /**
     * Create or refresh an active block.
     */
    public function block(
        string $ip,
        string $reason,
        int $score,
        int $minutes = 60
    ): void {
        $now = date('Y-m-d H:i:s');
        $until = date(
            'Y-m-d H:i:s',
            time() + ($minutes * 60)
        );

        try {
            $statement = $this->pdo()->prepare(
                'INSERT INTO security_blocks
                (
                    ip,
                    active_ip,
                    reason,
                    score,
                    blocked_until,
                    active,
                    created_at
                )
             VALUES
                (
                    :ip,
                    :active_ip,
                    :reason,
                    :score,
                    :until,
                    1,
                    :created_at
                )'
            );

            $statement->execute([
                'ip' => $ip,
                'active_ip' => $ip,
                'reason' => $reason,
                'score' => $score,
                'until' => $until,
                'created_at' => $now,
            ]);
        } catch (\PDOException $e) {

            /*
         * Si otra solicitud concurrente creó primero
         * el bloqueo activo, actualizamos esa fila.
         */
            $statement = $this->pdo()->prepare(
                'UPDATE security_blocks
             SET reason = :reason,
                 score = :score,
                 blocked_until = :until
             WHERE active_ip = :ip
               AND active = 1'
            );

            $statement->execute([
                'ip' => $ip,
                'reason' => $reason,
                'score' => $score,
                'until' => $until,
            ]);
        }
    }

    /**
     * Return an active block by IP.
     *
     * @return array<string, mixed>|null
     */
    public function active(string $ip): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM security_blocks WHERE ip = :ip AND active = 1 AND blocked_until > :now ORDER BY id DESC LIMIT 1');
        $statement->execute(['ip' => $ip, 'now' => date('Y-m-d H:i:s')]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Deactivate blocks for an IP.
     */
    public function unblock(string $ip): int
    {
        $statement = $this->pdo()->prepare('UPDATE security_blocks SET active = 0 WHERE ip = :ip AND active = 1');
        $statement->execute(['ip' => $ip]);

        return $statement->rowCount();
    }

    /**
     * List recent active blocks.
     *
     * @return list<array<string, mixed>>
     */
    public function list(int $limit = 50, int $offset = 0): array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM security_blocks ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
