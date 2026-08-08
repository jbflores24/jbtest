# Configuración

Este documento lista las variables de configuración visibles en el código actual y explica por qué existen dentro del testbed.

## Aplicación

Definidas en `config/app.php`:

| Variable | Valor por defecto | Propósito | Por qué importa |
| --- | --- | --- | --- |
| `APP_NAME` | `JB API` | Nombre de la aplicación | Ayuda a identificar el entorno durante pruebas y logs |
| `APP_ENV` | `production` | Entorno de ejecución | Cambia el comportamiento de seguridad y depuración |
| `APP_DEBUG` | `false` | Activa la salida de depuración | Permite inspeccionar fallos sin alterar rutas |
| `APP_URL` | `http://localhost:8000` | URL base de la aplicación | Sirve como referencia para despliegue local |
| `APP_BASE_ROUTE` | `/api` | Prefijo que retira el front controller | Mantiene consistencia con el router y evita duplicar prefijos |
| `ROUTE_CACHE_ENABLED` | `false` | Activa el caché de rutas | Reduce trabajo en ejecución, útil para comparar escenarios |
| `ROUTE_CACHE_PATH` | `storage/cache/routes.json` | Archivo del caché de rutas | Define dónde guardar la tabla compilada |
| `CORS_ALLOWED_ORIGINS` | `*` | Orígenes permitidos para CORS | Simplifica pruebas locales y de laboratorio |

## Seguridad

Definidas en `config/security.php`:

| Variable | Valor por defecto | Propósito | Por qué importa |
| --- | --- | --- | --- |
| `SECURITY_ENABLED` | `true` | Activa o desactiva el middleware de seguridad | Permite comparar la ruta base contra la ruta protegida |
| `SECURITY_LEARNING_MODE` | `false` | Permite observar sin bloquear | Sirve para calibrar detectores antes de activar bloqueos |
| `SECURITY_FAIL_OPEN` | `true` | Controla el comportamiento fail-open | Evita que una falla del módulo rompa el testbed completo |

## Límite de solicitudes

Definidas en `config/rate_limit.php`:

| Variable | Valor por defecto | Propósito | Por qué importa |
| --- | --- | --- | --- |
| `RATE_LIMIT_MAX` | `120` | Máximo de solicitudes por ventana | Define el umbral de presión permitido |
| `RATE_LIMIT_WINDOW` | `60` | Tamaño de la ventana en segundos | Afecta el comportamiento de acumulación y limpieza |

## Helpers de despliegue

Usados por los endpoints de despliegue y reseteo por HTTP:

| Variable | Propósito | Por qué importa |
| --- | --- | --- |
| `DEPLOY_TOKEN` | Protege los endpoints de despliegue y revisión de configuración | Evita que alguien ejecute migraciones o seeders sin autorización |
| `ADMIN_RESET_TOKEN` | Protege el endpoint de reseteo de seguridad | Permite reiniciar el benchmark sin abrir las tablas a cualquiera |

## Autenticación

`AuthController` usa la clave JWT definida en configuración:

| Variable | Propósito | Por qué importa |
| --- | --- | --- |
| `JWT_SECRET` | Firma y verifica los tokens de acceso | Garantiza que los tokens del testbed sean consistentes y verificables |

## Base de datos

La aplicación espera las variables estándar usadas por la capa de conexión:

- `DB_DRIVER`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `DB_CHARSET`
- `DB_PATH`

## Uso operativo

Las variables de entorno no solo habilitan la conexión; también marcan el estado experimental del testbed. En concreto:

- definen si la seguridad está activa;
- permiten alternar entre observación y bloqueo;
- controlan qué tan agresivo será el rate limit;
- protegen los helpers de despliegue y limpieza.