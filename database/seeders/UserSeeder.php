<?php

declare(strict_types=1);

use Jb\Database\Connection;
use Jb\Database\Seeder;

/**
 * DATOS SINTÉTICOS — NO USAR EN PRODUCCIÓN.
 * Usuarios de prueba para el testbed jbtest.
 * Contraseñas: todas son "password123" (bcrypt).
 * Estas credenciales son exclusivamente para benchmarks y experimentos de seguridad.
 */
return new class (Connection::getInstance()) extends Seeder {
    public function run(): void
    {
        $pdo = $this->connection->pdo();

        // Obtener IDs de roles
        $stmt = $pdo->query('SELECT id, nombre FROM roles');
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[$row['nombre']] = (int) $row['id'];
        }

        // Contraseña común para todos los usuarios de prueba: "password123"
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);

        $users = [
            [
                'name' => 'Admin Test',
                'email' => 'admin@jbtest.local',
                'password' => $passwordHash,
                'role_id' => $roles['admin'] ?? null,
            ],
            [
                'name' => 'Editor Test',
                'email' => 'editor@jbtest.local',
                'password' => $passwordHash,
                'role_id' => $roles['editor'] ?? null,
            ],
            [
                'name' => 'Lector Test',
                'email' => 'lector@jbtest.local',
                'password' => $passwordHash,
                'role_id' => $roles['lector'] ?? null,
            ],
        ];

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role_id, active, created_at, updated_at) VALUES (:name, :email, :password, :role_id, 1, NOW(), NOW())');

        foreach ($users as $user) {
            $stmt->execute([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => $user['password'],
                'role_id' => $user['role_id'],
            ]);
        }
    }
};