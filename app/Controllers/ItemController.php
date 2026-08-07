<?php

declare(strict_types=1);

namespace App\Controllers;

use Jb\Core\HttpException;
use Jb\Core\Request;
use Jb\Core\Response;
use Jb\Database\Connection;
use PDO;

class ItemController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * GET /items — listar items con paginación.
     */
    public function index(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $page = max(1, (int) ($request->input('page', '1')));
        $perPage = min(100, max(1, (int) ($request->input('per_page', '20'))));
        $offset = ($page - 1) * $perPage;

        $totalStmt = $pdo->query('SELECT COUNT(*) FROM items');
        $total = (int) $totalStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT i.id, i.categoria_id, i.nombre, i.descripcion, i.uuid, i.created_at, i.updated_at,
                    c.nombre as categoria_nombre
             FROM items i
             JOIN item_categorias c ON i.categoria_id = c.id
             ORDER BY i.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return Response::success([
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * GET /items/{id} — obtener un item por ID.
     */
    public function show(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $id = (int) $request->input('id');

        $stmt = $pdo->prepare(
            'SELECT i.id, i.categoria_id, i.nombre, i.descripcion, i.uuid, i.created_at, i.updated_at,
                    c.nombre as categoria_nombre
             FROM items i
             JOIN item_categorias c ON i.categoria_id = c.id
             WHERE i.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new HttpException('Item no encontrado.', 404);
        }

        return Response::success(['data' => $item]);
    }

    /**
     * POST /items — crear un nuevo item.
     */
    public function store(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $body = $request->body();

        $categoriaId = (int) ($body['categoria_id'] ?? 0);
        $nombre = trim((string) ($body['nombre'] ?? ''));
        $descripcion = trim((string) ($body['descripcion'] ?? ''));

        if ($categoriaId <= 0 || $nombre === '') {
            throw new HttpException('categoria_id y nombre son requeridos.', 422);
        }

        // Verificar que la categoría existe
        $catStmt = $pdo->prepare('SELECT id FROM item_categorias WHERE id = :id');
        $catStmt->execute(['id' => $categoriaId]);
        if (!$catStmt->fetch()) {
            throw new HttpException('Categoría no encontrada.', 422);
        }

        $uuid = self::uuidv4();

        $stmt = $pdo->prepare(
            'INSERT INTO items (categoria_id, nombre, descripcion, uuid, created_at, updated_at)
             VALUES (:cat, :nombre, :desc, :uuid, NOW(), NOW())'
        );
        $stmt->execute([
            'cat' => $categoriaId,
            'nombre' => $nombre,
            'desc' => $descripcion,
            'uuid' => $uuid,
        ]);

        $id = (int) $pdo->lastInsertId();

        return new Response([
            'status' => 'success',
            'message' => 'Item creado.',
            'data' => [
                'id' => $id,
                'categoria_id' => $categoriaId,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'uuid' => $uuid,
            ],
        ], 201);
    }

    /**
     * PUT /items/{id} — actualizar un item.
     */
    public function update(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $id = (int) $request->input('id');
        $body = $request->body();

        // Verificar que el item existe
        $checkStmt = $pdo->prepare('SELECT id FROM items WHERE id = :id');
        $checkStmt->execute(['id' => $id]);
        if (!$checkStmt->fetch()) {
            throw new HttpException('Item no encontrado.', 404);
        }

        $fields = [];
        $params = ['id' => $id];

        if (isset($body['categoria_id'])) {
            $catId = (int) $body['categoria_id'];
            $catStmt = $pdo->prepare('SELECT id FROM item_categorias WHERE id = :id');
            $catStmt->execute(['id' => $catId]);
            if (!$catStmt->fetch()) {
                throw new HttpException('Categoría no encontrada.', 422);
            }
            $fields[] = 'categoria_id = :cat_id';
            $params['cat_id'] = $catId;
        }

        if (isset($body['nombre'])) {
            $fields[] = 'nombre = :nombre';
            $params['nombre'] = trim((string) $body['nombre']);
        }

        if (isset($body['descripcion'])) {
            $fields[] = 'descripcion = :desc';
            $params['desc'] = trim((string) $body['descripcion']);
        }

        if (empty($fields)) {
            throw new HttpException('No hay campos para actualizar.', 422);
        }

        $fields[] = 'updated_at = NOW()';
        $sql = 'UPDATE items SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $pdo->prepare($sql)->execute($params);

        return Response::success(['message' => 'Item actualizado.']);
    }

    /**
     * DELETE /items/{id} — eliminar un item.
     */
    public function destroy(Request $request): Response
    {
        $pdo = $this->connection->pdo();
        $id = (int) $request->input('id');

        $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new HttpException('Item no encontrado.', 404);
        }

        return Response::success(['message' => 'Item eliminado.']);
    }

    private static function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}