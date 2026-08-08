# Handoff

**Snapshot date:** 8 August 2026
**Audience:** a developer, contractor, or coding agent with no access to the conversation that produced this state — read this first, before touching anything.
**Supersedes:** the status/progress sections of `development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` and `CODEX_VSCODE_CONTINUATION_INSTRUCTION.md` (dated 7 August 2026, in `development_handoff/`, which is now an intentionally-preserved historical archive, not the live project state). This document exists precisely because that earlier handoff was once itself overstated and had to be corrected (`development_handoff/handoff/CODEX_HANDOFF_CORRECTION_PROMPT.md`) — every claim below was independently re-verified (not just recalled) on 8 August 2026 before being written down; see §7.
**Revised later the same day:** §3/§4/§5/§6/§8 were updated after this snapshot's own "immediate next step" (CI) landed, plus a self-contained publishable Docker bundle added as a follow-up. §1/§2/§7 are unchanged from the original snapshot. See `docs/CHANGELOG.md`'s two `2026-08-08` entries for the full detail this revision summarizes, and §7's added paragraph for how this specific revision was checked.

## 1. What this project is

A bachelor-thesis Design Science Research artefact: a small web application that evaluates a learner-submitted Austrian ICD-10 BMASGPK 2026 code against a synthetic case and returns one of `correct`/`suboptimal`/`incorrect` with an explanation, via explicit, traceable, deterministic rules — not a real diagnostic or clinical tool. Full commission/scope/non-goals: `development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §1-2 (still accurate for *scope*; only its status sections are superseded).

**Read in this order if you're starting cold:**

1. This document.
2. `CLAUDE.md` (repo root) — operational rules, especially the documentation-upkeep table. Read this before changing anything.
3. `docs/README.md` — what the four `docs/` files are each for.
4. `docs/DEVELOPMENT_DOCUMENTATION.md` — *why* everything is built the way it is.
5. `docs/IMPLEMENTATION_SPECIFICATION.md` — *exactly* what exists (schema, API, file layout, versions).
6. `docs/REQUIREMENTS_TRACEABILITY.md` — every requirement checked against real evidence.
7. `docs/CHANGELOG.md` — the full dated history; the most authoritative source for "what actually happened, in what order."
8. The `chapter3_*.md` files at repo root — the upstream methodological specification (rule semantics, case design, test design). These are the *authority* on what the software should do; `docs/` describes what it *actually* does.

## 2. Current baseline identifiers

| Baseline | Version | Status |
|---|---|---|
| Requirements catalogue | `0.5` | Working; audited clean (§5) |
| Source register | `0.4` | Working |
| Domain taxonomy | `DOMBASE-0.1` | Working, unchanged since 6 Aug |
| Rule catalogue | `RULEBASE-0.1` | Working, unchanged since 6 Aug |
| Data/interaction model | `MODELBASE-0.1` | Working, unchanged since 6 Aug |
| Catalogue subset | `SUBSET-0.1` | 13 records, unchanged |
| Case/subset plan | `CASEPLAN-0.2` | Supersedes `CASEPLAN-0.1` (7 Aug pre-freeze coverage review) |
| Synthetic cases | `CASEBASE-0.2` | 8 cases (was 4) |
| Reference responses | `RCBASE-0.2` | 18 rows (was 14) |
| Test catalogue | `TESTBASE-0.1` | 17 specifications, all now bound to real implementation |
| Prototype data baseline | `PROTOBASE-0.2` | Supersedes `PROTOBASE-0.1` |
| **`1.0` anything** | **none** | **Not frozen. This is the actual next milestone, blocked on two supervisor decisions — §6.** |

Nothing here is frozen. Every identifier above is a *working* baseline. Don't treat any count/ID as final without checking `docs/CHANGELOG.md` for a more recent entry first — this document is a snapshot, `docs/CHANGELOG.md` is the living record.

## 3. What exists and is verified working

Everything below was independently re-confirmed on 8 August 2026 (not just recalled from earlier sessions) — see §7 for how.

- **Data pipeline** (`prototype_baseline_0_1/`, Python): reproduces `SUBSET-0.1` byte-for-byte from the frozen `DIAGLIST2026.xlsx` (checksum-verified); loads `CASEBASE-0.2`/`RCBASE-0.2` into MySQL transactionally and idempotently (`inserted` then `no_op` on identical re-import).
- **PHP backend** (`app/src/`): a full rule engine (`RULE-GATE/MAP/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-01`), repositories over raw PDO, and an HTTP API (`GET /api/cases`, `GET /api/cases/{id}`, `POST /api/cases/{id}/evaluate`). No framework, no ORM — see `docs/DEVELOPMENT_DOCUMENTATION.md` §4-5 for why.
- **React frontend** (`app/frontend/`): single-page (no router) learner workflow — case list → case detail (search/select/submit a code) → feedback. Built into `app/public/` via Vite.
- **Docker**: `Dockerfile` at the **repository root** (not `app/` — moved deliberately, see §4) builds a multi-stage image (Node build stage → Composer stage → `php:8.4-apache` runtime, no permanent Node service). `prototype_stack/compose.yaml` runs `db` (MySQL) + `bootstrap` (one-shot loader) + `app`.
- **Full PHP test suite: 85/85 passing as of the last full run** (49 unit, 31 integration, 5 e2e). Independently re-confirmed subsets on 8 Aug: unit 49/49, e2e 5/5 (integration/persistence correctly skipped in that specific re-check because the compose `db` service isn't published to the host port — see §7, this is by design, not a failure).
- **`TEST-E2E-01`/`TEST-E2E-02`**: real Selenium suite (`app/tests/E2E/`, `php-webdriver/webdriver`), not a manual script — drives an actual browser against the actual deployed stack.
- **CI**: `.github/workflows/ci.yml`, five jobs (`python-checks`, `php-unit`, `backend-integration`, `e2e`, `publish-images`) on every push/PR. The four test jobs were verified by actually executing them via `act` against the local Docker daemon (three passed in full; `e2e` was verified up to an architecture-specific limit — this development machine is arm64 and the job's official Selenium image has no `linux/arm64` build, so only that one image pull couldn't be dry-run here). **Not yet verified: an actual run on real GitHub Actions infrastructure** — that only happens once this work is pushed.
- **Self-contained publishable bundle**: `docker-compose.yml` at the repo root (distinct from `prototype_stack/compose.yaml`) — `docker compose up` brings up `db`→`bootstrap`→`app` correctly ordered in one command (unlike the `prototype_stack` deployment scaffold in §6, which still needs a 3-step manual sequence), and `docker compose --profile test up`/`run --rm test` runs the entire suite fully containerized. Verified end to end: **85/85 tests, 492 assertions**, zero host PHP/Composer/Node/Python required. Full rationale: `docs/DEVELOPMENT_DOCUMENTATION.md` §10.6. The `publish-images` CI job that pushes this bundle's three images to GHCR has had every individual build command verified locally, but not the authenticate-and-push step itself (needs a real `GITHUB_TOKEN` from an actual GitHub Actions run).
- **Requirements traceability**: all 31 `REQ-*` entries audited; zero undeclared gaps (`docs/REQUIREMENTS_TRACEABILITY.md`).

## 4. Notable decisions since the 7 August brief (don't re-litigate these without reading why first)

Full rationale for each is in `docs/DEVELOPMENT_DOCUMENTATION.md` §10; this is just the index:

1. **MySQL is deliberately unpinned below the major version** (`mysql:latest` in `compose.yaml`, was `mysql:8.4.8`) — project owner's explicit instruction, not an oversight. **Concrete consequence you will hit:** `mysql:latest` has already jumped from `8.4.8` to `26.7.0`; MySQL refuses to open a data directory across that big a version jump, so if you `docker compose up` and get an unhealthy `db` container, run `docker compose down -v` first (drops the volume, safe — nothing but the versioned baseline lives in it) and retry.
2. **`Dockerfile`/`.dockerignore` live at the repo root, not `app/`.** This is so `prototype_stack/stack.sh sync` can pull *this whole repository* as the application source (project owner's stated intention) and find a `Dockerfile` at the checkout root, which `stack.sh` hard-requires. Don't move it back.
3. **Pre-freeze coverage review done**: `CASEBASE-0.1`/`RCBASE-0.1` (4 cases/14 responses) → `CASEBASE-0.2`/`RCBASE-0.2` (8 cases/18 responses), closing the FEV1-suffix and inpatient-status-branch gaps flagged in the original brief §23.2.
4. **Selenium, not Playwright**, for all browser-driven tests — explicit project-owner decision. Playwright was used once, then removed.
5. **A cluster of pre-existing `chapter3_*.md` control documents and `prototype_baseline_0_1/README.md`/`baseline_manifest.json` had gone stale after decision 3** (still saying `CASEBASE-0.1`, "four cases", etc. in current tense) and were fixed on 8 August — see `docs/CHANGELOG.md`'s "Control-document staleness cleanup" entry. **One acknowledged limitation from that cleanup**: several `chapter3_*.md` files (especially `chapter3_test_catalogue.md`, `chapter3_rule_catalogue.md`) still read throughout in a broader *pre-implementation* tense ("candidate", "intended to", "not yet executed") dating from before the PHP app existed. The specific wrong facts (stale IDs/counts) were fixed; the surrounding prose tone was deliberately *not* rewritten wholesale — treat "candidate"/"intended" language in those files as historical framing, and trust `docs/` for current status instead.
6. **CI, plus a self-contained publishable Docker bundle**, added as two related follow-ups once the implementation phase's testing was otherwise complete — full rationale `docs/DEVELOPMENT_DOCUMENTATION.md` §10.6. The bundle applies the same "test tooling never starts by default" principle used for the standalone Selenium container (decision 4): a bare `docker compose up` starts only `db`+`bootstrap`+`app`; the test suite and Selenium sit behind an explicit `test` Compose profile.

## 5. What's NOT done, and why that's correct (not an oversight)

- **No `1.0` freeze anywhere.** This is the real remaining milestone (brief §20 Phase 6), and it's blocked on two decisions that are explicitly the supervisor's to make, not an implementation gap:
  - `OPEN-RQ-01` — final wording of the thesis research question.
  - `OPEN-EVAL-01` — whether independent domain-expert review is required beyond internal technical verification.
- **No exact git commit is pinned.** `stack.sh verify --frozen` requires a 40-hex commit SHA; development has used the moving `main` branch throughout, correctly (brief §17 distinguishes this explicitly).
- **CI exists but has never run on real GitHub Actions infrastructure.** Everything in §3's CI/bundle bullets was verified by executing it locally (`act` for the four test jobs; direct `docker compose`/`docker build` for the bundle and for each of `publish-images`'s individual build steps) — this repository hasn't been pushed to GitHub since CI was added, so no workflow run exists yet on the actual service. A genuine gap, not a hedge: local dry-runs are strong evidence, not proof.
- **The `publish-images` job has never actually authenticated to or pushed anything to GHCR.** It can't be, locally — that step needs the real `GITHUB_TOKEN` a GitHub Actions run provides. Using this project's own specified/executed-in-development/principal-verified taxonomy: every other step is executed-in-development; the push itself is still only specified.
- **Pushing this work to GitHub hasn't happened.** Left for the project owner, deliberately — it's the action that would resolve both gaps above, and it also has a real side effect beyond this repository (a public commit history, and eventually GHCR packages under a personal account) that shouldn't happen as a side effect of an agent's own initiative.

## 6. How to resume the environment

**Nothing is currently running.** Re-confirmed with `docker ps -a` while writing this revision: no container from this project exists on this machine right now — the CI/bundle validation work since the original snapshot brought everything up and back down several times, most recently via the bundle's own isolated test run. Don't trust an *older* claim that something is "already up," including an earlier version of this exact document — check `docker ps` yourself first, every time.

Two ways to bring the stack up, depending on what you're doing:

**A. The self-contained bundle (`docker-compose.yml`, repo root) — simplest, no `.env` required:**

```bash
docker compose up -d --wait app              # db → bootstrap → app, correctly ordered, one command
curl http://127.0.0.1:8080/api/health         # {"status":"ok"}

# Full test suite, fully containerized (no host PHP/Composer/Node/Python needed):
docker compose --profile test up -d --wait selenium
docker compose --profile test run --rm test   # unit + integration + e2e in one go
```

**B. The `stack.sh`-managed deployment scaffold (`prototype_stack/`) — what production/`stack.sh sync` actually uses:**

```bash
cd prototype_stack
export APP_SOURCE_DIR=..   # repo root, where Dockerfile now lives — NOT ../app
docker compose --env-file .env -f compose.yaml up -d --wait db
docker compose --env-file .env -f compose.yaml run --rm --no-deps bootstrap
docker compose --env-file .env -f compose.yaml up -d --wait app

# Selenium (separate, not part of the compose stack above — see docs/DEVELOPMENT_DOCUMENTATION.md §10.5):
cd ../app/tests/E2E
docker compose up -d
```

If `.env` doesn't exist yet: `cd prototype_stack && ./stack.sh init`, then fill in the database password placeholders it copies from `.env.example`.

Full command reference, environment variables, and both paths: `docs/IMPLEMENTATION_SPECIFICATION.md` §6.4/§6.5 and `app/tests/E2E/README.md`.

**Note on integration/persistence tests against path B specifically:** the compose `db` service does not publish port 3306 to the host by design (only `app` and `bootstrap`, which share its Docker network, can reach it). To run `app/tests/Integration/*` or `prototype_baseline_0_1/tests/test_mysql_persistence.py` from the host against path B, either exec into a container on that network, or stand up a separate throwaway `docker run -p 3306:3306 mysql:latest` and load the baseline into *that* (exactly how this was done during development — see `docs/CHANGELOG.md` entries from 7 August for the precise commands). Don't be alarmed if these two specifically fail with "connection refused" against the path-B stack — that's expected, not a regression. Path A's `test` service doesn't have this problem (§3).

## 7. How this document was actually verified (not just recalled)

Before writing this, a 4-agent read-only verification workflow independently re-checked: git state, docker/stack reachability, live test re-execution, and a documentation-consistency sweep — specifically so this handoff wouldn't repeat the mistake the *previous* handoff made (see the supersession note at the top of this file). That sweep is what found the staleness cluster described in §4.5. Full findings: `docs/CHANGELOG.md`, entries dated 8 August 2026.

**The same-day revision (§3/§4/§5/§6/§8)** was checked differently. The CI job and its `act` validation were done directly in this session. The publishable-bundle work was a separate, parallel effort against this same repository — its `docker compose --profile test run --rm test` result of 85/85 is its own author's, not re-executed as part of this revision. This revision cross-read the current `.github/workflows/ci.yml`, `Dockerfile`, `docker-compose.yml`, `docs/DEVELOPMENT_DOCUMENTATION.md` §10.6, `docs/IMPLEMENTATION_SPECIFICATION.md` §6.5, and both `2026-08-08` `docs/CHANGELOG.md` entries directly against each other for consistency, and re-ran `docker ps -a`/`git log`/`git status` at revision time (nothing running; no new commits) — but did not independently re-execute the bundle's own test run. Treat the "85/85" bundle figure as verified by its own author and cross-checked for consistency here, not independently re-run by this revision.

## 8. Immediate next step

CI landed (§3, §4.6) — the item this snapshot originally pointed to as "next" is done. There is no queued implementation task right now; what remains is either supervisor-level (`OPEN-RQ-01`, `OPEN-EVAL-01`, §5) or the project owner's own call to make (pushing to GitHub, which both lets real CI run for the first time and triggers the first `publish-images` GHCR push — §5). Whoever picks this up next should confirm with the project owner before assuming either of those is now wanted, rather than inferring it from this document alone.
