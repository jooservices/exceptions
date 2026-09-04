# jooservices/exceptions

Follow the JOOservices workspace `AGENTS.md` at `../../AGENTS.md`
(identity, soulevilx, quality, runtime, coding practice). Do not weaken those
rules.

This file adds project-only rules.

- PHP `^8.5`, **zero runtime dependencies**; framework-agnostic — no Illuminate/Symfony in `src/`
- All tooling via Docker (`php:8.5-cli-bookworm`, image `jooservices/exceptions:php85`)
- CI on GitHub-hosted `ubuntu-latest` via `tools/ci/docker-compose`
- `make ci` is the local gate: Pint (per) + PHPCS + PHPStan level max (strict rules) + PHPMD + docs:verify + PHPUnit 100% statement coverage
- Exceptions are value objects: `withContext()` returns new instances through the abstract `copyWithContext()` — never `new static(...)` cloning
- `getContext()` is always redacted; `getRawContext()` is tests/internal only
- `toLogArray()` previous chain carries class + code only — never messages
- Branch model: `develop` for integration, `master` for production, tags from `master`
