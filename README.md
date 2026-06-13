# API de Reservas — Prueba Técnica

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
php artisan serve              # http://localhost:8000
```

La API queda en `http://localhost:8000/api`.

> **Portada visual:** al abrir la raíz `http://localhost:8000/` se muestra una
> página con la estructura de la API (endpoints, ejemplo de request/response,
> datos sembrados y reglas de negocio leídas de `config/reservations.php`). Es
> autocontenida: no requiere `npm run build`.

## Pruebas

```bash
php artisan test     # o: composer test
```

43 pruebas (unitarias + feature) sobre SQLite en memoria. Ver
[`docs/plan-pruebas.md`](docs/plan-pruebas.md) para el mapa regla → prueba.

## Endpoints

Documentación detallada con ejemplos `curl` en [`docs/api.md`](docs/api.md).

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/reservations` | Crear una reserva |
| `POST` | `/api/reservations/{id}/cancel` | Cancelar y calcular reembolso |
| `GET`  | `/api/reservations/{id}` | Ver una reserva |
| `GET`  | `/api/users/{id}/reservations?from=&to=` | Listar reservas de un usuario por rango |

### Ejemplo rápido

```bash
# Crear (con los datos del seed: usuario 2, servicio 1)
curl -X POST http://localhost:8000/api/reservations \
  -H "Content-Type: application/json" \
  -d '{"user_id":2,"service_id":1,"starts_at":"2026-07-14 10:00"}'

# Cancelar
curl -X POST http://localhost:8000/api/reservations/1/cancel
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

## Qué haría con más tiempo

- Endpoint de disponibilidad (slots libres por profesional/día).
- DTOs y respuestas de error más ricas (catálogo de códigos, i18n de mensajes).
- Prueba de concurrencia real con dos conexiones simultáneas (hoy se cubre la ruta
  transaccional con bloqueo; ver `docs/plan-pruebas.md`).
- Cobertura de código y CI (GitHub Actions con Pint + tests).

## Transparencia sobre uso de IA

Ver [`NOTAS.md`](NOTAS.md).
