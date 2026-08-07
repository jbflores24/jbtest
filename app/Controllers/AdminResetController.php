<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\Config;
use Jb\Core\HttpException;
use Jb\Core\Request;
use Jb\Core\Response;
use Jb\Database\Connection;

class AdminResetController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Config $config,
    ) {
    }

    /**
     * POST /admin/reset-security — trunca las tablas internas de rate-limit
     * y eventos de seguridad del framework para poder repetir benchmarks sin SSH.
     *
     * Protegido con un token estático (ADMIN_RESET_TOKEN) distinto al JWT normal.
     * Las tablas truncadas son: security_blocks, security_logs, security_scores,
     * security_whitelist, security_blacklist, security_audit.
     */
    public function reset(Request $request): Response
    {
        $body = $request->body();
        $providedToken = trim((string) ($body['reset_token'] ?? ''));

        $expectedToken = $_ENV['ADMIN_RESET_TOKEN'] ?? null;

        if ($expectedToken === null || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            throw new HttpException('Token de reseteo inválido.', 403);
        }

        $pdo = $this->connection->pdo();

        $tables = [
            'security_blocks',
            'security_logs',
            'security_scores',
            'security_whitelist',
            'security_blacklist',
            'security_audit',
        ];

        $truncated = [];
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE `{$table}`");
            $truncated[] = $table;
        }

        return Response::success([
            'message' => 'Tablas de seguridad reseteadas para nuevo benchmark.',
            'truncated' => $truncated,
        ]);
    }
}