# jbtest

`jbtest` es un banco de pruebas en PHP 8.2 construido para medir, comparar y repetir escenarios sobre la capa de seguridad, el flujo de autenticación, el CRUD de items y los helpers de despliegue sin shell.

La idea del repositorio no es parecer una aplicación final, sino servir como una base controlada para pruebas reales. Por eso el alcance es pequeño, los datos son sintéticos y las rutas están pensadas para ser predecibles.

## Por qué existe

Este proyecto se mantiene separado del framework base por una razón práctica: permite experimentar sin contaminar el código del producto principal. Eso hace más fácil responder preguntas como:

- cuánto cuesta pasar por el middleware de seguridad;
- qué cambia cuando una ruta se ejecuta con o sin autenticación;
- cómo se comporta el rate limit bajo carga;
- qué tan repetible es un despliegue en un entorno sin acceso por SSH.

## Qué incluye

- `GET /health` como verificación base sin middleware de seguridad.
- `GET /health-secured` con autenticación y validaciones de seguridad.
- Inicio y cierre de sesión con JWT.
- CRUD de items protegido por autenticación.
- Validación pública de items por UUID con `JOIN` a `item_categorias`.
- Endpoints de prueba y reseteo de seguridad para benchmarks.
- Helpers de despliegue por HTTP para escenarios de hosting compartido.

## Inicio rápido

```bash
composer install
php bin/jb migrate
php bin/jb seed RoleSeeder
php bin/jb seed UserSeeder
php bin/jb seed ItemCategoriaSeeder
php bin/jb seed ItemSeeder
```

Si el entorno no permite acceso por shell, usa los endpoints de despliegue definidos en `routes/api.php` junto con `DEPLOY_TOKEN`.

## Cómo leer la documentación

- [TESTBED.md](TESTBED.md) resume el propósito experimental y el flujo recomendado.
- [docs/architecture.md](docs/architecture.md) explica cómo se organiza el sistema y por qué.
- [docs/configuration.md](docs/configuration.md) lista las variables relevantes y su función.
- [docs/endpoints.md](docs/endpoints.md) documenta las rutas expuestas.
- [docs/testing.md](docs/testing.md) describe cómo se prueban y comparan los escenarios.

## Relación con el estudio

- [jb-framework](https://github.com/jbflores24/jb-framework): repositorio base del framework evaluado.
- [pruebas_jb](https://github.com/jbflores24/pruebas_jb): artefactos experimentales, trazabilidad y reproducibilidad.
- [research-2026-v1](https://github.com/jbflores24/jbtest/tree/research-2026-v1): referencia estable de este banco de pruebas.

## Alcance

Todo lo que se documenta aquí debe existir en `routes/api.php`, `config/`, `database/` o `tests/`. Si una ruta, tabla o variable no está en el código actual, no se debe asumir como parte del testbed.