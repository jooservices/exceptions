# Installation

## Requirements

- PHP `^8.5`
- Composer 2

The library has **zero runtime dependencies**.

## Install

```bash
composer require jooservices/exceptions
```

## Verify

```php
// runnable
use JOOservices\Exceptions\Contracts\JOOExceptionInterface;

echo interface_exists(JOOExceptionInterface::class) ? 'ok' : 'missing';
```
