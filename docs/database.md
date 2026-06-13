# Base de datos

Esquema, modelos y convenciones de datos del proyecto. Documento vivo: se
actualiza con cada migración relevante.

## Motor

- **Desarrollo:** SQLite (`database/database.sqlite`), cero configuración.
- **Producción:** por definir (probablemente MySQL/PostgreSQL) — ver
  [ADR 0001](decisions/0001-stack-inicial.md). Gracias al ORM agnóstico de
  Laravel, el cambio será de bajo impacto.

Driver configurado vía `DB_CONNECTION=sqlite` en `.env`.

## Migraciones

Viven en `database/migrations/`. Aplicarlas:

```bash
php artisan migrate              # aplica las pendientes
php artisan migrate:fresh        # recrea todo desde cero (¡borra datos!)
php artisan migrate:fresh --seed # recrea y siembra
```

Las migraciones base que trae el proyecto:

| Migración | Tablas que crea |
|-----------|-----------------|
| `..._create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `..._create_cache_table` | `cache`, `cache_locks` |
| `..._create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |

> Las tablas `sessions`, `cache*` y `jobs*` existen porque sesión, caché y colas
> usan el driver `database` (ver [architecture.md](architecture.md)).

## Esquema actual

### `users`

Tabla principal de usuarios autenticables.

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint (PK) | autoincremental |
| `name` | string | |
| `email` | string | único |
| `email_verified_at` | timestamp | nullable |
| `password` | string | hasheado (cast `hashed`) |
| `remember_token` | string | para "recordarme" |
| `created_at` / `updated_at` | timestamps | |

### `password_reset_tokens`

| Columna | Tipo | Notas |
|---------|------|-------|
| `email` | string (PK) | |
| `token` | string | |
| `created_at` | timestamp | nullable |

### `sessions`

Sesiones persistidas (driver `database`). Claves: `id` (PK), `user_id` (index,
nullable), `ip_address`, `user_agent`, `payload`, `last_activity` (index).

### `cache` / `cache_locks`

Caché sobre base de datos. `cache(key PK, value, expiration)` y
`cache_locks(key PK, owner, expiration)`.

### `jobs` / `job_batches` / `failed_jobs`

Soporte de colas sobre base de datos: `jobs` (cola pendiente), `job_batches`
(lotes) y `failed_jobs` (jobs fallidos con su excepción).

## Dominio de reservas

### `professionals`

`id`, `name`, timestamps. Un profesional ofrece servicios y atiende reservas.

### `services`

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint (PK) | |
| `name` | string | |
| `duration_minutes` | unsigned int | duración fija del servicio |
| `price_cents` | unsigned int | precio en centavos (sin floats) |
| `non_refundable` | bool | si nunca reembolsa |
| `professional_id` | FK → professionals | |

### `reservations`

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | bigint (PK) | |
| `user_id` | FK → users | |
| `service_id` | FK → services | |
| `professional_id` | FK → professionals | derivado del servicio |
| `starts_at` / `ends_at` | datetime | `ends_at = starts_at + duración` |
| `status` | string→enum `ReservationStatus` | `active` / `cancelled` |
| `price_cents` | unsigned int | snapshot del precio al reservar |
| `refund_cents` | unsigned int (nullable) | calculado al cancelar |
| `cancelled_at` | datetime (nullable) | |

Índices: `(professional_id, starts_at)` y `(user_id, status)` para las consultas
de solapamiento y de límite de activas.

### `users` (extendida)

Se añadió `plan` (string→enum `UserPlan`: `standard`/`premium`, default `standard`).

## Modelos

### `User` (`app/Models/User.php`)

Único modelo de dominio por ahora. Extiende `Authenticatable`.

- **Fillable:** `name`, `email`, `password` (vía atributo `#[Fillable]`).
- **Hidden:** `password`, `remember_token` (vía atributo `#[Hidden]`).
- **Casts:** `email_verified_at` → `datetime`, `password` → `hashed`.
- Usa los traits `HasFactory` y `Notifiable`.

> En Laravel 13 estos modelos usan **atributos PHP** (`#[Fillable]`, `#[Hidden]`)
> en lugar de las propiedades `$fillable` / `$hidden` tradicionales.

## Factories y seeders

- **Factories** (`database/factories/`): `UserFactory` genera usuarios de prueba.
- **Seeders** (`database/seeders/`): `DatabaseSeeder` es el punto de entrada
  (`php artisan db:seed`).

## Convenciones

- Tablas en plural `snake_case`; modelos en singular `StudlyCase` (ver
  [conventions.md](conventions.md)).
- Una migración = un cambio coherente.
- No editar migraciones ya aplicadas/compartidas; crear una nueva.
- Toda relación nueva entre modelos se documenta aquí.
