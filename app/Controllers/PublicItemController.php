<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\HttpException;
use Jb\Core\Request;
use Jb\Core\Response;
use Jb\Database\Connection;
use PDO;

class PublicItemController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * GET /public/items/{uuid}/validar — endpoint público con JOIN a item_categorias.
     * Análogo al endpoint de validación de constancias del sistema en producción.
     * No requiere autenticación.
     */
    public function validar(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $uuid = $request->input('uuid');

        $stmt = $pdo->prepare(
            'SELECT i.id, i.nombre, i.descripcion, i.uuid, i.created_at, i.updated_at,
                    c.id as categoria_id, c.nombre as categoria_nombre
             FROM items i
             JOIN item_categorias c ON i.categoria_id = c.id
             WHERE i.uuid = :uuid
             LIMIT 1'
        );
        $stmt->execute(['uuid' => $uuid]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new HttpException('Item no encontrado.', 404);
        }

        return Response::success([
            'valid' => true,
            'data' => [
                'id' => (int) $item['id'],
                'uuid' => $item['uuid'],
                'nombre' => $item['nombre'],
                'descripcion' => $item['descripcion'],
                'categoria' => [
                    'id' => (int) $item['categoria_id'],
                    'nombre' => $item['categoria_nombre'],
                ],
                'created_at' => $item['created_at'],
                'updated_at' => $item['updated_at'],
            ],
        ]);
    }
}