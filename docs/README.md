# Documentación — laravel_prueba

Documentación técnica del proyecto, dirigida al equipo de desarrollo.

## Índice

| Documento | Descripción |
|-----------|-------------|
| [Getting Started](getting-started.md) | Requisitos, instalación y comandos del día a día. |
| [Arquitectura](architecture.md) | Estructura de carpetas, capas y flujo de una petición. |
| [Base de datos](database.md) | Esquema, migraciones, modelos y convenciones de datos. |
| [API de Reservas](api.md) | Referencia de endpoints con ejemplos `curl`. |
| [Plan — API de Reservas](plan-api-reservas.md) | Plan de implementación del módulo de reservas. |
| [Plan de pruebas](plan-pruebas.md) | Estrategia y casos de prueba. |
| [Convenciones](conventions.md) | Estándares de código, naming, flujo de Git y PRs. |
| [Decisiones de arquitectura (ADR)](decisions/) | Registro de decisiones técnicas importantes. |

## Sobre estos documentos

- Viven en el repositorio y versionan junto al código: cualquier cambio relevante en el código debe acompañarse de su actualización en docs en el **mismo PR**.
- Están en Markdown plano, legibles desde GitHub o el editor sin levantar nada.
- A medida que el proyecto crezca se irán añadiendo documentos (p. ej. `api.md`, `deployment.md`).

## ¿Cómo contribuir a la documentación?

1. Edita o crea el archivo `.md` correspondiente dentro de `docs/`.
2. Si tomas una decisión técnica con impacto a futuro, añade un ADR en `docs/decisions/` (ver el [primer ADR](decisions/0001-stack-inicial.md) como plantilla).
3. Mantén el índice de arriba actualizado cuando agregues documentos nuevos.
