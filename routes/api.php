<?php

declare(strict_types=1);

use App\Controllers\AdminResetController;
use App\Controllers\AuthController;
use App\Controllers\DeployController;
use App\Controllers\HealthController;
use App\Controllers\ItemController;
use App\Controllers\PublicItemController;
use App\Controllers\SecurityTestController;
use Jb\Auth\AuthMiddleware;
use Jb\Security\SecurityRoutes;

// ── Health (baseline sin middleware de seguridad) ──────────────
$router->get('/health', [HealthController::class, 'health']);

// ── Health con cadena completa de seguridad ────────────────────
$router->get('/health-secured', [HealthController::class, 'healthSecured'], [
    AuthMiddleware::class,
]);

// ── Autenticación ──────────────────────────────────────────────
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/logout', [AuthController::class, 'logout'], [
    AuthMiddleware::class,
]);

// ── CRUD Items (protegido con JWT) ─────────────────────────────
$router->get('/items', [ItemController::class, 'index'], [
    AuthMiddleware::class,
]);
$router->get('/items/{id}', [ItemController::class, 'show'], [
    AuthMiddleware::class,
]);
$router->post('/items', [ItemController::class, 'store'], [
    AuthMiddleware::class,
]);
$router->put('/items/{id}', [ItemController::class, 'update'], [
    AuthMiddleware::class,
]);
$router->delete('/items/{id}', [ItemController::class, 'destroy'], [
    AuthMiddleware::class,
]);

// ── Endpoint público de validación con JOIN ────────────────────
$router->get('/public/items/{uuid}/validar', [PublicItemController::class, 'validar']);

// ── Endpoint de prueba de seguridad (dispara detectores) ───────
$router->post('/security/test/trigger', [SecurityTestController::class, 'trigger'], [
    AuthMiddleware::class,
]);

// ── Admin reset de tablas de seguridad para benchmarks ─────────
$router->post('/admin/reset-security', [AdminResetController::class, 'reset']);

// ── Deploy sin shell (solo testbed, NUNCA en producción real) ──
$router->post('/admin/deploy/migrate', [DeployController::class, 'migrate']);
$router->post('/admin/deploy/seed', [DeployController::class, 'seed']);
$router->get('/admin/security-scores-check', [DeployController::class, 'securityScoresCheck']);

// ── Rutas del panel de seguridad del framework ─────────────────
SecurityRoutes::register($router);