<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\Request;
use Jb\Core\Response;

class SecurityTestController
{
    /**
     * POST /security/test/trigger — endpoint de prueba que envía deliberadamente
     * un payload que dispara el motor de detección de anomalías.
     * Útil para medir bloqueo bajo carga real en benchmarks.
     */
    public function trigger(Request $request): Response
    {
        // Este endpoint recibe cualquier payload y lo procesa.
        // El SecurityMiddleware analizará el contenido y disparará detectores
        // si encuentra patrones sospechosos (SQL injection, XSS, etc.).
        $body = $request->body();

        return Response::success([
            'message' => 'Payload recibido por el endpoint de prueba de seguridad.',
            'received_keys' => array_keys($body),
        ]);
    }
}