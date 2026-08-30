# Setup

All development runs under Docker (workspace rule — no host-level PHP,
Composer, or extensions).

## Prerequisites

- Docker with the `docker compose` plugin
- `php:8.5-cli-bookworm` base image (pulled automatically)

## First run

```bash
make build      # builds jooservices/exceptions:php85
make install    # composer install inside the container + git hooks
make check      # lint + docs + tests
```

## The image

`Dockerfile` — `php:8.5-cli-bookworm` + git + unzip + pcov (coverage) +
Composer 2. CI uses the same image.

## Daily loop

```bash
make shell               # interactive shell in the container
make lint-fix            # Pint auto-fix
make test                # PHPUnit without coverage
make test-coverage       # with the 100% statement gate
make docs-verify         # README/docs snippets must parse and run
make ci                  # the exact CI gate
```

## Git hooks

CaptainHook installs on `composer install`:

- `commit-msg`: Conventional Commits with uppercase subject
- `pre-commit`: secret scan (Gitleaks when available) + `composer lint`
- `pre-push`: secret scan + `composer test`
