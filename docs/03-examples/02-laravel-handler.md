# Laravel Handler Example

Complete consumer-side example: a Laravel exception handler that reports
structured payloads and renders safe error envelopes.

```php
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as BaseHandler;
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ExceptionHandler extends BaseHandler implements ExceptionHandler
{
    public function report(Throwable $exception): void
    {
        if ($exception instanceof LoggableExceptionInterface) {
            $this->reportStructured($exception);

            return;
        }

        parent::report($exception);
    }

    public function render($request, Throwable $exception): Response
    {
        if ($exception instanceof JOOExceptionInterface && $request->expectsJson()) {
            return $this->renderStructured($exception);
        }

        return parent::render($request, $exception);
    }

    private function reportStructured(LoggableExceptionInterface $exception): void
    {
        logger()->log(
            $exception->logLevel(),
            $exception->getMessage(),
            $exception->toLogArray(),
        );
    }

    private function renderStructured(JOOExceptionInterface $exception): Response
    {
        $payload = [
            'error' => [
                'code' => 'exception.generic',
                'message' => $exception->getMessage(),
            ],
        ];

        if ($exception instanceof LoggableExceptionInterface) {
            $payload['error']['code'] = $exception->errorCode();
            $payload['error']['context'] = $exception->getContext();
        }

        return response()->json($payload, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
```

Register the redactor in a service provider as shown in
[Laravel Integration](../02-user-guide/09-laravel-integration.md).
