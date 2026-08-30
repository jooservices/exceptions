# Knowledge Base — JOOservices Exceptions Rebuild

Source of truth: `../archives/JOOservices.2/exceptions` (final release `v1.0.1`, audit `audit_260808.md`, CHANGELOG).
Purpose: capture everything learned so the package can be **rebuilt from scratch** with
SOLID, DRY, KISS, YAGNI, design patterns, PHP 8.5 syntax, PSR-*, and DX as hard constraints.
**No implementation here — this is the spec for the rebuild.**

---

## 1. What the package was

`jooservices/exceptions` — a **framework-agnostic PHP 8.5+ library, zero runtime dependencies**,
that provides shared exception contracts, base classes, and structured diagnostic context for
the whole JOOservices ecosystem (`dto`, `client`, `useragent`, ...).

Public API surface (final state, `src/` = 7 classes + 6 contracts + 2 traits + 1 test helper):

| Layer | Files |
| --- | --- |
| Contracts | `JOOExceptionInterface`, `JOORuntimeExceptionInterface`, `JOOLogicExceptionInterface`, `ContextAwareExceptionInterface`, `LoggableExceptionInterface`, `ContextRedactorInterface` |
| Base | `AbstractJOORuntimeException` (extends `RuntimeException`), `AbstractJOOLogicException` (extends `LogicException`), `AbstractContextAwareException`, `AbstractContextAwareLogicException` |
| Concerns | `HasExceptionContext` (trait, inheritance escape hatch), `ProvidesStructuredContext` (shared trait) |
| Support | `ExceptionContext` (immutable VO), `DefaultContextRedactor`, `CompositeContextRedactor`, `ExceptionLogPayload`, `LogLevel` |
| Testing | `ExceptionContextAssertion` (consumer test helpers) |

### The final hierarchy

```
Throwable
  JOOExceptionInterface (marker, extends Throwable)
    ├── JOORuntimeExceptionInterface (marker)
    │     └── AbstractJOORuntimeException extends RuntimeException
    │           └── AbstractContextAwareException   (+ ContextAware + Loggable)
    └── JOOLogicExceptionInterface (marker)
          └── AbstractJOOLogicException extends LogicException
                └── AbstractContextAwareLogicException (+ ContextAware + Loggable)
  ContextAwareExceptionInterface (extends JOOExceptionInterface, behavioral)
  LoggableExceptionInterface (extends JOOExceptionInterface, behavioral)
```

Key design: **marker interfaces are empty** (adoption without breaking call sites);
behavioral contracts (`getContext()`, `withContext()`, `errorCode()`, `logLevel()`, `toLogArray()`)
are separate interfaces — Interface Segregation.

---

## 2. Evolution history (what went wrong, in order)

1. **`v0.5.0` (2026-07-18):** Marker interfaces, runtime/logic bases, context-aware base,
   `ExceptionContext`, `HasExceptionContext` trait, 100% statement coverage gate, CI.
2. **`v0.6.0` (2026-08-07):** `errorCode()`, `logLevel()`, `toLogArray()`,
   `LoggableExceptionInterface`, test helpers, default redaction; trait requires explicit
   `copyWithContext()` (replacing reflection-based cloning).
3. **`v1.0.0` (2026-08-08):** The audit (`audit_260808.md`) landed as one release:
   `getRawContext()`, `AbstractContextAwareLogicException`, `CompositeContextRedactor`,
   `LogLevel`, `ExceptionLogPayload`, `ProvidesStructuredContext`, fail-fast trait init,
   expanded sensitive keys, previous-summary without messages, doc/CI parity.
4. **`v1.0.1` (2026-08-19):** Docs/CI accuracy fixes (dropped Fortify/Codacy claims, Packagist
   publish made optional, coverage jobs cleanup).

### Historical bugs worth never repeating

| Bug | Why it happened | Guard for rebuild |
| --- | --- | --- |
| `withContext()` used `new static(...)` → **dropped subclass constructor invariants** (LSP violation) | Convenience over correctness | Abstract `copyWithContext()` template-method hook (see §7) |
| `toLogArray()` included previous-exception **messages** → secrets bypassed redaction in logs | Convenience over security | Previous summary = class + code only |
| Deep non-circular context treated like recursion → whole context replaced | json guard only for recursion/depth errors | Independent depth cap (`MAX_DEPTH = 64`) + json probe |
| Broken custom-redactor doc sample: multi-array `array_map` **renumbers string keys** | Docs not executed | `composer docs:verify` guard that runs README/doc snippets |
| Sensitive keys were exact-match only → `csrf_token`, `auth_token`, `credentials`, `api-key`, `session_id`, `password_confirmation` **leaked** | Under-specified default | Expanded alias list + `CompositeContextRedactor::withExtraKeys()` |
| Trait used without `initContext()` silently returned empty context | Soft-fail convenience | Fail-fast `LogicException` |
| `array_map`/`array_merge` with integer keys silently corrupts context shape | Loose typing | `ExceptionContext` stringifies all keys at construction |
| Reflection-based context copying in the trait | Magic | Consumer-owned `copyWithContext()` |

---

## 3. SOLID as applied (keep all of this)

- **S — Single Responsibility:** contracts / bases / context VO / redactor strategy / log payload builder / test helper are cleanly separated. No class does two jobs.
- **O — Open/Closed:** redaction is a pluggable strategy (`ContextRedactorInterface`); log payload shape is the only weak spot — expose an extension hook only if demand exists (YAGNI).
- **L — Liskov:** `copyWithContext()` is the key — subclasses with extra constructor state preserve invariants; `new static(...)` is forbidden.
- **I — Interface Segregation:** empty markers vs behavioral contracts; `ContextAware` and `Loggable` are separate so trait users can implement only what they need.
- **D — Dependency Inversion:** depends on `ContextRedactorInterface`; `DefaultContextRedactor` is a fallback, never hard-wired.

---

## 4. Design patterns used (all appropriate — keep)

| Pattern | Where | Verdict |
| --- | --- | --- |
| Marker Interface | `JOOExceptionInterface` + runtime/logic markers | Correct — zero-cost ecosystem catching |
| Strategy | `ContextRedactorInterface` + default/composite implementations | Correct |
| Template Method | abstract `copyWithContext()` hook | Correct — the LSP saver |
| Immutable Value Object | `ExceptionContext` (final, readonly, merge returns new) | Correct |
| Trait Composition | `HasExceptionContext` as inheritance escape hatch | Correct, justified, documented |
| Static Service Locator | `setRedactor()` / `getRedactor()` global | Pragmatic for zero-DI library; **document async (Swoole/RoadRunner) limits** |
| Static Factory | `CompositeContextRedactor::withExtraKeys()`, `LogLevel::all()` | Small DX wins, keep |

**Do not add:** interfaces-per-class bloat, CQRS/event layers around exceptions,
Laravel providers/HTTP helpers/Illuminate types inside the package.

---

## 5. PHP 8.5 / PSR requirements (mandatory)

- `declare(strict_types=1)` in every `src/` file.
- PSR-4: `JOOservices\Exceptions\` → `src/`; tests `JOOservices\Exceptions\Tests\` → `tests/`.
- Coding style: Laravel Pint **`per` preset** (PER-CS 3.0). Also PHPCS + PHPStan **level 10** + PHPMD.
- `composer.json`: `"php": "^8.5"`, **zero runtime deps**, dev deps only
  (pint, phpstan, phpunit ^13, phpmd, php_codesniffer, captainhook).
- PHP 8.5 features used: `readonly` properties, constructor property promotion,
  `match`, named arguments, `static` return types, `null` union (`ExceptionContext|null`),
  first-class enums? — **no**: `LogLevel` stayed a final class of `string` constants
  (deliberate, to keep `logLevel(): string` free-form contract; document that choice).
- 100% statement coverage **enforced** by `tools/ensure-coverage.php` after
  `phpunit --coverage-clover`; CI runs `composer ci` (lint:all → docs:verify → test:coverage).

---

## 6. Contract inventory (the exact public API to rebuild)

### Root marker
```php
interface JOOExceptionInterface extends Throwable {}   // catch-all: catch (JOOExceptionInterface $e)
```

### Behavioral contracts
- `ContextAwareExceptionInterface extends JOOExceptionInterface`:
  `getContext(): array<string, mixed>` (MUST be redacted before return),
  `withContext(array $context): static` (immutable — returns new instance).
- `LoggableExceptionInterface extends JOOExceptionInterface`:
  `errorCode(): string`, `logLevel(): string`, `toLogArray(): array<string, mixed>`.
- `ContextRedactorInterface`: `redact(array $context): array<string, mixed>`
  (sensitive values replaced with `[REDACTED]`, never removed).

### Defaults (contracts with sensible fallbacks)
- `errorCode()` default: `'exception.generic'`.
- `logLevel()` default: `LogLevel::ERROR` (`'error'`).
- Redactor default: `DefaultContextRedactor`.

### Base classes (constructor contract)
`__construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?ExceptionContext $context = null)`
— context-aware bases take an optional pre-built context; plain bases take the first three.
Logic bases extend `LogicException`, runtime bases extend `RuntimeException` — **SPL semantics preserved**.

### Escape hatch (trait)
`HasExceptionContext`: for exceptions that cannot extend the bases (third-party parent).
Requirements: implement `ContextAwareExceptionInterface` (+ preferably `LoggableExceptionInterface`),
call `initContext()` in constructor (**fail-fast if forgotten**), implement `copyWithContext()`.

---

## 7. Key mechanics that must survive the rebuild

### Immutable context chaining
1. `withContext([...])` → `copyWithContext($this->context->merge($additional))` → **new instance**.
2. `copyWithContext()` is **abstract**; every concrete subclass reconstructs its own
   constructor invariants and passes the merged `ExceptionContext`. Never `new static(...)`.
3. `ExceptionContext` is `final`, holds `readonly array<string, mixed> $data`,
   stringifies keys at construction, `merge()` uses `array_merge` semantics
   (later keys win), provides `toArray()`, `isEmpty()`, `has()`, `get()`.

### Redaction pipeline
`getContext()` → `AbstractContextAwareException::getRedactor()->redact($this->getRawContext())`.
- Global registry: `setRedactor()` / `removeRedactor()` / cached `getRedactor()` (lazy `??=` default).
- `DefaultContextRedactor`:
  - case-insensitive **exact-match** key list (`SENSITIVE_KEYS`) — see §9;
  - recursive walk with independent `MAX_DEPTH = 64` cap;
  - `json_encode(JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)` probe first:
    recursion (`JSON_ERROR_RECURSION`) and depth (`JSON_ERROR_DEPTH`) → `['_context' => '[UNSERIALIZABLE_CONTEXT]']`;
  - safe descriptors preserve siblings: resources → `[RESOURCE:type]`,
    objects → `[OBJECT:Class]`, non-finite floats → `[NON_FINITE_FLOAT]` (never dropped);
  - values are **masked, not removed** (log consumers see the field existed).
- `CompositeContextRedactor`: inner redactor (default `DefaultContextRedactor`) + extra
  exact-match keys, recursively, lowercased+deduped at construction.

### Log payload shape (stable, documented — consumers rely on these keys)
```php
[
  'message'    => string,      // raw message — NEVER redacted, hard rule: no secrets in messages
  'class'      => class-string,
  'error_code' => string,      // {package}.{domain}.{reason}
  'log_level'  => string,      // PSR-3 vocabulary only (LogLevel)
  'context'    => array<string, mixed>,  // already redacted
  'previous'   => list<array{class: class-string, code: int}>,  // NO messages
]
```
`ExceptionLogPayload` is the single shared builder (`@internal`), consumed by both
`ProvidesStructuredContext` and `HasExceptionContext` — **this fixed the old DRY gap**
(dual-edit drift of `toLogArray`/`previousSummary`). Keep it as the one and only builder.

### Error-code conventions
`{package}.{domain}.{reason}` — stable, lowercase, dot-separated, ASCII, no HTTP status
encoded (map status at the HTTP boundary), additive over time. Default: `exception.generic`.

---

## 8. DX rules learned (hard-won)

1. **Do not break `array_map`/`array_merge` key shape** — integer keys silently renumber;
   `ExceptionContext` stringifies keys at the boundary. `composer docs:verify` exists because
   a broken multi-array `array_map` sample shipped in README + docblock.
2. **Every sensitive key must be masked by default** — or clearly documented as exact-match
   with a recommended app list. Leaked `csrf_token`/`auth_token` was a High-severity audit finding.
3. **Fail fast, never soft-fail** — missing `initContext()` throws `LogicException`.
4. **`getRawContext()` is a footgun** — unredacted, exists for tests/internal only;
   document loudly: logs and API responses must use `getContext()`.
5. **Previous-exception chains must not leak messages** into logs.
6. **Messages must never carry secrets** — documented as a hard consumer rule; runtime
   guard is out of scope (KISS) but the docs must state it.
7. **Static global redactor is process-wide mutable state** — fine for PHP-FPM;
   document isolation needs for Swoole/RoadRunner/multi-tenant.
8. **Subclass ergonomics:** prefer static factories on exceptions
   (`HydrationException::forField($path, $expectedType)`) over bespoke constructor signatures;
   keep constructors canonical `(message, code, previous, ?context)`.
9. **Test helpers in `Testing/` namespace** for consumers:
   `assertHasContext`, `assertErrorCode`, `assertLogLevel`, `assertContextKeyRedacted`.
10. **Docs must be executable** — snippet verification in CI (docs:verify) caught the
    worst DX bug in the package's history.

---

## 9. Default sensitive key list (final, rebuild verbatim)

Exact match after `strtolower()`, masked with `[REDACTED]`:

```
access-token, access_token, api-key, api_key, apikey, auth_token, authorization,
bearer, client_secret, cookie, credential, credentials, csrf_token, jwt, password,
password_confirmation, passwd, private_key, pwd, refresh-token, refresh_token,
secret, session, session_id, set-cookie, token, x-api-key
```

Policy: **exact-match, not substring** — document this; compound/domain-specific keys go
through `CompositeContextRedactor::withExtraKeys([...])`.

---

## 10. What to drop / avoid in the rebuild (YAGNI + lessons)

- **No** Laravel providers, HTTP status mapping, Illuminate/Symfony imports (framework-agnostic is the point).
- **No** `getRawContextForTesting()` extra variants — keep redacted-only API + raw for tests.
- **No** JSON `SENSITIVE_KEYS` external file — a rejected design; hardcoded const list wins.
- **No** `psr/log` dependency — `LogLevel` string constants suffice.
- **No** reflection-based cloning anywhere.
- **No** global state beyond the single redactor registry.
- **No** docs claims that CI does not run (AGENTS.md once claimed an optional dependency-review job that didn't exist).
- Keep `SECURITY.md` support versions in sync with reality.
- Keep AGENTS.md package-scoped: "Laravel layering applies to consuming applications, not this package".

## 11. Verification tooling to recreate

- `tools/ensure-coverage.php` — clover XML → 100% statements gate.
- `tools/ensure-docs-snippets.php` — README/doc code snippets must parse/run (`composer docs:verify`).
- Composer scripts: `lint` (pint, phpcs, phpstan), `lint:all` (+phpmd), `test` (`--no-coverage`),
  `test:coverage` (+ gate), `check`, `ci`.
- Git hooks via captainhook: Gitleaks secret scan pre-commit (checksum-pinned), identity checks.
- CI order: security → lint/analyse → tests → coverage upload. `composer audit --locked`.

---

## 12. Audit's "do not do" list (still binding)

- Do not add Laravel providers, HTTP status helpers, or Illuminate types into this package.
- Do not lower the 100% coverage gate.
- Do not invent CQRS/event layers around exceptions.
- Do not invent abstraction for its own sake — the package's simplicity is its strength.

---

## 13. Rebuild checklist (spec-level)

- [ ] Marker contracts: root + runtime + logic (empty).
- [ ] Behavioral contracts: context-aware, loggable, redactor.
- [ ] SPL bases: runtime (`RuntimeException`) + logic (`LogicException`).
- [ ] Context bases (runtime + logic) with `copyWithContext()` template method.
- [ ] `HasExceptionContext` trait with fail-fast `initContext()`.
- [ ] `ExceptionContext` immutable VO (string keys, merge semantics).
- [ ] `DefaultContextRedactor` + `CompositeContextRedactor` (SENSITIVE_KEYS verbatim from §9).
- [ ] `ExceptionLogPayload` single builder + `LogLevel` vocabulary.
- [ ] `Testing/ExceptionContextAssertion` helpers.
- [ ] 100% coverage gate + docs-verify tooling + lint stack (Pint per, PHPCS, PHPStan L10, PHPMD).
- [ ] Docs: architecture, user guide, error-code conventions, risks, migration, examples.
- [ ] Zero runtime dependencies; `php ^8.5`; PSR-4.