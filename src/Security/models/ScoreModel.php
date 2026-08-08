<?php

declare(strict_types=1);

namespace Jb\Security\models;

class ScoreModel extends SecurityModel
{
    /**
     * Record a hit in a time window and return current attempts.
     *
     * @param string $key Logical counter key (e.g. ip, or "login:{ip}")
     * @param string $ip Real client IP, stored in the `ip` column
     * @param string $fingerprint Request fingerprint hash
     * @param int $window Window duration in seconds
     */
    public function hit(
        string $key,
        string $ip,
        string $fingerprint,
        int $window
    ): int {
        $now = time();
        $nowStr = date('Y-m-d H:i:s', $now);
        $expiresStr = date('Y-m-d H:i:s', $now + $window);

        $statement = $this->pdo()->prepare(
            'SELECT id
             FROM security_scores
             WHERE score_key = :key
               AND expires_at > :now
             ORDER BY id DESC
             LIMIT 1'
        );

        $statement->execute([
            'key' => $key,
            'now' => $nowStr,
        ]);

        $row = $statement->fetch();

        if (is_array($row)) {
            return $this->incrementAttempts(
                (int) $row['id'],
                $nowStr
            );
        }

        try {
            $this->pdo()->prepare(
                'INSERT INTO security_scores
                    (
                        score_key,
                        fingerprint,
                        attempts,
                        expires_at,
                        ip,
                        window_start
                    )
                 VALUES
                    (
                        :key,
                        :fp,
                        1,
                        :expires,
                        :ip,
                        :window_start
                    )'
            )->execute([
                'key' => $key,
                'fp' => $fingerprint !== ''
                    ? $fingerprint
                    : null,
                'expires' => $expiresStr,
                'ip' => $ip,
                'window_start' => $nowStr,
            ]);

            return 1;

        } catch (\PDOException $e) {
            /*
             * Another concurrent request may have inserted
             * the score row for this IP/window first.
             */
            $existing = $this->pdo()->prepare(
                'SELECT id
                 FROM security_scores
                 WHERE ip = :ip
                   AND window_start = :window_start
                 ORDER BY id DESC
                 LIMIT 1'
            );

            $existing->execute([
                'ip' => $ip,
                'window_start' => $nowStr,
            ]);

            $existingRow = $existing->fetch();

            if (is_array($existingRow)) {
                return $this->incrementAttempts(
                    (int) $existingRow['id'],
                    $nowStr
                );
            }

            /*
             * Could not persist the hit. Preserve the existing
             * fail-open behavior of the security subsystem.
             */
            return 1;
        }
    }

    /**
     * Atomically increment attempts and return the value produced
     * by this connection.
     *
     * MySQL LAST_INSERT_ID(expr) is connection-scoped, so concurrent
     * requests cannot overwrite the value returned to this request.
     */
    private function incrementAttempts(
        int $id,
        string $nowStr
    ): int {
        $statement = $this->pdo()->prepare(
            'UPDATE security_scores
             SET attempts = LAST_INSERT_ID(attempts + 1),
                 updated_at = :now
             WHERE id = :id'
        );

        $statement->execute([
            'now' => $nowStr,
            'id' => $id,
        ]);

        $value = $this->pdo()
            ->query('SELECT LAST_INSERT_ID()')
            ->fetchColumn();

        return (int) $value;
    }

    /**
     * Return high risk score rows.
     *
     * @return list<array<string, mixed>>
     */
    public function highRisk(int $limit = 10): array
    {
        $statement = $this->pdo()->prepare(
            'SELECT *
             FROM security_scores
             ORDER BY attempts DESC
             LIMIT :limit'
        );

        $statement->bindValue(
            'limit',
            $limit,
            \PDO::PARAM_INT
        );

        $statement->execute();

        return $statement->fetchAll();
    }
}