# Convenciones del proyecto

Estándares acordados por el equipo. El objetivo es que el código sea consistente sin importar quién lo escriba.

## Estilo de código (PHP)

- El formato lo dicta **Laravel Pint** — no discutimos estilo manualmente, lo aplica la herramienta.
- Antes de commitear: `./vendor/bin/pint`.
- Reglas base de `.editorconfig`: indentación de **4 espacios**, fin de línea `LF`, charset `UTF-8`, salto de línea final obligatorio.
- YAML usa **2 espacios** de indentación.

## Naming

Seguimos las convenciones estándar de Laravel:

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Modelo | Singular, `StudlyCase` | `User`, `OrderItem` |
| Tabla | Plural, `snake_case` | `users`, `order_items` |
| Controlador | `StudlyCase` + sufijo `Controller` | `UserController` |
| Método de controlador | `camelCase`, verbos REST | `index`, `store`, `update` |
| Migración | descriptiva, `snake_case` | `create_orders_table` |
| Ruta (nombre) | `snake_case` con puntos | `users.index` |
| Variable / método | `camelCase` | `$activeUsers`, `getTotal()` |
| Constante | `UPPER_SNAKE_CASE` | `STATUS_ACTIVE` |

## Estructura

- Lógica de negocio fuera de los controladores: los controladores son delgados (validan, delegan, responden).
- Validación en **Form Requests** (`php artisan make:request`) cuando crezca más allá de reglas triviales.
- Cada migración hace **un cambio coherente**; no mezclar tablas no relacionadas.
- Las decisiones técnicas con impacto a futuro se registran como **ADR** en `docs/decisions/`.

## Pruebas

- Toda feature nueva con lógica debe llevar al menos una prueba.
- `tests/Feature` para flujos end-to-end (rutas, endpoints); `tests/Unit` para lógica aislada.
- Corren sobre SQLite en memoria — ver [getting-started](getting-started.md#pruebas).
- Verde antes de pedir review: `composer test`.

## Flujo de Git

- **Nunca** commitear directo a la rama principal; trabajar en ramas.
- Nombre de rama: `tipo/descripcion-corta` → `feature/login`, `fix/migracion-orders`, `chore/update-deps`.
- Commits en imperativo y concretos: `Agrega validación de email en registro`.
- Nunca commitear `.env` ni secretos (ya está en `.gitignore`).

## Pull Requests

- PRs pequeños y enfocados; más fáciles de revisar.
- Descripción con: **qué** cambia, **por qué**, y **cómo probarlo**.
- Incluir actualización de documentación en `docs/` en el mismo PR si aplica.
- Pint y tests en verde antes de marcar listo para review.
- Requiere al menos una aprobación antes de mergear.

## Variables de entorno

- Toda variable nueva se documenta también en `.env.example` (sin el valor real/secreto).
- No subir credenciales al repo bajo ninguna circunstancia.
