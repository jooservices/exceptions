<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

use JOOservices\Exceptions\Contracts\ContextRedactorInterface;

/**
 * Safe default context redactor for structured logging.
 *
 * Single-pass recursive walk:
 *  - Cycles are impossible: objects are never recursed into (descriptor only)
 *    and array self-references hit the MAX_DEPTH guard.
 *  - Values are masked, never removed, so log consumers see the field existed.
 */
final class DefaultContextRedactor implements ContextRedactorInterface
{
    /** Stable replacement value for redacted context entries. */
    public const REDACTED_VALUE = '[REDACTED]';

    /**
     * Hard cap for nested array walking. Independent of any encoder depth so
     * redactArray() stays safe for self-referencing arrays.
     */
    private const MAX_DEPTH = 64;

    /**
     * Exact key match after strtolower(). Compound aliases are listed
     * explicitly (matching is not substring-based, so "csrf_token" is not
     * covered by "token").
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'access-token',
        'access_token',
        'api-key',
        'api_key',
        'apikey',
        'auth_token',
        'authorization',
        'bearer',
        'client_secret',
        'cookie',
        'credential',
        'credentials',
        'csrf_token',
        'jwt',
        'password',
        'password_confirmation',
        'passwd',
        'private_key',
        'pwd',
        'refresh-token',
        'refresh_token',
        'secret',
        'session',
        'session_id',
        'set-cookie',
        'token',
        'x-api-key',
    ];

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    public function redact(array $context): array
    {
        return $this->redactArray($context, 0);
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    private function redactArray(array $context, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_context' => '[MAX_DEPTH]'];
        }

        $redacted = [];

        foreach ($context as $key => $value) {
            $normalisedKey = (string) $key;

            if (in_array(strtolower($normalisedKey), self::SENSITIVE_KEYS, true)) {
                $redacted[$normalisedKey] = self::REDACTED_VALUE;

                continue;
            }

            if (is_array($value)) {
                $redacted[$normalisedKey] = $this->redactArray($value, $depth + 1);

                continue;
            }

            $redacted[$normalisedKey] = match (true) {
                is_resource($value) => sprintf('[RESOURCE:%s]', get_resource_type($value)),
                is_object($value) => sprintf('[OBJECT:%s]', $value::class),
                is_float($value) && !is_finite($value) => '[NON_FINITE_FLOAT]',
                default => $value,
            };
        }

        return $redacted;
    }
}
