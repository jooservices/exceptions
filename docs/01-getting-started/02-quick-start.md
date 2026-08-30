# Quick Start

## 1. Adopt the root marker (no breaking changes)

```php
// runnable
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;

class ExistingException extends \RuntimeException implements JOOExceptionInterface {}

$isJooservices = new ExistingException() instanceof JOOExceptionInterface;
```

## 2. Reparent your package base (minor)

```php
// runnable
use JOOservices\Exceptions\Base\AbstractJOORuntimeException;

class ClientException extends AbstractJOORuntimeException {}

$semantics = new ClientException() instanceof \RuntimeException;
```

## 3. Add structured context (major)

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;

final class LookupException extends AbstractContextAwareException
{
    public static function forEntity(string $entity, int $id): self
    {
        return (new self("Lookup failed for {$entity}"))->withContext([
            'entity' => $entity,
            'id' => $id,
        ]);
    }

    public function errorCode(): string
    {
        return 'catalog.lookup.failed';
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

$exception = LookupException::forEntity('product', 42);

$context = $exception->getContext(); // redacted
$payload = $exception->toLogArray();
```

## 4. Log it

```php
// runnable
use JOOservices\Exceptions\Support\LogLevel;

$levels = LogLevel::all();

$hasError = in_array('error', $levels, true);
```

## 5. Redact domain secrets (bootstrap once)

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\CompositeContextRedactor;

AbstractContextAwareException::setRedactor(
    CompositeContextRedactor::withExtraKeys(['national_id', 'ssn']),
);

AbstractContextAwareException::removeRedactor();
```

Next: [Basic Concepts](./03-basic-concepts.md).
