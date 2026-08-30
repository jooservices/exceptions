# Dependency Rules

Hard boundaries for this package:

| Rule | Detail |
| --- | --- |
| No Illuminate / Symfony imports in `src/` | The package is framework-agnostic by design |
| No HTTP semantics | No status codes, no response generators |
| Zero runtime dependencies | `composer.json` requires only `php ^8.5` |
| Dev dependencies only for quality | Pint, PHPCS, PHPStan, PHPMD, PHPUnit, CaptainHook |
| No `psr/log` | `LogLevel` enum carries the PSR-3 vocabulary as string values |

## Ecosystem direction

```text
exceptions  ←  dto, client, useragent, ... (everything)
exceptions  ←  nothing
```

`exceptions` is the foundation layer. It must never depend on domain packages —
that would create cycles and break the single catch clause. Domain packages
throw leaf exceptions extending these bases.

## Runtime environment

Development, tests, and CI run in Docker (`php:8.5-cli-bookworm` base image).
No host-level PHP, Composer, or extension installs. See
[Setup](../04-development/01-setup.md).
