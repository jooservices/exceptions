# Basic Concepts

## Markers vs capabilities

- **Marker interfaces** (`JOOExceptionInterface`, `JOORuntimeExceptionInterface`, `JOOLogicExceptionInterface`) carry no methods — adoption never breaks call sites.
- **Capability interfaces** (`ContextAwareExceptionInterface`, `LoggableExceptionInterface`) declare behaviour; trait users can implement only what they need.

## Runtime vs logic

- **Runtime** (`AbstractJOORuntimeException`): operational conditions — timeouts, network issues, resource limits. Callers may recover.
- **Logic** (`AbstractJOOLogicException`): programmer errors — invalid arguments, invariant violations. Fix the calling code.

Choose by SPL semantics, not by where the exception is thrown.

## Context as value object

`ExceptionContext` is final and immutable. `withContext()` never mutates — it
returns a new exception instance. Merge semantics: later keys win. Keys are
never renumbered.

## Redaction

`getContext()` is **always** redacted. `getRawContext()` exists for tests and
internal diagnostics only — never log it, never return it from an API.

## Static factories

Create exceptions through named factories, not ad-hoc constructor calls.
Diagnostics go into context; messages stay human-readable and secret-free.
