# Reproducibilidad del testbed

## Requisitos

- PHP 8.2 o superior.
- Extensiones PHP: `curl`, `pdo_mysql`, `json`.
- Acceso a MariaDB para las rutinas de despliegue y auditoria.
- Acceso HTTP al entorno donde corre `jbtest`.
- El valor local de `DEPLOY_TOKEN` para consultar la configuracion.

## Configuracion local

El experimento utiliza al menos estas variables de entorno:

- `SECURITY_ENABLED`
- `SECURITY_FAIL_OPEN`
- `SECURITY_LEARNING_MODE`
- `DEPLOY_TOKEN`
- `ADMIN_RESET_TOKEN`

Los valores concretos no deben publicarse si contienen credenciales o tokens reales.

## Verificacion de seguridad

1. Invocar `GET /api/admin/security-config-check?token=...`.
2. Confirmar en la respuesta el valor de `env_SECURITY_ENABLED`.
3. Repetir el procedimiento cuando se cambie el archivo `.env`.

## Verificacion de integridad

1. Invocar `GET /api/admin/security-scores-check?token=...&ip=...`.
2. Confirmar que las filas retornadas corresponden a la IP esperada.
3. Usar `POST /api/admin/reset-security` solo cuando se quiera limpiar el estado de seguridad antes de una nueva corrida.

## Relacion con el benchmark

La orquestacion y el analisis estadistico se realizan en el proyecto cliente `pruebas_jb`. Este repositorio conserva el sistema evaluado y su documentacion tecnica.
