# Troubleshooting

## "must call initContext() in its constructor"

`LogicException` thrown by `HasExceptionContext` when the trait is used without
`initContext()`. Call `$this->initContext()` at the end of the constructor (after
`parent::__construct()`).

## "Invalid error code ... Use {package}.{domain}.{reason}"

`toLogArray()` validates `errorCode()`. Codes must be lowercase,
dot-separated, ASCII slugs with at least one dot (or `exception.generic`).

## "Invalid log level ..."

`toLogArray()` validates `logLevel()` against the PSR-3 vocabulary. Return
`LogLevel::*->value`, never `'warn'` or `'fatal'`.

## My context key leaked in logs

- The default redactor masks **exact-match** keys only — compound domain keys
  need `CompositeContextRedactor::withExtraKeys([...])`.
- Confirm the app-level global redactor was registered at bootstrap and not
  removed by `removeRedactor()`.
- `getRawContext()` is unredacted — check no caller logs it by mistake.

## My custom redactor breaks nested keys

Write it recursively and prefer decorating `DefaultContextRedactor`. The
`maskExtra()` walk in `CompositeContextRedactor` is the reference implementation.

## Context looks different after a deep merge

`withContext()` merges with later-keys-win semantics and never mutates the
original. Keys are preserved as provided; integer keys are not renumbered
(unlike raw `array_merge`).

## Async workers see each other's redactor

The global registry is process-wide by design (fine for PHP-FPM). In
Swoole/RoadRunner multi-tenant setups, use `withRedactor()` per instance.
