# TESTBED - jbtest

`jbtest` es el repositorio experimental usado para validar el middleware de seguridad, el flujo de autenticación, los endpoints de CRUD de items y los helpers de despliegue por HTTP.

El objetivo es mantener un banco de pruebas reproducible, con entradas claras, datos previsibles y un conjunto pequeño de rutas que se puedan medir o reiniciar con facilidad.

## Por qué se diseñó así

La prioridad del testbed es permitir comparación, no complejidad. Por eso se usa una estructura sencilla:

- pocas entidades;
- datos sintéticos;
- rutas públicas y protegidas claramente separadas;
- mecanismos de reseteo para repetir corridas;
- despliegue por HTTP solo donde hace falta demostrar compatibilidad con hosting compartido.

Ese enfoque ayuda a aislar el costo real de cada capa y evita mezclar el benchmark con lógica de negocio innecesaria.

## Flujos principales

- Verificación base: `GET /health`
- Verificación con seguridad: `GET /health-secured`
- Inicio y cierre de sesión: `POST /auth/login`, `POST /auth/logout`
- CRUD de items: `GET|POST|PUT|DELETE /items`
- Validación pública por UUID: `GET /public/items/{uuid}/validar`
- Disparador de seguridad: `POST /security/test/trigger`
- Reseteo de seguridad: `POST /admin/reset-security`
- Despliegue por HTTP: `POST /admin/deploy/migrate`, `POST /admin/deploy/seed`
- Revisión de configuración de seguridad: `GET /admin/security-config-check`
- Revisión de conteos de seguridad: `GET /admin/security-scores-check`

Todas las rutas se sirven bajo `APP_BASE_ROUTE`, que por defecto es `/api`.

## Propósito del conjunto de datos

El proyecto usa datos sintéticos para que el flujo de benchmark se pueda repetir sin tocar información real.

Eso permite dos cosas importantes:

1. repetir la misma prueba con la misma forma de entrada;
2. resetear el estado sin depender de una base de datos de producción.

- `RoleSeeder`: crea roles y permisos mínimos para autenticación.
- `UserSeeder`: genera usuarios de prueba para los distintos perfiles.
- `ItemCategoriaSeeder`: prepara las categorías necesarias para el `JOIN`.
- `ItemSeeder`: carga suficientes items para medir paginación y lectura.

## Variables de entorno

Estas son las variables más relevantes para el testbed:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `JWT_SECRET`
- `SECURITY_ENABLED`
- `SECURITY_LEARNING_MODE`
- `SECURITY_FAIL_OPEN`
- `RATE_LIMIT_MAX`
- `RATE_LIMIT_WINDOW`
- `DEPLOY_TOKEN`
- `ADMIN_RESET_TOKEN`

## Flujo recomendado de benchmark

1. Ejecutar migraciones.
2. Sembrar los datos sintéticos.
3. Comparar `GET /health` contra `GET /health-secured`.
4. Probar el endpoint de validación pública.
5. Disparar la ruta de prueba de seguridad.
6. Reiniciar las tablas de seguridad antes de la siguiente corrida.

## Qué se busca medir

- El costo base del arranque HTTP.
- El costo de autenticación y validación de usuario.
- El impacto de los detectores de seguridad sobre la petición.
- El costo de consultas con `JOIN` en rutas públicas.
- La capacidad de repetir pruebas sin intervención manual.

## Notas

- `jbtest` es el proyecto que se documenta aquí, no el framework base.
- Cualquier ruta que no exista en `routes/api.php` no debe documentarse como parte de este testbed.
- Las rutas del panel de seguridad provienen de `Jb\Security\SecurityRoutes` y solo deben documentarse si esa versión del paquete forma parte del alcance actual.