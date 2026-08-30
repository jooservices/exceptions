# Loggable Exceptions

Every context-aware exception is also loggable:

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\ExceptionLogPayload;
use JOOservices\Exceptions\Support\LogLevel;

final class AuditException extends AbstractContextAwareException
{
    public function errorCode(): string
    {
        return 'audit.write.failed';
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

$exception = (new AuditException('audit log unavailable'))
    ->withContext(['partition' => 'p7', 'user_id' => 9]);

$payload = $exception->toLogArray();

$hasSchema = $payload['log_schema'] === ExceptionLogPayload::SCHEMA_VERSION;
$hasCode = $payload['error_code'] === 'audit.write.failed';
```

## Payload shape (stable, versioned)

```text
message    string                  raw message — never redacted, keep it secret-free
class      class-string            the concrete exception
error_code string                  {package}.{domain}.{reason}
log_level  string                  PSR-3 vocabulary via LogLevel enum
log_schema string                  'exceptions.log.v1' — bump when the shape changes
context    array                   already redacted
previous   list<{class, code}>     chain summary — NO messages (leak guard)
```

## Fail fast on drift

- `errorCode()` values violating `{package}.{domain}.{reason}` throw `LogicException` from `toLogArray()`.
- `logLevel()` values outside the PSR-3 vocabulary throw `LogicException` from `toLogArray()`.
- Use `LogLevel` enum constants in overrides — autocomplete kills `warn` vs `warning` drift.

## Logging it

```php
$logger->log($exception->logLevel(), $exception->getMessage(), $exception->toLogArray());
```
