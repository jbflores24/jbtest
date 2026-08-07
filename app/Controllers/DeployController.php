<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\Config;
use Jb\Core\HttpException;
use Jb\Core\Request;
use Jb\Core\Response;
use Jb\Database\Connection;
use Jb\Database\Migrator;
use Jb\Database\Seeder;
use PDO;

/**
 * Endpoints de despliegue sin shell.
 *
 * ADVERTENCIA: Este controlador SOLO debe existir en el proyecto testbed jbtest.
 * NUNCA debe replicarse en un sistema con datos reales (apiLenguas, etc.).
 * Es una excepción deliberada para un entorno de hosting compartido sin acceso
 * SSH ni terminal, donde no se puede ejecutar `php jb migrate` ni `php jb seed`.
 */
class DeployController
{
    /** @var list<string> Whitelist de seeders ejecutables vía HTTP */
    private const ALLOWED_SEEDERS = [
        'RoleSeeder',
        'UserSeeder',
        'ItemCategoriaSeeder',
        'ItemSeeder',
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly Config $config,
    ) {
    }

    /**
     * POST /admin/deploy/migrate — ejecuta migraciones pendientes.
     * Equivalente a `php jb migrate`. Idempotente: solo ejecuta las no aplicadas.
     */
    public function migrate(Request $request): Response
    {
        $this->validateDeployToken($request);

        $migrationsPath = $this->config->basePath() . '/database/migrations';
        $migrator = new Migrator($this->connection, $migrationsPath);
        $executed = $migrator->run();

        return Response::success([
            'message' => $executed === [] ? 'Sin migraciones pendientes.' : 'Migraciones ejecutadas.',
            'executed' => $executed,
            'count' => count($executed),
        ]);
    }

    /**
     * POST /admin/deploy/seed — ejecuta un seeder específico (whitelist).
     * Recibe parámetro `seeder` en el body (ej. "RoleSeeder").
     */
    public function seed(Request $request): Response
    {
        $this->validateDeployToken($request);

        $body = $request->body();
        $seederName = trim((string) ($body['seeder'] ?? ''));

        if ($seederName === '' || !in_array($seederName, self::ALLOWED_SEEDERS, true)) {
            throw new HttpException(
                'Seeder no permitido. Seeders disponibles: ' . implode(', ', self::ALLOWED_SEEDERS),
                400
            );
        }

        $seederPath = $this->config->basePath() . '/database/seeders/' . $seederName . '.php';

        if (!is_file($seederPath)) {
            throw new HttpException("Archivo de seeder no encontrado: {$seederName}.php", 400);
        }

        $seeder = require $seederPath;
        if (!$seeder instanceof Seeder) {
            throw new HttpException("El archivo no retorna una instancia de Seeder: {$seederName}", 500);
        }

        $seeder->run();

        return Response::success([
            'message' => "Seeder ejecutado: {$seederName}",
            'seeder' => $seederName,
        ]);
    }

    /**
     * GET /admin/security-scores-check — verifica integridad del conteo por IP.
     * Protegido con DEPLOY_TOKEN vía query param ?token=...
     * Recibe ?ip=X.X.X.X y devuelve las filas en security_scores para esa IP.
     */
    public function securityScoresCheck(Request $request): Response
    {
        $this->validateDeployToken($request);

        $ip = trim((string) ($request->input('ip', '')));
        if ($ip === '') {
            throw new HttpException('Parámetro ?ip= requerido.', 400);
        }

        $pdo = $this->connection->pdo();
        $stmt = $pdo->prepare(
            'SELECT id, score_key, attempts, window_start, expires_at, fingerprint
             FROM security_scores
             WHERE ip = :ip
             ORDER BY id DESC'
        );
        $stmt->execute(['ip' => $ip]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::success([
            'ip' => $ip,
            'rows_found' => count($rows),
            'rows' => $rows,
        ]);
    }

    /**
     * Valida el token de despliegue (DEPLOY_TOKEN).
     * Falla cerrado: si la variable no está definida en .env, rechaza todo.
     */
    private function validateDeployToken(Request $request): void
    {
        // El token puede venir en el body (POST) o en query param (GET)
        $body = $request->body();
        $providedToken = trim((string) ($body['token'] ?? $request->input('token', '')));

        $expectedToken = $_ENV['DEPLOY_TOKEN'] ?? null;

        if ($expectedToken === null || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            throw new HttpException('Token de despliegue inválido.', 403);
        }
    }
}