# Basic Examples

## Full leaf exception

```php
// runnable
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;

final class CartException extends AbstractContextAwareException
{
    public static function checkoutFailed(string $cartId, string $reason): self
    {
        return (new self("Checkout failed: {$reason}"))
            ->withContext(['cart_id' => $cartId, 'reason' => $reason]);
    }

    public function errorCode(): string
    {
        return 'cart.checkout.failed';
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

$exception = CartException::checkoutFailed('cart-9', 'payment timeout');
$instanceOfContract = $exception instanceof ContextAwareExceptionInterface;
$context = $exception->getContext();
```

## Chaining context across layers

```php
// runnable
use JOOservices\Exceptions\Support\ExceptionContext;

final class ChainedException extends \JOOservices\Exceptions\Base\AbstractContextAwareException
{
    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$upstream = (new ChainedException('timeout'))->withContext(['attempt' => 1]);

$wrapped = (new ChainedException('retry failed', 0, $upstream))
    ->withContext(['attempt' => 2]);

$payload = $wrapped->toLogArray();
$previousSummary = $payload['previous']; // [{class, code}] — no messages
```

## Verifying behaviour in consumer tests

```php
// runnable
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Testing\ExceptionContextAssertion;

final class VerifyException extends \JOOservices\Exceptions\Base\AbstractContextAwareException
{
    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}

$exception = (new VerifyException('boom'))->withContext(['password' => 'secret', 'user' => 'viet']);

ExceptionContextAssertion::assertContextKeyRedacted($exception, 'password');
ExceptionContextAssertion::assertHasContext($exception, ['user' => 'viet']);
ExceptionContextAssertion::assertLogPayloadSchema($exception);
ExceptionContextAssertion::assertNoValueInContext($exception, 'secret');
```
