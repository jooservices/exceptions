# Data Flow

## Context flow

```text
1. Caller builds an exception via a static factory
       new self(...)->withContext(['path' => $path, 'expectedType' => $type])
                                    │
2. withContext() merges into the immutable ExceptionContext
   and returns a NEW instance via copyWithContext()        ← subclass reconstructs its own invariants
                                    │
3. getContext() pipes the raw context through the redactor
       instance redactor ?? global redactor ?? DefaultContextRedactor
                                    │
4. Consumers (loggers, handlers) receive the sanitised array
```

## Log payload flow

```text
exception->toLogArray()
      → ExceptionLogPayload::build(exception, errorCode(), logLevel(), getContext())
      → validates error code ({package}.{domain}.{reason}) and log level (PSR-3)
      → emits: message | class | error_code | log_level | log_schema | context | previous
             context is ALREADY redacted
             previous is class + code only — never messages (leak guard)
```

## Redaction flow

```text
DefaultContextRedactor::redact($context)
      → single-pass recursive walk
      → exact-match sensitive keys → [REDACTED]
      → arrays recursed with a depth cap (64) — self-references die at the cap
      → resources → [RESOURCE:type], objects → [OBJECT:Class] (never recursed),
        non-finite floats → [NON_FINITE_FLOAT] — siblings preserved

CompositeContextRedactor
      → inner redactor first (default: DefaultContextRedactor)
      → then extra domain keys masked recursively
```
