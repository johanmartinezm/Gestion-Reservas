# CLAUDE.md

Guía para agentes de IA (Claude Code) que trabajan en este repositorio.

## Qué es este proyecto

`laravel_prueba` — aplicación web sobre **Laravel 13** (PHP 8.3). Desarrollo
nuevo; por ahora solo el esqueleto base. La documentación del equipo vive en
[`docs/`](docs/README.md).

## Stack

- **Backend:** Laravel `^13.8`, PHP `^8.3`
- **Base de datos:** SQLite (dev), driver `database` para sesión/caché/colas
- **Frontend:** Vite `^8`, Tailwind CSS `^4`
- **Tests:** PHPUnit `^12.5` (SQLite en memoria)
- **Formato:** Laravel Pint

## Comandos clave

```bash
composer dev      # levanta server + queue + logs + vite en paralelo
composer test     # config:clear + php artisan test
./vendor/bin/pint # formatea el código PHP
php artisan tinker
php artisan route:list
```

Detalle completo en [docs/getting-started.md](docs/getting-started.md).

## Reglas al modificar código

- **Formato:** corre `./vendor/bin/pint` después de tocar PHP. No formatees a mano.
- **Estilo:** sigue [docs/conventions.md](docs/conventions.md) (naming, estructura, Git).
- **Controladores delgados:** la lógica de negocio no vive en el controlador.
- **Validación:** usa Form Requests cuando supere reglas triviales.
- **Tests:** toda lógica nueva lleva prueba; deja `composer test` en verde antes de terminar.
- **Migraciones:** una migración = un cambio coherente. No edites migraciones ya aplicadas en otras ramas; crea una nueva.
- **Entorno:** nunca toques `.env` real; si agregas una variable, documéntala en `.env.example`.
- **No commitear** secretos, `vendor/`, `node_modules/` ni assets compilados (ya en `.gitignore`).

## Documentación

- Si un cambio afecta setup, arquitectura o convenciones, actualiza `docs/` en el mismo cambio.
- Decisiones técnicas con impacto a futuro → nuevo ADR en `docs/decisions/` (ver [0001](docs/decisions/0001-stack-inicial.md)).

## Convenciones de plataforma

- Entorno de desarrollo en **Windows / PowerShell**. Para comandos de shell, usa
  sintaxis PowerShell (`copy` en vez de `cp`, `New-Item` en vez de `touch`).
