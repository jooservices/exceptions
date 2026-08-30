# Testing

- Framework: PHPUnit 13, strict mode (fail on risky/warning/deprecation/notice).
- Coverage: **100% statement coverage enforced** by `tools/ensure-coverage.php`
  after the PHPUnit run. Lowering the gate is forbidden.
- Fake data rule: no hard-coded fixture secrets; fixtures are deterministic
  test doubles.

```bash
make test             # fast, no coverage
make test-coverage    # coverage text + clover + gate
```

## Suite layout

| Area | Covers |
| --- | --- |
| `tests/Unit/*` | one test class per production class, incl. redaction security tables |
| `tests/Fixtures/*` | concrete exception fixtures (canonical, stateful, trait-based, uninitialized), spy redactor |

## Security-sensitive cases (must stay green)

- Every default sensitive key alias, case-insensitively, including nested arrays
- Previous chain in `toLogArray()` contains **no messages**
- Self-referencing and over-deep arrays terminate
- Trait fail-fast without `initContext()`
- Invalid error codes / log levels rejected by `toLogArray()`

## Consumer test helpers

Use `JOOservices\Exceptions\Testing\ExceptionContextAssertion` in your own
suites — see [Basic Examples](../03-examples/01-basic-examples.md).
