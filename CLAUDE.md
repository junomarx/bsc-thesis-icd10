# Project notes for Claude Code

Austrian ICD-10 (BMASGPK 2026) educational prototype — bachelor thesis
artefact. **Read `HANDOFF.md` (repo root) first** — it's the current,
independently-verified snapshot of what exists and what's next, dated at
the top. For deeper background: `development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md`
and `CODEX_VSCODE_CONTINUATION_INSTRUCTION.md` (commission/scope only —
their status sections are superseded by `HANDOFF.md`). The `chapter3_*.md`
files at the repository root are the upstream specification the
implementation in `app/` realizes — don't invent rule/case behaviour that
isn't traceable to them, but note a few of these files still carry stale
pre-implementation tense in places; `HANDOFF.md` §4.5 explains what was and
wasn't cleaned up.

## Documentation upkeep (required, not optional)

`docs/` holds five living documentation sources, plus the compiled user-guide
PDF, that must stay in sync with the code rather than being written once and
left to drift:

| When you... | ...update |
|---|---|
| Make any material implementation change (new feature, fixed bug, changed contract, added test, container/build change) | `docs/CHANGELOG.md` — always, dated entry, same day's work can share a section |
| Change anything user-visible in installation, startup/shutdown, the learner workflow, test commands, operational cautions, or troubleshooting | `docs/USER_GUIDE.tex` and regenerated `docs/USER_GUIDE.pdf` — update and compile both in the same turn |
| Change a schema, rule, API endpoint/shape, frontend contract, build/deploy setup, or anything else `docs/IMPLEMENTATION_SPECIFICATION.md` describes | `docs/IMPLEMENTATION_SPECIFICATION.md` — keep it matching the code exactly |
| Make a new architectural/technology/design decision, or deviate from an existing one | `docs/DEVELOPMENT_DOCUMENTATION.md` — add the decision *and* its rationale, don't just note that something changed |
| Implement or change anything that satisfies (or newly leaves ungapped) a `REQ-*` from `chapter3_requirements_catalogue.md` | `docs/REQUIREMENTS_TRACEABILITY.md` — update that requirement's row; don't let it drift out of sync the way `chapter3_requirements_catalogue.md` itself once did (see its `CASEPLAN-0.2` cross-reference fix in `docs/CHANGELOG.md`) |

Do this in the same turn as the implementation change, not as a follow-up
"documentation pass" — the changelog entry is written *because* you just
did the thing, not reconstructed later from memory. If a change is purely
internal refactoring with no observable contract change, a short
`CHANGELOG.md` entry still applies; the specification doesn't need touching.

`docs/README.md` explains how the documents relate to each other and
to the upstream `chapter3_*.md` control artefacts. Read it once if you
haven't touched this repo before.

## Current stack

React 19 + Vite (frontend, `app/frontend/`) → PHP 8.4, no framework
(backend, `app/src/`) → MySQL via Docker, deliberately unpinned below the
major version (`mysql:latest`, `prototype_stack/`) — see
`docs/IMPLEMENTATION_SPECIFICATION.md` §6.3 before bumping this: a
major-line jump (it has already gone from 8.4.8 to 26.7.0) requires
`docker compose down -v` first, MySQL itself refuses an in-place upgrade
across it. Python handles offline data preparation
(`prototype_baseline_0_1/scripts/`, unchanged from the adopted candidate).
See `docs/IMPLEMENTATION_SPECIFICATION.md` §6 for exact environment
variables and build commands.

`Dockerfile` and `.dockerignore` live at the **repository root**, not
`app/` — this repository is intended to be pulled as-is by
`prototype_stack/stack.sh sync` (once `git-source.conf` points at it),
which requires a `Dockerfile` at the checkout root. Don't move it back
under `app/`. It has two build targets, `runtime` (default — a bare
`docker build .` must keep resolving to this one, so it stays the *last*
stage in the file) and `dev` (adds test tooling — see next section). Don't
reorder the stages without keeping that default intact.

## CI and the self-contained bundle

`.github/workflows/ci.yml` runs on every push/PR (`python-checks`,
`php-unit`, `backend-integration`, `e2e`) and, on `main` only after those
four pass, builds and publishes three images to GHCR (`publish-images`).
`docker-compose.yml` at the repo root is the corresponding "pull everything
and run it" bundle — `docker compose up` for the app, `docker compose
--profile test run --rm test` for the full suite, fully containerized. This
is a *separate* file from `prototype_stack/compose.yaml` (the stack.sh
deployment scaffold) on purpose — see
`docs/DEVELOPMENT_DOCUMENTATION.md` §10.6 before touching either.
All three project-owned GHCR tags are required to publish both
`linux/amd64` and `linux/arm64`; do not remove the QEMU setup, either value
from `platforms:`, or the post-publish manifest check. Do not force
`platform: linux/amd64` in the Compose bundle: native Apple Silicon support
is intentional (see the same document §10.7).

## Testing tooling

Browser-driven system/integration/regression tests use **Selenium** via
`php-webdriver/webdriver`, not Playwright — explicit project-owner decision
(see `docs/CHANGELOG.md`, 2026-08-07). Don't reintroduce Playwright. The
actual suite lives at `app/tests/E2E/` (`TEST-E2E-01`/`02`); see
`app/tests/E2E/README.md` to run it — it needs both the app and a
Selenium/browser container up, so it is not part of a bare
`php vendor/bin/phpunit` run without that infrastructure available.
