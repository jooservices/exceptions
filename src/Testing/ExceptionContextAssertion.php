<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Testing;

use AssertionError;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Support\ExceptionLogPayload;

/**
 * Assertion helpers for consumer test suites.
 */
final class ExceptionContextAssertion
{
    /**
     * @param  array<string, mixed>  $expected
     *
     * @throws AssertionError when a context entry is missing or mismatched
     */
    public static function assertHasContext(
        ContextAwareExceptionInterface $exception,
        array $expected,
    ): void {
        $actual = $exception->getContext();

        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual) || $actual[$key] !== $value) {
                throw new AssertionError(sprintf(
                    'Exception context does not contain the expected value for "%s".',
                    (string) $key,
                ));
            }
        }
    }

    /**
     * @throws AssertionError when the error code does not match
     */
    public static function assertErrorCode(LoggableExceptionInterface $exception, string $expected): void
    {
        if ($exception->errorCode() !== $expected) {
            throw new AssertionError(sprintf(
                'Expected error code "%s", got "%s".',
                $expected,
                $exception->errorCode(),
            ));
        }
    }

    /**
     * @throws AssertionError when the log level does not match
     */
    public static function assertLogLevel(LoggableExceptionInterface $exception, string $expected): void
    {
        if ($exception->logLevel() !== $expected) {
            throw new AssertionError(sprintf(
                'Expected log level "%s", got "%s".',
                $expected,
                $exception->logLevel(),
            ));
        }
    }

    /**
     * @throws AssertionError when the key is missing or not redacted
     */
    public static function assertContextKeyRedacted(
        ContextAwareExceptionInterface $exception,
        string $key,
        string $placeholder = DefaultContextRedactor::REDACTED_VALUE,
    ): void {
        $actual = $exception->getContext();

        if (!array_key_exists($key, $actual) || $actual[$key] !== $placeholder) {
            throw new AssertionError(sprintf(
                'Expected context key "%s" to be redacted as "%s".',
                $key,
                $placeholder,
            ));
        }
    }

    /**
     * Asserts the stable log payload shape (keys, types, schema version).
     *
     * @throws AssertionError when the payload violates the schema
     */
    public static function assertLogPayloadSchema(LoggableExceptionInterface $exception): void
    {
        $payload = $exception->toLogArray();

        $expectedKeys = ['message', 'class', 'error_code', 'log_level', 'log_schema', 'context', 'previous'];

        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $payload)) {
                throw new AssertionError(sprintf('Log payload is missing key "%s".', $key));
            }
        }

        self::assertPayloadSchemaVersion($payload);
        self::assertPayloadValueTypes($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws AssertionError when the schema version does not match
     */
    private static function assertPayloadSchemaVersion(array $payload): void
    {
        if ($payload['log_schema'] !== ExceptionLogPayload::SCHEMA_VERSION) {
            throw new AssertionError(
                'Expected log schema "' . ExceptionLogPayload::SCHEMA_VERSION
                . '", got ' . get_debug_type($payload['log_schema']) . '.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws AssertionError when any value has the wrong type
     */
    private static function assertPayloadValueTypes(array $payload): void
    {
        if (
            !is_string($payload['message'])
            || !is_string($payload['class'])
            || !is_string($payload['error_code'])
            || !is_string($payload['log_level'])
            || !is_array($payload['context'])
            || !is_array($payload['previous'])
        ) {
            throw new AssertionError('Log payload has unexpected value types.');
        }
    }

    /**
     * Recursively asserts no context entry equals the given value.
     * Guards against secrets that survived redaction.
     *
     * @throws AssertionError when the value is found anywhere in the context
     */
    public static function assertNoValueInContext(
        ContextAwareExceptionInterface $exception,
        mixed $value,
    ): void {
        self::assertNoValueInArray($exception->getContext(), $value);
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AssertionError when the value is found in the array
     */
    private static function assertNoValueInArray(array $context, mixed $value): void
    {
        foreach ($context as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $nested */
                $nested = $entry;
                self::assertNoValueInArray($nested, $value);

                continue;
            }

            if ($entry === $value) {
                throw new AssertionError('Context contains a forbidden value.');
            }
        }
    }
}
