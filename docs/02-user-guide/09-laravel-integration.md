# Laravel Integration

The package is framework-agnostic; Laravel integration is pure consumer code.

## 1. Register the redactor in a service provider

```php
use Illuminate\Support\ServiceProvider;
use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\CompositeContextRedactor;

final class ExceptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        AbstractContextAwareException::setRedactor(
            CompositeContextRedactor::withExtraKeys(['national_id', 'ssn']),
        );
    }
}
```

## 2. Report through the exception handler

```php
use Illuminate\Contracts\Debug\ExceptionHandler;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use Throwable;

final class Handler extends ExceptionHandler
{
    public function report(Throwable $exception): void
    {
        if ($exception instanceof LoggableExceptionInterface) {
            $this->logPayload($exception);

            return;
        }

        parent::report($exception);
    }

    private function logPayload(LoggableExceptionInterface $exception): void
    {
        logger()->log(
            $exception->logLevel(),
            $exception->getMessage(),
            $exception->toLogArray(),
        );
    }
}
```

## 3. Map to API error envelopes at the HTTP boundary

```php
use Illuminate\Http\JsonResponse;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use Throwable;

final class ApiErrorEnvelope
{
    public function for(Throwable $exception): JsonResponse
    {
        if ($exception instanceof LoggableExceptionInterface) {
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                    'context' => $exception->getContext(), // already redacted
                ],
            ], 500);
        }

        return response()->json(['error' => ['code' => 'exception.generic']], 500);
    }
}
```

HTTP status mapping stays in the application — the package never carries
status codes.
