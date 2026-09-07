# jooservices/exceptions

[![CI](https://github.com/jooservices/exceptions/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/exceptions/actions/workflows/ci.yml)
[![Coverage (develop)](https://codecov.io/gh/jooservices/exceptions/branch/develop/graph/badge.svg?token=1YIRTZE5SH)](https://codecov.io/gh/jooservices/exceptions/branch/develop)
[![Quality Gate (master)](https://sonarcloud.io/api/project_badges/measure?project=jooservices_exceptions&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=jooservices_exceptions)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/exceptions/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/exceptions)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![GitHub Release](https://img.shields.io/github/v/release/jooservices/exceptions?display_name=tag)](https://github.com/jooservices/exceptions/releases)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/exceptions)](https://packagist.org/packages/jooservices/exceptions)
[![Total Downloads](https://img.shields.io/packagist/dt/jooservices/exceptions)](https://packagist.org/packages/jooservices/exceptions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

The **JOOservices Exceptions Library** is a PHP 8.5+ foundational library providing shared exception contracts, context-aware base classes, and secret redaction for the JOOservices package ecosystem.

> [!WARNING]
> **`v4.0.0` is a complete ground-up rebuild and is not backward compatible
> with earlier package lines.** Rewrite integrations before upgrading; there are
> no legacy shims or deprecation bridges. See the [changelog](./CHANGELOG.md).

## Upgrade highlights

- Fresh exception contracts and context-aware base classes.
- Zero runtime dependencies and framework-agnostic implementation.
- Redacted diagnostic context remains separate from exception messages.

## Requirements

- PHP `^8.5`
- Docker (recommended — local tooling uses `php:8.5-cli-bookworm`)

## Installation

```bash
composer require jooservices/exceptions
```

## Features

- **Ecosystem-wide catching**: root marker `JOOExceptionInterface` for one catch clause across all packages.
- **SPL semantics**: `AbstractJOORuntimeException` (operational) and `AbstractJOOLogicException` (programmer errors).
- **Structured context**: immutable, redacted diagnostic context on `AbstractContextAwareException`, `AbstractContextAwareLogicException`, or via the `HasExceptionContext` trait.
- **Stable error metadata**: `errorCode()` (`{package}.{domain}.{reason}`), `logLevel()` (PSR-3 vocabulary), `toLogArray()` (versioned `log_schema`).
- **Sensitive data redaction**: `DefaultContextRedactor` + `CompositeContextRedactor::withExtraKeys()`.
- **Framework decoupled**: zero runtime dependencies.

## Quick start

### Catching ecosystem exceptions

```php
// runnable
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;

$caught = false;

try {
    throw new class('demo') extends \RuntimeException implements JOOExceptionInterface {};
} catch (JOOExceptionInterface $exception) {
    $caught = $exception instanceof \Throwable;
}
```

### Declaring a package exception

```php
use JOOservices\Exceptions\Base\AbstractJOORuntimeException;

abstract class ClientException extends AbstractJOORuntimeException {}
```

### Context-aware exceptions

```php
// runnable
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

    public function errorCode(): string
    {
        return 'dto.hydration.failed';
    }

    public function logLevel(): string
    {
        return LogLevel::ERROR->value;
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$exception = HydrationException::forField('user.email', 'string');

// getContext() is always redacted before it reaches the logger
$context = $exception->getContext();
$logPayload = $exception->toLogArray();
```

### Redaction bootstrap

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\CompositeContextRedactor;

AbstractContextAwareException::setRedactor(
    CompositeContextRedactor::withExtraKeys(['national_id', 'ssn']),
);

AbstractContextAwareException::removeRedactor();
```

Never put secrets in exception **messages**. Put diagnostics in context and rely on redaction.

## Design notes

- `withContext()` returns a new exception; existing instances remain immutable.
- `getContext()` is always redacted; use `getRawContext()` only for tests or internal handling.
- `toLogArray()` includes the previous exception class and code, never previous messages.

## Documentation

- [Documentation Hub](./docs/README.md)
- [Architecture](./docs/00-architecture/01-project-overview.md)
- [Quick Start](./docs/01-getting-started/02-quick-start.md)
- [Laravel Integration](./docs/02-user-guide/09-laravel-integration.md)
- [Risks and Gaps](./docs/05-maintenance/01-risks-and-gaps.md)
- [Changelog](./CHANGELOG.md)
- [Workflow guide](./WORKFLOWS.md)
- [Contributing](./CONTRIBUTING.md)
- [Security policy](./SECURITY.md)
- [Support](./SUPPORT.md)
- [Governance](./GOVERNANCE.md)
- [Code of Conduct](./CODE_OF_CONDUCT.md)

## Development

Everything runs under Docker (`php:8.5-cli-bookworm`). GitHub Actions uses
GitHub-hosted `ubuntu-latest` with the same Compose image via `tools/ci/docker-compose`:

```bash
make build            # builds jooservices/exceptions:php85 (php:8.5-cli-bookworm + pcov)
make install          # composer install inside the container
make lint             # Pint (per preset) + PHPCS + PHPStan (level max) + PHPMD
make test             # PHPUnit, no coverage
make test-coverage    # PHPUnit with 100% statement coverage gate
make docs-verify      # README/docs code snippets must parse and run
make check            # lint + docs + tests
make ci               # the full local CI gate (lint + docs + coverage)
```

- [Setup](./docs/04-development/01-setup.md)
- [Testing](./docs/04-development/02-testing.md)
- [CI/CD](./docs/04-development/03-ci-cd.md)
- [Release Process](./docs/04-development/04-release-process.md)

## License

MIT — see [LICENSE](./LICENSE).
