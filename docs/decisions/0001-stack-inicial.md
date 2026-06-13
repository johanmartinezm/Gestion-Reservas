# 0001 — Stack inicial del proyecto

- **Estado:** Aceptado
- **Fecha:** 2026-06-13

## Contexto

`laravel_prueba` es un desarrollo nuevo. Necesitábamos definir el stack base
antes de empezar a construir funcionalidad, de modo que el equipo parta de una
fundación consistente y documentada.

## Decisión

Adoptamos el siguiente stack:

| Capa | Tecnología | Versión |
|------|------------|---------|
| Lenguaje | PHP | `^8.3` |
| Framework | Laravel | `^13.8` |
| Base de datos | SQLite (local/dev) | — |
| Frontend build | Vite | `^8.0` |
| CSS | Tailwind CSS | `^4.0` |
| Testing | PHPUnit | `^12.5` |
| Formateo | Laravel Pint | `^1.27` |
| Logs en dev | Laravel Pail | `^1.2` |
| REPL | Laravel Tinker | `^3.0` |

Configuración por defecto: sesiones, caché y colas sobre **base de datos**
(driver `database`); mail en driver `log` para desarrollo.

## Justificación

- **Laravel 13 + PHP 8.3**: última versión estable del framework; estructura
  predecible y convenciones claras, lo que acelera el desarrollo y facilita el
  trabajo con agentes de IA.
- **SQLite**: cero configuración para arrancar en local; suficiente para
  desarrollo temprano. Migrar a MySQL/PostgreSQL más adelante es trivial gracias
  al ORM agnóstico de Laravel (sería un nuevo ADR).
- **Tailwind 4 + Vite 8**: stack frontend moderno y rápido, ya integrado en el
  esqueleto oficial de Laravel.
- **Pint + PHPUnit**: formateo y pruebas automatizadas desde el día uno.

## Consecuencias

- El equipo debe tener PHP 8.3+ y Node 20+ instalados localmente.
- Para producción habrá que decidir el motor de base de datos definitivo
  (probablemente no SQLite) en un ADR posterior.
- Las dependencias de colas/caché/sesión sobre BD implican que las migraciones
  base (`cache`, `jobs`, `sessions`) deben correrse antes de usar esas features.

## Alternativas consideradas

- **Starter kits (Breeze/Jetstream)**: descartado por ahora; preferimos partir
  del esqueleto limpio y añadir solo lo que necesitemos.
- **MySQL desde el inicio**: descartado por la fricción de configuración en
  local; SQLite cubre la etapa inicial.
