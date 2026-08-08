# Changelog

All notable implementation changes are recorded here, newest first. This is
a development log, not the principal verification record — deviation
categories and formal conformance verdicts belong to the eventual
`chapter3_test_catalogue.md` §3.2.2 procedure, not here.

**Format for new entries:** one dated section per work session (or per
material increment within a session, if there's good reason to split them).
Within a section, group bullets under `Added` / `Changed` / `Fixed` /
`Removed` / `Verified` / `Deviations` as applicable — omit empty groups.
Reference `REQ-*`/`RULE-*`/`TEST-*` identifiers where a change implements or
affects one. Every entry should let a reader answer "what changed, why, and
was it actually tested" without opening the diff.

## 2026-08-08 — Apple Silicon installation failure: native multi-platform publication

The project owner tested the new user guide on macOS/Apple Silicon. The
documented `docker compose pull` stopped after pulling MySQL because the
project-owned `app` and `bootstrap` tags had no `linux/arm64/v8` manifest.
This was a real distribution defect in the advertised installation path, not
a local Docker configuration problem.

### Fixed

- `.github/workflows/ci.yml`'s `publish-images` job now sets up QEMU and
  Buildx, and builds each of the `runtime`, `dev`, and `bootstrap` tags for
  both `linux/amd64` and `linux/arm64`. Docker's maintained actions were
  advanced to their current major versions (`setup-qemu`/`setup-buildx`/
  `login` v4, `build-push` v7) as part of the corrected publication path.
- The job now inspects all three GHCR indexes after pushing and uses `jq` to
  require both Linux architectures. A single-architecture publication can no
  longer finish successfully while silently breaking the documented install.

### Changed

- `docs/USER_GUIDE.tex` revision 0.2 and regenerated
  `docs/USER_GUIDE.pdf` now state the AMD64/ARM64 contract, explain the exact
  `no matching manifest for linux/arm64/v8` error, and provide the verified
  native fallback: `docker compose build bootstrap app` followed by
  `docker compose up -d --wait app`. The optional test-image build path is
  also explicit.
- `docs/IMPLEMENTATION_SPECIFICATION.md` §6.5 defines the two-platform image
  contract and its registry assertion; `docs/DEVELOPMENT_DOCUMENTATION.md`
  §10.7 records the decision to publish native images rather than force
  `linux/amd64` emulation; `HANDOFF.md`, `CLAUDE.md`,
  `docs/REQUIREMENTS_TRACEABILITY.md`, and the root Compose commentary were
  updated to preserve the same invariant.

### Verified

- On the affected ARM64 Docker daemon, `docker buildx imagetools inspect`
  confirmed that all three current project tags contain `linux/amd64` only;
  `mysql:latest` and `seleniarm/standalone-chromium:latest` both contain
  native ARM64 variants, isolating the defect to the project publication.
- `docker compose build bootstrap app`: both images built as `arm64`.
  `docker compose up -d --wait app`: MySQL became healthy, bootstrap exited
  0, and the app started; `/api/health` returned `{"status":"ok"}` and
  `/api/cases` returned the synthetic case list.
- `docker compose --profile test build test` produced a native ARM64 test
  image; the containerized suite then passed **85/85 tests, 492 assertions**,
  including the Selenium browser tests.
- `actionlint .github/workflows/ci.yml`, `docker compose config --quiet`, and
  the user-guide LaTeX build all pass. The revised PDF is nine A4 pages with
  no LaTeX warnings, undefined references, underfull boxes, or overfull boxes;
  the changed pages were rendered and visually inspected.
- Real GitHub Actions run
  [31257017708](https://github.com/junomarx/bsc-thesis-icd10/actions/runs/31257017708)
  passed all four test jobs, built and pushed all three image indexes, and
  passed the new two-architecture assertion. Independent inspection found
  `linux/amd64` and `linux/arm64` in every live tag.
- The exact originally failing command, `docker compose pull`, then completed
  on the affected ARM64 daemon. `docker compose up -d --wait app` recreated
  `app` and `bootstrap` from the published ARM64 digests; bootstrap exited 0,
  MySQL was healthy, `/api/health` returned OK, and six learner-visible cases
  loaded.

## 2026-08-08 — User-facing LaTeX guide established

Project-owner request to make installation and day-to-day prototype use a
documented, maintained part of the artefact before returning to the remaining
implementation work.

### Added

- `docs/USER_GUIDE.tex` and compiled `docs/USER_GUIDE.pdf`: an eight-page
  user guide that begins with a quick introduction and Docker Compose
  installation procedure, then covers the real learner workflow, feedback
  meanings, start/stop/update/reset operations, optional full-suite testing,
  troubleshooting, data/privacy boundaries, and a command summary.
- The default installation path uses the published GHCR images from the root
  `docker-compose.yml`; a source-build fallback, macOS/Linux and PowerShell
  port overrides, and an explicit warning around destructive volume reset are
  included.

### Changed

- `README.md`, `docs/README.md`, and `HANDOFF.md` now link the guide and its
  LaTeX source so a new user or contributor can discover it from the normal
  project entry points. `docs/README.md` also records the reproducible build
  and cleanup commands.
- `CLAUDE.md` now requires any user-visible installation, workflow, test,
  operational, or troubleshooting change to update `USER_GUIDE.tex` and
  regenerate `USER_GUIDE.pdf` in the same turn.
- `.gitignore` excludes only the guide's LaTeX intermediate files while
  keeping its source and compiled PDF trackable.

### Verified

- `latexmk -pdf -interaction=nonstopmode -halt-on-error -outdir=docs
  docs/USER_GUIDE.tex`: clean two-pass build with no LaTeX warnings,
  undefined references, underfull boxes, or overfull boxes; output confirmed
  as an eight-page A4 PDF with embedded Type 1 fonts.
- Rendered and visually inspected all eight PDF pages, including the title
  page, table of contents, code blocks, caution boxes, cross-references, and
  final command table.

## 2026-08-08 — First real GitHub Actions runs: found and fixed the actual Selenium-grid check bug

Closed the one deviation the previous entry flagged as unverified: pushed
the accumulated working-tree state (this session's baseline 0.2/E2E work,
the CI workflow, the bundle, and the docs set) to `origin/main` as three
commits on top of the project owner's own `f1b927f`, triggering CI for
real for the first time.

### Fixed

- `.github/workflows/ci.yml`, `e2e` job, "Wait for Selenium grid" step,
  in two passes:
  1. First real run: `python-checks`, `php-unit`, and `backend-integration`
     all passed on the first try; `e2e` failed, isolated (via the Actions
     API's per-step conclusions) to exactly this step — every step before
     it, including starting the Selenium container, succeeded. Initially
     assumed a too-tight timeout (60s = 30×2s, never exercised on real
     runner hardware, only dry-run via `act`, which can't pull the
     amd64-only `selenium/standalone-chrome` image on this arm64
     development machine) and widened it to 180s.
  2. Second real run, same commit's fix pushed: failed at the *identical*
     step again, and step timing showed it ran for the full 180s before
     giving up — proving it wasn't a timing issue at all. Reproduced the
     official `selenium/standalone-chrome:latest` image locally (via
     `docker run --platform linux/amd64`, emulated on this arm64 machine)
     and hit `/status` directly: the grid reports ready in ~2 seconds, but
     its JSON is pretty-printed with a **space** after the colon
     (`"ready": true`), while the wait loop's `grep -q '"ready":true'` had
     none — so it could never match, regardless of how long the loop ran.
     Replaced the brittle string match with
     `jq -e '.value.ready == true'` and reverted the timeout to a sane 60s
     (30×2s is now more than enough — the real grid is ready in ~2s).
     Not yet re-verified against a real run — see Deviations.

### Verified

- `actionlint .github/workflows/ci.yml`: clean after both fixes.
- Real run `python-checks`, `php-unit`, `backend-integration` jobs: passed
  unmodified on both real GitHub Actions executions (runs
  [31249835326](https://github.com/junomarx/bsc-thesis-icd10/actions/runs/31249835326),
  [31253568661](https://github.com/junomarx/bsc-thesis-icd10/actions/runs/31253568661)) —
  the workflow syntax, service-container MySQL setup, and Composer/PHPUnit
  steps all work as designed on real infrastructure, not just under `act`.
- Root cause confirmed directly, not inferred: local
  `docker run --platform linux/amd64 selenium/standalone-chrome:latest` +
  `curl 127.0.0.1:4444/status` reproduces the exact pretty-printed
  `"ready": true` response shape that broke the old `grep` pattern.

### Verified (third run, after the `jq` fix)

- Run [31257358358](https://github.com/junomarx/bsc-thesis-icd10/actions/runs/31257358358):
  all five jobs passed — `python-checks`, `php-unit`, `backend-integration`,
  `e2e` (including the Selenium-grid wait step), and `publish-images`. This
  closes the last open deviation from the two entries above: the CI
  workflow and the self-contained bundle are now verified end to end on
  real GitHub Actions infrastructure, not just locally and under `act`.
  GHCR packages remain private by default under a personal account — no
  action needed unless anonymous `docker pull` is wanted, at which point
  it's a one-time visibility change in GitHub's package settings.

## 2026-08-08 — Self-contained publishable bundle + CI publish job

Project owner's follow-up request: publish the whole project as something
pullable in one shot ("dependencies, code, tests etc"), tying into the CI
work above. Presented with a choice between one literal all-in-one
container versus a Compose-orchestrated bundle of several images, the
project owner chose the latter (`AskUserQuestion`, this session) — full
rationale in `docs/DEVELOPMENT_DOCUMENTATION.md` §10.6.

### Added

- `Dockerfile`: new `dev` build target (`vendor-dev` stage keeps Composer
  dev dependencies instead of `--no-dev`; `dev` stage adds `app/tests/`,
  `phpunit.xml`, and — see Fixed, below — the one oracle CSV the test
  harness needs). Reordered so `runtime` stays the *last* stage (bare
  `docker build .` must keep defaulting to the lean image).
- `docker-compose.yml` (repo root): the publishable bundle — `db` +
  `bootstrap` + `app` by default; `selenium` + `test` behind a `test`
  Compose profile. Every service sets both `image:` (a `ghcr.io/...` tag)
  and `build:` (dual-mode: `docker compose build` locally, `docker compose
  pull` once published). `app`/`test` depend on `bootstrap` via
  `condition: service_completed_successfully`, not just `db: service_healthy`
  — needed because, unlike the deployment stack, this bundle is meant to
  come up correctly ordered from *one* `docker compose up`, not a sequence
  of separate CLI commands.
- `.github/workflows/ci.yml`: fifth job, `publish-images` — builds and
  pushes the three images `docker-compose.yml` references to GHCR using
  the automatic `GITHUB_TOKEN`, gated on the other four jobs passing and on
  `push` to `main`.

### Fixed

- `app/tests/Integration/ReferenceResponseTest.php`'s oracle-CSV path
  (`dirname(__DIR__, 3) . '/prototype_baseline_0_1/verification/...'`)
  resolves correctly on the host checkout but pointed at nothing inside the
  container (no sibling `prototype_baseline_0_1/` directory exists in any
  image) — found by actually running the bundled test suite, not by
  inspection. Fixed by copying only that one CSV into the `dev` image at
  the equivalent path, rather than changing the test's path logic; the
  Python data pipeline itself stays out of every `app` image.
- `.dockerignore`: added a narrow `!`-negation so that one CSV survives the
  blanket `prototype_baseline_0_1/` exclusion above it.
- `.github/workflows/ci.yml`: three new step names contained a bare
  `docker-compose.yml: app`-style colon inside an unquoted YAML scalar,
  which parses as a nested mapping key (`actionlint`/schema validation both
  flagged it immediately). Reworded to avoid the colon. Also fixed an
  unrelated pre-existing `shellcheck` warning (unused loop variable in the
  e2e job's Selenium-grid wait loop) while `actionlint` was already running.
- Removed an orphaned `act` container (`tail -f /dev/null`, no driving `act`
  process left on the host) left over from the previous entry's validation
  run — safe, confirmed nothing was still using it before removing it.

### Verified

- `docker build .` (no `--target`) and `docker build --target dev .`: both
  succeed. `docker run --rm <dev image> php vendor/bin/phpunit --testsuite unit`:
  **49/49** inside the freshly built image.
- Full bundle, isolated test Compose project, fully containerized (no host
  PHP/Composer/Node/Python): `docker compose up -d --wait app` brought up
  `db → bootstrap → app` correctly ordered on the first try; a second
  `up` re-ran `bootstrap` idempotently (`no_op`, matching `load_mysql.py`'s
  design); `docker compose --profile test run --rm test` ran the *complete*
  suite end-to-end against the bundle's own Docker network —
  **85/85 tests, 492 assertions**, including all 3 `TEST-E2E-01` browser
  variants and both `TEST-E2E-02` checks via a `seleniarm/standalone-chromium`
  container reached over the bundle's internal network (`http://app`,
  `http://selenium:4444` — no `host.docker.internal` needed this time,
  unlike the split dev-machine setup in `app/tests/E2E/README.md`).
- `.github/workflows/ci.yml`: `python3 -c "import yaml; yaml.safe_load(...)"`
  clean; `actionlint` (installed via `brew install actionlint`) clean after
  the fixes above.

### Deviations

- **Not yet independently verified**: the `publish-images` job's actual
  push to GHCR. The real `GITHUB_TOKEN` only exists inside a real GitHub
  Actions run, so this can only be proven by pushing this work and letting
  it run for real — not done as part of writing it (pushing is the
  project owner's call). Every individual build command the job runs was
  verified locally (see Verified, above); only the authenticate-and-push
  step is unverified.
- GHCR packages are private by default under a personal-account owner;
  making one public for anonymous `docker pull` (no login required) is a
  one-time step in that package's GitHub settings after the first
  successful publish. Not done yet since nothing has been published yet.

## 2026-08-08 — CI: closed the "no automated regression" gap

Project owner's instruction, following the test-category status check
(unit/integration done, system/system-integration/regression identified as
gaps). Closes the "No CI" item in `HANDOFF.md` §5.

### Added

- `.github/workflows/ci.yml`: four independent jobs on every push to `main`
  and every PR:
  - `python-checks` — `TEST-DAT-01` reproducibility + `test_runtime_contract.py`, no DB.
  - `php-unit` — the full `tests/Unit/` suite (`TEST-MAP/GATE/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-01`), no DB.
  - `backend-integration` — MySQL as a GitHub Actions service container; applies the schema, loads `PROTOBASE-0.2`, runs `test_mysql_persistence.py`, then `tests/Integration/` (`TEST-API-01`, `TEST-RC-01`, `TEST-DET-01`, `TEST-ARC-01`).
  - `e2e` — builds the real Docker images, brings up the full `db`→`bootstrap`→`app` stack via the actual `prototype_stack/compose.yaml`, starts Selenium (official `selenium/standalone-chrome` — GitHub-hosted runners are amd64), and runs `tests/E2E/` (`TEST-E2E-01`, `TEST-E2E-02`) against it, then tears everything down (`if: always()`).
- `app/tests/E2E/docker-compose.yml`: `image` is now `${SELENIUM_IMAGE:-seleniarm/standalone-chromium:latest}` so the same file serves local arm64 development and the CI job's amd64 override, rather than hardcoding one architecture.

### Fixed

- `prototype_stack/compose.yaml`: the `app` service's `build:` had no
  `target:`. This was harmless while `Dockerfile` had a single deployable
  stage, but is no longer harmless now that `Dockerfile` has grown a `dev`
  target (self-contained image with test files, for a separate publishing
  use case — see the Dockerfile's own comments) as its *last* stage — Docker
  builds the last stage by default when no target is given, so
  `docker compose build app` was silently about to start building the
  heavier `dev` image instead of the lean `runtime` one for actual
  deployment, contradicting the Dockerfile's own comment that `runtime` is
  what gets deployed. Added `target: runtime`. Found by validating the CI
  workflow, not by inspection — a concrete example of why the e2e job's
  "build the real images the real way" step earns its keep.

### Verified

Using `act` (nektos/act, installed locally) to actually *execute* each job
against the local Docker daemon, not just parse the YAML:

- `python-checks`: **passed in full** — identical checksums/digest to every
  prior run.
- `php-unit`: **passed in full** — 49/49, 81 assertions.
- `backend-integration`: **passed in full**, including the MySQL service
  container, schema apply, baseline load, and both test suites — 31/31 PHP
  integration assertions (383 assertions).
- `e2e`: verified through image build → MySQL start → bootstrap (schema
  apply + load, exact matching canonical digest `226a48ba...`) → app
  container start/healthy → `curl /api/health` succeeding. The one step
  that could not be executed on this specific development machine is
  pulling the *official* `selenium/standalone-chrome` image, which has no
  `linux/arm64` manifest at all (confirmed: `no matching manifest for
  linux/arm64/v8`) — a hardware/architecture limitation of dry-running an
  amd64-targeted CI step on Apple Silicon via `act`, not a workflow defect.
  The identical test code, identical `host.docker.internal` networking
  pattern, and identical PHPUnit invocation were already proven to pass
  5/5 earlier the same day using the arm64-equivalent
  `seleniarm/standalone-chromium` image locally. Confidence in the e2e job
  is therefore high but not absolute until it actually runs on a real
  (amd64) GitHub Actions runner — check for a `CI` run against the commit
  that introduces this workflow before treating it as fully proven.

### Deviations

- Two dry-run failures during validation were local-environment artifacts,
  not workflow bugs, and are recorded here so they aren't mistaken for a
  real defect if rediscovered: (1) a stale `icd-learning-prototype_mysql_data`
  Docker volume from earlier manual testing sessions had different
  baked-in MySQL credentials than the CI job's fresh `.env` — MySQL only
  applies credentials on first init of an empty data directory, so
  recreating the container against an old volume doesn't reset them. Fixed
  by removing the volume (safe — fully reproducible from the versioned
  baseline). This cannot occur on an actual GitHub Actions runner, which
  never has a pre-existing volume. (2) A transient "port 8080 already
  allocated" failure on a retry, gone on the next attempt with nothing
  found holding the port — not investigated further since it didn't
  recur.
- Installing `act` itself (`brew install act`, needed to dry-run this
  workflow locally) triggered Homebrew to autoremove several libraries the
  host PHP installation was linked against (`tidy-html5`, `oniguruma`,
  `libzip`, `openldap`, `apr`, `rtmpdump`, and transitively `libpq`/
  `argon2`/`capstone`), breaking every local PHP invocation (`dyld`
  "Library not loaded" errors) until fixed. Declined to `brew reinstall
  php`, since the straightforward fix path requires trusting a third-party
  tap (`shivammathur/php`) not already in use on this machine — a decision
  about the host environment, not this repository, left for the project
  owner rather than made unilaterally. Fixed surgically instead:
  reinstalled the removed packages, used `otool -L` to walk every
  `@loader_path`-relative reference in the `php` binary and each loaded
  extension to find what was still missing (`libpq`, `argon2`, `capstone`),
  installed those, then confirmed nothing else was missing with a second
  exhaustive sweep. Verified fixed by running
  `php vendor/bin/phpunit --testsuite unit` to a clean 49/49 before
  continuing. Host-machine-only; nothing in the repository was at risk, and
  no CI job depends on a local `act` installation.

## 2026-08-08 — Control-document staleness cleanup (found by independent verification workflow)

Before writing a handoff document, ran a 4-agent read-only verification
workflow (git/docker/test/docs state) specifically so the handoff would
reflect confirmed facts rather than recalled claims — the project has
already been burned once by an overstated handoff
(`CODEX_HANDOFF_CORRECTION_PROMPT.md`). The docs-consistency agent found a
real gap: the previous day's pre-freeze coverage review (`CASEBASE-0.1`→`0.2`,
`RCBASE-0.1`→`0.2`, `CASEPLAN-0.1`→`0.2`) updated `chapter3_requirements_catalogue.md`,
`chapter3_reference_case_coverage_plan.md`, and everything under `docs/`, but
missed five other pre-existing artefacts that were never revisited.

### Fixed

- `prototype_baseline_0_1/baseline_manifest.json`: `case_plan_id` still read
  `CASEPLAN-0.1` while its sibling fields in the *same* prior edit
  (`prototype_baseline_id`, `case_baseline_id`, `reference_response_baseline_id`)
  had already been bumped to `0.2` — a live data inconsistency, not just
  stale prose.
- `chapter3_input_source_baseline_register.md` §10: two checklist items
  (four-field whitelist reproducibility; the pre-freeze coverage review
  itself) were still shown as open `[ ]` TODOs even though both were
  completed on 2026-08-07 — marked `[x]` with a dated note each.
- `chapter3_test_catalogue.md`, `chapter3_data_model_and_interaction_baseline.md`,
  `chapter3_rule_catalogue.md`, `chapter3_domain_error_taxonomy_and_classification_baseline.md`:
  header cross-references and body prose naming `CASEBASE-0.1`/`RCBASE-0.1`/`CASEPLAN-0.1`
  and the old four-case/14-response counts in current tense, updated to
  `0.2`/eight-case/18-response with historical framing preserved where the
  statement was about the original baseline specifically (e.g. "`CASE-004`
  is fixed as `verification_only` in `CASEBASE-0.1`" stays true as history;
  it now also notes `CASE-008`'s later addition under `CASEBASE-0.2`).
- `chapter3_reference_case_coverage_plan.md`: three residual lines the
  original coverage-review edit missed (header's "Downstream data/interaction
  model" line, one present-tense `CASEPLAN-0.1` sentence, one coverage-table
  column label).
- `prototype_baseline_0_1/README.md`: title, status block, pipeline diagram,
  and every count updated — this file was still written entirely in
  pre-adoption tense ("exploratory/candidate... does not establish that the
  pipeline has been adopted, executed, or verified"), which has been false
  since 2026-08-07's real MySQL execution; also pointed its file listing at
  the current `_0_2` CSVs instead of the superseded `_0_1` ones.
- `prototype_baseline_0_1/mysql_schema.sql`: one comment naming `RCBASE-0.1`.

### Deviations

- Acknowledged, not fixed: several `chapter3_*.md` documents (most visibly
  `chapter3_test_catalogue.md` and `chapter3_rule_catalogue.md`) are written
  throughout in a broader pre-implementation tense — "candidate", "intended
  to", "the handoff does not inherit an exploratory execution verdict" —
  dating from before the PHP application existed at all. This cleanup fixed
  every place that tense produced an actively *wrong* fact (a stale ID or
  count); it did not rewrite the surrounding prose tone throughout those
  documents, which would be a much larger editorial pass than this specific
  fix warranted. Flagged explicitly in the next handoff document rather than
  left for the next reader to discover independently.
- The versioned `_0_1` data files themselves (`data/cases_0_1.csv`,
  `data/case_code_domain_0_1.csv`, `verification/reference_responses_0_1.csv`)
  were deliberately left untouched — per this project's own immutable-baseline
  design, a superseded version's file must keep its original tag forever,
  not be rewritten to match the new one.

### Verified

- Independent re-verification (same workflow, before any fix was applied):
  git state (3 commits, working tree matches expected in-progress files),
  docker/stack state (app + Selenium containers still up from 2026-08-07,
  health checks 200), and test re-execution (Python structural 5/5, PHP unit
  49/49, PHP e2e 5/5 against the live stack; PHP integration and Python
  persistence correctly skipped — the compose `db` service was never
  published to the host, matching its by-design internal-only networking,
  not a regression).
- Post-fix: `grep` for `CASEBASE-0.1`/`RCBASE-0.1`/`CASEPLAN-0.1` across every
  `chapter3_*.md` file confirms every remaining occurrence is now explicitly
  marked superseded/historical — none left in unqualified current tense.

## 2026-08-07 — Requirements traceability audit (`REQ-TRC-01`)

Systematic pass over all 31 `REQ-*` entries in `chapter3_requirements_catalogue.md`
(catalogue version 0.5), checking each against actual current evidence
rather than planned intent — the last actionable item from the brief's §25
"definition of done" before the freeze step itself.

### Added

- `docs/REQUIREMENTS_TRACEABILITY.md`: full audit table, organized by the
  catalogue's own section groupings, each requirement marked Verified
  (with a concrete pointer), Deferred to freeze (genuinely not due yet —
  `REQ-CFG-01`, the final-report half of `REQ-VER-05`), or Thesis-text scope
  (`REQ-SCP-03`, the main-text half of `REQ-VER-07`).

### Fixed

- `chapter3_requirements_catalogue.md` still referenced the superseded
  `CASEPLAN-0.1`/`CASEBASE-0.1`/`RCBASE-0.1` and "four base cases and
  fourteen atomic response variants" in its header and §12 freeze criteria
  — left over from before the pre-freeze coverage review. Updated to
  `CASEPLAN-0.2`/`CASEBASE-0.2`/`RCBASE-0.2` and "eight/eighteen." This is a
  cross-reference correction, not a change to any requirement's acceptance
  criterion, so the catalogue version (0.5) was not incremented for it —
  consistent with how `chapter3_reference_case_coverage_plan.md`'s *own*
  version was bumped when *its* content actually changed, versus this being
  only a stale pointer to that already-recorded change.

### Verified

- Result of the audit: zero undeclared gaps. Every `Accepted`/`Scope
  constraint` requirement has either verified evidence or a correctly
  deferred/out-of-repository-scope status — see
  `docs/REQUIREMENTS_TRACEABILITY.md` §3 for the full disposition.

## 2026-08-07 — Automated Selenium end-to-end tests: `TEST-E2E-01`/`TEST-E2E-02`

Closed the last open item from the implementation "definition of done"
(`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §25): `TEST-E2E-01`/`02` previously
existed only as a one-off manual Playwright walkthrough script (deleted
after use, not committed). Replaced with a real, committed, repeatable
Selenium suite, per the project owner's standing tooling decision.

### Added

- `app/tests/E2E/SeleniumTestCase.php`: base test case managing the
  `RemoteWebDriver` lifecycle (`php-webdriver/webdriver` ^1.15, installed as
  a composer dev dependency) plus shared page-interaction helpers
  (open case list, open a case, search+select+submit a code, read the
  result heading/explanation/improvement banner) and a plain-HTTP helper
  for the one non-browser assertion `TEST-E2E-02` needs.
- `app/tests/E2E/LearnerWorkflowTest.php` (`TEST-E2E-01`): drives a real
  browser through `CASE-001` for all three feedback classes
  (`J44.02`→correct, `J44.09`→suboptimal, `J44.01`→incorrect), asserting
  the rendered heading, explanation fragment, and improvement banner for
  each.
- `app/tests/E2E/VerificationOnlyCaseVisibilityTest.php` (`TEST-E2E-02`):
  confirms `CASE-004`/`CASE-008` never appear in the rendered case list
  (the frontend has no client-side router, so the case list is the only
  navigation surface a learner's browser has — confirming absence there is
  a complete check), plus a direct-HTTP confirmation that both remain
  reachable through the evaluation endpoint (classification correctness
  itself stays the job of `TEST-RC-01`, not re-asserted here).
- `app/tests/E2E/docker-compose.yml`: standalone Selenium+browser container,
  deliberately kept out of `prototype_stack/compose.yaml` since it is test
  tooling, not part of the deployed learner-facing stack.
- `app/tests/E2E/README.md`: how to run the suite, including the
  `ICD_E2E_SELENIUM_URL` / `ICD_E2E_BROWSER_BASE_URL` / `ICD_E2E_BASE_URL`
  environment contract.
- New `e2e` PHPUnit testsuite in `app/phpunit.xml` (alongside `unit` and
  `integration`).

### Deviations / findings

- `selenium/standalone-chrome` (the official image) has no `linux/arm64`
  build — Chrome for Testing isn't built for arm64 Linux, so it cannot run
  under Docker Desktop on Apple Silicon. Used
  `seleniarm/standalone-chromium` (community arm64 build, Chromium instead
  of Chrome) instead; `docker-compose.yml` documents switching back to the
  official image on amd64 hosts.
- One test-authoring bug found and fixed by running against the real app
  (not an app bug): `LearnerWorkflowTest` initially expected no improvement
  banner for the `incorrect`/`J44.01` case, but `RULE-EVID-01` correctly
  supplies an improvement code (`J44.02`) as corrective direction even for
  a hard `incorrect` result (matches `chapter3_rule_catalogue.md`
  `RULE-EVID-01` and the earlier `RC-001-03` behaviour already verified
  under `TEST-RC-01`) — the test's expectation was wrong, not the app;
  fixed the data provider.

### Verified

- Full suite, one run, against the live `prototype_stack` Compose stack
  (`mysql:latest`/26.7.0 + app) and a `seleniarm/standalone-chromium`
  container: `TEST-E2E-01` **3/3** submission variants, `TEST-E2E-02`
  **2/2** (list-exclusion + endpoint-reachability). Combined with unit +
  integration in one run against a freshly loaded `PROTOBASE-0.2` baseline:
  **85/85**, 492 assertions.
- Networking verified explicitly before the test run: `docker exec
  e2e-selenium-1 curl http://host.docker.internal:8080/api/health` reached
  the app container from inside the Selenium container.

## 2026-08-07 — Pre-freeze coverage review: `CASEBASE-0.2`/`RCBASE-0.2`/`PROTOBASE-0.2`

Conducted the pre-freeze coverage review flagged in
`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §23.2 at the project owner's direction.
Decision: adopt the brief's own suggested expansion rather than declare the
original four-case suite sufficient. Full design rationale in
`docs/DEVELOPMENT_DOCUMENTATION.md` §10.3; full table updates in
`chapter3_reference_case_coverage_plan.md` §1.1/§4-§6.

### Added

- `CASE-005` (J44.0, FEV1 20% → suffix 0), `CASE-006` (J44.1, FEV1 exactly
  35% → suffix 1), `CASE-007` (J44.0, FEV1 exactly 70% → suffix 3), `CASE-008`
  (inpatient `Z01.6!` main diagnosis, exercising the *inpatient* branch of
  `RULE-STATUS-01` — `CASE-004` only covered the hospital-outpatient/LKF-scored
  branch). Each uses a minimal single-code response domain (the accepted/
  prohibited response only), matching `CASE-003`'s precedent, since the
  `DEPTH`/`EVID`/`SPEC` branches a full six-code family would re-trigger are
  already proven by `CASE-001`/`CASE-002`.
- `RC-005-01`..`RC-008-01`: one reference-response row per new case.
  `RCBASE-0.2` totals 18 rows (6 correct / 2 suboptimal / 10 incorrect),
  up from `RCBASE-0.1`'s 14 (3/2/9).
- `prototype_baseline_0_1/data/cases_0_2.csv`,
  `data/case_code_domain_0_2.csv`,
  `verification/reference_responses_0_2.csv` (new versioned files;
  `subset_0_1.csv` is untouched — no new catalogue code was needed).

### Changed

- `baseline_manifest.json`: `prototype_baseline_id` → `PROTOBASE-0.2`,
  `case_baseline_id` → `CASEBASE-0.2`, `reference_response_baseline_id` →
  `RCBASE-0.2`; added `supersedes`/`change_summary` fields.
  `subset_baseline_id`, `domain_baseline_id`, `rule_baseline_id`,
  `model_baseline_id` are unchanged.
- `scripts/runtime_data.py`: `RUNTIME_FILES` now points at
  `cases_0_2.csv`/`case_code_domain_0_2.csv`.
- `validate_baseline.py`: expected constants and file paths updated for 8
  cases / 18 relations / 2 verification-only cases (`CASE-004`, `CASE-008`).
- `tests/test_runtime_contract.py`: expected counts updated; canonical
  digest recomputed and hardcoded to
  `226a48ba7cb1df54b42efbb1fbeb499e4f0e7587fd3a24cb792648eb0363e877`.
- `tests/test_mysql_persistence.py`: expected row counts, acceptable sets,
  case-value rows, and the FK-violation fixture's `case_baseline_id` updated
  for `CASEBASE-0.2`.
- `Dockerfile.bootstrap`: `COPY` now references `cases_0_2.csv`/
  `case_code_domain_0_2.csv`.
- `app/tests/Integration/ReferenceResponseTest.php`: oracle path updated to
  `reference_responses_0_2.csv`.
- `chapter3_reference_case_coverage_plan.md`: header bumped to
  `CASEPLAN-0.2` (supersedes `CASEPLAN-0.1`); §1.1 (new), §4, §5, §6, §8, §9
  updated with the new cases/rows/coverage status. `CASE-001`-`CASE-004`
  and `RC-001-*`-`RC-004-*` content is unchanged, only re-labelled under the
  new baseline IDs.

### Verified

- Structural (Python, real frozen source): `validate_baseline.py` → PASS
  (8 cases, 18 relations, 6/2/10 class distribution).
  `test_runtime_contract.py`: **5/5**, including the recomputed digest.
- Persistence (fresh MySQL, both `8.4.8` ad hoc and later `mysql:latest`
  via the actual compose `bootstrap` service — see the MySQL-relaxation
  entry below): schema apply → first load `inserted` → re-import `no_op` →
  `test_mysql_persistence.py`: **5/5**, twice.
- PHP: full suite **80/80** (up from 76 — `TEST-RC-01`'s data provider now
  yields 18 cases instead of 14), 464 assertions.
- Live API/Compose stack: all four new cases spot-checked through the
  actual running container —
  `POST /api/cases/CASE-005/evaluate {"submitted_code":"J44.00"}` →
  `correct`; `CASE-006`+`J44.11` → `correct`; `CASE-007`+`J44.03` →
  `correct`; `CASE-008`+`Z01.6` → `incorrect`/`RULE-STATUS-01`.
  `GET /api/cases` confirmed excluding both `CASE-004` and `CASE-008`;
  `GET /api/cases/CASE-008` confirmed `404`.

## 2026-08-07 — MySQL version pin relaxed from an exact patch to unpinned-minor

Project owner's instruction: don't treat the MySQL patch version as
something that needs freezing this early — "we can pull in the latest
version and it should not cause any blockers." Full rationale in
`docs/DEVELOPMENT_DOCUMENTATION.md` §10.1.

### Changed

- `prototype_stack/compose.yaml`: `db` image `mysql:8.4.8` → `mysql:latest`.
- `prototype_baseline_0_1/tests/test_mysql_persistence.py`:
  `test_server_and_runtime_schema` no longer asserts an exact version
  prefix; it now asserts a major-version floor
  (`assertGreaterEqual(major_version, 8, ...)`), the only real prerequisite
  (CHECK constraint support, MySQL ≥ 8.0.16).
- `prototype_stack/README.md`: services diagram label updated from
  "MySQL 8.4.8" to "MySQL (latest)".

### Deviations / findings

- **`mysql:latest` currently resolves to MySQL 26.7.0** — a materially
  different release line from `8.4.8`. Reusing the existing `mysql_data`
  named volume across that jump failed outright: MySQL's own server refused
  to open the data directory (`Invalid MySQL server upgrade: Cannot upgrade
  from 80408 to 260700`), and Compose reported the container unhealthy. Fix
  was `docker compose down -v` to drop the volume, then a fresh `up`
  succeeded. This is recorded as a concrete, discovered cost of the
  unpinned-tag decision, not treated as a bug to route around — see
  `docs/IMPLEMENTATION_SPECIFICATION.md` §6.3 for the operational
  consequence going forward (a `mysql:latest` release bump will require
  the same volume drop).

### Verified

- Full Compose stack (db → bootstrap → app) brought up successfully against
  `mysql:latest` (observed as 26.7.0) on a fresh volume; bootstrap's own
  `test_runtime_contract.py` + `test_mysql_persistence.py`: **10/10**
  against that version. (Combined with the coverage-review entry above,
  since both were verified in the same stack run.)

## 2026-08-07 — `Dockerfile` moved to the repository root for `stack.sh --sync` compatibility

Project owner's stated intention: configure `prototype_stack/config/git-source.conf`
to sync *this repository itself* as the application source via
`stack.sh sync`. Full rationale in `docs/DEVELOPMENT_DOCUMENTATION.md` §10.2.

### Changed

- Moved `app/Dockerfile` → `/Dockerfile` and `app/.dockerignore` →
  `/.dockerignore`; every `COPY` instruction rewritten to reference
  `app/...` paths relative to the new repo-root build context.
  `prototype_stack/compose.yaml` required no change — `dockerfile:
  Dockerfile` under `context: ${APP_SOURCE_DIR:-.runtime/app}` already
  expected a Dockerfile at the build context's root; only the Dockerfile's
  physical location was wrong for that expectation.
- Local (non-`stack.sh`) verification now exports `APP_SOURCE_DIR=..` from
  `prototype_stack/` (previously `../app`).

### Verified

- `docker compose build bootstrap app` succeeded against the new root-context
  Dockerfile (all layers either built fresh or served from cache correctly).
- Full stack `up` (db → bootstrap → app) succeeded end to end on the
  relocated Dockerfile — see the combined verification note in the
  MySQL-relaxation entry above; all three changes were verified together in
  one stack run.

## 2026-08-07 — Application layer stood up: data pipeline adoption, PHP evaluator/API, React frontend, Docker Compose

Continuation of implementation work per `CODEX_VSCODE_CONTINUATION_INSTRUCTION.md`
and `CODEX_HANDOFF_CORRECTION_PROMPT.md`, starting from a read-only inventory
that found only `README.md` + `development_handoff/` in the repository —
i.e. no prior application-layer code existed anywhere in this project.

### Added — data pipeline adoption

- Promoted `development_handoff/control/*.md`,
  `development_handoff/candidate/prototype_baseline_0_1/`, and
  `development_handoff/candidate/prototype_stack/` to the repository root,
  matching the canonical layout in `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §26.
  `development_handoff/` itself was left in place, unmodified.

### Added — PHP application (`app/`)

- Rule engine: `RuleGate`, `RuleMap`, `RuleStatus`, `RuleDepth`, `RuleEvid`,
  `RuleSpec`, `RuleCorrect`, `Precedence`, orchestrated by `Evaluator`
  (`RULE-GATE/MAP/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-01`).
- Value objects: `CaseFacts`, `CatalogueRecord`, `BaselineIdentity`,
  `GateResult`, `MapResult`, `EvaluationResult`.
- Repositories (`BaselineRepository`, `CatalogueRepository`,
  `CaseRepository`) over raw PDO against the existing four-table schema.
- HTTP layer: `public/index.php` front controller, `CaseController`,
  `EvaluationController`, implementing `GET /api/cases`,
  `GET /api/cases/{id}`, `POST /api/cases/{id}/evaluate` per
  `MODELBASE-0.1` §7 (`REQ-INT-01`, `REQ-RUL-05`).
- `SpecificationGapException` for the "eligible but no terminal rule"
  branch, mapped to HTTP 500 — never silently `incorrect`.

### Added — React frontend (`app/frontend/`)

- Vite + React 19 single-page app: case list (learner-visible only) → case
  detail (facts + searchable code select) → result view (class,
  explanation, improvement target). No client-side router.
- Intended-use disclaimer rendered on the landing view (`REQ-SCP-02`).

### Added — containerization

- `app/Dockerfile`: multi-stage build (Node 22 build stage → Composer
  vendor stage → `php:8.4-apache` runtime), per brief §17 (no permanent
  Node service).
- `app/docker/apache-vhost.conf` (DocumentRoot=`public/`, `.htaccess`
  routes `/api/*` to the front controller).

### Verified

- **Data pipeline (Python, real frozen source):** SHA-256 of
  `DIAGLIST2026.xlsx` and all five contextual/core PDFs/xlsx matched the
  source register exactly. `prepare_subset.py --check-existing` and
  `validate_baseline.py`: **PASS**, output digest
  `2cf8f44b...c9f3122` matches the manifest. `test_runtime_contract.py`:
  **5/5**, canonical digest `ca24056...894aa` matches the manifest.
- **MySQL persistence (`mysql:8.4.8` in Docker, not host's 9.1):** schema
  apply, first load (`inserted`), identical re-import (`no_op`),
  `test_mysql_persistence.py`: **5/5**. Re-run a second time end-to-end
  through the actual `bootstrap` service inside `prototype_stack/compose.yaml`
  against a freshly created compose-managed database: schema apply →
  `inserted` → re-run → `no_op` → `test_runtime_contract.py` +
  `test_mysql_persistence.py`: **10/10**.
- **PHP test suite:** 76/76 (49 unit + 27 integration, 420 assertions),
  covering `TEST-MAP/GATE/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-01`,
  `TEST-API-01`, `TEST-DET-01`, `TEST-ARC-01`, and all 14 `RC-*` rows via
  `TEST-RC-01`.
- **Docker Compose, executed for the first time in this project:**
  `docker build` for `bootstrap` and `app` images succeeded; full
  `up -d --wait db` → `run --rm bootstrap` → `up -d --wait app` sequence
  succeeded; app reachable on the published port with a healthy DB.
- **Browser walkthrough (Playwright, one-off script — see Deviations):**
  case list correctly excludes `CASE-004`; submitting `J44.02`/`J44.09`/`J44.01`
  against `CASE-001` renders Correct/Suboptimal/Incorrect respectively with
  the expected explanation text and (for suboptimal) improvement code;
  zero browser console errors. Run twice — once against the PHP built-in
  dev server, once against the actual Docker Compose stack on port 8080 —
  with identical results both times.
- `CASE-004` confirmed excluded from `GET /api/cases` and
  `GET /api/cases/CASE-004` (404), while
  `POST /api/cases/CASE-004/evaluate` still classifies correctly
  (`incorrect` / `RULE-STATUS-01`) — `TEST-E2E-02`'s boundary, checked
  against both the dev server and the Compose stack.

### Deviations

- MySQL: used a Docker `mysql:8.4.8` container instead of the host's
  Homebrew MySQL 9.1.0, to keep the persistence tests' explicit version
  assertion meaningful.
- `prototype_stack/stack.sh`'s git-sync workflow (`sync`/`up`/`doctor`)
  assumes the app lives in a separate Git repository and explicitly rejects
  an `APP_SOURCE_DIR` containing `..`. Since the app was built inside this
  same repository, `docker compose` was invoked directly with
  `APP_SOURCE_DIR=../app`, bypassing `stack.sh`'s sync commands. `stack.sh`
  was not modified.
- An unplanned commit (`e4463ad`, "commit meta documents related to impl")
  was found already pushed to `origin/main`, containing a `.venv/`
  directory (~6,500 files) swept in before a `.gitignore` existed. The
  repository owner confirmed this commit was theirs; no history rewrite was
  performed. A `.gitignore` was added going forward
  (`.venv/`, `__pycache__/`, `vendor/`, `node_modules/`, generated frontend
  build output under `app/public/`).
- Browser-driven verification used Playwright rather than the project's
  eventual standard. **Superseded same day** — see the following entry.

## 2026-08-07 — Browser-testing tooling: Playwright retired in favour of Selenium

### Changed

- Project owner specified Selenium as the standing tool for all future
  system/system-integration/regression browser-driven tests, replacing the
  Playwright used for the initial `TEST-E2E-01`/`02` walkthrough above.

### Removed

- `playwright` devDependency removed from `app/frontend/package.json`
  (`npm uninstall playwright`) and its downloaded Chromium browser cache
  deleted (`~/Library/Caches/ms-playwright`, ~554 MB), since it was not wired
  into any committed test — only into an ad hoc verification script that was
  itself deleted after use.

### Not yet done

- No Selenium-based test exists yet. The next browser-driven test (whenever
  written) should use Selenium from the start rather than reintroducing
  Playwright.

## 2026-08-07 — Documentation set established

### Added

- `docs/README.md`, `docs/DEVELOPMENT_DOCUMENTATION.md`,
  `docs/IMPLEMENTATION_SPECIFICATION.md`, and this changelog, at the
  project owner's request, to keep a DSR-thesis-facing account of
  decisions/rationale (`DEVELOPMENT_DOCUMENTATION.md`), a precise as-built
  reference (`IMPLEMENTATION_SPECIFICATION.md`), and a running log of
  changes (this file) in sync with ongoing development.
- `CLAUDE.md` at the repository root, recording the standing rule for when
  each of the three documents above must be updated.
