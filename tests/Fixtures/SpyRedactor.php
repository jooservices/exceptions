<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Contracts\ContextRedactorInterface;

/**
 * Recording redactor for registry tests. Faithful pass-through with
 * string-normalised keys (mirrors the interface's return contract).
 */
final class SpyRedactor implements ContextRedactorInterface
{
    /** @var array<array-key, mixed>|null */
    public ?array $lastContext = null;

    public int $calls = 0;

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    public function redact(array $context): array
    {
        $this->calls++;
        $this->lastContext = $context;

        $normalised = [];

        foreach ($context as $key => $value) {
            $normalised[(string) $key] = $value;
        }

        return $normalised;
    }
}
