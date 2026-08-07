<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Auth\AuthService;
use Jb\Auth\JWT;
use Jb\Auth\TokenRevocationList;
use Jb\Core\Config;
use Jb\Core\HttpException;
use Jb\Core\Request;
use Jb\Core\Response;
use Jb\Database\Connection;
use PDO;

class AuthController
{
    public function __construct(
        private readonly Config $config,
        private readonly Connection $connection,
    ) {
    }

    /**
     * POST /auth/login — autentica con email/password y retorna JWT.
     */
    public function login(Request $request): Response
    {
        $body = $request->body();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            throw new HttpException('Email y contraseña son requeridos.', 422);
        }

        $pdo = $this->connection->pdo();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.password, u.active, r.nombre as role
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             WHERE u.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new HttpException('Credenciales inválidas.', 401);
        }

        if (!$user['active']) {
            throw new HttpException('Usuario inactivo.', 403);
        }

        // Obtener permisos del rol
        $permStmt = $pdo->prepare(
            'SELECT p.nombre FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = (SELECT role_id FROM users WHERE id = :user_id)'
        );
        $permStmt->execute(['user_id' => $user['id']]);
        $permissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);

        $jwt = new JWT((string) $this->config->get('auth.jwt_secret', 'change-me'));
        $revokedPath = $this->config->basePath() . '/storage/auth/revoked_tokens.json';
        $authService = new AuthService(
            $jwt,
            (int) $this->config->get('auth.jwt_ttl', 3600),
            (int) $this->config->get('auth.jwt_refresh_ttl', 1209600),
            new TokenRevocationList($revokedPath)
        );

        $tokens = $authService->generateTokens([
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'permissions' => $permissions,
        ]);

        return Response::success([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_in' => $tokens['expires_in'],
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    }

    /**
     * POST /auth/logout — revoca el token actual.
     */
    public function logout(Request $request): Response
    {
        $authorization = $request->header('authorization', '');
        $token = AuthService::extractBearerToken($authorization);

        $jwt = new JWT((string) $this->config->get('auth.jwt_secret', 'change-me'));
        $revokedPath = $this->config->basePath() . '/storage/auth/revoked_tokens.json';
        $authService = new AuthService(
            $jwt,
            (int) $this->config->get('auth.jwt_ttl', 3600),
            (int) $this->config->get('auth.jwt_refresh_ttl', 1209600),
            new TokenRevocationList($revokedPath)
        );

        $authService->revokeToken($token);

        return Response::success(['message' => 'Sesión cerrada exitosamente.']);
    }
}