# Getting Started

Guía para levantar el proyecto en local desde cero.

## Requisitos

| Herramienta | Versión |
|-------------|---------|
| PHP | `^8.3` |
| Composer | 2.x |
| Node.js | 20+ (recomendado para Vite 8) |
| npm | 10+ |

> La base de datos por defecto es **SQLite**, así que no necesitas instalar MySQL/PostgreSQL para empezar.

## Instalación

El proyecto incluye un script de Composer que automatiza todo el setup:

```bash
composer setup
```

Este comando ejecuta, en orden:

1. `composer install` — instala dependencias PHP.
2. Copia `.env.example` a `.env` si no existe.
3. `php artisan key:generate` — genera la `APP_KEY`.
4. `php artisan migrate --force` — corre las migraciones.
5. `npm install --ignore-scripts` — instala dependencias de frontend.
6. `npm run build` — compila los assets con Vite.

### Setup manual (alternativa)

Si prefieres hacerlo paso a paso:

```bash
composer install
cp .env.example .env          # En Windows/PowerShell: copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

> **SQLite:** si el archivo de base de datos no existe, créalo con
> `New-Item database/database.sqlite` (PowerShell) o `touch database/database.sqlite` (bash)
> antes de migrar. Confirma que `DB_CONNECTION=sqlite` en tu `.env`.

## Desarrollo

Para trabajar en local arranca todos los procesos a la vez:

```bash
composer dev
```

Esto levanta en paralelo (vía `concurrently`):

| Proceso | Comando | Qué hace |
|---------|---------|----------|
| `server` | `php artisan serve` | Servidor HTTP de la app. |
| `queue` | `php artisan queue:listen` | Procesa la cola de jobs (driver `database`). |
| `logs` | `php artisan pail` | Muestra los logs en vivo. |
| `vite` | `npm run dev` | Hot reload de assets (CSS/JS). |

La app queda disponible en `http://localhost:8000` (o la `APP_URL` que configures).

## Pruebas

```bash
composer test
```

Equivale a `php artisan config:clear` seguido de `php artisan test`.
Las pruebas corren sobre SQLite **en memoria** (`:memory:`), por lo que no tocan tu base de datos local. Configurado en `phpunit.xml`.

## Formateo de código

El proyecto usa **Laravel Pint** (sobre PHP-CS-Fixer):

```bash
./vendor/bin/pint            # aplica el formato
./vendor/bin/pint --test     # solo verifica, sin modificar
```

## Comandos útiles

```bash
php artisan tinker           # REPL interactivo
php artisan route:list       # lista todas las rutas
php artisan migrate:fresh    # recrea la BD desde cero (¡borra datos!)
php artisan migrate:fresh --seed  # recrea y siembra datos
```
