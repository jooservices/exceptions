# Risks and Gaps

| Category | Risk / Gap | Mitigation |
| --- | --- | --- |
| Global redactor | Process-wide static registry | Safe under PHP-FPM. Async/tenants: per-instance `withRedactor()`. |
| Exact-match keys | No substring matching | Expanded default list + `CompositeContextRedactor::withExtraKeys()`. |
| Exception messages | Never redacted | Hard rule: no secrets in messages; diagnostics go in context. |
| Previous chain | Historical message leaks | `toLogArray()` previous = class + code only. |
| `getRawContext()` | Returns unredacted secrets if misused | Documented for tests/internal only; logs must use `getContext()`. |
| Trait misuse | Missing `initContext()` | Fail-fast `LogicException`. |
| Deep / cyclic context | Memory exhaustion | Single-pass walker with depth cap (64); objects never recursed. |
| Custom redactors | Shallow implementations drop nested masking | Docs + `docs:verify` run every sample; prefer decorating the default. |
