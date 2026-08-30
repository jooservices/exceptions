# Changelog

All notable changes to this package will be documented in this file. Format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

> [!WARNING]
> **This changelog starts at `v4.0.0`.** This is a fresh, incompatible rebuild;
> earlier package lines are archived and are not ancestors of this repository.

## [Unreleased]

## [4.0.0] - 2026-08-29

### Added

- Root marker `JOOExceptionInterface` + runtime/logic markers for ecosystem-wide catching.
- SPL-preserving bases: `AbstractJOORuntimeException`, `AbstractJOOLogicException`.
- Context-aware bases: `AbstractContextAwareException`, `AbstractContextAwareLogicException`
  with the abstract `copyWithContext()` template method (immutable context chaining,
  subclass invariants preserved).
- `HasExceptionContext` trait for third-party inheritance trees (fail-fast `initContext()`).
- `ExceptionContext` immutable value object (merge semantics, no key renumbering).
- `DefaultContextRedactor` — single-pass recursive walker with depth cap, exact-match
  sensitive key list, resource/object/non-finite-float descriptors.
- `CompositeContextRedactor::withExtraKeys()` for domain-specific secrets.
- `ExceptionLogPayload` — single versioned log builder (`exceptions.log.v1` schema key,
  previous chain as class + code only).
- `LogLevel` string-backed enum (PSR-3 vocabulary, no `psr/log` dependency).
- `ErrorCode` convention validation (`{package}.{domain}.{reason}`), enforced at `toLogArray()` time.
- `ExceptionContextAssertion` consumer test helpers (context, code, level, redaction,
  payload schema, forbidden values).
- Per-instance `withRedactor()` override for async worker scopes.
- Executable docs guard (`docs:verify`) and 100% statement coverage gate.
- Docker-first toolchain: `php:8.5-cli-bookworm` image, Makefile, CaptainHook git hooks.
- GitHub Actions quality/security and tag-release workflows.

[Unreleased]: https://github.com/jooservices/exceptions/compare/v4.0.0...HEAD
[4.0.0]: https://github.com/jooservices/exceptions/releases/tag/v4.0.0
