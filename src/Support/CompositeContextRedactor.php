<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

use JOOservices\Exceptions\Contracts\ContextRedactorInterface;

/**
 * Runs an inner redactor (defaults to DefaultContextRedactor), then masks
 * additional exact-match keys recursively.
 *
 * The safe way to extend the default key list for domain-specific secrets:
 *
 *   CompositeContextRedactor::withExtraKeys(['national_id', 'ssn'])
 */
final class CompositeContextRedactor implements ContextRedactorInterface
{
    /** Hard cap for a custom inner redactor that returns a cyclic array. */
    private const MAX_DEPTH = 64;

    /** @var list<string> */
    private readonly array $extraKeys;

    /**
     * @param  list<string>  $extraKeys  case-insensitive exact keys to mask after the inner redactor
     */
    public function __construct(
        private readonly ContextRedactorInterface $inner = new DefaultContextRedactor(),
        array $extraKeys = [],
    ) {
        $normalised = [];
        foreach ($extraKeys as $key) {
            $normalised[] = strtolower($key);
        }
        $this->extraKeys = array_values(array_unique($normalised));
    }

    /**
     * @param  list<string>  $extraKeys
     */
    public static function withExtraKeys(
        array $extraKeys,
        ?ContextRedactorInterface $inner = null,
    ): self {
        return new self($inner ?? new DefaultContextRedactor(), $extraKeys);
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    public function redact(array $context): array
    {
        return $this->maskExtra($this->inner->redact($context), 0);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function maskExtra(array $context, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_context' => '[MAX_DEPTH]'];
        }

        $redacted = [];

        foreach ($context as $key => $value) {
            $name = (string) $key;

            if (in_array(strtolower($name), $this->extraKeys, true)) {
                $redacted[$name] = DefaultContextRedactor::REDACTED_VALUE;

                continue;
            }

            if (!is_array($value)) {
                $redacted[$name] = $value;

                continue;
            }

            /** @var array<string, mixed> $nested */
            $nested = $value;
            $redacted[$name] = $this->maskExtra($nested, $depth + 1);
        }

        return $redacted;
    }
}
