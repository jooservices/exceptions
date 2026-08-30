# Modules and Domains

| Module | Responsibility | Files |
| --- | --- | --- |
| `Contracts` | Catch + capability vocabulary | `JOOExceptionInterface`, runtime/logic markers, `ContextAwareExceptionInterface`, `LoggableExceptionInterface`, `ContextRedactorInterface` |
| `Base` | Abstract exception hierarchy | `AbstractJOORuntimeException`, `AbstractJOOLogicException`, `AbstractContextAwareException`, `AbstractContextAwareLogicException` |
| `Concerns` | Shared behaviour traits | `ProvidesStructuredContext` (internal, used by the context bases), `HasExceptionContext` (inheritance escape hatch) |
| `Support` | Value objects + strategies | `ExceptionContext`, `DefaultContextRedactor`, `CompositeContextRedactor`, `ExceptionLogPayload`, `LogLevel`, `ErrorCode` |
| `Testing` | Consumer test helpers | `ExceptionContextAssertion` |

## Design decisions

- **Context immutability**: exceptions are value objects; `withContext()` returns a new instance via the abstract `copyWithContext()` template method — never `new static(...)` cloning.
- **Redaction is a Strategy**: `ContextRedactorInterface` + default/composite implementations; a global registry (with per-instance override) wires it without a DI container.
- **Single log builder**: `ExceptionLogPayload` is the only place the log array shape lives (DRY — the abstract bases and the trait share it).
- **Fail fast**: trait without `initContext()`, invalid error codes, and invalid log levels throw `LogicException` instead of degrading silently.
