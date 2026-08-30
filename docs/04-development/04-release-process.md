# Release Process

Branch model: work lands on `develop`; production is `master`. Tags from
`master`.

## Steps

1. **Release branch**: `release/<version>` from latest `develop`.
2. **Metadata only**: version in CHANGELOG, no feature commits.
3. **PR** `release/<version>` → `master`; required CI green.
4. **Tag** `v<version>` on `master` — the release workflow validates and
   creates the GitHub release. Packagist publication must be confirmed through
   the package's configured webhook.
5. **Merge back**: `master` → `develop` via PR.

## Versioning

SemVer for the public API: contracts, base classes, payload shape
(`log_schema`), and documented behaviour. Breaking changes bump major and add
migration notes.

## Pre-tag checklist

```bash
make ci                # lint + docs + 100% coverage
make audit             # composer audit --locked
make validate          # composer validate --strict
```

- CHANGELOG entry present and dated
- No secrets in the diff (Gitleaks pre-push)
- Docs snippets verified (`make docs-verify`)
