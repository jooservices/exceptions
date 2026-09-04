# CI/CD

Local `make ci` runs the Composer quality gate inside Docker. GitHub Actions
mirrors that tooling on GitHub-hosted `ubuntu-latest` runners via
`tools/ci/docker-compose` and the repository image `jooservices/exceptions:php85`.
See [WORKFLOWS.md](../../WORKFLOWS.md) for the full Actions map.

## Pull-request CI gate (order matters)

1. **Validate**: `composer validate --strict` + `docs:verify`
2. **Lint** (parallel): Pint `--test` (per), PHPCS, PHPStan level max (strict rules), PHPMD
3. **Security** (parallel after lint): Composer audit, OSV Scanner, Dependency Review, Gitleaks, Semgrep
4. **Test**: PHPUnit Unit suite with Clover coverage
5. **Coverage upload**: enforce **100%** statement coverage, then Codecov + SonarQube

## Pull request rules

- Semantic PR titles (`feat: …`, `fix: …`, uppercase subject) via `semantic-pr.yml`
- Commitlint on every PR commit via `commitlint.yml`
- PR labeler
- Required checks must be green before merge — never merge red
- Branch model: work in `feature/*` / `chore/*` → PR into `develop`; `master` is production

## Release workflow

On `v*.*.*` tags reachable from `master`: validate + lint + docs + 100% coverage
+ Trivy + SBOM, then a GitHub Release. Packagist publication is delegated to its
verified GitHub webhook. See [Release Process](./04-release-process.md).
