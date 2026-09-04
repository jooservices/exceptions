# GitHub Actions workflow flow

This document describes the workflows currently defined in
`.github/workflows/`. Jobs run on GitHub-hosted `ubuntu-latest` runners;
PHP-related commands run through `tools/ci/docker-compose` (repository Docker
image `jooservices/exceptions:php85`). The pull-request gate and the post-merge
pass are split into two workflows; branch protection on `master`/`develop`
requires the pull-request checks before merge.

## Overall event flow

```mermaid
flowchart TD
    native[GitHub Secret Scanning and Push Protection] --> Alerts[GitHub security alerts or blocked push]

    pr[PR to master or develop] --> CI[CI — full quality gate]
    pr --> CodeQL[CodeQL]
    pr --> Commitlint[Commitlint]
    pr --> Semantic[Semantic PR Title]
    pr --> PathLabel[PR Labeler]
    pr --> Audit{Changed files under .github?}
    Audit -->|yes| WorkflowAudit[Workflow audit]

    push[Push to master or develop] --> PostMerge[CI post-merge]
    push --> CodeQL
    push --> Audit

    master[Push to master] --> Scorecard[OpenSSF Scorecard]

    tag[Push tag v*.*.*] --> Release[Release]

    weekly[Weekly schedules] --> CodeQL
    weekly --> LinkCheck[Link check]
    weekly --> Scorecard
    weekly --> WorkflowAudit

    daily[Daily schedule] --> Stale[Stale]

    manual[workflow_dispatch] --> LinkCheck
    manual --> Scorecard
    manual --> Stale
    manual --> WorkflowAudit
```

## Pull-request gate (`ci.yml`)

**Trigger:** pull requests targeting `master` or `develop`.
Concurrent runs for the same pull request cancel older in-progress runs.

```mermaid
flowchart TD
    PR[Pull request] --> V[Validate + docs:verify]
    V --> L[Lint matrix x4 — fail-fast]
    L --> S[Security matrix x3 — fail-fast]
    L --> T[Test Unit + coverage artifact]
    S --> C[Coverage upload]
    T --> C

    L --- L1[Pint · PHPCS · PHPStan · PHPMD]
    S --- S1[Dependencies: Composer audit + OSV Scanner + Dependency Review]
    S --- S2[Secrets: Gitleaks OSS CLI in pinned Docker image]
    S --- S3[SAST: Semgrep OSS]
    T --- T1[Unit suite + coverage artifact]
    C --- C1[Enforce 100% statement coverage]
    C --- C2[Upload to Codecov and SonarQube]
```

## Post-merge pass (`ci-post-merge.yml`)

**Trigger:** pushes to `master` or `develop` (i.e., right after a merge).

```text
Validate → Test Unit → Coverage upload → Codecov + Sonar
```

## Release flow (`release.yml`)

**Trigger:** push of a tag matching `v*.*.*`. Runs are not cancelled.
Fails if the tag is not reachable from `origin/master`.

## Other workflows

| Workflow | Trigger | Flow / result |
| --- | --- | --- |
| `codeql.yml` | Push/PR on `master` or `develop`; Monday 06:00 UTC | CodeQL for GitHub Actions |
| `commitlint.yml` | PR opened, edited, synchronized, reopened | Conventional Commits via `.github/commitlint.config.mjs` |
| `semantic-pr.yml` | PR opened, edited | PR title type + uppercase subject |
| `pr-labeler.yml` | PR opened, synchronized, reopened | Path labels from `.github/labeler.yml` |
| `link-check.yml` | Monday 04:00 UTC; manual | Lychee Markdown link check |
| `scorecard.yml` | Push to `master`; Monday 00:00 UTC; manual | OpenSSF Scorecard (GitHub-hosted) |
| `stale.yml` | Daily 01:00 UTC; manual | Stale issues/PRs |
| `workflow-audit.yml` | `.github/**` changes; Monday 03:00 UTC; manual | Actionlint + Zizmor |

## Branch protection

Both `master` and `develop` require pull requests with these status checks:
`Validate`, the four `Lint (…)` legs, the three `Security (…)` legs,
`Test (Unit)`, `Coverage upload`, `Validate commit messages`, and
`Validate PR Title`. Strict mode requires the branch to be up to date.
Force pushes and deletions are denied.

## Notes

- All declared workflows use dedicated repository configuration; none use
  `jooservices/workflows`.
- Secret scanning has two layers: GitHub Secret Scanning and Push Protection,
  plus Gitleaks in the PR security gate.
- Coverage gate is **100%** statement coverage (`tools/ensure-coverage.php`).
- Containers run as the runner user through `tools/ci/docker-compose`
  (`DOCKER_UID`/`DOCKER_GID`).
