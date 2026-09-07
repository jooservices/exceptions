# API Reference

This page maps the public exception API to the detailed guides. The package
keeps exception instances immutable and separates diagnostic context from
exception messages.

## Contracts and base classes

- [Root interface](./01-root-interface.md) — catch all JOOservices exceptions through `JOOExceptionInterface`.
- [Context-aware exceptions](./02-context-aware-exceptions.md) — add immutable context with `withContext()`.
- [Exception hierarchy](../00-architecture/02-exception-hierarchy.md) — choose runtime versus logic exception bases.

## Context and security

- [Context redaction](./03-context-redaction.md) — configure redactors and protect sensitive values.
- [Best practices](./06-best-practices.md) — safe messages, context, and logging boundaries.

## Logging and error codes

- [Loggable exceptions](./05-loggable-exceptions.md) — implement `errorCode()`, `logLevel()`, and `toLogArray()`.
- [Error code conventions](./08-error-code-conventions.md) — validate `{package}.{domain}.{reason}` codes.

## Framework integration

- [Laravel integration](./09-laravel-integration.md) — map redacted exception payloads at the HTTP boundary.

For installation and the smallest runnable examples, see [Quick Start](../01-getting-started/02-quick-start.md).
