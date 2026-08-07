<?php

declare(strict_types=1);

use Jb\Database\Connection;
use Jb\Database\Seeder;

/**
 * DATOS SINTÉTICOS — NO USAR EN PRODUCCIÓN.
 * Categorías genéricas para el dominio de prueba items/item_categorias.
 */
return new class (Connection::getInstance()) extends Seeder {
    public function run(): void
    {
        $pdo = $this->connection->pdo();

        $categorias = [
            'Electrónica',
            'Ropa y Accesorios',
            'Hogar y Jardín',
            'Deportes y Aire Libre',
            'Libros y Papelería',
            'Alimentos y Bebidas',
            'Salud y Belleza',
            'Automotriz',
            'Juguetes y Juegos',
            'Mascotas',
            'Música e Instrumentos',
            'Películas y Series',
            'Software y Apps',
            'Herramientas',
            'Oficina',
            'Viajes y Equipaje',
            'Bebés y Niños',
            'Ferretería',
            'Jardinería',
            'Arte y Manualidades',
        ];

        $stmt = $pdo->prepare('INSERT INTO item_categorias (nombre, created_at, updated_at) VALUES (:nombre, NOW(), NOW())');

        foreach ($categorias as $nombre) {
            $stmt->execute(['nombre' => $nombre]);
        }
    }
};