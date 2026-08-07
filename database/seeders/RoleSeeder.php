<?php

declare(strict_types=1);

use Jb\Database\Connection;
use Jb\Database\Seeder;

/**
 * DATOS SINTÉTICOS — NO USAR EN PRODUCCIÓN.
 * Este seeder puebla roles y permisos de prueba para el testbed jbtest.
 * Las credenciales aquí generadas son exclusivamente para benchmarks y experimentos.
 */
return new class (Connection::getInstance()) extends Seeder {
    public function run(): void
    {
        $pdo = $this->connection->pdo();

        // ── Roles ──────────────────────────────────────────────
        $roles = [
            ['nombre' => 'admin', 'descripcion' => 'Administrador del sistema — acceso total'],
            ['nombre' => 'editor', 'descripcion' => 'Editor — puede crear y modificar items'],
            ['nombre' => 'lector', 'descripcion' => 'Lector — solo consulta'],
        ];

        $roleIds = [];
        foreach ($roles as $role) {
            $stmt = $pdo->prepare('INSERT INTO roles (nombre, descripcion, created_at, updated_at) VALUES (:nombre, :descripcion, NOW(), NOW())');
            $stmt->execute(['nombre' => $role['nombre'], 'descripcion' => $role['descripcion']]);
            $roleIds[$role['nombre']] = (int) $pdo->lastInsertId();
        }

        // ── Permissions ────────────────────────────────────────
        $permissions = [
            ['nombre' => 'items.read', 'descripcion' => 'Consultar items'],
            ['nombre' => 'items.create', 'descripcion' => 'Crear items'],
            ['nombre' => 'items.update', 'descripcion' => 'Actualizar items'],
            ['nombre' => 'items.delete', 'descripcion' => 'Eliminar items'],
            ['nombre' => 'admin.security', 'descripcion' => 'Acceso al panel de seguridad'],
            ['nombre' => 'admin.reset', 'descripcion' => 'Resetear datos de seguridad para benchmarks'],
        ];

        $permIds = [];
        foreach ($permissions as $perm) {
            $stmt = $pdo->prepare('INSERT INTO permissions (nombre, descripcion, created_at, updated_at) VALUES (:nombre, :descripcion, NOW(), NOW())');
            $stmt->execute(['nombre' => $perm['nombre'], 'descripcion' => $perm['descripcion']]);
            $permIds[$perm['nombre']] = (int) $pdo->lastInsertId();
        }

        // ── Role ↔ Permission assignments ──────────────────────
        $assignments = [
            'admin' => ['items.read', 'items.create', 'items.update', 'items.delete', 'admin.security', 'admin.reset'],
            'editor' => ['items.read', 'items.create', 'items.update'],
            'lector' => ['items.read'],
        ];

        $insertRp = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id, created_at, updated_at) VALUES (:role_id, :perm_id, NOW(), NOW())');

        foreach ($assignments as $roleName => $permNames) {
            foreach ($permNames as $permName) {
                $insertRp->execute([
                    'role_id' => $roleIds[$roleName],
                    'perm_id' => $permIds[$permName],
                ]);
            }
        }
    }
};