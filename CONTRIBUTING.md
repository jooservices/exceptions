# Contributing

`jooservices/exceptions` is a PHP 8.5+ framework-agnostic package with zero
runtime dependencies.

## Workflow

- Run all tooling through Docker: `make ci`, `make audit`, and `make validate`.
- Branch from `develop`; open PRs back to `develop`.
- Release branches go to `master`; tags are created from `master` and merged
  back to `develop` afterward.
- Use Conventional Commits with an uppercase imperative subject. Never bypass
  hooks or failing checks.

Every public behavior change needs tests, documentation, and 100% statement
coverage. Preserve immutable exception semantics: `copyWithContext()` must
reconstruct the concrete type; `new static()` cloning is forbidden.

Report security issues privately through [SECURITY.md](SECURITY.md).

See also [SUPPORT.md](SUPPORT.md), [GOVERNANCE.md](GOVERNANCE.md), and
[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
