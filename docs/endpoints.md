# Endpoints

Esta lista cubre las rutas definidas en `routes/api.php`.
Todas las rutas son relativas a `APP_BASE_ROUTE`, que por defecto es `/api`.

## Por qué se documentan así

Las rutas se agrupan por intención y no solo por controlador porque el objetivo del testbed es comparar comportamientos. La documentación debe dejar claro qué escenario representa cada endpoint:

- línea base;
- autenticación;
- acceso a datos;
- validación pública;
- seguridad y operaciones de mantenimiento.

## Salud

| Método | Ruta | Auth | Propósito | Razón de existencia |
| --- | --- | --- | --- | --- |
| `GET` | `/health` | No | Verificación base sin middleware de seguridad | Sirve como punto de comparación para medir el costo mínimo de la aplicación |
| `GET` | `/health-secured` | Sí | El mismo flujo, pero con autenticación y validaciones de seguridad | Permite medir cuánto agrega la cadena completa sobre la línea base |

## Autenticación

| Método | Ruta | Auth | Propósito | Razón de existencia |
| --- | --- | --- | --- | --- |
| `POST` | `/auth/login` | No | Iniciar sesión con correo y contraseña | Genera un flujo realista para el uso de JWT y roles |
| `POST` | `/auth/logout` | Sí | Revocar el token actual | Permite cerrar sesión y limpiar el contexto de prueba |

## Items

| Método | Ruta | Auth | Propósito | Razón de existencia |
| --- | --- | --- | --- | --- |
| `GET` | `/items` | Sí | Listar items con paginación | Mide lectura sobre un conjunto de datos sintéticos |
| `GET` | `/items/{id}` | Sí | Leer un item específico | Sirve para validar acceso puntual a una fila |
| `POST` | `/items` | Sí | Crear un item nuevo | Demuestra validaciones, inserción y generación de UUID |
| `PUT` | `/items/{id}` | Sí | Actualizar un item | Cubre modificación de datos y verificación de existencia |
| `DELETE` | `/items/{id}` | Sí | Eliminar un item | Completa el ciclo CRUD y ayuda a limpiar pruebas |

## Validación pública

| Método | Ruta | Auth | Propósito | Razón de existencia |
| --- | --- | --- | --- | --- |
| `GET` | `/public/items/{uuid}/validar` | No | Validar un item por UUID y devolver su categoría | Reproduce un caso público similar a una consulta de validación externa |

## Seguridad y despliegue

| Método | Ruta | Auth | Propósito | Razón de existencia |
| --- | --- | --- | --- | --- |
| `POST` | `/security/test/trigger` | Sí | Enviar payloads por la cadena de seguridad | Dispara detectores para observar bloqueos y registros |
| `POST` | `/admin/reset-security` | Token | Limpiar las tablas de seguridad para una nueva corrida | Permite repetir benchmarks sin acceso a consola |
| `POST` | `/admin/deploy/migrate` | Token | Ejecutar migraciones pendientes por HTTP | Hace posible preparar el entorno en hosting compartido |
| `POST` | `/admin/deploy/seed` | Token | Ejecutar un seeder permitido por HTTP | Reconstruye los datos sintéticos sin depender de shell |
| `GET` | `/admin/security-scores-check` | Token | Revisar las filas de puntuación por IP | Ayuda a comprobar el comportamiento del rate limit |
| `GET` | `/admin/security-config-check` | Token | Revisar las banderas efectivas de seguridad | Permite confirmar el estado real antes de medir |

## Rutas de seguridad

`Jb\Security\SecurityRoutes::register($router)` agrega rutas adicionales del panel de seguridad que pertenecen a la capa del framework. Solo deben documentarse si esa versión del paquete está dentro del alcance actual del benchmark.

## Criterio de documentación

Si una ruta no está en este archivo porque no aparece en el código actual, no debe asumirse como parte del testbed. Eso evita que la documentación se aleje de la implementación real.