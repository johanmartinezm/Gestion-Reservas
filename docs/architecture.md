# Arquitectura

Visión general de la estructura del proyecto y cómo fluyen los datos. Documento
vivo: se actualiza a medida que el proyecto crece.

## Patrón general

Aplicación **Laravel 13** siguiendo el patrón MVC del framework. Por ahora es el
esqueleto base; este documento define dónde debe vivir cada cosa a medida que se
construye funcionalidad.

## Estructura de carpetas

```
laravel_prueba/
├── app/
│   ├── Http/
│   │   └── Controllers/    # Controladores (delgados)
│   ├── Models/             # Modelos Eloquent
│   └── Providers/          # Service providers
├── bootstrap/
│   ├── app.php             # Bootstrap de la app (rutas, middleware, excepciones)
│   └── cache/              # Cachés de framework generadas
├── config/                 # Archivos de configuración
├── database/
│   ├── factories/          # Factories para tests/seeders
│   ├── migrations/         # Esquema de la base de datos
│   └── seeders/            # Datos de siembra
├── public/                 # Document root (index.php, assets compilados)
├── resources/
│   ├── css/ · js/          # Fuentes de frontend (compiladas por Vite)
│   └── views/              # Plantillas Blade
├── routes/
│   ├── web.php             # Rutas web (con sesión, CSRF)
│   └── console.php         # Comandos Artisan tipo closure
├── storage/                # Logs, cachés, archivos generados
└── tests/                  # Unit y Feature
```

## Configuración de la aplicación

En Laravel 13 la configuración del kernel está en **`bootstrap/app.php`** (no hay
`app/Http/Kernel.php`). Ahí se registran:

- Archivos de rutas (`web`, `console`).
- Middleware global y de grupo.
- Manejo de excepciones.

Los **service providers** propios se registran en `bootstrap/providers.php`
(actualmente solo `AppServiceProvider`).

## Flujo de una petición

```
Petición HTTP
   │
   ▼
public/index.php  →  bootstrap/app.php
   │
   ▼
Middleware (global → grupo)
   │
   ▼
routes/web.php  →  Controlador
   │
   ▼
Lógica de negocio (Servicios) → Modelos (Eloquent) → Base de datos
   │
   ▼
Vista Blade / respuesta JSON
```

## Dónde va cada cosa (convenciones de capas)

| Responsabilidad | Ubicación |
|-----------------|-----------|
| Recibir petición y responder | `app/Http/Controllers/` (controladores delgados) |
| Validación de entrada | Form Requests (`app/Http/Requests/`) |
| Lógica de negocio | Clases de servicio / acciones (a definir al crecer) |
| Acceso a datos | Modelos Eloquent en `app/Models/` |
| Esquema de datos | Migraciones en `database/migrations/` |
| Presentación | Vistas Blade en `resources/views/` |
| Tareas en segundo plano | Jobs (cola sobre driver `database`) |

> A medida que aparezca lógica de negocio real, decidiremos la estructura
> concreta (Services, Actions, etc.) y la registraremos en un ADR
> (`docs/decisions/`).

## Frontend

- **Vite 8** compila los assets desde `resources/`.
- **Tailwind CSS 4** para estilos.
- En desarrollo, `npm run dev` (incluido en `composer dev`) da hot reload.
- En build, `npm run build` genera los assets en `public/build/`.

## Servicios sobre base de datos

Sesión, caché y colas usan el driver `database` (ver [database.md](database.md)),
por lo que dependen de que sus tablas estén migradas.

## Módulo de Reservas

El primer módulo de negocio implementado es la **API de reservas**. Sigue las
capas descritas arriba:

- **HTTP:** `routes/api.php` → `ReservationController` / `UserReservationController`
  (delgados) → Form Requests → `CreateReservationData` (DTO).
- **Dominio:** `App\Services\ReservationService` orquesta las reglas, apoyándose en
  `OperatingHours`, `HolidayCalendar`, `AvailabilityChecker` y `RefundCalculator`.
  Las violaciones son excepciones de `App\Exceptions\` (con `render()` → JSON).
- **Datos:** modelos `Reservation`, `Service`, `Professional` y `User` (con `plan`).
  `App\Support\SeedImporter` normaliza `data/seed.json`.
- **Respuesta:** `ReservationResource` (API Resource).
- **Configuración:** parámetros de negocio en `config/reservations.php`.

Detalle de endpoints en [api.md](api.md), esquema en [database.md](database.md) y
la decisión de diseño en [ADR 0002](decisions/0002-diseno-api-reservas.md).
