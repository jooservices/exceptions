<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

/**
 * Immutable value object wrapping structured exception diagnostic context.
 *
 * Keys are preserved exactly as provided (PHP normalises numeric string keys
 * to integers). merge() never renumbers keys the way array_merge() would.
 * Values should be safely serialisable (scalars, arrays, or objects with
 * __toString / Stringable). Never store raw request objects, credentials, or
 * closures here.
 *
 * This class is intentionally dependency-free and final to prevent
 * subclassing that could introduce mutability.
 */
final class ExceptionContext
{
    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        $this->data = self::stringifyKeys($data);
    }

    /**
     * Returns a new instance with additional entries merged in.
     * Later keys overwrite earlier ones. Integer keys keep their value —
     * no array_merge() list renumbering.
     *
     * @param  array<array-key, mixed>  $additional
     */
    public function merge(array $additional): self
    {
        $merged = $this->data;

        foreach (self::stringifyKeys($additional) as $key => $value) {
            $merged[$key] = $value;
        }

        return new self($merged);
    }

    /**
     * Returns the raw context array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    private static function stringifyKeys(array $data): array
    {
        $normalised = [];

        foreach ($data as $key => $value) {
            $normalised[(string) $key] = $value;
        }

        return $normalised;
    }
}
