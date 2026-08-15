<?php

declare(strict_types=1);

namespace Jb\Security\models;

class ScoreModel extends SecurityModel
{
    /**
     * Registra un hit en una ventana temporal y devuelve los intentos actuales.
     *
     * @param string $key Clave lógica del contador (por ejemplo, ip o "login:{ip}")
     * @param string $ip IP real del cliente, almacenada en la columna `ip`
     * @param string $fingerprint Huella del request
     * @param int $window Duración de la ventana en segundos
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
             * Otra solicitud concurrente pudo haber insertado
             * primero la fila de puntuación para esta IP/ventana.
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
             * No se pudo persistir el hit. Conserva el comportamiento
             * fail-open existente del subsistema de seguridad.
             */
            return 1;
        }
    }

    /**
     * Incrementa los intentos de forma atómica y devuelve el valor
     * producido por esta conexión.
     *
     * LAST_INSERT_ID(expr) de MySQL es específico de la conexión, por lo que solicitudes concurrentes
     * no pueden sobrescribir el valor devuelto a esta petición.
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
     * Devuelve las filas de puntuación de alto riesgo.
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