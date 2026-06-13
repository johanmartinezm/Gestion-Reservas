# Plan de pruebas — API de Reservas

> Estado: **propuesto**. Fecha: 2026-06-13.
> Complementa a [plan-api-reservas.md](plan-api-reservas.md).

## 1. Estrategia

- **Framework:** PHPUnit 12 (ya en el proyecto), sobre **SQLite en memoria**
  (`:memory:`, configurado en `phpunit.xml`). Aislado de la BD local.
- **Trait `RefreshDatabase`** en pruebas que tocan BD.
- **Factories** para construir datos (`UserFactory`, `ProfessionalFactory`,
  `ServiceFactory`, `ReservationFactory`) → sin fixtures manuales.
- **Tiempo controlado:** `Carbon::setTestNow()` para fijar "ahora" y probar
  anticipación, horarios y tramos de reembolso de forma determinista.
- **Zona horaria:** los casos se construyen en `America/Bogota` para validar la
  conversión correcta.
- **Dos niveles:**
  - **Unit** — reglas de negocio puras, sin BD (rápidas, exhaustivas en tramos).
  - **Feature** — endpoints HTTP end-to-end (request → respuesta JSON + estado en BD).

## 2. Pirámide de pruebas

```
Feature (HTTP)      ─ flujos completos por endpoint y errores
Unit (dominio)      ─ RefundCalculator, OperatingHours, HolidayCalendar,
                      AvailabilityChecker, SeedImporter
```

## 3. Pruebas unitarias (dominio)

### `RefundCalculatorTest`
| # | Caso | Esperado |
|---|------|----------|
| U1 | Estándar, cancela > 24h antes | reembolso 100% |
| U2 | Estándar, cancela entre 24h y 4h | 50% |
| U3 | Estándar, cancela < 4h | 0% |
| U4 | Premium, cancela entre 4h y 1h | 50% |
| U5 | Premium, cancela > 4h antes | 100% |
| U6 | Premium, cancela < 1h | 0% |
| U7 | Servicio `non_refundable` (cualquier plan/tiempo) | 0% |
| U8 | Cálculo en centavos sin error de redondeo (precio impar, p.ej. 50% de 999) | entero correcto |

### `OperatingHoursTest`
| # | Caso | Esperado |
|---|------|----------|
| U9 | Martes 10:00 | permitido |
| U10 | Domingo | rechazado |
| U11 | Festivo 2026 (p.ej. 1-ene) | rechazado |
| U12 | Lunes 06:30 (antes de apertura) | rechazado |
| U13 | Servicio que termina 19:30 (se sale del cierre) | rechazado |
| U14 | Límite exacto: inicia 18:00, dura 60min, cierra 19:00 | permitido |

### `HolidayCalendarTest`
| # | Caso | Esperado |
|---|------|----------|
| U15 | Festivo conocido de Colombia 2026 | `isHoliday` = true |
| U16 | Día hábil normal | false |

### `AvailabilityCheckerTest`
| # | Caso | Esperado |
|---|------|----------|
| U17 | Reserva que se solapa parcialmente con otra del mismo profesional | conflicto |
| U18 | Reservas contiguas (una termina cuando la otra empieza) | sin conflicto |
| U19 | Solapamiento pero **distinto profesional** | sin conflicto |
| U20 | Solapamiento con una reserva **cancelada** | sin conflicto |

### `SeedImporterTest` (manejo de datos sucios)
| # | Caso | Esperado |
|---|------|----------|
| U21 | Fechas en varios formatos | todas normalizadas a Carbon |
| U22 | Fila con fecha no parseable | descartada + reportada, no rompe |
| U23 | Usuario sin `plan` | default `standard` |
| U24 | Servicio sin `duration` | descartado/flagged según política |

## 4. Pruebas de feature (HTTP)

### Crear reserva — `POST /api/reservations`
| # | Caso | Esperado |
|---|------|----------|
| F1 | Datos válidos dentro de reglas | `201` + reserva en BD, `ends_at` calculado |
| F2 | Anticipación < 2h | `422` `insufficient_lead_time` |
| F3 | Domingo / festivo / fuera de horario | `422` `outside_operating_hours` |
| F4 | Solapamiento mismo profesional | `422` `overlapping_reservation` |
| F5 | Usuario con 3 reservas activas futuras | `422` `active_reservation_limit` |
| F6 | Payload inválido (falta `service_id`) | `422` validación estándar |
| F7 | `service_id` / `user_id` inexistente | `404` o `422` (según binding) |

### Cancelar reserva — `POST /api/reservations/{reservation}/cancel`
| # | Caso | Esperado |
|---|------|----------|
| F8 | Cancela estándar > 24h | `200` + `status=cancelled` + reembolso 100% |
| F9 | Cancela `non_refundable` | `200` + cancelada + reembolso 0% |
| F10 | Cancela reserva ya cancelada | `422`/`409` `reservation_not_cancellable` |
| F11 | Cancela reserva inexistente | `404` |

### Listar — `GET /api/users/{user}/reservations?from=&to=`
| # | Caso | Esperado |
|---|------|----------|
| F12 | Rango con reservas | `200` + solo las del rango y del usuario |
| F13 | Rango sin reservas | `200` + lista vacía |
| F14 | Rango inválido (`from` > `to`) | `422` |

## 5. Concurrencia (caso borde de la rúbrica)

| # | Caso | Esperado | Estado |
|---|------|----------|--------|
| C1 | Dos creaciones que solaparían al mismo profesional | solo una persiste; la otra recibe conflicto | ✅ `ConcurrencyTest` |
| C2 | Cuatro intentos del mismo usuario con el límite en 3 | solo 3 prosperan; el cuarto se rechaza | ✅ `ConcurrencyTest` |

> Se prueba a nivel de servicio ejecutando los intentos en secuencia inmediata.
> SQLite en memoria (una conexión) no permite hilos realmente simultáneos, así que
> no se simula paralelismo real; lo que se garantiza es la invariante protegida por
> `DB::transaction()` + `lockForUpdate()` (bloqueo pesimista). Documentado en el README.

## 6. Cobertura objetivo

- **Mínimo exigido por la prueba:** 5 pruebas.
- **Objetivo de este plan:** ~24 unit + ~14 feature, priorizando las críticas
  (reglas de reembolso, horario, solapamiento, límite y datos sucios).
- Ejecutar con `composer test`; opcional `--coverage` si el entorno lo permite.

## 7. Mapa reglas de negocio → pruebas

| Regla de negocio | Pruebas |
|------------------|---------|
| Horario Lun–Sáb 7–19, domingos/festivos | U9–U16, F3 |
| Anticipación mínima 2h | F2 |
| Duración fija + no solapamiento profesional | U17–U20, F1, F4, C1 |
| Reembolso estándar (100/50/0) | U1–U3, F8 |
| Reembolso premium (100/50/0) | U4–U6 |
| `non_refundable` nunca reembolsa, sí cancela | U7, F9 |
| Límite 3 reservas activas | F5 |
| Manejo de datos inconsistentes | U21–U24 |
