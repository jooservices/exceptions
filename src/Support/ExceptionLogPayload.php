<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

use LogicException;
use Throwable;

/**
 * Builds the stable structured log array used by context-aware exceptions.
 *
 * @internal Package-shared helper; not a public extension point.
 */
final class ExceptionLogPayload
{
    /** Versioned schema key so log pipelines can migrate on shape changes. */
    public const SCHEMA_VERSION = 'exceptions.log.v1';

    /**
     * @param  array<string, mixed>  $context  already-redacted context
     * @return array{
     *     message: string,
     *     class: class-string,
     *     error_code: string,
     *     log_level: string,
     *     log_schema: string,
     *     context: array<string, mixed>,
     *     previous: list<array{class: class-string, code: int}>
     * }
     *
     * @throws LogicException when the error code or log level violates the contract
     */
    public function build(
        Throwable $exception,
        string $errorCode,
        string $logLevel,
        array $context,
    ): array {
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

    /**
     * Previous chain as class + code only. Messages are deliberately excluded
     * — they bypass context redaction and leak secrets into logs.
     *
     * @return list<array{class: class-string, code: int}>
     */
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
