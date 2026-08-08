# Pruebas

`jbtest` usa PHPUnit y un flujo pequeño orientado a benchmarks.

## Por qué este esquema de pruebas

Las pruebas no buscan cubrir todos los casos de un producto completo. Buscan validar que el testbed siga siendo estable, repetible y comparable. Por eso el enfoque se centra en tres cosas:

- que la base arranque de forma consistente;
- que la seguridad se pueda activar, desactivar y medir;
- que los datos sintéticos se puedan reconstruir sin fricción.

## Suites disponibles

- `phpunit.xml`
- `tests/Unit`
- `tests/Integration`
- `tests/Benchmark`

`phpunit.xml` arranca PHPUnit con `vendor/autoload.php` y usa SQLite en memoria para la ejecución de pruebas.

## Qué debería probar cada suite

- `Unit`: piezas aisladas, sin depender de la base de datos real del proyecto.
- `Integration`: comportamiento entre controladores, servicios y base de datos.
- `Benchmark`: flujos orientados a medición, comparación y repetición.

## Comandos comunes

```bash
composer test
composer test-unit
composer test-integration
```

## Ciclo de benchmark

1. Sembrar los datos sintéticos.
2. Llamar a `GET /health` para medir la línea base.
3. Llamar a `GET /health-secured` para medir la ruta asegurada.
4. Usar `POST /security/test/trigger` para ejercitar los detectores.
5. Usar `POST /admin/reset-security` antes de la siguiente repetición.

## Qué verificar

- El endpoint base debe mantenerse ligero y predecible.
- El endpoint asegurado debe incluir el contexto de autenticación.
- El endpoint de validación pública debe hacer el `JOIN` entre `items` e `item_categorias`.
- Los endpoints de despliegue solo deben aceptar los seeders permitidos.
- Los cambios en seguridad deben reflejarse en la medición, no romper el flujo.

## Resultado esperado

Una documentación de pruebas útil no solo dice cómo ejecutar comandos. También deja claro por qué el testbed usa SQLite en memoria, por qué existen rutas de reseteo y por qué separamos la línea base de la ruta asegurada.