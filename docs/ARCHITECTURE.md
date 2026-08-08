# Arquitectura

`jbtest` está organizado como un banco de pruebas pequeño para una API con tres capas principales:

- Entrada HTTP y rutas en `public/index.php` y `routes/api.php`
- Controladores de aplicación en `app/Controllers`
- Servicios del framework y de seguridad en `src/`

## Por qué esta arquitectura

La arquitectura es simple a propósito. Para medir bien un componente, conviene reducir al mínimo todo lo que lo rodea. Si la base fuera más compleja, el benchmark mezclaría demasiadas variables al mismo tiempo.

Con esta separación se consigue:

- una entrada HTTP clara y fácil de reproducir;
- controladores delgados, centrados en la intención del endpoint;
- servicios de seguridad reutilizables y aislados;
- datos y rutas fáciles de reiniciar entre corridas.

## Flujo de una petición

1. El front controller arranca la aplicación.
2. Las rutas se registran desde `routes/api.php`.
3. El middleware se aplica donde corresponde, especialmente en las rutas protegidas.
4. Los controladores leen la petición y hablan con la capa de base de datos.
5. La respuesta se devuelve en formato JSON.

## Capa de seguridad

El subsistema de seguridad se controla desde `config/security.php` y desde el middleware en `src/Security`.

Esta capa existe para comparar comportamiento con y sin protección adicional. En una prueba de rendimiento eso es importante porque permite separar dos escenarios:

- una ruta limpia, usada como línea base;
- una ruta protegida, usada para medir el costo de inspección y bloqueo.

Comportamientos importantes:

- Algunas rutas pueden excluirse del middleware de seguridad.
- El middleware ejecuta comprobaciones previas antes de que corra el controlador.
- Los detectores pueden bloquear una petición o permitirla en modo de aprendizaje.
- Los eventos de seguridad se guardan en las tablas creadas por migración.

## Modelo del dominio

El dominio actual es intencionalmente pequeño:

- `users`
- `roles`
- `permissions`
- `role_permissions`
- `item_categorias`
- `items`
- Tablas de seguridad para bloqueos, bitácoras, puntuaciones, whitelist, blacklist y auditoría

La razón de mantener este modelo corto es que el benchmark debe probar una forma de trabajo, no una aplicación rica en negocio. El valor está en la repetición y la comparación.

## Controladores

- `HealthController` expone las verificaciones de salud base y segura.
- `AuthController` maneja el inicio y cierre de sesión con JWT.
- `ItemController` gestiona el CRUD protegido de items.
- `PublicItemController` valida un item por UUID.
- `SecurityTestController` envía payloads por la cadena de seguridad.
- `AdminResetController` limpia las tablas de seguridad para una corrida nueva.
- `DeployController` ejecuta migraciones, seeders y comprobaciones de configuración por HTTP.

## Lectura recomendada

Si quieres entender el sistema en orden, empieza por:

1. `public/index.php`
2. `routes/api.php`
3. `config/app.php`
4. `config/security.php`
5. `app/Controllers/HealthController.php`
6. `app/Controllers/ItemController.php`
7. `src/Security/SecurityMiddleware.php`