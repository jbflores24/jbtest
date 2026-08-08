# TESTBED ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â jbtest

Proyecto generado a partir de **jb-framework** (https://github.com/jbflores24/jb-framework) como *testbed* para experimentos de rendimiento y seguridad. Este proyecto **no es producciÃƒÆ’Ã‚Â³n**. Los resultados alimentan un artÃƒÆ’Ã‚Â­culo cientÃƒÆ’Ã‚Â­fico sobre el middleware de seguridad del framework.

La documentación del experimento y su trazabilidad se concentra en:

- [docs/benchmarks/README.md](docs/benchmarks/README.md)
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md)
- [docs/PERFORMANCE.md](docs/PERFORMANCE.md)
---

## 1. Migraciones

### 1.1 Migraciones del nÃƒÆ’Ã‚Âºcleo del framework (ya existÃƒÆ’Ã‚Â­an en jb-framework)

El framework **no incluye migraciones pre-empaquetadas** en el scaffolding de un proyecto nuevo. Las tablas de seguridad son creadas por la migraciÃƒÆ’Ã‚Â³n `2026_06_08_000001_create_security_tables.php` generada en este proyecto. Los modelos del framework (`Jb\Security\models\*`) referencian estas tablas directamente:

| Tabla | Modelo del framework | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|
| `security_blocks` | `BlockModel` | Bloqueos activos por IP |
| `security_logs` | `LogModel` | BitÃƒÆ’Ã‚Â¡cora de eventos de seguridad |
| `security_scores` | `ScoreModel` | Contadores de intentos (rate-limit interno) |
| `security_whitelist` | `WhitelistModel` | IPs confiables |
| `security_blacklist` | `BlacklistModel` | IPs bloqueadas permanentemente |
| `security_audit` | `AuditModel` | AuditorÃƒÆ’Ã‚Â­a de acciones administrativas |

### 1.2 Migraciones generadas para este proyecto

| Archivo | Tabla | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|
| `2026_06_08_000001_create_security_tables.php` | `security_blocks`, `security_logs`, `security_scores`, `security_whitelist`, `security_blacklist`, `security_audit` | Tablas requeridas por el mÃƒÆ’Ã‚Â³dulo de seguridad del framework |
| `2026_06_08_000002_create_roles.php` | `roles` | Roles de usuario (admin, editor, lector) |
| `2026_06_08_000003_create_permissions.php` | `permissions` | Permisos granulares |
| `2026_06_08_000004_create_role_permissions.php` | `role_permissions` | Pivote rol ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬Â permiso |
| `2026_06_08_000005_create_users.php` | `users` | Usuarios con FK a roles |
| `2026_06_08_000006_create_item_categorias.php` | `item_categorias` | CategorÃƒÆ’Ã‚Â­as de items (dominio genÃƒÆ’Ã‚Â©rico) |
| `2026_06_08_000007_create_items.php` | `items` | Items con UUID pÃƒÆ’Ã‚Âºblico y FK a categorÃƒÆ’Ã‚Â­as |

---

## 2. Seeders

| Archivo | PropÃƒÆ’Ã‚Â³sito | Registros |
|---|---|---|
| `RoleSeeder.php` | Roles (admin, editor, lector) + permisos + asignaciones | 3 roles, 6 permisos, ~12 asignaciones |
| `UserSeeder.php` | Usuarios de prueba (DATOS SINTÃƒÆ’Ã¢â‚¬Â°TICOS) | 3 usuarios (1 por rol) |
| `ItemCategoriaSeeder.php` | CategorÃƒÆ’Ã‚Â­as genÃƒÆ’Ã‚Â©ricas | 20 categorÃƒÆ’Ã‚Â­as |
| `ItemSeeder.php` | Items sintÃƒÆ’Ã‚Â©ticos con UUIDs ÃƒÆ’Ã‚Âºnicos | 7,500 items |

**Credenciales de prueba (DATOS SINTÃƒÆ’Ã¢â‚¬Â°TICOS ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â NO USAR EN PRODUCCIÃƒÆ’Ã¢â‚¬Å“N):**

| Email | ContraseÃƒÆ’Ã‚Â±a | Rol |
|---|---|---|
| `admin@jbtest.local` | `password123` | admin |
| `editor@jbtest.local` | `password123` | editor |
| `lector@jbtest.local` | `password123` | lector |

---

## 3. Comandos de despliegue

### 3.1 Despliegue local (con shell)

Ejecutar en orden, desde la raÃƒÆ’Ã‚Â­z del proyecto (`jbtest/`):

```bash
# 1. Instalar dependencias (solo la primera vez)
composer install

# 2. Ejecutar todas las migraciones
php jb migrate

# 3. Poblar la base de datos con datos sintÃƒÆ’Ã‚Â©ticos
php jb seed RoleSeeder
php jb seed UserSeeder
php jb seed ItemCategoriaSeeder
php jb seed ItemSeeder
```

### 3.2 Despliegue en hosting compartido (sin shell, vÃ­a HTTP)

Cuando no hay acceso SSH ni Composer, la migraciÃ³n y el seeding se realizan mediante los endpoints de `DeployController` (secciÃ³n 4.9), protegidos con `DEPLOY_TOKEN`.

```bash
# 0. Definir tokens en .env antes de desplegar
ADMIN_RESET_TOKEN=<valor-seguro>
DEPLOY_TOKEN=<valor-seguro>

# 1. Migrar (idempotente ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â solo ejecuta las pendientes)
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

**Nota sobre compatibilidad con hosting compartido:** El archivo `config/database.php` lee `DB_USER`, `DB_PASS`, `DB_NAME` del `.env` sin hardcodear credenciales. Las migraciones usan exclusivamente DDL/DML estÃ¡ndar (CREATE TABLE, INSERT, TRUNCATE). No se usan sentencias `GRANT`, `CREATE USER`, ni privilegios globales.

---

## 4. Endpoints

### 4.1 Health (baseline sin middleware)

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | Middleware de seguridad | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|---|
| `GET` | `/api/health` | No | No (excluido vÃƒÆ’Ã‚Â­a `excluded_paths`) | Baseline para comparar latencia con y sin seguridad |

### 4.2 Health con seguridad

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | Middleware de seguridad | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|---|
| `GET` | `/api/health-secured` | JWT | SÃƒÆ’Ã‚Â­ (cadena completa) | Medir overhead del middleware de seguridad |

### 4.3 AutenticaciÃƒÆ’Ã‚Â³n

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `POST` | `/api/auth/login` | No | Login con email/password, retorna JWT |
| `POST` | `/api/auth/logout` | JWT | Revoca el token actual |

### 4.4 CRUD Items (dominio genÃƒÆ’Ã‚Â©rico)

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `GET` | `/api/items` | JWT | Listar items con paginaciÃƒÆ’Ã‚Â³n y JOIN a categorÃƒÆ’Ã‚Â­as |
| `GET` | `/api/items/{id}` | JWT | Obtener item por ID con JOIN |
| `POST` | `/api/items` | JWT | Crear item |
| `PUT` | `/api/items/{id}` | JWT | Actualizar item |
| `DELETE` | `/api/items/{id}` | JWT | Eliminar item |

### 4.5 ValidaciÃƒÆ’Ã‚Â³n pÃƒÆ’Ã‚Âºblica con JOIN

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `GET` | `/api/public/items/{uuid}/validar` | No | Endpoint pÃƒÆ’Ã‚Âºblico con JOIN a `item_categorias`. AnÃƒÆ’Ã‚Â¡logo al endpoint de validaciÃƒÆ’Ã‚Â³n de constancias del sistema en producciÃƒÆ’Ã‚Â³n. Usado para medir el costo del JOIN bajo carga. |

### 4.6 Prueba de seguridad

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `POST` | `/api/security/test/trigger` | JWT | Recibe payloads que disparan el motor de detecciÃƒÆ’Ã‚Â³n de anomalÃƒÆ’Ã‚Â­as. ÃƒÆ’Ã…Â¡til para medir bloqueo bajo carga real. |

### 4.7 Admin reset (benchmarks)

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `POST` | `/api/admin/reset-security` | Token estÃƒÆ’Ã‚Â¡tico (`ADMIN_RESET_TOKEN`) | Trunca `security_blocks`, `security_logs`, `security_scores`, `security_whitelist`, `security_blacklist`, `security_audit` para repetir benchmarks sin acceso SSH. |

### 4.8 Panel de seguridad del framework

| MÃƒÆ’Ã‚Â©todo | Ruta | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|
| `GET` | `/api/security/dashboard` | Dashboard de seguridad |
| `GET` | `/api/security/blocks` | Listar bloqueos activos |
| `POST` | `/api/security/blocks/block` | Bloquear IP manualmente |
| `POST` | `/api/security/blocks/unblock` | Desbloquear IP |
| `GET` | `/api/security/logs` | BitÃƒÆ’Ã‚Â¡cora de eventos |
| `GET` | `/api/security/whitelist` | Listar whitelist |
| `POST` | `/api/security/whitelist/add` | Agregar IP a whitelist |
| `POST` | `/api/security/whitelist/remove` | Remover IP de whitelist |
| `GET` | `/api/security/blacklist` | Listar blacklist |
| `POST` | `/api/security/blacklist/add` | Agregar IP a blacklist |
| `POST` | `/api/security/blacklist/remove` | Remover IP de blacklist |
| `GET` | `/api/security/export/csv` | Exportar logs a CSV |

### 4.9 Deploy sin shell (solo testbed)

**ADVERTENCIA:** Estos endpoints SOLO deben existir en el proyecto testbed jbtest. NUNCA deben replicarse en un sistema con datos reales (apiLenguas, etc.). Son una excepciÃƒÆ’Ã‚Â³n deliberada para un entorno de hosting compartido sin acceso SSH ni terminal.

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `POST` | `/api/admin/deploy/migrate` | `DEPLOY_TOKEN` en body | Ejecuta migraciones pendientes (idempotente). Equivalente a `php jb migrate`. |
| `POST` | `/api/admin/deploy/seed` | `DEPLOY_TOKEN` en body | Ejecuta un seeder por nombre (whitelist: RoleSeeder, UserSeeder, ItemCategoriaSeeder, ItemSeeder). ParÃƒÆ’Ã‚Â¡metro: `seeder`. |

### 4.10 VerificaciÃƒÆ’Ã‚Â³n de integridad del rate-limit

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `GET` | `/api/admin/security-scores-check?token=...&ip=X.X.X.X` | `DEPLOY_TOKEN` en query | Verifica que el ÃƒÆ’Ã‚Â­ndice ÃƒÆ’Ã‚Âºnico compuesto en `security_scores` estÃƒÆ’Ã‚Â¡ previniendo condiciones de carrera. Si `rows_found > 1` para la misma IP dentro de la misma ventana activa, el ÃƒÆ’Ã‚Â­ndice no estÃƒÆ’Ã‚Â¡ funcionando. |

### 4.11 ComprobaciÃƒÆ’Ã‚Â³n de configuraciÃƒÆ’Ã‚Â³n para benchmarks

| MÃƒÆ’Ã‚Â©todo | Ruta | Auth | PropÃƒÆ’Ã‚Â³sito |
|---|---|---|---|
| `GET` | `/api/admin/security-config-check?token=...` | `DEPLOY_TOKEN` en query | Devuelve el valor efectivo de `SECURITY_ENABLED` junto con otras banderas de configuraciÃƒÆ’Ã‚Â³n para que el orquestador pueda confirmar si la seguridad estÃƒÆ’Ã‚Â¡ ON u OFF antes de medir. |

---

## 5. Variables de entorno adicionales

Agregar al `.env`:

```env
# Token para el endpoint de reset de seguridad (cambiar en producciÃƒÆ’Ã‚Â³n)
ADMIN_RESET_TOKEN=jb-reset-benchmark-2026

# Token de despliegue y comprobaciÃƒÆ’Ã‚Â³n de configuraciÃƒÆ’Ã‚Â³n
DEPLOY_TOKEN=<valor-seguro>

# Interruptor usado por el orquestador experimental
SECURITY_ENABLED=true
SECURITY_FAIL_OPEN=true
SECURITY_LEARNING_MODE=false
```

---

## 6. Notas para el artÃƒÆ’Ã‚Â­culo cientÃƒÆ’Ã‚Â­fico

- **Middleware de seguridad:** El `SecurityMiddleware` del framework ejecuta 9 detectores en pipeline (Method, Path, Bot, Payload, Injection, RateLimit, Login, NotFound, Session) mÃƒÆ’Ã‚Â¡s una fase post-response. Se puede activar/desactivar globalmente con `SECURITY_ENABLED=true/false` en `.env`.
- **Rate limiting:** El framework usa un rate limiter basado en archivos JSON (por minuto). La configuraciÃƒÆ’Ã‚Â³n estÃƒÆ’Ã‚Â¡ en `config/rate_limit.php` (lee `RATE_LIMIT_MAX` y `RATE_LIMIT_WINDOW` del `.env`).
- **JWT:** ImplementaciÃƒÆ’Ã‚Â³n propia del framework (`Jb\Auth\JWT`) con HS256, refresh tokens, y lista de revocaciÃƒÆ’Ã‚Â³n en archivo JSON.
- **Datos sintÃƒÆ’Ã‚Â©ticos:** Los 7,500 items con JOIN a 20 categorÃƒÆ’Ã‚Â­as permiten medir el costo real del JOIN bajo carga. En el artÃƒÆ’Ã‚Â­culo de referencia, el JOIN de producciÃƒÆ’Ã‚Â³n solo costÃƒÆ’Ã‚Â³ ~5% mÃƒÆ’Ã‚Â¡s que un SELECT 1; este volumen debe ser suficiente para poner eso a prueba.

