# Root Interface

`JOOExceptionInterface` is the catch-all for the whole ecosystem.

```php
// runnable
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;

$caught = false;

try {
    throw new class('demo') extends \RuntimeException implements JOOExceptionInterface {};
} catch (JOOExceptionInterface $exception) {
    $caught = $exception instanceof \Throwable;
}

// any exception from dto, client, useragent, ... lands in the same clause
```

## Adoption rules

- It extends `Throwable` and declares **no methods** — adding it to an existing
  exception hierarchy is always backward compatible.
- Package-level bases should implement it once; leaf classes inherit the marker.
- Catch order matters: catch concrete package exceptions first, then
  `JOOExceptionInterface`, then `\Throwable`.

## Runtime and logic markers

```php
// runnable
use JOOservices\Exceptions\Contracts\JOOLogicExceptionInterface;
use JOOservices\Exceptions\Contracts\JOORuntimeExceptionInterface;

$runtime = new class extends \RuntimeException implements JOORuntimeExceptionInterface {};
$logic = new class extends \LogicException implements JOOLogicExceptionInterface {};
```
