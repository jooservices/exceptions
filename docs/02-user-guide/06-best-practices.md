# Best Practices

## Always

- **Static factories** for domain fields; canonical constructor for `(message, code, previous, context)`.
- **Diagnostics in context**, never in messages. Messages are human copy and are never redacted.
- **`getContext()`** for logs and API responses; `getRawContext()` for tests only.
- **`LogLevel` enum** values in `logLevel()` overrides.
- **Stable error codes** per the [conventions](./08-error-code-conventions.md).
- **One global redactor** registered at bootstrap; per-instance `withRedactor()` for async scopes.

## Leaf exception template

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;

final class InventoryException extends AbstractContextAwareException
{
    public static function outOfStock(string $sku, int $requested): self
    {
        return (new self("SKU {$sku} is out of stock"))
            ->withContext(['sku' => $sku, 'requested' => $requested]);
    }

    public function errorCode(): string
    {
        return 'inventory.stock.exhausted';
    }

    public function logLevel(): string
    {
        return LogLevel::WARNING->value;
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$exception = InventoryException::outOfStock('SKU-1', 5);
```

## Correlation IDs

Put `request_id` / `trace_id` into context yourself; the package adds no
correlation fields automatically:

```php
throw InventoryException::outOfStock('SKU-1', 5)
    ->withContext(['trace_id' => $traceId]);
```

## Never

- Never embed secrets in messages or error codes.
- Never log `getRawContext()`.
- Never encode HTTP status codes into `errorCode()` — map at the HTTP boundary.
- Never use `new static(...)` cloning instead of `copyWithContext()`.
- Never subclass `ExceptionContext` or the redactors — they are `final` on purpose.
