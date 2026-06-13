# NOTAS — Transparencia sobre el uso de IA

Este documento describe, con honestidad, qué partes del desarrollo se hicieron con
ayuda de un asistente de IA, qué se ajustó o reescribió a mano y por qué.

## Qué se hizo con ayuda de IA

- **Scaffolding general** de la solución: estructura de carpetas, migraciones,
  modelos, factories, controladores, Form Requests, recursos y rutas siguiendo las
  convenciones de Laravel.
- **Primera versión de los componentes de reglas** (`OperatingHours`,
  `AvailabilityChecker`, `RefundCalculator`, `HolidayCalendar`) y del
  `ReservationService`.
- **Borrador de las pruebas** unitarias y de feature.
- **Redacción de la documentación** (este archivo, README, ADR y docs de apoyo).

## Qué se ajustó / revisó manualmente

- **Reglas de negocio:** se verificó contra el enunciado cada tramo de reembolso
  (estándar vs premium) y la semántica de "entre X y Y horas". Los tramos quedaron
  parametrizados en `config/reservations.php` para que sean auditables.
- **Manejo de zona horaria:** se decidió explícitamente almacenar en UTC y evaluar
  reglas en `America/Bogota`; se añadieron pruebas con fechas límite (cierre a las
  19:00, festivos, domingos) para validarlo.
- **Solapamiento con rangos semiabiertos `[inicio, fin)`:** se ajustó para que dos
  reservas contiguas no cuenten como conflicto, y se cubrió con prueba.
- **Concurrencia:** se añadió `DB::transaction()` + `lockForUpdate()` a la creación
  y cancelación (no estaba en el primer borrador), por ser un criterio de evaluación.
- **Parser de fechas del seed:** se corrigió el modo estricto de Carbon (lanzaba
  excepción en vez de devolver `false`) y se priorizó el formato `d/m/Y` para no
  confundirlo con el formato estadounidense.
- **Aserciones de pruebas:** se ajustaron a la envoltura `data` de los API
  Resources y a los tipos (`float` vs `int`) tras correr la suite.

## Por qué este enfoque

El objetivo fue usar la IA para acelerar el trabajo mecánico (scaffolding,
boilerplate, primera redacción) y dedicar el criterio propio a lo que la prueba
realmente evalúa: **correctitud de las reglas, casos borde, concurrencia y
claridad**. Todo el código fue leído, entendido y ejecutado; la suite de pruebas
(43 casos) sirve como red de seguridad y como evidencia de las decisiones.
