# Traceability del experimento

| Elemento experimental | Implementacion | Archivo | Funcion/clase | Evidencia |
| --------------------- | -------------- | ------- | ------------- | --------- |
| Endpoint evaluado | Validacion publica por UUID | `app/Controllers/PublicItemController.php` | `validar()` | Consulta `items` + `item_categorias` y retorna `valid=true` cuando existe coincidencia. |
| Routing del endpoint | Ruta publica sin autenticacion de negocio | `routes/api.php` | definicion de ruta | `GET /public/items/{uuid}/validar`. |
| Alternancia ON/OFF | Interrupcion global del middleware de seguridad | `src/Core/Application.php` | `run()` | Condiciona la ejecucion de `SecurityMiddleware` a `security.enabled`. |
| Lectura de `SECURITY_ENABLED` | Configuracion de seguridad | `config/security.php` | archivo de config | Lee `$_ENV['SECURITY_ENABLED']`. |
| Pipeline de seguridad | Cadena de detectores + preflight + post-response | `src/Security/SecurityMiddleware.php` | `handle()` | Ejecuta limpieza, preflight, detectores y respuesta. |
| Fingerprint | Huella de request | `src/Security/utils/SecurityRequest.php` | `fromRequest()` | SHA-256 sobre IP, `User-Agent` y `Accept-Language`. |
| Rate limiting interno | Contador por ventana | `src/Security/detectors/RateLimitDetector.php` | `analyze()` | Usa `security_scores` y umbrales de configuracion. |
| Bloqueos activos | Persistencia de bloqueos por IP | `src/Security/models/BlockModel.php` | `block()`, `active()`, `unblock()` | Tabla `security_blocks`. |
| Registro de amenazas | Bitacora de eventos | `src/Security/models/LogModel.php` | `create()` | Tabla `security_logs`. |
| Conteo de score | Ventanas de intento | `src/Security/models/ScoreModel.php` | `hit()` | Tabla `security_scores`. |
| Comprobacion de configuracion | Estado efectivo de la seguridad | `app/Controllers/DeployController.php` | `securityConfigCheck()` | Retorna `env_SECURITY_ENABLED`. |
| Reset de seguridad | Limpieza previa a benchmarking | `app/Controllers/AdminResetController.php` | `reset()` | Trunca `security_blocks`, `security_logs`, `security_scores`, `security_whitelist`, `security_blacklist` y `security_audit`. |
| Verificacion de integridad | Consulta de scores por IP | `app/Controllers/DeployController.php` | `securityScoresCheck()` | Consulta `security_scores` por IP y devuelve conteo y filas. |
| Documentacion de benchmark | Metodologia del proyecto base | `docs/benchmarks/methodology.md` | documento | Describe calentamiento, iteraciones y comparabilidad. |
| Reproducibilidad | Guia operativa | `docs/benchmarks/reproducibility.md` | documento | Resume variables, verificacion y relacion con el benchmark. |
