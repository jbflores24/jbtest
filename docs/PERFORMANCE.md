# Performance

## Objetivo

La Etapa 7 oficial introduce optimizaciones básicas y microbenchmarks reproducibles para medir el costo del enrutamiento, QueryBuilder y JWT.

## Cache de rutas

El Router ahora compila los patrones dinámicos una sola vez y puede persistir esa compilación a disco para reutilizarla entre requests.

Configuración en config/app.php:

```php
'routes_cache' => [
    'enabled' => filter_var($_ENV['ROUTE_CACHE_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOL),
    'path' => $_ENV['ROUTE_CACHE_PATH'] ?? 'storage/cache/routes.json',
],
```

Variables de entorno disponibles:

```env
ROUTE_CACHE_ENABLED=false
ROUTE_CACHE_PATH=storage/cache/routes.json
```

Recomendación:

- Activar ROUTE_CACHE_ENABLED=true en producción.
- Mantenerlo desactivado en desarrollo si se editan rutas con frecuencia.
- Limpiar el archivo de cache si se despliega un cambio manual de rutas sin reiniciar procesos.

## Benchmarks

Benchmarks disponibles:

- php vendor/bin/phpunit --testsuite Benchmark --filter RouterBenchmarkTest --testdox
- php vendor/bin/phpunit --testsuite Benchmark --filter QueryBuilderBenchmarkTest --testdox
- php vendor/bin/phpunit --testsuite Benchmark --filter JwtBenchmarkTest --testdox

Qué mide cada uno:

- RouterBenchmarkTest: costo promedio de dispatch con y sin cache compilada de rutas.
- QueryBuilderBenchmarkTest: costo promedio de SELECT con filtros, orden y limit sobre SQLite en memoria.
- JwtBenchmarkTest: costo promedio de encode y decode HMAC-SHA256.

## Hotspots revisados

### Rate limiting basado en archivo

El RateLimiter actual escribe un archivo JSON por identidad y ventana. Es adecuado para desarrollo, single node o tráfico moderado, pero escala mal bajo alta concurrencia por:

- I/O síncrono por request.
- Contención sobre el mismo archivo.
- Dificultad para escalar horizontalmente.

Recomendación futura: abstraer el backend a Redis o Memcached.

### Logging síncrono

Logger y DistributedLogger escriben a disco en el request actual. Esto simplifica la operación, pero añade latencia acumulada cuando el volumen de logs aumenta.

Recomendación futura:

- Reducir niveles en producción.
- Rotar archivos.
- Mover eventos de alto volumen a transporte asíncrono.

## Resultado esperado

En una máquina local estándar, el benchmark del Router con cache habilitada debe permanecer por debajo de 2 ms promedio por dispatch del escenario medido.

## Relacion con el testbed experimental

En `jbtest` el rendimiento que se evalua en el Experimento A no es el del Router aislado, sino el impacto agregado de activar o desactivar la cadena global de seguridad sobre un endpoint publico estable:

- `GET /api/public/items/{uuid}/validar`

La comparacion ON/OFF se realiza en `pruebas_jb/` mediante el benchmark HTTP y el orquestador. Esta documentacion local conserva el contexto del framework base y remite a esa metodologia cuando se analizan resultados experimentales.
