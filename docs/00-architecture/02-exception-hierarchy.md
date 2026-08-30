# Exception Hierarchy

```mermaid
graph TD
    Throwable[Throwable] --> JOOExceptionInterface[JOOExceptionInterface]

    JOOExceptionInterface --> JOORuntimeExceptionInterface
    JOOExceptionInterface --> JOOLogicExceptionInterface
    JOOExceptionInterface --> ContextAwareExceptionInterface
    JOOExceptionInterface --> LoggableExceptionInterface

    RuntimeException --> AbstractJOORuntimeException
    LogicException --> AbstractJOOLogicException

    AbstractJOORuntimeException -. implements .-> JOORuntimeExceptionInterface
    AbstractJOOLogicException -. implements .-> JOOLogicExceptionInterface

    AbstractJOORuntimeException --> AbstractContextAwareException
    AbstractJOOLogicException --> AbstractContextAwareLogicException

    AbstractContextAwareException -. implements .-> ContextAwareExceptionInterface
    AbstractContextAwareException -. implements .-> LoggableExceptionInterface
    AbstractContextAwareLogicException -. implements .-> ContextAwareExceptionInterface
    AbstractContextAwareLogicException -. implements .-> LoggableExceptionInterface
```

## Structure guidelines

1. **Leaf classes**: concrete `final` exception classes per failure state, created through static factories.
2. **Abstract bases**: runtime vs logic, preserving SPL semantics.
3. **Context bases**: `AbstractContextAwareException` (runtime) and `AbstractContextAwareLogicException` (logic).
4. **Markers**: `JOOExceptionInterface` (+ runtime/logic markers) on every package-level base.
5. **Trait escape hatch**: `HasExceptionContext` when a third-party parent blocks inheritance; call `initContext()` and implement `copyWithContext()`.
