# Context-Aware Exceptions

## The copyWithContext() contract

Every concrete subclass MUST implement `copyWithContext()`:

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;

final class PaymentException extends AbstractContextAwareException
{
    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$original = new PaymentException('declined');
$augmented = $original->withContext(['gateway' => 'stripe']);

$originalStillEmpty = $original->getRawContext() === [];
$augmentedHasContext = $augmented->getRawContext() === ['gateway' => 'stripe'];
```

- `withContext()` merges and returns a **new instance** — the original is untouched.
- Reconstruct the concrete exception explicitly so its constructor invariants
  remain visible and protected by its own implementation.
- Subclasses with extra constructor state must reconstruct it themselves:

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;

final class OrderException extends AbstractContextAwareException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?ExceptionContext $context = null,
        public readonly string $orderId = '',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            $context,
            $this->orderId,
        );
    }
}

$copy = (new OrderException('boom', orderId: 'ORD-1'))->withContext(['attempt' => 2]);
$orderIdPreserved = $copy->orderId === 'ORD-1';
```

## Logic exceptions with context

For invariant failures, extend `AbstractContextAwareLogicException` the same
way — the API is identical.

## The trait escape hatch

When a third-party parent blocks inheritance, use `HasExceptionContext`:

```php
// runnable
use JOOservices\Exceptions\Concerns\HasExceptionContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Support\ExceptionContext;

class WrappedGuzzleException extends \RuntimeException implements ContextAwareExceptionInterface
{
    use HasExceptionContext;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->initContext();
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        $copy = new self($this->getMessage(), $this->getCode(), $this->getPrevious());
        $copy->initContext($context->toArray());

        return $copy;
    }
}

$wrapped = (new WrappedGuzzleException('request failed'))->withContext(['uri' => '/v1/x']);
$hasContext = $wrapped->getRawContext() === ['uri' => '/v1/x'];
```

Rules for trait users:

1. Implement `ContextAwareExceptionInterface` (and preferably `LoggableExceptionInterface`).
2. Call `$this->initContext()` in the constructor — **forgetting it throws `LogicException` on first use**.
3. Implement `copyWithContext()` for your own constructor invariants.
