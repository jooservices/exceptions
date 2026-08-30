# CI/CD

`make ci` mirrors the GitHub Actions quality gate: the same image and the
same Composer command. The workflow requires a Docker-capable self-hosted
Linux runner.

## CI gate (order matters)

1. **Security**: `composer audit --locked`, secret scan (Gitleaks, checksum-pinned), Semgrep
2. **Quality**: Pint `--test` (per preset), PHPCS, PHPStan level max (strict rules, no ignores), PHPMD, `docs:verify`
3. **Tests**: PHPUnit with the 100% statement coverage gate

## Pull request rules

- Semantic PR titles (`feat: ...`, `fix: ...`, uppercase subject)
- PR labeler
- Required checks must be green before merge — never merge red
- Branch model: work in `feature/*` → PR into `develop`; `master` is production

## Release workflow

On `v*.*.*` tags reachable from `master`: `composer validate` + the full CI
gate + dependency audit, then a GitHub release. Packagist publication is
intentionally delegated to its verified GitHub webhook; the workflow does not
claim to publish packages itself. See [Release Process](./04-release-process.md).
