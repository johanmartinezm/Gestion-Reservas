# Plan de implementación — API de Reservas (Laravel)

> Estado: **ejecutado** (implementado; 43 pruebas en verde).
> Fecha: 2026-06-13. Basado en la prueba técnica de `docs/Prueba.pdf`.
> Plan de pruebas detallado en [plan-pruebas.md](plan-pruebas.md).
> Referencia de la API en [api.md](api.md); decisión en [ADR 0002](decisions/0002-diseno-api-reservas.md).

## 1. Enfoque general

- **Forma:** API HTTP REST sobre el Laravel 13 ya montado.
- **Persistencia:** SQLite (ya configurado). Los datos de `data/seed.json` se
  **ingieren a la BD** mediante un importador testeable, normalizando las
  inconsistencias intencionales (demuestra manejo de datos sucios).
- **Arquitectura por capas:** Controlador delgado → Form Request (validación de
  forma + autorización) → **DTO** → **Service de dominio** (reglas de negocio) →
  Modelos Eloquent → **API Resource** (forma de la respuesta). Las violaciones de
  reglas se lanzan como **excepciones de dominio** con `render()` propio → JSON
  con códigos HTTP correctos.
- **Zona horaria:** se guarda en UTC; las reglas (horario, anticipación,
  reembolsos) se evalúan en `America/Bogota` con `CarbonImmutable`.
- **Calidad de código:** `declare(strict_types=1)` en clases de dominio, tipado
  estricto en firmas, Pint como formateador.

## 2. Decisiones resueltas

| Tema | Decisión | Razón |
|------|----------|-------|
| Endpoint de cancelación | `POST /api/reservations/{reservation}/cancel` | Es un cambio de estado con cálculo de reembolso, no un borrado. |
| Autenticación | **Sin auth**; `user_id` viaja en payload/ruta | La prueba no la pide; enfoca el tiempo en reglas de negocio. Documentado como supuesto. |
| Identificadores en rutas | **Route Model Binding** (`{reservation}`, `{user}`) | 404 automático y controladores más limpios. |
| Dinero | Entero en **centavos** (`int`), nunca `float` | Evita errores de redondeo en reembolsos. |
| Estados / planes | **Enums nativos** backed + casts Eloquent | Seguridad de tipos y autodocumentación. |
| Parámetros de negocio | **`config/reservations.php`** | Horario, anticipación, tramos de reembolso, límite y festivos configurables, sin números mágicos. |

## 3. Modelo de datos (migraciones)

| Tabla | Campos clave |
|-------|--------------|
| `users` (extender) | + `plan` string casteado a enum `UserPlan` (`standard`/`premium`), default `standard` |
| `professionals` | `id`, `name` |
| `services` | `id`, `name`, `duration_minutes` (int), `price_cents` (int), `non_refundable` (bool), `professional_id` (FK) |
| `reservations` | `id`, `user_id` (FK), `service_id` (FK), `professional_id` (FK), `starts_at`, `ends_at`, `status` (enum `ReservationStatus` `active`/`cancelled`), `price_cents` (int, snapshot), `refund_cents` (int, nullable), `cancelled_at` (nullable), timestamps |

> `ends_at` se calcula al crear (`starts_at + service.duration_minutes`).
> `price_cents` se "congela" en la reserva por si el catálogo cambia luego.
> Índices en `reservations(professional_id, starts_at)` y `(user_id, status)`.

**Enums:** `App\Enums\UserPlan`, `App\Enums\ReservationStatus` (backed, con casts).

**Modelos Eloquent:** `Professional`, `Service`, `Reservation` (+ relaciones,
casts de fechas a `immutable_datetime`, casts de enums y dinero); `User`
extendido con `plan` y relación `reservations`.

## 4. Datos semilla (`data/seed.json` + importador)

- Crear `data/seed.json` con datos representativos **e inconsistencias
  intencionales** documentadas:
  - fechas en formatos distintos (`2026-07-10 09:00`, ISO 8601, `10/07/2026 9am`),
  - campos faltantes (servicio sin `duration`, usuario sin `plan`),
  - un servicio `non_refundable`.
- **`App\Support\SeedImporter`** (clase de dominio, testeable de forma aislada):
  - parseo de fechas tolerante (varios formatos → Carbon; si no se puede, descarta
    la fila y registra warning),
  - defaults para campos faltantes (`plan` → `standard`, etc.),
  - filas inválidas se omiten y se reportan (no rompen la importación),
  - devuelve un reporte de filas importadas/descartadas.
- **`SeedJsonSeeder`** delgado: solo lee el archivo e invoca a `SeedImporter`.
- Documentar en README la estructura del JSON y la política de inconsistencias.

## 5. Reglas de negocio (núcleo)

Componentes dedicados y testeables de forma aislada (parámetros desde
`config/reservations.php`):

- **`HolidayCalendar`** — festivos de Colombia 2026 (config); `isHoliday(date)`.
- **`OperatingHours`** — Lun–Sáb 7:00–19:00 Bogotá; rechaza domingos y festivos;
  valida que **toda la duración** caiga dentro del horario.
- **`AvailabilityChecker`** — sin solapamiento para el mismo profesional (compara
  rangos `[starts_at, ends_at)`).
- **`RefundCalculator`** — calcula reembolso (en centavos) según horas de
  anticipación, `plan` del usuario y `non_refundable`:

| Caso | >24h | 24h–4h | 4h–1h | <1h (o <4h estándar) |
|------|------|--------|-------|------|
| Estándar | 100% | 50% | 0% | 0% |
| Premium | 100% | 100% | 50% | 0% |
| `non_refundable` | 0% en todos los casos (pero la cancelación sí se permite) |

- **`ReservationService`** orquesta, recibiendo un **DTO `CreateReservationData`**:
  - `create(...)`: anticipación ≥ 2h → horario → festivos/domingos → no
    solapamiento → límite de **3 reservas activas futuras** por usuario → persiste.
    **Todo dentro de `DB::transaction()` con `lockForUpdate()`** sobre las
    reservas relevantes para evitar *race conditions* (concurrencia básica).
  - `cancel(reservation)`: marca `cancelled`, calcula `refund_cents` vía
    `RefundCalculator`, dentro de transacción.
  - `listForUser(user, from, to)`: reservas en rango de fechas.

## 6. Excepciones de dominio

- Base `App\Exceptions\DomainException` con `render()` → JSON
  `{ "error": "codigo", "message": "..." }` y status 422.
- Específicas: `OutsideOperatingHoursException`, `InsufficientLeadTimeException`,
  `OverlappingReservationException`, `ActiveReservationLimitException`,
  `ReservationNotCancellableException` (cada una con su `error` code).

## 7. Endpoints (`routes/api.php`)

Registrar `api.php` en `bootstrap/app.php` (hoy no existe). Route Model Binding.

| Método | Ruta | Acción | Éxito |
|--------|------|--------|-------|
| `POST` | `/api/reservations` | Crear reserva | `201` |
| `POST` | `/api/reservations/{reservation}/cancel` | Cancelar + monto reembolsado | `200` |
| `GET` | `/api/users/{user}/reservations?from=&to=` | Listar en rango | `200` |
| `GET` | `/api/reservations/{reservation}` | Ver una reserva (apoyo) | `200` |

- **Form Requests** (`StoreReservationRequest`, `ListReservationsRequest`) para
  validar forma (campos requeridos, tipos, fecha válida, rango coherente).
- **API Resources** (`ReservationResource`) para la forma de la respuesta.
- **Errores:** dominio → `422`; modelo no encontrado → `404`; validación → `422`
  estándar de Laravel. Render JSON ya activo para `api/*`.

## 8. Pruebas automatizadas

Ver el **[plan de pruebas](plan-pruebas.md)** para el detalle de casos. Resumen:
Feature (endpoints) + Unit (reglas) sobre SQLite en memoria, con **factories**
(`ProfessionalFactory`, `ServiceFactory`, `ReservationFactory`). Objetivo ≥ 5,
apuntando a ~12 cubriendo reglas y casos borde.

## 9. Documentación (entregables de la prueba)

- **`README.md`** (raíz, reemplazando el default): cómo correr, decisiones
  técnicas y por qué, supuestos, qué quedó fuera, qué haría con más tiempo.
- **`NOTAS.md`**: qué se hizo con ayuda de IA, qué se ajustó/reescribió y por qué.
- Actualizar `docs/` (`database.md`, `architecture.md`) y añadir `docs/api.md`
  con endpoints + ejemplos `curl`.
- ADR `docs/decisions/0002-diseno-api-reservas.md`.

## 10. Orden de ejecución

1. `config/reservations.php` + enums (`UserPlan`, `ReservationStatus`).
2. Migraciones + modelos + relaciones + casts + factories.
3. `data/seed.json` + `SeedImporter` + `SeedJsonSeeder`.
4. Componentes de reglas (`HolidayCalendar`, `OperatingHours`,
   `AvailabilityChecker`, `RefundCalculator`).
5. DTO + excepciones de dominio + `ReservationService` (con transacción/locks).
6. Form Requests + API Resources + Controladores + `routes/api.php` + registro
   en `bootstrap/app.php`.
7. Pruebas (correr `composer test` en verde).
8. README, NOTAS, docs, ADR.
9. `./vendor/bin/pint` para formatear.
