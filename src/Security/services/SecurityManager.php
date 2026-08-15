<?php

declare(strict_types=1);

namespace Jb\Security\services;

use Jb\Security\models\AuditModel;
use Jb\Security\models\BlacklistModel;
use Jb\Security\models\BlockModel;
use Jb\Security\models\LogModel;
use Jb\Security\models\ScoreModel;
use Jb\Security\models\WhitelistModel;
use Jb\Security\utils\SecurityRequest;

class SecurityManager
{
    public function __construct(
        private readonly BlockModel $blocks,
        private readonly LogModel $logs,
        private readonly ScoreModel $scores,
        private readonly WhitelistModel $whitelist,
        private readonly BlacklistModel $blacklist,
        private readonly AuditModel $audit,
        private readonly ScoringEngine $scoring
    ) {
    }

    /**
     * Evalúa el estado persistido de permitido, denegado y bloqueo activo.
     */
    public function preflight(SecurityRequest $request): ?array
    {
        if ($this->whitelist->contains($request->ip)) {
            return ['allow' => true];
        }

        if ($this->blacklist->contains($request->ip)) {
            return ['blocked' => true, 'reason' => 'blacklist', 'score' => 100, 'severity' => 'critical'];
        }

        $block = $this->blocks->active($request->ip);

        return $block === null ? null : [
            'blocked' => true,
            'reason' => (string) $block['reason'],
            'score' => (int) $block['score'],
            'severity' => 'high',
        ];
    }

    /**
     * Persiste y, opcionalmente, bloquea una amenaza detectada.
     *
     * @param array{reason: string, score: int, severity: string} $result
     */
    public function recordThreat(SecurityRequest $request, array $result, bool $block = true): void
    {
        $this->logs->create([
            'ip' => $request->ip,
            'user_id' => $request->userId,
            'endpoint' => $request->path,
            'method' => $request->method,
            'reason' => $result['reason'],
            'severity' => $result['severity'],
            'score' => $result['score'],
            'fingerprint' => $request->fingerprint,
        ]);

        if ($block) {
            $this->blocks->block($request->ip, $result['reason'], $result['score'], $this->scoring->blockMinutes($result['score']));
        }
    }

    /**
     * Devuelve estadísticas del panel.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return [
            'blocks' => count($this->blocks->list(1000)),
            'logs' => count($this->logs->list(1000)),
            'whitelist' => count($this->whitelist->list()),
            'blacklist' => count($this->blacklist->list()),
            'high_risk' => $this->scores->highRisk(10),
        ];
    }

    public function blockIp(string $ip, string $reason, ?int $adminId = null): void
    {
        $this->blocks->block($ip, $reason, 100, 1440);
        $this->audit->record($adminId, 'block_ip', $ip);
    }

    public function unblockIp(string $ip, ?int $adminId = null): int
    {
        $count = $this->blocks->unblock($ip);
        $this->audit->record($adminId, 'unblock_ip', $ip);

        return $count;
    }

    public function whitelist(): WhitelistModel
    {
        return $this->whitelist;
    }

    public function blacklist(): BlacklistModel
    {
        return $this->blacklist;
    }

    public function logs(): LogModel
    {
        return $this->logs;
    }

    public function blocks(): BlockModel
    {
        return $this->blocks;
    }

    public function audit(): AuditModel
    {
        return $this->audit;
    }
}
