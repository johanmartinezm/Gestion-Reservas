# API — Referencia de endpoints

Base URL local: `http://localhost:8000/api/v1/v1`. Todas las respuestas son JSON.
Sin autenticación. Rate limit: 60 peticiones/minuto por IP (ver supuestos en el README).

> **Especificación e importación:** disponible como [OpenAPI 3.1](openapi.yaml)
> (importable en Swagger UI, Insomnia o Postman) y como
> [colección de Postman](postman_collection.json) lista para usar (define la
> variable `base_url`).

## Formato de errores

Los errores siguen **problem+json** (RFC 7807), con `Content-Type:
application/problem+json`:

```json
{
  "type": "/problems/outside_operating_hours",
  "title": "Outside Operating Hours",
  "status": 422,
  "detail": "Descripción legible.",
  "code": "outside_operating_hours"
}
```

| `code` | HTTP | Significado |
|--------|------|-------------|
| `insufficient_lead_time` | 422 | Menos de 2 h de anticipación. |
| `outside_operating_hours` | 422 | Domingo, festivo o fuera de 07:00–19:00. |
| `overlapping_reservation` | 422 | El profesional ya está ocupado en ese rango. |
| `active_reservation_limit` | 422 | El usuario ya tiene 3 reservas activas. |
| `reservation_not_cancellable` | 409 | La reserva ya está cancelada. |

Los errores de **validación de forma** también usan problem+json e incluyen un
miembro `errors` con los detalles por campo. Los recursos inexistentes devuelven
`404` con `type: /problems/not-found`.

---

## Crear reserva

`POST /api/v1/reservations`

**Body**

| Campo | Tipo | Requerido | Notas |
|-------|------|-----------|-------|
| `user_id` | int | sí | Debe existir. |
| `service_id` | int | sí | Debe existir; define duración, precio y profesional. |
| `starts_at` | datetime | sí | Hora local de Bogotá si no trae offset. |

```bash
curl -X POST http://localhost:8000/api/v1/reservations \
  -H "Content-Type: application/json" \
  -d '{"user_id":2,"service_id":1,"starts_at":"2026-07-14 10:00"}'
```

**201 Created**

```json
{
  "data": {
    "id": 5,
    "user_id": 2,
    "service_id": 1,
    "professional_id": 1,
    "starts_at": "2026-07-14T10:00:00-05:00",
    "ends_at": "2026-07-14T10:30:00-05:00",
    "status": "active",
    "price_cents": 3000000,
    "refund_cents": null,
    "cancelled_at": null
  }
}
```

---

## Cancelar reserva

`POST /api/v1/reservations/{id}/cancel`

Calcula el reembolso según anticipación, plan y si el servicio es no reembolsable.

```bash
curl -X POST http://localhost:8000/api/v1/reservations/5/cancel
```

**200 OK**

```json
{
  "data": {
    "id": 5,
    "status": "cancelled",
    "refund_cents": 3000000,
    "cancelled_at": "2026-07-13T09:00:00-05:00"
  }
}
```

---

## Ver reserva

`GET /api/v1/reservations/{id}` → `200 OK` con el mismo recurso. `404` si no existe.

---

## Disponibilidad de un profesional

`GET /api/v1/professionals/{id}/availability?date=YYYY-MM-DD&service_id={id}`

Devuelve los horarios de inicio libres del profesional para esa fecha y la
duración del servicio indicado, respetando horario, festivos, anticipación
mínima y reservas existentes. Días cerrados (domingo/festivo) devuelven `slots` vacío.

```bash
curl "http://localhost:8000/api/v1/professionals/1/availability?date=2026-06-16&service_id=1"
```

**200 OK**

```json
{
  "data": {
    "professional_id": 1,
    "service_id": 1,
    "date": "2026-06-16",
    "duration_minutes": 30,
    "slots": [
      "2026-06-16T07:00:00-05:00",
      "2026-06-16T07:30:00-05:00",
      "2026-06-16T08:00:00-05:00"
    ]
  }
}
```

---

## Listar reservas de un usuario

`GET /api/v1/users/{id}/reservations?from=YYYY-MM-DD&to=YYYY-MM-DD`

Filtra por `starts_at` dentro del rango `[from, to]`. Ambos parámetros son
requeridos y `to` debe ser ≥ `from`.

```bash
curl "http://localhost:8000/api/v1/users/2/reservations?from=2026-07-01&to=2026-07-31"
```

**200 OK**

```json
{
  "data": [
    { "id": 2, "user_id": 2, "status": "active", "starts_at": "2026-07-10T11:00:00-05:00", "...": "..." }
  ]
}
```
