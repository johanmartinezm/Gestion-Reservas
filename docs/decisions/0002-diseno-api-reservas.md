# 0002 — Diseño de la API de Reservas

- **Estado:** Aceptado
- **Fecha:** 2026-06-13

## Contexto

Implementar el módulo de gestión de reservas (prueba técnica, `docs/Prueba.pdf`)
sobre el esqueleto Laravel existente, cubriendo reglas de negocio con casos borde
y concurrencia básica.

## Decisión

API REST en Laravel con arquitectura por capas:

- **Controladores delgados** + **Form Requests** (validación de forma) + **DTO**
  (`CreateReservationData`).
- **`ReservationService`** como orquestador de reglas, apoyado en componentes
  de regla aislados y testeables: `OperatingHours`, `HolidayCalendar`,
  `AvailabilityChecker`, `RefundCalculator`.
- **Excepciones de dominio** con `render()` propio → JSON con código de error y
  status HTTP adecuado (422 / 409).
- **API Resources** para la forma de la respuesta.
- **Enums nativos** (`UserPlan`, `ReservationStatus`) y **dinero en centavos**.
- **Parámetros de negocio** centralizados en `config/reservations.php`.

### Puntos clave

- **Concurrencia:** creación y cancelación dentro de `DB::transaction()` con
  `lockForUpdate()` sobre las reservas del profesional y el conteo de activas del
  usuario, para evitar solapamientos o exceder el límite ante peticiones simultáneas.
- **Zona horaria:** la app opera en `America/Bogota` (`config/app.php`), por ser un
  negocio de un solo huso; instante almacenado, casts y salida de la API quedan
  consistentes en hora local. Las reglas se evalúan en esa zona.
- **Solapamiento:** rangos semiabiertos `[inicio, fin)` (reservas contiguas no
  colisionan).
- **Datos sucios:** `SeedImporter` normaliza/descarta filas del `seed.json` y
  reporta lo omitido.

## Consecuencias

- Reglas auditables y testeables de forma unitaria; el servicio queda simple.
- Cambios de parámetros (horario, tramos, festivos) no requieren tocar código.
- Sin autenticación por ahora (ver README); habría que añadir Sanctum para producción.

## Alternativas consideradas

- **Lógica en controladores:** descartada por dificultar pruebas y reutilización.
- **Reembolsos hardcodeados en código:** descartado a favor de configuración.
- **`DELETE` para cancelar:** descartado; cancelar es un cambio de estado con
  cálculo de reembolso, no un borrado.
