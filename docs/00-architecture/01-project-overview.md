# Project Overview

`jooservices/exceptions` is a **framework-agnostic PHP 8.5+ library with zero
runtime dependencies**. It provides the shared exception vocabulary for every
JOOservices package: contracts to catch by, base classes to extend, and
structured, secret-safe diagnostic context.

## Why it exists

Every ecosystem package needs the same three things from its errors:

1. **Catchability** — applications want one catch clause for all JOOservices errors.
2. **Structure** — logs need stable machine-readable fields, not ad-hoc arrays.
3. **Safety** — diagnostics must never leak secrets into logs or responses.

Reimplementing those per package causes drift (different interfaces, different
log shapes, different redaction). This package is the single source of truth.

## Design constraints

| Constraint | Rule |
| --- | --- |
| No framework imports | Illuminate / Symfony types are forbidden in `src/` |
| No HTTP semantics | Status codes and response shapes belong to the app layer |
| Zero runtime dependencies | `php ^8.5` only |
| SPL preserved | `RuntimeException` / `LogicException` semantics stay intact |
| 100% statement coverage | Enforced in CI |
| PHP 8.5 baseline | strict_types, readonly, enums, final classes |

## Package size

19 production files: 6 contracts, 4 abstract bases, 2 traits, 6 support
classes, 1 consumer test helper.
