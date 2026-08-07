<?php

declare(strict_types=1);

use Jb\Database\Connection;
use Jb\Database\Seeder;

/**
 * DATOS SINTÉTICOS — NO USAR EN PRODUCCIÓN.
 * Genera entre 5,000 y 10,000 items de prueba con UUIDs únicos,
 * distribuidos entre las categorías existentes.
 * Propósito: ejercitar el endpoint de validación con JOIN bajo carga.
 */
return new class (Connection::getInstance()) extends Seeder {
    private const TOTAL_ITEMS = 7500;

    public function run(): void
    {
        $pdo = $this->connection->pdo();

        // Obtener IDs de categorías existentes
        $stmt = $pdo->query('SELECT id FROM item_categorias ORDER BY id');
        $categoriaIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($categoriaIds)) {
            throw new RuntimeException('No hay categorías. Ejecuta primero ItemCategoriaSeeder.');
        }

        $numCategorias = count($categoriaIds);

        // Nombres base para generar variaciones
        $adjectives = ['Premium', 'Básico', 'Profesional', 'Estándar', 'Deluxe', 'Económico', 'Clásico', 'Moderno', 'Compacto', 'Industrial'];
        $nouns = ['Producto', 'Artículo', 'Kit', 'Set', 'Pack', 'Unidad', 'Componente', 'Accesorio', 'Equipo', 'Sistema'];

        $stmt = $pdo->prepare(
            'INSERT INTO items (categoria_id, nombre, descripcion, uuid, created_at, updated_at) VALUES (:cat, :nombre, :desc, :uuid, NOW(), NOW())'
        );

        $pdo->beginTransaction();

        for ($i = 1; $i <= self::TOTAL_ITEMS; $i++) {
            $catId = $categoriaIds[$i % $numCategorias];
            $adj = $adjectives[$i % count($adjectives)];
            $noun = $nouns[($i * 7) % count($nouns)];
            $nombre = "$adj $noun #$i";
            $descripcion = "Item sintético generado para pruebas de carga y benchmarks. Categoría ID: $catId.";
            $uuid = self::uuidv4();

            $stmt->execute([
                'cat' => $catId,
                'nombre' => $nombre,
                'desc' => $descripcion,
                'uuid' => $uuid,
            ]);

            // Commit en lotes de 500 para no saturar la memoria de transacción
            if ($i % 500 === 0) {
                $pdo->commit();
                $pdo->beginTransaction();
            }
        }

        $pdo->commit();
    }

    private static function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant 1

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
};