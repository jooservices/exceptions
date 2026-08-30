# Implementation Plan — `jooservices/exceptions` Rebuild

Companion to `knowledge.md` (what the package was / lessons learned). This document specifies **how** to rebuild it, class by class, with PHP 8.5 syntax, PSR-*, SOLID/DRY/KISS/YAGNI, design patterns, and DX as hard constraints.

---

## 0. Delivery contract

| Item | Value |
| --- | --- |
| Package | `jooservices/exceptions` |
| PHP | `^8.5` |
| Runtime deps | **zero** |
| PSR-4 | `JOOservices\Exceptions\` → `src/`; tests `JOOservices\Exceptions\Tests\` → `tests/` |
| Style | Laravel Pint **`per`** preset (PER-CS 3.0) + PHPCS |
| Static analysis | PHPStan **level 10** + PHPMD |
| Tests | PHPUnit 13, **100% statement coverage enforced** |
| Docs | Executable snippets (`docs:verify`), architecture + user guide + examples |
| CI | Security → lint/analyse → tests → coverage upload; `composer audit --locked`; semantic-PR; secret scan |

---

## 1. File tree (target)

```
exceptions/
├── composer.json
├── pint.json                 # per preset
├── phpcs.xml.dist
├── phpstan.neon              # level 10
├── phpmd.xml.dist
├── phpunit.xml
├── captainhook.json          # Gitleaks pre-commit (checksum-pinned)
├── tools/
│   ├── ensure-coverage.php   # clover → 100% statements gate
│   └── ensure-docs-snippets.php  # README/doc snippets parse & run
├── src/
│   ├── Contracts/
│   │   ├── JOOExceptionInterface.php
│   │   ├── JOORuntimeExceptionInterface.php
│   │   ├── JOOLogicExceptionInterface.php
│   │   ├── ContextAwareExceptionInterface.php
│   │   ├── LoggableExceptionInterface.php
│   │   └── ContextRedactorInterface.php
│   ├── Base/
│   │   ├── AbstractJOORuntimeException.php
│   │   ├── AbstractJOOLogicException.php
│   │   ├── AbstractContextAwareException.php
│   │   └── AbstractContextAwareLogicException.php
│   ├── Concerns/
│   │   ├── ProvidesStructuredContext.php
│   │   └── HasExceptionContext.php
│   ├── Support/
│   │   ├── ExceptionContext.php
│   │   ├── DefaultContextRedactor.php
│   │   ├── CompositeContextRedactor.php
│   │   ├── ExceptionLogPayload.php
│   │   ├── LogLevel.php        # string-backed enum
│   │   └── ErrorCode.php       # validation vocabulary
│   └── Testing/
│       └── ExceptionContextAssertion.php
├── tests/
│   ├── TestCase.php
│   └── Unit/                  # one test class per src class, 100% statements
└── docs/                      # see §11
```

---

## 2. Contracts

### 2.1 Root marker
```php
interface JOOExceptionInterface extends Throwable {}
```
Empty on purpose: any existing exception can adopt it with zero call-site changes. Ecosystem catch-all:
```php
catch (JOOExceptionInterface $e) { /* all JOOservices errors */ }
```

### 2.2 Runtime / logic markers
```php
interface JOORuntimeExceptionInterface extends JOOExceptionInterface {}
interface JOOLogicExceptionInterface extends JOOExceptionInterface {}
```
Empty. Runtime = operational, possibly recoverable (timeout, network). Logic = programmer/domain-invariant errors, fix the caller, don't recover.

### 2.3 Behavioral contracts
```php
interface ContextAwareExceptionInterface extends JOOExceptionInterface
{
    /** @return array<string, mixed> MUST be redacted before return */
    public function getContext(): array;

    /** @param array<string, mixed> $context */
    public function withContext(array $context): static;
}

interface LoggableExceptionInterface extends JOOExceptionInterface
{
    public function errorCode(): string;
    public function logLevel(): string;
    /** @return array<string, mixed> */
    public function toLogArray(): array;
}

interface ContextRedactorInterface
{
    /** @param array<string, mixed> $context @return array<string, mixed> */
    public function redact(array $context): array;
}
```
- Interface Segregation: markers vs behavioral split — trait users can implement only `ContextAwareExceptionInterface` and still get defaults.
- `withContext()` returns `static` and **must not mutate** the original (exceptions-as-value-objects).
- Redactor contract: mask with a placeholder (`[REDACTED]`), never remove keys.

---

## 3. Bases

### 3.1 SPL bases (constructor contract)
```php
abstract class AbstractJOORuntimeException extends RuntimeException implements JOORuntimeExceptionInterface
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
```
`AbstractJOOLogicException` is identical but extends `LogicException` and implements `JOOLogicExceptionInterface`.

**Do not** change constructor shapes — SPL semantics and subclass ergonomics depend on it.

### 3.2 Context-aware bases (Template Method)
```php
abstract class AbstractContextAwareException extends AbstractJOORuntimeException implements
    ContextAwareExceptionInterface,
    LoggableExceptionInterface
{
    use ProvidesStructuredContext;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ExceptionContext|null $context = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context ?? new ExceptionContext();
    }

    // — Redactor registry (static, zero-DI) —
    private static ?ContextRedactorInterface $redactor = null;
    private static ?ContextRedactorInterface $defaultRedactor = null;

    public static function setRedactor(ContextRedactorInterface $redactor): void { self::$redactor = $redactor; }
    public static function removeRedactor(): void { self::$redactor = null; }
    public static function getRedactor(): ContextRedactorInterface
    {
        return self::$redactor ?? (self::$defaultRedactor ??= new DefaultContextRedactor());
    }
}
```
`AbstractContextAwareLogicException` mirrors it on the logic side (extends `AbstractJOOLogicException`), sharing the **same static redactor registry** via `AbstractContextAwareException::getRedactor()`.

---

## 4. Concerns

### 4.1 `ProvidesStructuredContext` (shared trait, `@internal`)
Single home for context + loggable behaviour — **the DRY fix** (both context bases use it, drift is impossible):

```php
trait ProvidesStructuredContext
{
    private ExceptionContext $context;
    private ?ContextRedactorInterface $instanceRedactor = null;

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->resolveRedactor()->redact($this->getRawContext());
    }

    /** @return array<string, mixed> unredacted — tests/internal only, never logs */
    public function getRawContext(): array
    {
        return $this->context->toArray();
    }

    /** @param array<string, mixed> $context */
    public function withContext(array $context): static
    {
        return $this->copyWithContext($this->context->merge($context));
    }

    /** Per-instance override; falls back to the global registry. Async-safe escape hatch. */
    public function withRedactor(ContextRedactorInterface $redactor): static
    {
        $copy = $this->copyWithContext($this->context);
        $copy->instanceRedactor = $redactor;

        return $copy;
    }

    private function resolveRedactor(): ContextRedactorInterface
    {
        return $this->instanceRedactor ?? AbstractContextAwareException::getRedactor();
    }

    public function errorCode(): string { return ErrorCode::GENERIC; }
    public function logLevel(): string { return LogLevel::ERROR->value; }

    /** @return array<string, mixed> */
    public function toLogArray(): array
    {
        return (new ExceptionLogPayload())->build(
            $this,
            $this->errorCode(),
            $this->logLevel(),
            $this->getContext(),
        );
    }

    abstract protected function copyWithContext(ExceptionContext $context): static;
}
```

**Template Method rules (never violate again):**
- `copyWithContext()` stays **abstract** — `new static(...)` is forbidden (it silently dropped subclass constructor invariants in v0.6).
- Even canonical constructors must reconstruct the concrete type explicitly with
  `new self(...)`; subclasses with extra constructor state must preserve that
  state in their own `copyWithContext()`.

### 4.2 `HasExceptionContext` (inheritance escape hatch)
For exceptions that cannot extend the bases (third-party parent, e.g. Guzzle). Contract for users: implement `ContextAwareExceptionInterface` (+ prefer `LoggableExceptionInterface`), call `initContext()` in the constructor, implement `copyWithContext()`.

```php
trait HasExceptionContext
{
    private ExceptionContext $exceptionContext;

    /** MUST be called in the using class constructor. Fail-fast if forgotten. */
    protected function initContext(array $initial = []): void
    {
        $this->exceptionContext = new ExceptionContext($initial);
    }

    public function getContext(): array
    {
        return AbstractContextAwareException::getRedactor()->redact($this->getRawContext());
    }

    public function getRawContext(): array
    {
        return $this->requireContext()->toArray();
    }

    public function withContext(array $context): static
    {
        return $this->copyWithContext($this->requireContext()->merge($context));
    }

    public function errorCode(): string { return ErrorCode::GENERIC; }
    public function logLevel(): string { return LogLevel::ERROR->value; }

    public function toLogArray(): array
    {
        return (new ExceptionLogPayload())->build(
            $this,
            $this->errorCode(),
            $this->logLevel(),
            $this->getContext(),
        );
    }

    abstract protected function copyWithContext(ExceptionContext $context): static;

    private function requireContext(): ExceptionContext
    {
        if (!isset($this->exceptionContext)) {
            throw new LogicException(sprintf(
                '%s must call initContext() in its constructor before using context methods.',
                static::class,
            ));
        }

        return $this->exceptionContext;
    }
}
```
Same `spl_object_id`-free policy: copy via consumer-owned `copyWithContext()`.

---

## 5. Support

### 5.1 `ExceptionContext` — immutable value object
```php
final class ExceptionContext
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /** @param array<array-key, mixed> $data */
    public function __construct(array $data = [])
    {
        $this->data = self::stringifyKeys($data);   // integer keys → strings (list-key corruption guard)
    }

    /** @param array<array-key, mixed> $additional */
    public function merge(array $additional): self
    {
        return new self(array_merge($this->data, self::stringifyKeys($additional)));  // later wins
    }

    /** @return array<string, mixed> */
    public function toArray(): array { return $this->data; }
    public function isEmpty(): bool { return $this->data === []; }
    public function has(string $key): bool { return array_key_exists($key, $this->data); }
    public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }

    /** @param array<array-key, mixed> $data @return array<string, mixed> */
    private static function stringifyKeys(array $data): array
    {
        $normalised = [];
        foreach ($data as $key => $value) {
            $normalised[(string) $key] = $value;
        }

        return $normalised;
    }
}
```

### 5.2 `DefaultContextRedactor` — single-pass, no `json_encode` probe
Improvement over v1.0.1: the archive double-walked context (JSON probe, then recursive walk). Drop the probe entirely:

- **Cycles are impossible** in the walk: arrays are recursed with a depth counter (`MAX_DEPTH = 64`), objects are never recursed (descriptor only) — so array self-references hit the depth cap and die safely.
- Single pass = half the work, no JSON error-mapping edge cases.

```php
final class DefaultContextRedactor implements ContextRedactorInterface
{
    public const REDACTED_VALUE = '[REDACTED]';
    private const MAX_DEPTH = 64;

    /** exact-match after strtolower(); aliases listed explicitly — not substring */
    private const SENSITIVE_KEYS = [
        'access-token', 'access_token', 'api-key', 'api_key', 'apikey',
        'auth_token', 'authorization', 'bearer', 'client_secret', 'cookie',
        'credential', 'credentials', 'csrf_token', 'jwt', 'password',
        'password_confirmation', 'passwd', 'private_key', 'pwd',
        'refresh-token', 'refresh_token', 'secret', 'session', 'session_id',
        'set-cookie', 'token', 'x-api-key',
    ];

    /** @param array<string, mixed> $context @return array<string, mixed> */
    public function redact(array $context): array
    {
        return $this->redactArray($context, 0);
    }

    /** @param array<array-key, mixed> $context @return array<string, mixed> */
    private function redactArray(array $context, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_context' => '[MAX_DEPTH]'];
        }

        $redacted = [];

        foreach ($context as $key => $value) {
            $normalisedKey = (string) $key;

            if (in_array(strtolower($normalisedKey), self::SENSITIVE_KEYS, true)) {
                $redacted[$normalisedKey] = self::REDACTED_VALUE;

                continue;
            }

            if (is_array($value)) {
                $redacted[$normalisedKey] = $this->redactArray($value, $depth + 1);

                continue;
            }

            $redacted[$normalisedKey] = match (true) {
                is_resource($value) => sprintf('[RESOURCE:%s]', get_resource_type($value)),
                is_object($value) => sprintf('[OBJECT:%s]', $value::class),
                is_float($value) && !is_finite($value) => '[NON_FINITE_FLOAT]',
                default => $value,
            };
        }

        return $redacted;
    }
}
```

### 5.3 `CompositeContextRedactor` — default + domain keys
```php
final class CompositeContextRedactor implements ContextRedactorInterface
{
    /** @var list<string> */
    private readonly array $extraKeys;

    /** @param list<string> $extraKeys case-insensitive exact keys */
    public function __construct(
        private readonly ContextRedactorInterface $inner = new DefaultContextRedactor(),
        array $extraKeys = [],
    ) {
        $normalised = [];
        foreach ($extraKeys as $key) {
            $normalised[] = strtolower($key);
        }
        $this->extraKeys = array_values(array_unique($normalised));
    }

    /** @param list<string> $extraKeys */
    public static function withExtraKeys(array $extraKeys, ?ContextRedactorInterface $inner = null): self
    {
        return new self($inner ?? new DefaultContextRedactor(), $extraKeys);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    public function redact(array $context): array
    {
        return $this->maskExtra($this->inner->redact($context));
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function maskExtra(array $context): array
    {
        $redacted = [];

        foreach ($context as $key => $value) {
            $name = (string) $key;

            if (in_array(strtolower($name), $this->extraKeys, true)) {
                $redacted[$name] = DefaultContextRedactor::REDACTED_VALUE;

                continue;
            }

            if (!is_array($value)) {
                $redacted[$name] = $value;

                continue;
            }

            /** @var array<string, mixed> $nested */
            $nested = $value;
            $redacted[$name] = $this->maskExtra($nested);
        }

        return $redacted;
    }
}
```

### 5.4 `ExceptionLogPayload` — the only log builder (DRY)
Versioned schema key (new in rebuild) + fail-fast validation of code/level:

```php
final class ExceptionLogPayload
{
    public const SCHEMA_VERSION = 'exceptions.log.v1';

    /**
     * @param array<string, mixed> $context already-redacted
     * @return array{
     *     message: string,
     *     class: class-string,
     *     error_code: string,
     *     log_level: string,
     *     log_schema: string,
     *     context: array<string, mixed>,
     *     previous: list<array{class: class-string, code: int}>
     * }
     */
    public function build(Throwable $exception, string $errorCode, string $logLevel, array $context): array
    {
        ErrorCode::assertValid($errorCode);
        if (LogLevel::tryFrom($logLevel) === null) {
            throw new LogicException(sprintf('Invalid log level "%s".', $logLevel));
        }

        return [
            'message' => $exception->getMessage(),
            'class' => $exception::class,
            'error_code' => $errorCode,
            'log_level' => $logLevel,
            'log_schema' => self::SCHEMA_VERSION,
            'context' => $context,
            'previous' => $this->previousSummary($exception),
        ];
    }

    /** @return list<array{class: class-string, code: int}> NO messages — leak guard */
    private function previousSummary(Throwable $exception): array
    {
        $summary = [];
        $previous = $exception->getPrevious();

        while ($previous !== null) {
            $summary[] = [
                'class' => $previous::class,
                'code' => (int) $previous->getCode(),
            ];
            $previous = $previous->getPrevious();
        }

        return $summary;
    }
}
```

### 5.5 `LogLevel` — string-backed enum (new in rebuild)
```php
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';
}
```
- Contract stays `logLevel(): string` (PSR-3 compatible — `->value` at the boundary).
- Autocomplete + `tryFrom()` validation kill the `'warn'` vs `'warning'` drift.
- `LogLevel::all(): list<string>` helper if consumers need the vocabulary.

### 5.6 `ErrorCode` — convention enforcement (new in rebuild)
```php
final class ErrorCode
{
    public const GENERIC = 'exception.generic';

    /** @var string stable, lowercase, dot-separated, ASCII */
    private const PATTERN = '/^[a-z0-9]+(?:\.[a-z0-9]+)+$/';

    public static function isValid(string $code): bool
    {
        return $code === self::GENERIC || preg_match(self::PATTERN, $code) === 1;
    }

    public static function assertValid(string $code): void
    {
        if (!self::isValid($code)) {
            throw new LogicException(sprintf('Invalid error code "%s". Use {package}.{domain}.{reason}.', $code));
        }
    }
}
```
Enforced at `toLogArray()` time — malformed codes never reach log pipelines.

---

## 6. Testing helpers (`src/Testing/ExceptionContextAssertion.php`)

```php
final class ExceptionContextAssertion
{
    /** @param array<string, mixed> $expected */
    public static function assertHasContext(ContextAwareExceptionInterface $exception, array $expected): void;
    public static function assertErrorCode(LoggableExceptionInterface $exception, string $expected): void;
    public static function assertLogLevel(LoggableExceptionInterface $exception, string $expected): void;
    public static function assertContextKeyRedacted(
        ContextAwareExceptionInterface $exception,
        string $key,
        string $placeholder = DefaultContextRedactor::REDACTED_VALUE,
    ): void;

    // — new in rebuild —
    public static function assertLogPayloadSchema(LoggableExceptionInterface $exception): void;
    // message:string, class:string, error_code:string, log_level:string,
    // log_schema:'exceptions.log.v1', context:array, previous:list

    public static function assertNoValueInContext(ContextAwareExceptionInterface $exception, mixed $value): void;
    // recursive scan: no entry anywhere equals $value (secrets that survived redaction)
}
```
All throw `AssertionError` with the expected-vs-actual message.

---

## 7. Consumer contract (how leaf exceptions look)

```php
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ErrorCode;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;

final class HydrationException extends AbstractContextAwareException
{
    public static function forField(string $path, string $expectedType): self
    {
        return (new self("Hydration failed for field '{$path}'"))
            ->withContext(['path' => $path, 'expectedType' => $expectedType]);
    }

    public function errorCode(): string { return ErrorCode::dtoHydrationFailed(); /* 'dto.hydration.failed' */ }
    public function logLevel(): string { return LogLevel::ERROR->value; }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}
```

### Trait path (third-party parent, e.g. dto's `DtoException` with extra ctor state)
```php
class DtoException extends AbstractJOORuntimeException implements ContextAwareExceptionInterface
{
    use HasExceptionContext;

    public function __construct(
        string $message,
        public readonly string $path = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->initContext(['path' => $path]);
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        $copy = new self($this->getMessage(), $this->getPath(), $this->getCode(), $this->getPrevious());
        $copy->initContext($context->toArray());

        return $copy;
    }
}
```

### Bootstrap (global redactor, once per process)
```php
AbstractContextAwareException::setRedactor(
    CompositeContextRedactor::withExtraKeys(['national_id', 'ssn']),
);
```

---

## 8. composer.json skeleton

```jsonc
{
    "name": "jooservices/exceptions",
    "type": "library",
    "license": "MIT",
    "require": { "php": "^8.5" },
    "require-dev": {
        "captainhook/captainhook": "^5.24",
        "laravel/pint": "^1.18",
        "phpmd/phpmd": "^2.15",
        "phpstan/phpstan": "^2.0",
        "phpunit/phpunit": "^13.0",
        "squizlabs/php_codesniffer": "^3.13.6 || ^4.0.0"
    },
    "autoload": { "psr-4": { "JOOservices\\Exceptions\\": "src/" } },
    "autoload-dev": { "psr-4": { "JOOservices\\Exceptions\\Tests\\": "tests/" } },
    "scripts": {
        "lint:pint": "vendor/bin/pint --test",
        "lint:pint:fix": "vendor/bin/pint",
        "lint:phpcs": "vendor/bin/phpcs",
        "lint:phpstan": "vendor/bin/phpstan analyse",
        "lint:phpmd": "vendor/bin/phpmd src text phpmd.xml.dist",
        "lint:fast": ["@lint:pint", "@lint:phpcs"],
        "lint": ["@lint:pint", "@lint:phpcs", "@lint:phpstan"],
        "lint:all": ["@lint:pint", "@lint:phpcs", "@lint:phpstan", "@lint:phpmd"],
        "lint:fix": ["@lint:pint:fix"],
        "test": "vendor/bin/phpunit --no-coverage",
        "test:coverage": "vendor/bin/phpunit --coverage-text --coverage-clover build/coverage.xml && php tools/ensure-coverage.php build/coverage.xml 100",
        "docs:verify": "php tools/ensure-docs-snippets.php",
        "check": ["@lint:all", "@docs:verify", "@test"],
        "ci": ["@lint:all", "@docs:verify", "@test:coverage"]
    },
    "config": { "sort-packages": true, "optimize-autoloader": true }
}
```

---

## 9. Verification tooling

### `tools/ensure-coverage.php`
Parse `build/coverage.xml` (clover), compute statement coverage, exit non-zero if below the required percentage (100). Used in CI and locally via `composer test:coverage`.

### `tools/ensure-docs-snippets.php`
- Extract every `<?php` code block from `README.md` + `docs/**` marked as executable.
- Run each snippet through `php -l` (parse) and, where marked, execute with `use` imports stubbed — the guard that would have caught the broken multi-array `array_map` sample in v0.6.
- Coverage of at least the redactor/context samples is mandatory.

---

## 10. Test matrix (unit, per class, 100% statements)

| Class | Must cover |
| --- | --- |
| `AbstractJOORuntimeException` / `AbstractJOOLogicException` | SPL parent, ctor passthrough, markers |
| `AbstractContextAwareException` / `...LogicException` | ctor default context, redactor registry (set/remove/get, lazy default cache) |
| `ProvidesStructuredContext` | getContext redaction, getRawContext, withContext immutability, withRedactor override & fallback, errorCode/logLevel defaults, toLogArray shape |
| `HasExceptionContext` | initContext, **fail-fast LogicException when init skipped**, withContext, copyWithContext, toLogArray |
| `ExceptionContext` | stringified keys, merge later-wins, has/get/isEmpty, immutability |
| `DefaultContextRedactor` | every sensitive alias (table test), recursion depth, MAX_DEPTH guard, resource/object/non-finite descriptors, sibling preservation, key casing |
| `CompositeContextRedactor` | extra keys recursive, case-insensitive, dedupe, inner delegation, defaults |
| `ExceptionLogPayload` | full shape incl. `log_schema`, previous chain class+code only (no messages), invalid code/level → `LogicException` |
| `LogLevel` | all values, `tryFrom` round-trip |
| `ErrorCode` | valid/invalid patterns, GENERIC passthrough |
| `ExceptionContextAssertion` | pass + fail paths for all 6 helpers |
| Fixtures | subclass with extra ctor state (invariant preservation via copyWithContext), trait-user with third-party parent |

**Security tests (non-negotiable):**
- `toLogArray()['previous']` contains **no** messages.
- Every key in the sensitive table leaks nothing through nested arrays or mixed-case.
- A self-referential array terminates (`[MAX_DEPTH]`), single-pass walker.

---

## 11. Docs map (rebuild verbatim structure)

```
docs/README.md
docs/00-architecture/01-project-overview.md
docs/00-architecture/02-exception-hierarchy.md      # include Loggable + ContextAware nodes
docs/00-architecture/03-dependency-rules.md         # no Illuminate/Symfony/HTTP; exceptions ← everyone
docs/00-architecture/04-modules-and-domains.md
docs/00-architecture/05-data-flow.md
docs/01-getting-started/01-installation.md
docs/01-getting-started/02-quick-start.md
docs/01-getting-started/03-basic-concepts.md
docs/02-user-guide/01-root-interface.md
docs/02-user-guide/02-context-aware-exceptions.md
docs/02-user-guide/03-context-redaction.md          # recursive samples only
docs/02-user-guide/04-migration-guide.md            # copyWithContext BC callout
docs/02-user-guide/05-loggable-exceptions.md        # log_schema, payload shape, PSR-3 levels
docs/02-user-guide/06-best-practices.md             # never put secrets in messages; request_id/trace_id
docs/02-user-guide/07-troubleshooting.md
docs/02-user-guide/08-error-code-conventions.md     # {package}.{domain}.{reason}
docs/02-user-guide/09-laravel-integration.md        # handler mapping + provider bootstrap cookbook
docs/03-examples/01-basic-examples.md
docs/03-examples/02-laravel-handler.md              # real consumer code, run by docs:verify
docs/04-development/...                              # setup, testing, ci-cd, release, contributing
docs/05-maintenance/01-risks-and-gaps.md            # global redactor async, exact-match policy, messages, getRawContext
```

---

## 12. CI/CD (GitHub Actions)

| Job | Steps |
| --- | --- |
| **Security** | `composer audit --locked`; secret scan (Gitleaks, checksum-pinned); Semgrep; dependency-review on PRs |
| **Quality** | Pint `--test`, PHPCS, PHPStan L10, PHPMD, `docs:verify` |
| **Tests** | PHPUnit (matrix `coverage: none` + xdebug) → `test:coverage` 100% gate → upload coverage |
| **PR** | semantic-PR titles, PR labeler |
| **Release** | on `v*.*.*` tag: `composer validate` + `composer ci`, GitHub release, Packagist publish (skip-with-warning when creds unset) |
| **Scorecard** | on `master` push + weekly |

Order matters: security → quality → tests → publish.

---

## 13. Git/branch model

Per workspace AGENTS.md: `master` (production, tags) + `develop` (integration). All work in `feature/*` PRs → `develop`; releases `release/<version>` → `master`. Git identity `Viet Vu <jooservices@gmail.com>`; Conventional Commits; never `main`.

---

## 14. Milestones (implementation order)

| # | Milestone | Exit criteria |
| --- | --- | --- |
| M0 | Scaffold: composer.json, tooling configs, hooks, CI skeleton, TestCase | `composer check` green on empty lib |
| M1 | Contracts (6 files) + docs skeleton | interfaces compile, marker tests |
| M2 | Bases + concerns (4 bases, 2 traits) | hierarchy tests, copyWithContext invariant test |
| M3 | Support (ExceptionContext, redactors, payload, LogLevel, ErrorCode) | 100% coverage, security table tests |
| M4 | Testing helpers | helper pass/fail tests |
| M5 | Docs: user guide, examples, Laravel cookbook, risks | `docs:verify` green |
| M6 | Hardening: audit pass (re-run the archive audit checklist), CHANGELOG | zero open findings |
| M7 | Release `v1.0.0`: branch `release/1.0.0` → `master`, tag, Packagist | published, badges green |

---

## 15. Do not do (binding)

- No Laravel providers / HTTP status mapping / Illuminate or Symfony imports in `src/`.
- No `psr/log` dependency — `LogLevel` enum is the vocabulary.
- No `new static(...)` cloning anywhere.
- No reflection-based copying, no `json_encode` probe in the redactor.
- No lowering of the 100% statement gate.
- No previous-exception messages in `toLogArray()`.
- No global state beyond the redactor registry (plus the per-instance override).
- No `main` branch, no AI identity on commits, no docs claims CI doesn't run.

---

## 16. Future backlog (YAGNI — only if demand)

- `#[Deprecated]`-driven removal path for `getRawContext()` (tests currently need it).
- Aggregate/`MultipleExceptions` support (only if a domain needs grouped failures).
- `ExceptionContext::dot()` path accessor (only if consumers query nested context).
- Global redactor → per-correlation-context registry for async multi-tenant (per-instance override is the interim answer).
