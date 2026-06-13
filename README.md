# API de Reservas — Prueba Técnica

[![CI](https://github.com/johanmartinezm/Gestion-Reservas/actions/workflows/ci.yml/badge.svg)](https://github.com/johanmartinezm/Gestion-Reservas/actions/workflows/ci.yml)

![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8-646CFF?logo=vite&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![PHPUnit](https://img.shields.io/badge/tests-48%20passing-6DB33F?logo=php&logoColor=white)
![Code style: Pint](https://img.shields.io/badge/code%20style-Pint-F9322C)
![License](https://img.shields.io/badge/license-MIT-blue)

Servicio HTTP (API REST) para la gestión de **creación y cancelación de reservas**
de un sistema de citas, con sus reglas de negocio (horarios, anticipación,
solapamiento, reembolsos, límites). Construido con **Laravel 13 / PHP 8.3+**.

## Requisitos

- PHP **8.3+** (probado en 8.4)
- Composer 2.x
- SQLite (incluido con PHP; no requiere servidor de BD)

## Instalación y ejecución

```bash
composer install
cp .env.example .env          # Windows PowerShell: copy .env.example .env
php artisan key:generate
touch database/database.sqlite # PowerShell: New-Item database/database.sqlite
php artisan migrate --seed     # crea el esquema y carga data/seed.json
npm install                    # dependencias de frontend
npm run build                  # compila la portada (Tailwind + Vite)
php artisan serve              # http://localhost:8000
```

La API queda en `http://localhost:8000/api/v1/v1` (versionada, con rate limit de
60 req/min por IP). Los errores siguen **problem+json** (RFC 7807).

> **Portada visual:** al abrir la raíz `http://localhost:8000/` se muestra una
> página (estilizada con **Tailwind CSS** compilado por **Vite**) con la
> estructura de la API: endpoints, ejemplo de request/response, datos sembrados y
> reglas de negocio leídas de `config/reservations.php`. Requiere `npm run build`
> (o `npm run dev` para hot-reload). El atajo `composer setup` hace todo el setup
> de una sola vez.

## Pruebas

```bash
php artisan test     # o: composer test
```

51 pruebas (unitarias + feature) sobre SQLite en memoria. Ver
[`docs/plan-pruebas.md`](docs/plan-pruebas.md) para el mapa regla → prueba.

## Endpoints

Documentación detallada con ejemplos `curl` en [`docs/api.md`](docs/api.md).
Especificación **OpenAPI 3.1** en [`docs/openapi.yaml`](docs/openapi.yaml) y
**colección de Postman** en [`docs/postman_collection.json`](docs/postman_collection.json).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/v1/reservations` | Crear una reserva |
| `POST` | `/api/v1/reservations/{id}/cancel` | Cancelar y calcular reembolso |
| `GET`  | `/api/v1/reservations/{id}` | Ver una reserva |
| `GET`  | `/api/v1/users/{id}/reservations?from=&to=` | Listar reservas de un usuario por rango |
| `GET`  | `/api/v1/professionals/{id}/availability?date=&service_id=` | Horarios libres de un profesional |

### Ejemplo rápido

```bash
# Crear (con los datos del seed: usuario 2, servicio 1)
curl -X POST http://localhost:8000/api/v1/reservations \
  -H "Content-Type: application/json" \
  -d '{"user_id":2,"service_id":1,"starts_at":"2026-07-14 10:00"}'

# Cancelar
curl -X POST http://localhost:8000/api/v1/reservations/1/cancel
```

## Reglas de negocio implementadas

- **Horario:** lunes a sábado, 07:00–19:00 (hora de Bogotá). No domingos ni
  festivos de Colombia 2026. Toda la duración del servicio debe caber en la franja.
- **Anticipación mínima:** la reserva debe crearse con ≥ 2 horas de antelación.
- **Duración fija por servicio** y **sin solapamiento** entre reservas del mismo
  profesional (rangos semiabiertos: dos reservas contiguas no se solapan).
- **Reembolsos** (sobre el precio del servicio):

  | Plan | > 24 h | 24 h – 4 h | 4 h – 1 h | resto |
  |------|--------|------------|-----------|-------|
  | Estándar | 100 % | 50 % | 0 % | 0 % |
  | Premium | 100 % | 100 % | 50 % | 0 % |

- **Servicios `non_refundable`:** nunca reembolsan, pero **sí** se pueden cancelar.
- **Límite:** máximo **3 reservas activas** (no canceladas y futuras) por usuario.

Todos los parámetros (horario, anticipación, tramos, límite, festivos) viven en
[`config/reservations.php`](config/reservations.php).

## Arquitectura (resumen)

```
HTTP → Controller (delgado) → FormRequest (validación) → DTO
     → ReservationService (orquesta reglas, transacción + lock)
        ├── OperatingHours / HolidayCalendar
        ├── AvailabilityChecker
        └── RefundCalculator
     → Modelos Eloquent → SQLite
     → ApiResource (forma de la respuesta)
```

Las violaciones de reglas son **excepciones de dominio** (`app/Exceptions/`) que
se renderizan como JSON `{ "error": "...", "message": "..." }`. Detalle completo
en [`docs/architecture.md`](docs/architecture.md) y [`docs/database.md`](docs/database.md).

## Decisiones técnicas (qué elegí y por qué)

- **Laravel + API HTTP:** el proyecto ya estaba sobre Laravel 13; aprovecho su
  validación, ORM, contenedor de DI y testing. La API es la forma más natural de
  exponer y probar el servicio.
- **Persistencia en SQLite:** cero configuración para correr y evaluar; el ORM es
  agnóstico, migrar a MySQL/Postgres sería trivial.
- **Reglas en componentes separados** (`OperatingHours`, `AvailabilityChecker`,
  `RefundCalculator`, `HolidayCalendar`): cada regla se prueba de forma aislada y
  el `ReservationService` solo orquesta.
- **Dinero en centavos (enteros):** evita errores de redondeo con floats.
- **Enums nativos** para `status` y `plan`: seguridad de tipos.
- **Concurrencia:** la creación corre dentro de `DB::transaction()` con
  `lockForUpdate()` sobre las reservas del profesional y el conteo de activas del
  usuario, evitando que dos peticiones simultáneas creen reservas solapadas o
  superen el límite.
- **Zona horaria `America/Bogota`:** como el negocio opera únicamente en Colombia
  (un solo huso), la app usa `America/Bogota` como timezone (`config/app.php`). Así
  el instante almacenado, los casts y la salida de la API son consistentes y la
  entrada sin offset se interpreta como hora local. Las reglas (horario,
  anticipación, reembolsos) se evalúan siempre en esa zona. *Trade-off:* en una app
  multi-región se almacenaría en UTC y se convertiría por usuario; aquí no aplica.
- **Cancelación con `POST .../cancel`:** es un cambio de estado con cálculo de
  reembolso, no un borrado; por eso no uso `DELETE`.
- **API versionada (`/api/v1`) + rate limiting** (60 req/min por IP) y errores en
  formato **problem+json (RFC 7807)** con `type`/`title`/`status`/`detail`/`code`,
  para un contrato de errores estable y consistente.

## Supuestos

- **Sin autenticación.** La prueba no la pide; el `user_id` viaja en el payload o
  la ruta. En producción se añadiría Sanctum/JWT y se derivaría el usuario del token.
- El **profesional se deriva del servicio** (cada servicio pertenece a un
  profesional en el catálogo).
- Las **fechas sin zona horaria** en la entrada se interpretan como hora de Bogotá.
- Los **festivos** son una lista hardcodeada de 2026 (`config/reservations.php`),
  como permite el enunciado.
- En los datos de ejemplo, las **filas irrecuperables se descartan** (no se
  intenta adivinar datos faltantes salvo defaults seguros como `plan=standard`).

### Manejo de inconsistencias del `seed.json`

`App\Support\SeedImporter` normaliza al importar:
- Fechas en varios formatos (`Y-m-d H:i`, ISO 8601 con offset, `d/m/Y H:i`).
- Fecha no parseable → la reserva se descarta y se reporta.
- Usuario sin `plan` → `standard`. Usuario sin `email` → se descarta.
- Servicio sin `duration_minutes` o sin `professional_id` → se descarta.
- `price` se convierte a centavos.

Cada fila descartada se informa por consola al sembrar (no aborta la carga).

## Qué dejé por fuera (y por qué)

- **Autenticación/autorización:** fuera del alcance pedido (ver supuestos).
- **Paginación** en el listado: el volumen esperado es bajo; se añadiría con
  `paginate()` y los meta de `ResourceCollection`.
- **Gestión CRUD de catálogo** (crear servicios/profesionales por API): el foco
  es reservas; el catálogo se carga vía seed.
- **Reprogramación** de reservas: no estaba en los requisitos.

## Concurrencia

La creación de reservas corre dentro de un **`Cache::lock` por profesional** (más
`DB::transaction()`), de modo que dos peticiones para el mismo profesional se
serializan y no pueden crear reservas solapadas ni exceder el límite, incluso en
motores que no soportan `SELECT ... FOR UPDATE` (como SQLite). Cubierto por
`tests/Feature/ConcurrencyTest.php`.

## CI

`.github/workflows/ci.yml` ejecuta en cada push/PR: instalación, **Pint** (estilo)
y la suite de **pruebas con cobertura** (pcov, umbral mínimo del **70%**) sobre PHP 8.4.

## Qué haría con más tiempo

- Respuestas de error más ricas (catálogo de códigos, i18n de mensajes) y un
  envelope estándar (problem+json).
- Servicio ↔ profesional muchos-a-muchos (hoy es 1:1) y endpoint de reprogramación.
- Paginación y filtros en el listado; `Idempotency-Key` al crear.
- Análisis estático (Larastan) y reporte de cobertura.
- Prueba de concurrencia con dos conexiones reales en paralelo (hoy se verifica la
  invariante con el lock; ver `docs/plan-pruebas.md`).

## Transparencia sobre uso de IA

Ver [`NOTAS.md`](NOTAS.md).
