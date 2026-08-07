# TESTBED — jbtest

Proyecto generado a partir de **jb-framework** (https://github.com/jbflores24/jb-framework) como *testbed* para experimentos de rendimiento y seguridad. Este proyecto **no es producción**. Los resultados alimentan un artículo científico sobre el middleware de seguridad del framework.

---

## 1. Migraciones

### 1.1 Migraciones del núcleo del framework (ya existían en jb-framework)

El framework **no incluye migraciones pre-empaquetadas** en el scaffolding de un proyecto nuevo. Las tablas de seguridad son creadas por la migración `2026_06_08_000001_create_security_tables.php` generada en este proyecto. Los modelos del framework (`Jb\Security\models\*`) referencian estas tablas directamente:

| Tabla | Modelo del framework | Propósito |
|---|---|---|
| `security_blocks` | `BlockModel` | Bloqueos activos por IP |
| `security_logs` | `LogModel` | Bitácora de eventos de seguridad |
| `security_scores` | `ScoreModel` | Contadores de intentos (rate-limit interno) |
| `security_whitelist` | `WhitelistModel` | IPs confiables |
| `security_blacklist` | `BlacklistModel` | IPs bloqueadas permanentemente |
| `security_audit` | `AuditModel` | Auditoría de acciones administrativas |

### 1.2 Migraciones generadas para este proyecto

| Archivo | Tabla | Propósito |
|---|---|---|
| `2026_06_08_000001_create_security_tables.php` | `security_blocks`, `security_logs`, `security_scores`, `security_whitelist`, `security_blacklist`, `security_audit` | Tablas requeridas por el módulo de seguridad del framework |
| `2026_06_08_000002_create_roles.php` | `roles` | Roles de usuario (admin, editor, lector) |
| `2026_06_08_000003_create_permissions.php` | `permissions` | Permisos granulares |
| `2026_06_08_000004_create_role_permissions.php` | `role_permissions` | Pivote rol ↔ permiso |
| `2026_06_08_000005_create_users.php` | `users` | Usuarios con FK a roles |
| `2026_06_08_000006_create_item_categorias.php` | `item_categorias` | Categorías de items (dominio genérico) |
| `2026_06_08_000007_create_items.php` | `items` | Items con UUID público y FK a categorías |

---

## 2. Seeders

| Archivo | Propósito | Registros |
|---|---|---|
| `RoleSeeder.php` | Roles (admin, editor, lector) + permisos + asignaciones | 3 roles, 6 permisos, ~12 asignaciones |
| `UserSeeder.php` | Usuarios de prueba (DATOS SINTÉTICOS) | 3 usuarios (1 por rol) |
| `ItemCategoriaSeeder.php` | Categorías genéricas | 20 categorías |
| `ItemSeeder.php` | Items sintéticos con UUIDs únicos | 7,500 items |

**Credenciales de prueba (DATOS SINTÉTICOS — NO USAR EN PRODUCCIÓN):**

| Email | Contraseña | Rol |
|---|---|---|
| `admin@jbtest.local` | `password123` | admin |
| `editor@jbtest.local` | `password123` | editor |
| `lector@jbtest.local` | `password123` | lector |

---

## 3. Comandos de despliegue

### 3.1 Despliegue local (con shell)

Ejecutar en orden, desde la raíz del proyecto (`jbtest/`):

```bash
# 1. Instalar dependencias (solo la primera vez)
composer install

# 2. Ejecutar todas las migraciones
php jb migrate

# 3. Poblar la base de datos con datos sintéticos
php jb seed RoleSeeder
php jb seed UserSeeder
php jb seed ItemCategoriaSeeder
php jb seed ItemSeeder
```

### 3.2 Despliegue en hosting compartido (sin shell, vía HTTP)

El hosting de destino no tiene acceso SSH ni Composer. Las dependencias se resuelven en GitHub Actions antes del FTP. Para migrar y sembrar se usan los endpoints de `DeployController` (sección 4.9), protegidos con `DEPLOY_TOKEN`.

```bash
# 0. Definir tokens en .env antes de desplegar
ADMIN_RESET_TOKEN=<valor-seguro>
DEPLOY_TOKEN=<valor-seguro>

# 1. Migrar (idempotente — solo ejecuta las pendientes)
curl -X POST https://TU-DOMINIO/api/admin/deploy/migrate \
  -H "Content-Type: application/json" \
  -d '{"token":"TU_DEPLOY_TOKEN"}'

# 2. Sembrar en orden
curl -X POST https://TU-DOMINIO/api/admin/deploy/seed \
  -H "Content-Type: application/json" \
  -d '{"token":"TU_DEPLOY_TOKEN","seeder":"RoleSeeder"}'

curl -X POST https://TU-DOMINIO/api/admin/deploy/seed \
  -H "Content-Type: application/json" \
  -d '{"token":"TU_DEPLOY_TOKEN","seeder":"UserSeeder"}'

curl -X POST https://TU-DOMINIO/api/admin/deploy/seed \
  -H "Content-Type: application/json" \
  -d '{"token":"TU_DEPLOY_TOKEN","seeder":"ItemCategoriaSeeder"}'

curl -X POST https://TU-DOMINIO/api/admin/deploy/seed \
  -H "Content-Type: application/json" \
  -d '{"token":"TU_DEPLOY_TOKEN","seeder":"ItemSeeder"}'
```

**Nota sobre compatibilidad con hosting compartido:** El archivo `config/database.php` lee `DB_USER`, `DB_PASS`, `DB_NAME` del `.env` sin hardcodear credenciales. Las migraciones usan exclusivamente DDL/DML estándar (CREATE TABLE, INSERT, TRUNCATE). No se usan sentencias `GRANT`, `CREATE USER`, ni privilegios globales. El mismo script corre en local con `root` y en producción con un usuario restringido sin modificar código.

---

## 4. Endpoints

### 4.1 Health (baseline sin middleware)

| Método | Ruta | Auth | Middleware de seguridad | Propósito |
|---|---|---|---|---|
| `GET` | `/api/health` | No | No (excluido vía `excluded_paths`) | Baseline para comparar latencia con y sin seguridad |

### 4.2 Health con seguridad

| Método | Ruta | Auth | Middleware de seguridad | Propósito |
|---|---|---|---|---|
| `GET` | `/api/health-secured` | JWT | Sí (cadena completa) | Medir overhead del middleware de seguridad |

### 4.3 Autenticación

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `POST` | `/api/auth/login` | No | Login con email/password, retorna JWT |
| `POST` | `/api/auth/logout` | JWT | Revoca el token actual |

### 4.4 CRUD Items (dominio genérico)

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `GET` | `/api/items` | JWT | Listar items con paginación y JOIN a categorías |
| `GET` | `/api/items/{id}` | JWT | Obtener item por ID con JOIN |
| `POST` | `/api/items` | JWT | Crear item |
| `PUT` | `/api/items/{id}` | JWT | Actualizar item |
| `DELETE` | `/api/items/{id}` | JWT | Eliminar item |

### 4.5 Validación pública con JOIN

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `GET` | `/api/public/items/{uuid}/validar` | No | Endpoint público con JOIN a `item_categorias`. Análogo al endpoint de validación de constancias del sistema en producción. Usado para medir el costo del JOIN bajo carga. |

### 4.6 Prueba de seguridad

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `POST` | `/api/security/test/trigger` | JWT | Recibe payloads que disparan el motor de detección de anomalías. Útil para medir bloqueo bajo carga real. |

### 4.7 Admin reset (benchmarks)

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `POST` | `/api/admin/reset-security` | Token estático (`ADMIN_RESET_TOKEN`) | Trunca `security_blocks`, `security_logs`, `security_scores`, `security_whitelist`, `security_blacklist`, `security_audit` para repetir benchmarks sin acceso SSH. |

### 4.8 Panel de seguridad del framework

| Método | Ruta | Propósito |
|---|---|---|
| `GET` | `/api/security/dashboard` | Dashboard de seguridad |
| `GET` | `/api/security/blocks` | Listar bloqueos activos |
| `POST` | `/api/security/blocks/block` | Bloquear IP manualmente |
| `POST` | `/api/security/blocks/unblock` | Desbloquear IP |
| `GET` | `/api/security/logs` | Bitácora de eventos |
| `GET` | `/api/security/whitelist` | Listar whitelist |
| `POST` | `/api/security/whitelist/add` | Agregar IP a whitelist |
| `POST` | `/api/security/whitelist/remove` | Remover IP de whitelist |
| `GET` | `/api/security/blacklist` | Listar blacklist |
| `POST` | `/api/security/blacklist/add` | Agregar IP a blacklist |
| `POST` | `/api/security/blacklist/remove` | Remover IP de blacklist |
| `GET` | `/api/security/export/csv` | Exportar logs a CSV |

### 4.9 Deploy sin shell (solo testbed)

**ADVERTENCIA:** Estos endpoints SOLO deben existir en el proyecto testbed jbtest. NUNCA deben replicarse en un sistema con datos reales (apiLenguas, etc.). Son una excepción deliberada para un entorno de hosting compartido sin acceso SSH ni terminal.

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `POST` | `/api/admin/deploy/migrate` | `DEPLOY_TOKEN` en body | Ejecuta migraciones pendientes (idempotente). Equivalente a `php jb migrate`. |
| `POST` | `/api/admin/deploy/seed` | `DEPLOY_TOKEN` en body | Ejecuta un seeder por nombre (whitelist: RoleSeeder, UserSeeder, ItemCategoriaSeeder, ItemSeeder). Parámetro: `seeder`. |

### 4.10 Verificación de integridad del rate-limit

| Método | Ruta | Auth | Propósito |
|---|---|---|---|
| `GET` | `/api/admin/security-scores-check?token=...&ip=X.X.X.X` | `DEPLOY_TOKEN` en query | Verifica que el índice único compuesto en `security_scores` está previniendo condiciones de carrera. Si `rows_found > 1` para la misma IP dentro de la misma ventana activa, el índice no está funcionando. |

---

## 5. Variables de entorno adicionales

Agregar al `.env`:

```env
# Token para el endpoint de reset de seguridad (cambiar en producción)
ADMIN_RESET_TOKEN=jb-reset-benchmark-2026
```

---

## 6. Notas para el artículo científico

- **Middleware de seguridad:** El `SecurityMiddleware` del framework ejecuta 9 detectores en pipeline (Method, Path, Bot, Payload, Injection, RateLimit, Login, NotFound, Session) más una fase post-response. Se puede activar/desactivar globalmente con `SECURITY_ENABLED=true/false` en `.env`.
- **Rate limiting:** El framework usa un rate limiter basado en archivos JSON (por minuto). La configuración está en `config/rate_limit.php` (lee `RATE_LIMIT_MAX` y `RATE_LIMIT_WINDOW` del `.env`).
- **JWT:** Implementación propia del framework (`Jb\Auth\JWT`) con HS256, refresh tokens, y lista de revocación en archivo JSON.
- **Datos sintéticos:** Los 7,500 items con JOIN a 20 categorías permiten medir el costo real del JOIN bajo carga. En el artículo de referencia, el JOIN de producción solo costó ~5% más que un SELECT 1; este volumen debe ser suficiente para poner eso a prueba.