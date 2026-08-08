# Experimento A en `jbtest`

## Proposito

`jbtest` es el sistema sometido a evaluacion en el Experimento A. El objetivo metodologico es comparar el comportamiento del endpoint publico de validacion con la cadena global de seguridad activa y desactivada.

El experimento no altera la implementacion del endpoint; solo cambia la configuracion efectiva de `SECURITY_ENABLED`.

## Endpoint evaluado

- metodo: `GET`
- ruta: `/api/public/items/{uuid}/validar`
- controlador: `App\Controllers\PublicItemController::validar`
- acceso a datos: `Jb\Database\Connection` y `PDO`
- consulta: `items` con `JOIN item_categorias`
- respuesta esperada: JSON `success` con `valid=true` cuando existe el UUID; `404` si no existe

## Flujo observado

Cuando `SECURITY_ENABLED=true`, la solicitud pasa por `Jb\Security\SecurityMiddleware` antes de llegar al controlador. Ese middleware:

1. verifica si el modulo esta habilitado;
2. aplica exclusiones por ruta y extensiones;
3. construye `SecurityRequest`;
4. ejecuta limpieza de estados expirados;
5. consulta bloqueos y listas;
6. ejecuta detectores pre-request;
7. deja pasar la solicitud al router si no hubo bloqueo;
8. ejecuta detectores post-response.

Cuando `SECURITY_ENABLED=false`, `Application::run()` omite ese middleware y la ruta se despacha directamente.

## Elementos experimentales relevantes

- `GET /api/admin/security-config-check?token=...` devuelve el valor efectivo de `SECURITY_ENABLED`.
- `GET /api/admin/security-scores-check?token=...&ip=...` permite verificar la persistencia de la tabla `security_scores`.
- `POST /api/admin/reset-security` trunca tablas de seguridad usando `ADMIN_RESET_TOKEN`.

## Persistencia implicada

- `security_scores`: conteo por ventana y clave logica.
- `security_blocks`: bloqueos activos por IP.
- `security_logs`: eventos de amenaza.
- `security_whitelist` y `security_blacklist`: excepciones y denegaciones persistentes.
- `security_audit`: auditoria administrativa.

## Consideraciones de reproducibilidad

La documentacion de reproduccion operativa del experimento, incluyendo variables de entorno, comandos y verificaciones, se encuentra en `docs/benchmarks/reproducibility.md`.
