# Context Redaction

`getContext()` always runs the context through a redactor before returning it.

## Default behaviour

- **Exact-match sensitive keys** (case-insensitive) become `[REDACTED]` —
  masked, never removed, so log consumers see the field existed.
- **Recursive** — nested arrays are walked to the same rules.
- **Safe descriptors** — resources → `[RESOURCE:type]`, objects → `[OBJECT:Class]`,
  non-finite floats → `[NON_FINITE_FLOAT]`; siblings are preserved.
- **Depth cap** — arrays deeper than 64 levels collapse to `['_context' => '[MAX_DEPTH]']`;
  self-referencing arrays terminate the same way (single pass, no encoder probe).

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\DefaultContextRedactor;

final class DemoException extends AbstractContextAwareException
{
    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$exception = (new DemoException('demo'))->withContext([
    'user_id' => 42,
    'headers' => ['Authorization' => 'Bearer secret', 'Accept' => 'application/json'],
]);

$context = $exception->getContext();

$redacted = $context['headers']['Authorization'] === DefaultContextRedactor::REDACTED_VALUE;
$kept = $context['user_id'] === 42;
```

## Sensitive key list (default)

```
access-token, access_token, api-key, api_key, apikey, auth_token, authorization,
bearer, client_secret, cookie, credential, credentials, csrf_token, jwt, password,
password_confirmation, passwd, private_key, pwd, refresh-token, refresh_token,
secret, session, session_id, set-cookie, token, x-api-key
```

Matching is **exact, not substring-based** — `csrf_token` is listed explicitly
because `token` does not cover it.

## Domain-specific keys

Extend the default list, never replace it blindly:

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\CompositeContextRedactor;

AbstractContextAwareException::setRedactor(
    CompositeContextRedactor::withExtraKeys(['national_id', 'ssn', 'bank_account']),
);

AbstractContextAwareException::removeRedactor();
```

`CompositeContextRedactor` runs the default redactor first, then masks your
extra keys recursively. Custom redactors should decorate
`DefaultContextRedactor` instead of reimplementing the walker.

## Per-instance overrides

For long-lived workers (Swoole / RoadRunner) or scoped tests, bind a redactor
to one instance:

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Contracts\ContextRedactorInterface;
use JOOservices\Exceptions\Support\ExceptionContext;

final class ScopedException extends AbstractContextAwareException
{
    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$passthrough = new class implements ContextRedactorInterface
{
    public function redact(array $context): array
    {
        return ['_passthrough' => $context];
    }
};

$scoped = (new ScopedException('demo'))->withRedactor($passthrough);
$shape = array_key_exists('_passthrough', $scoped->getContext());
```

## Hard rules

- `getRawContext()` is unredacted — tests and internal diagnostics **only**.
- Exception **messages** are never redacted — never put secrets in messages.
- `toLogArray()` never includes previous-exception messages (leak guard).
