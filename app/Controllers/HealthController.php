<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\Request;
use Jb\Core\Response;

class HealthController
{
    /**
     * GET /health — baseline sin middleware de seguridad.
     * Excluido del SecurityMiddleware vía excluded_paths.
     */
    public function health(Request $request): Response
    {
        return Response::success([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'jbtest-health',
        ]);
    }

    /**
     * GET /health-secured — misma lógica, pasando por la cadena completa
     * de middleware (auth, rate-limit, detección de anomalías).
     */
    public function healthSecured(Request $request): Response
    {
        return Response::success([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'jbtest-health-secured',
            'auth' => $request->attribute('auth'),
        ]);
    }
}