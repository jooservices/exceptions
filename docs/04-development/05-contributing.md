# Contributing

- Read the workspace [`AGENTS.md`](../../../AGENTS.md) (identity, branches,
  Docker, quality) — it binds this repo.
- Branch from `develop`; PR into `develop`. Conventional Commits, uppercase
  subject.
- Everything runs in Docker (`make ...`). Never install language runtimes on
  the host.
- New public API requires: 100% statement coverage, docs, CHANGELOG entry,
  PHPStan max + strict rules clean.
- `master` and `develop` are protected: PR required, CI green.
