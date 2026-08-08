# Benchmark methodology

## Objetivo

Medir comportamiento tecnico del framework con criterios repetibles y comparables.

## Alcance actual

Implementado:

- benchmarks internos para Router, QueryBuilder y JWT en tests/Benchmark.

Planeado:

- comparativos sistematicos contra frameworks externos.
- bateria de medicion con infraestructura dedicada.

## Metricas

1. Latencia media por request/operacion.
2. Uso de memoria por flujo.
3. Tiempo de bootstrap.
4. Tiempo de scaffolding CLI.
5. Complejidad ciclomatica por modulo.
6. Numero de queries por endpoint representativo.

## Reglas de medicion

- misma maquina y version de PHP para comparaciones directas;
- calentamiento previo;
- n iteraciones por escenario;
- reporte de promedio y dispersion;
- guardar contexto de entorno junto al resultado.

## Comparativos externos

Estado: Planeado.

Targets sugeridos:

- Laravel
- Slim
- CodeIgniter

La comparacion debe limitarse a escenarios equivalentes para evitar sesgos.

## Aplicacion al testbed `jbtest`

El experimento de rendimiento asociado a `jbtest` se apoya en estas reglas y las concreta en el siguiente diseno:

- seguridad: ON/OFF
- concurrencia: 1, 5, 10 y 20
- repeticiones: 10
- solicitudes medidas por corrida: 100

La carga se aplica al endpoint publico de validacion `GET /api/public/items/{uuid}/validar`, mientras que la verificacion del estado de la seguridad se realiza con `GET /api/admin/security-config-check`.

La documentacion operacional del experimento se encuentra en `docs/benchmarks/experiment-a.md`, `docs/benchmarks/reproducibility.md` y `docs/benchmarks/traceability.md`.
