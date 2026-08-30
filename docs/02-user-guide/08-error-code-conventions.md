# Error Code Conventions

Use stable, lowercase, dot-separated slugs:

```text
{package}.{domain}.{reason}
```

Examples:

- `dto.hydration.failed`
- `client.http.timeout`
- `auth.token.expired`
- `exception.generic` (package default)

## Rules

1. Codes are API/translation keys, not user-facing copy.
2. Do not encode HTTP status in the code string — map status at the HTTP boundary.
3. Prefer additive new codes over redefining old ones.
4. Keep codes ASCII, lowercase, no spaces, at least one dot.
5. Validation is enforced at `toLogArray()` time — malformed codes throw
   `LogicException` instead of polluting log pipelines.
