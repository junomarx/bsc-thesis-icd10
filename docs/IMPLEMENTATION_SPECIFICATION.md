# Implementation specification

**Scope:** precise, as-built description of the software in `app/` and its
relationship to `prototype_baseline_0_1/` and `prototype_stack/`. This is a
reference document — if the code and this document disagree, the code is
correct and this document is stale; fix the document (see
[CHANGELOG.md](CHANGELOG.md) discipline in `CLAUDE.md`).
**Rationale for these choices:** see [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md).
**Rule/case semantics authority:** `chapter3_rule_catalogue.md` (`RULEBASE-0.1`)
and `chapter3_data_model_and_interaction_baseline.md` (`MODELBASE-0.1`). This
document describes *how* those semantics are realized in code, not their
justification.

## 1. Repository layout

```text
Dockerfile                      multi-stage build: node build → composer install → php:8.4-apache runtime
                                 lives at the repo root (not app/) so prototype_stack/stack.sh's
                                 `sync` can pull this repository as the app source and find it at
                                 the checkout root — see docs/DEVELOPMENT_DOCUMENTATION.md §10.2.
                                 Two build targets: `runtime` (default, lean, deployed by
                                 prototype_stack/compose.yaml) and `dev` (adds dev Composer deps +
                                 app/tests/, published as the :dev image tag — see §10.6)
.dockerignore                   also at repo root, matching the Dockerfile's build context
docker-compose.yml               self-contained publishable bundle (db+bootstrap+app by default,
                                 +selenium+test behind a `test` Compose profile) — see §6.5/§10.6.
                                 Distinct from prototype_stack/compose.yaml (the stack.sh-managed
                                 deployment scaffold)
.github/workflows/ci.yml         5 jobs: python-checks, php-unit, backend-integration, e2e,
                                 publish-images (builds+pushes the 3 images docker-compose.yml
                                 references to GHCR, gated on the other 4 passing, main only)
app/
  composer.json / composer.lock PHP dependencies (runtime: none beyond ext-pdo/ext-json; dev: phpunit/phpunit, php-webdriver/webdriver)
  phpunit.xml                   three suites: unit, integration, e2e
  router.php                    dev-only front controller for `php -S`; NOT copied into the Docker image
  docker/apache-vhost.conf       DocumentRoot=public, AllowOverride All, DirectoryIndex index.html index.php
  public/
    index.php                    the real front controller (also used by Apache + router.php)
    .htaccess                     rewrites /api/* to index.php; everything else served as a static file
    index.html, assets/*          built by `npm run build` in frontend/ (vite outDir: ../public)
  src/
    Config.php                   reads ICD_DB_* environment
    Db.php                        PDO factory
    Bootstrap.php                 wires repositories + evaluator against one PDO connection
    Model/
      BaselineIdentity.php        the single `prototype_baseline` row
      CaseFacts.php                one case's rule-relevant facts + response domain
      CatalogueRecord.php          one SUBSET-0.1 catalogue row
    Repository/
      BaselineRepository.php
      CatalogueRepository.php
      CaseRepository.php
    Rules/
      RuleGate.php, GateResult.php         RULE-GATE-01
      RuleMap.php, MapResult.php           RULE-MAP-01
      RuleStatus.php                        RULE-STATUS-01
      RuleDepth.php                         RULE-DEPTH-01
      RuleEvid.php                          RULE-EVID-01
      RuleSpec.php                          RULE-SPEC-01
      RuleCorrect.php                       RULE-CORRECT-01
      Precedence.php                        RULE-PREC-01 (policy extracted as a pure function)
    Evaluation/
      Evaluator.php                orchestrates the above in RULEBASE-0.1 §6 order
      EvaluationResult.php          terminal result value object
      SpecificationGapException.php thrown if an eligible relation reaches no terminal rule
    Http/
      ApiResult.php                 {status, body} — HTTP-independent controller return type
      JsonResponse.php               writes ApiResult to the actual HTTP response
      CaseController.php             GET /api/cases, GET /api/cases/{id}
      EvaluationController.php       POST /api/cases/{id}/evaluate
  tests/
    Support/Fixtures.php           test-only CaseFacts/CatalogueRecord builders
    Unit/*                          rule predicates in isolation, no DB
    Integration/*                   full stack against a live MySQL baseline
    E2E/
      SeleniumTestCase.php          RemoteWebDriver lifecycle + page-interaction helpers
      LearnerWorkflowTest.php       TEST-E2E-01: real browser, CASE-001, all three classes
      VerificationOnlyCaseVisibilityTest.php  TEST-E2E-02: CASE-004/008 nav exclusion
      docker-compose.yml            standalone Selenium+browser container (not in prototype_stack)
      README.md                     how to run: start app, start Selenium, run --testsuite e2e
  frontend/
    package.json, vite.config.js    React 19 + Vite 8; build output → ../public (emptyOutDir: false)
    src/
      main.jsx, App.jsx, App.css, index.css
      api.js                        fetch wrappers for the three endpoints

prototype_baseline_0_1/            adopted candidate data pipeline (Python) — see its own README.md
prototype_stack/                   Docker Compose scaffold (db, bootstrap, app services)
```

## 2. Data model

### 2.1 Physical schema

Defined in `prototype_baseline_0_1/mysql_schema.sql`; four tables, no
others. All are `InnoDB`, `utf8mb4_unicode_ci`.

| Table | Primary key | Notable columns | FK to |
|---|---|---|---|
| `prototype_baseline` | `prototype_baseline_id` | `model_baseline_id`, `rule_baseline_id`, `case_baseline_id`, `subset_baseline_id`, `catalogue_edition`, `diaglist_sha256` | — |
| `catalogue_code` | `(subset_baseline_id, code)` | `marker` (nullable, `!`), `designation`, `short_designation` | — |
| `case_definition` | `(case_baseline_id, case_id)` | `encounter_setting`, `diagnosis_role`, `inpatient_lkf_scored` (nullable bool), `copd_base_code` (nullable), `fev1_stable_pct_predicted` (nullable `DECIMAL(6,2)`), `intended_use` | `catalogue_code` (via `copd_base_code`) |
| `case_code_domain` | `(case_baseline_id, case_id, subset_baseline_id, code)` | `is_acceptable` (bool) | `case_definition`, `catalogue_code` |

CHECK constraints enforce: `encounter_setting IN ('inpatient','hospital_outpatient')`,
`diagnosis_role IN ('main','additional')`, `intended_use IN ('learner_visible','verification_only')`,
and — the one cross-field rule — `inpatient_lkf_scored` is `NULL` iff
`encounter_setting = 'inpatient'` (non-`NULL` iff `hospital_outpatient`).

**No table stores an expected classification, determining rule, criterion,
or any other verification-oracle field.** This is asserted by
`ArchitectureIsolationTest::testRuntimeSchemaHasNoExpectedOutputColumnsOrTables()`.

### 2.2 PHP value objects (`src/Model/`)

| Class | Fields | Built by |
|---|---|---|
| `BaselineIdentity` | mirrors `prototype_baseline` row 1:1 | `BaselineRepository::current()` |
| `CatalogueRecord` | `code`, `marker` (`?string`), `designation`, `shortDesignation` | `CatalogueRepository` |
| `CaseFacts` | `caseId`, `shortDescription`, `encounterSetting`, `diagnosisRole`, `inpatientLkfScored` (`?bool`), `copdBaseCode` (`?string`), `fev1StablePctPredicted` (`?float`), `responseDomain` (`array<string,bool>` code→is_acceptable), `intendedUse` | `CaseRepository` |

`CaseFacts` exposes `hasDefinedRelation(code)`, `isAcceptable(code)`, and
`isLearnerVisible()` as its only behaviour — it is otherwise a plain data
holder consumed by the `Rules/*` predicates.

## 3. Rule engine

### 3.1 Evaluation tuple and algorithm

`Evaluator::evaluate(CaseFacts $case, ?CatalogueRecord $record, string $submittedCode): EvaluationResult`
implements exactly the pseudocode in `chapter3_rule_catalogue.md` §6:

```text
gate = RuleGate::evaluate(case, record, submittedCode)
if not gate.eligible:  return notEvaluated(gate.reason)

map = RuleMap::evaluate(case)

hardMatches = []
if RuleStatus::matches(case, record):            hardMatches += RULE-STATUS-01
if RuleDepth::matches(case, submittedCode):      hardMatches += RULE-DEPTH-01
if RuleEvid::matches(case, submittedCode, map):  hardMatches += RULE-EVID-01

if hardMatches not empty:
    primary = Precedence::primaryHardRule(hardMatches)   # STATUS > DEPTH > EVID
    return classified('incorrect', primary, ..., matchedRules=hardMatches)

if RuleSpec::matches(case, submittedCode, map):
    return classified('suboptimal', RULE-SPEC-01, ...)

if RuleCorrect::matches(case, submittedCode):
    return classified('correct', RULE-CORRECT-01, ...)

throw SpecificationGapException(...)   # never `incorrect` by default
```

### 3.2 Per-rule contract

| Rule class | Static method signature | Predicate (see `RULEBASE-0.1` for full rationale) |
|---|---|---|
| `RuleGate` | `evaluate(CaseFacts, ?CatalogueRecord, string): GateResult` | `null` record → `outside_active_subset`; code not in case's domain → `undefined_case_relation`; COPD case with no FEV1, or `!`-marked main-diagnosis hospital-outpatient case with no LKF flag → `missing_required_case_fact`; else eligible |
| `RuleMap` | `evaluate(CaseFacts): MapResult` | Inpatient + 4-char COPD base (`J44.[0-9]`) + FEV1 present → suffix `0/1/2/3` by `<35 / <50 / <70 / else`, target = base+suffix; else not applicable |
| `RuleStatus` | `matches(CaseFacts, CatalogueRecord): bool` | `marker === '!' && role === 'main' && (inpatient \|\| (hospital_outpatient && lkfScored === true))` |
| `RuleDepth` | `matches(CaseFacts, string): bool` | `inpatient && submittedCode` matches `/^J44\.[0-9]$/` (a bare 4-char parent) |
| `RuleEvid` | `matches(CaseFacts, string, MapResult): bool` | 6-char code, same 4-char base as case, suffix ∈ `{0,1,2,3}`, and that suffix ≠ `MapResult::expectedSuffix` |
| `RuleSpec` | `matches(CaseFacts, string, MapResult): bool` | inpatient + main + `MapResult` applicable + `submittedCode === copdBaseCode . '9'` (and is one of the four source-listed warning forms) |
| `RuleCorrect` | `matches(CaseFacts, string): bool` | `case->isAcceptable(submittedCode)` |
| `Precedence` | `primaryHardRule(array): ?string`; `terminalClass(array $hard, bool $spec, bool $accept): ?string` | Fixed priority `STATUS > DEPTH > EVID`; terminal policy hard→incorrect, else spec→suboptimal, else accept→correct, else `null` (gap) |

### 3.3 `EvaluationResult` shape

```php
final class EvaluationResult {
    public readonly string $evaluationStatus;      // 'classified' | 'not_evaluated'
    public readonly ?string $classification;        // 'correct' | 'suboptimal' | 'incorrect' | null
    public readonly ?string $reason;                 // gate-failure reason, only when not_evaluated
    public readonly ?string $determiningRule;        // 'RULE-*'
    public readonly ?string $criterion;               // stable machine-readable key
    public readonly ?string $explanation;             // learner-readable sentence
    public readonly ?array  $explanationElements;      // structured payload, see §3.4
    public readonly ?array  $matchedRules;             // all matched RULE-* ids, e.g. every hard match
    public readonly ?string $improvementCode;          // ICD code, only for DEPTH/EVID/SPEC
}
```

### 3.4 Explanation element keys per determining rule

These exact keys are what `TEST-RC-01` checks for presence/non-emptiness,
taken verbatim from `RCBASE-0.2`'s `required_explanation_elements` column.

| Determining rule | Keys present in `explanation_elements` |
|---|---|
| `RULE-STATUS-01` | `submitted_code`, `marker`, `diagnosis_role`, `encounter_setting`, `restriction` |
| `RULE-DEPTH-01` | `submitted_code`, `required_coding_level`, *(also `mapped_target`, not required by the oracle but included as a corrective hint)* |
| `RULE-EVID-01` | `submitted_code`, `fev1_stable_pct_predicted`, `submitted_suffix_meaning`, `expected_suffix`, `expected_code` |
| `RULE-SPEC-01` | `submitted_code`, `fev1_stable_pct_predicted`, `expected_code`, `improvement_direction` |
| `RULE-CORRECT-01` | `accepted_code` |

## 4. HTTP API

### 4.1 `GET /api/health`

Handled directly in `public/index.php`, before the database is even
connected. `200 {"status":"ok"}` always. Used to distinguish "app container
up, PHP working" from "app container up, DB unreachable" during startup.

### 4.2 `GET /api/cases`

Returns every `intended_use = 'learner_visible'` case (i.e. excludes
`CASE-004` and `CASE-008`, the two verification-only status fixtures),
ordered by `case_id`.

```json
{
  "cases": [
    {
      "case_id": "CASE-001",
      "short_description": "Documented COPD with acute lower-respiratory infection; stable-phase FEV1 = 55% predicted",
      "encounter_setting": "inpatient",
      "diagnosis_role": "main",
      "inpatient_lkf_scored": null,
      "fev1_stable_pct_predicted": 55
    }
  ]
}
```

### 4.3 `GET /api/cases/{case_id}`

`404 {"error":"case_not_found"}` if the case does not exist **or** is not
`learner_visible` (this is the one place `CASE-004`/`CASE-008` are
deliberately treated as if they do not exist — see §4.4 below for why the
evaluate endpoint differs). On success, the same fields as the list
endpoint plus:

```json
{
  "...": "as above",
  "supported_codes": [
    {"code": "J44.0", "designation": "...", "short_designation": "..."},
    {"code": "J44.02", "designation": "...", "short_designation": "..."}
  ]
}
```

`is_acceptable` is never present anywhere in this response.

### 4.4 `POST /api/cases/{case_id}/evaluate`

Request body: `{"submitted_code": "<one code string>"}`.

| Condition | Status | Body |
|---|---|---|
| Case does not exist (any `intended_use`) | 404 | `{"error":"case_not_found"}` |
| `submitted_code` missing, non-string, blank/whitespace, or an array | 400 | `{"evaluation_status":"not_evaluated","classification":null,"reason":"malformed_input"}` |
| Gate fails (outside subset / undefined relation / missing fact) | 200 | `{"evaluation_status":"not_evaluated","classification":null,"reason":"<reason>"}` |
| Classified | 200 | see below |
| Eligible but no terminal rule matched (should not occur — §3.4 of the dev docs) | 500 | `{"error":"specification_gap","message":"..."}` |

Classified response shape:

```json
{
  "evaluation_status": "classified",
  "classification": "suboptimal",
  "criterion": "supported_specificity_not_used",
  "explanation": "J44.09 leaves the FEV1 severity unspecified. The case already states a stable-phase FEV1 of 55%, which supports the more specific code J44.02.",
  "explanation_elements": {
    "submitted_code": "J44.09",
    "fev1_stable_pct_predicted": 55,
    "expected_code": "J44.02",
    "improvement_direction": "Use J44.02 to reflect the documented FEV1 value."
  },
  "determining_rule": "RULE-SPEC-01",
  "matched_rules": ["RULE-SPEC-01"],
  "improvement_code": "J44.02"
}
```

**Note on `CASE-004`/`CASE-008`:** unlike the detail endpoint, `evaluate`
performs no `intended_use` filtering. This is intentional
(`ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §15/§18: "`CASE-004` must be
retrievable by the technical verification path but excluded from
learner-facing case navigation" — `CASE-008` was added under the same rule
by the pre-freeze coverage review, see
[DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) §10.3) — the
verification harness needs `POST /api/cases/CASE-004/evaluate` and
`POST /api/cases/CASE-008/evaluate` to work; only *navigating to* either
case is blocked.

## 5. Frontend

### 5.1 Component tree (`frontend/src/App.jsx`)

```text
App                       — owns all state; three mutually exclusive views
 ├─ CaseList               (state: cases, loadingCases, listError)
 ├─ CaseDetail             (state: activeCase; local: search text, selected radio)
 └─ ResultView             (state: result)
```

State transitions: `CaseList` → (click a case) → `CaseDetail` → (submit) →
`ResultView` → ("try another code" → back to `CaseDetail` with the same
case; "back to cases" → back to `CaseList`, clearing `activeCase`).

### 5.2 `api.js` — the only place `fetch()` is called

```js
listCases()                       // GET  /api/cases
getCase(caseId)                    // GET  /api/cases/{caseId}
evaluate(caseId, submittedCode)    // POST /api/cases/{caseId}/evaluate
```

Each returns `{status, body}` from the parsed JSON response; components
branch on `status`/`body` rather than on thrown exceptions for expected
(4xx) outcomes.

### 5.3 Build output contract

`vite.config.js` sets `build.outDir = '../public'` with `emptyOutDir: false`
— i.e. `npm run build` (run from `frontend/`) writes `index.html` and a
content-hashed `assets/` directory directly into `app/public/`, alongside
the hand-written `index.php` and `.htaccess`, without deleting them. In the
Docker build (`Dockerfile`, at the repo root), the frontend build runs in
its own clean stage (no pre-existing `public/` contents to worry about); the
runtime stage explicitly copies only `app/public/index.php` and
`app/public/.htaccess` by name from the source tree, then copies the
frontend build stage's output on top — so a stale local host build can
never leak into the image by accident.

## 6. Build, environment, and deployment contract

### 6.1 Environment variables (read by `src/Config.php`)

| Variable | Required | Default | Meaning |
|---|---|---|---|
| `ICD_DB_HOST` | no | `127.0.0.1` | MySQL host |
| `ICD_DB_PORT` | no | `3306` | MySQL port |
| `ICD_DB_NAME` | **yes** | — | Database name |
| `ICD_DB_USER` | **yes** | — | Database user |
| `ICD_DB_PASSWORD` | no | `''` | Database password |

### 6.2 `Dockerfile` stages (repo root)

| Stage | Base image | Produces |
|---|---|---|
| `frontend-build` | `node:22-alpine` | `/app/public/{index.html,assets/*}` (fresh, no host contamination) |
| `vendor` | `composer:2` | `/app/vendor` (`--no-dev --optimize-autoloader`) |
| `runtime` (final) | `php:8.4-apache` | `pdo_mysql` + `rewrite` enabled; `docker/apache-vhost.conf` installed; `vendor/`, `src/`, `public/index.php`, `public/.htaccess`, and the frontend build output all copied into `/var/www/html` |

The `Dockerfile` and its `.dockerignore` live at the repository root rather
than under `app/`, specifically so `prototype_stack/stack.sh`'s `sync`/`up`
commands — which require a `Dockerfile` at the synced checkout's root — work
unmodified once this whole repository is configured as the app source (see
§6.3 and [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) §10.2).
Every `COPY` instruction is written relative to the repo root
(`app/frontend/...`, `app/composer.json`, `app/src`, etc.).

### 6.3 `prototype_stack/compose.yaml` services

| Service | Role | Lifecycle |
|---|---|---|
| `db` | `mysql:latest`, named volume `mysql_data` | long-running; healthcheck via `mysqladmin ping`; deliberately unpinned below the major version (§8, [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) §10.1) |
| `bootstrap` | built from `prototype_baseline_0_1/Dockerfile.bootstrap` | one-shot (`restart: "no"`); applies schema on an empty DB, then runs the idempotent loader |
| `app` | built from `Dockerfile` (repo root), context `${APP_SOURCE_DIR:-.runtime/app}` | long-running; published on `${APP_HTTP_PORT:-8080}` |

**`mysql:latest` and the named volume — a real operational consequence, not
just a style preference:** `mysql:latest` currently resolves to MySQL
**26.7.0** — a very different release line from `8.4.8`, which is what the
named volume `mysql_data` previously held from earlier development sessions.
MySQL's own server refuses to open a data directory across that large a
version jump (`Invalid MySQL server upgrade: Cannot upgrade from 80408 to
260700`); the container exits and Compose reports it unhealthy. This is not
a Docker/Compose quirk, it is MySQL's own upgrade-compatibility check. In
practice this means: every time `mysql:latest` moves to a materially newer
release line, `docker compose down -v` (removing `mysql_data`) is required
before a fresh `up` will succeed, and this destroys any not-yet-persisted
runtime data (currently none is persisted beyond the immutable baseline
load, so this is low-cost during development, but it would not be
low-cost in a longer-lived environment). Because this is exactly the kind
of concrete cost the version-relaxation decision (§10.1) should be judged
against, it is recorded here rather than only discovered by the next person
to run `up` after a `mysql:latest` release bump.

**Local-development note:** `stack.sh`'s own `up`/`doctor` commands assume
`APP_SOURCE_DIR` is a git-synced checkout *inside* `prototype_stack/` (and
reject any path containing `..`). Local verification instead invokes
`docker compose` directly with `APP_SOURCE_DIR=..` exported from
`prototype_stack/` (i.e. the repository root, where `Dockerfile` now lives
— §6.2), bypassing `stack.sh`'s sync-oriented commands. Once
`prototype_stack/config/git-source.conf` is pointed at this repository's own
remote and the current work is pushed, `stack.sh sync && stack.sh up` is
expected to work against it unmodified — see
[DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) §10.2.

### 6.4 Local development workflows

| Task | Command |
|---|---|
| Run PHP tests (unit only, no DB) | `cd app && php vendor/bin/phpunit --testsuite unit` |
| Run PHP tests (unit + integration, needs live MySQL + loaded baseline) | `ICD_DB_HOST=... ICD_DB_NAME=... ICD_DB_USER=... ICD_DB_PASSWORD=... php vendor/bin/phpunit --testsuite unit,integration` |
| Run the Selenium E2E suite (needs the app running + Selenium — see `app/tests/E2E/README.md`) | `php vendor/bin/phpunit --testsuite e2e` |
| Run everything in one pass (needs MySQL, the app, *and* Selenium all up) | `php vendor/bin/phpunit --testsuite unit,integration,e2e` — the bare `php vendor/bin/phpunit` with no `--testsuite` is equivalent and will fail fast/clearly if Selenium or the app aren't reachable |
| Serve the API + built frontend without Docker | `ICD_DB_*=... php -S 127.0.0.1:PORT -t public router.php` (from `app/`) |
| Rebuild the frontend into `app/public/` | `cd app/frontend && npm run build` |
| Frontend dev server with API proxy | `cd app/frontend && npm run dev` (proxies `/api` to `http://127.0.0.1:8080` per `vite.config.js`) |
| Full stack via Compose (local, no git-sync) | `cd prototype_stack && APP_SOURCE_DIR=.. docker compose --env-file .env -f compose.yaml up -d --wait db && docker compose ... run --rm --no-deps bootstrap && docker compose ... up -d --wait app` |
| Full stack via Compose (once `git-source.conf` points at this repo) | `./stack.sh sync && ./stack.sh up` |

### 6.5 Self-contained bundle (`docker-compose.yml`, repo root) and CI

Rationale: `docs/DEVELOPMENT_DOCUMENTATION.md` §10.6. This is a *different*
compose file from `prototype_stack/compose.yaml` — no `.env` required,
sensible defaults baked in, and it additionally bundles a `dev`-tagged app
image (tests + dev Composer dependencies included) plus Selenium behind a
`test` Compose profile.

| Task | Command |
|---|---|
| Build the normal-use images locally (native host architecture) | `docker compose build bootstrap app` |
| Build the optional test image locally | `docker compose --profile test build test` |
| Bring up the app (db → bootstrap → app, correctly ordered via `depends_on: condition: service_completed_successfully`) | `docker compose up -d --wait app` |
| Run the *entire* test suite (unit+integration+e2e) fully containerized, no host PHP/Composer/Node/Python needed | `docker compose --profile test up -d --wait selenium && docker compose --profile test run --rm test` |
| Pull the published images (Docker selects the host's AMD64 or ARM64 variant) | `docker compose pull` |
| Override any published image tag | `APP_IMAGE=... APP_DEV_IMAGE=... BOOTSTRAP_IMAGE=...` env vars, or edit the `image:` lines directly |

Published image tags (built and pushed by `.github/workflows/ci.yml`'s
`publish-images` job, on every push to `main`, only after the other four
jobs pass):

| Tag | Dockerfile target | Contents |
|---|---|---|
| `ghcr.io/junomarx/bsc-thesis-icd10:latest` | `runtime` | Lean deployment image — same as `prototype_stack/compose.yaml`'s `app` |
| `ghcr.io/junomarx/bsc-thesis-icd10:dev` | `dev` | Runtime + dev Composer deps + `app/tests/` — this is what the bundle's `test` service runs |
| `ghcr.io/junomarx/bsc-thesis-icd10-bootstrap:latest` | — (`prototype_baseline_0_1/Dockerfile.bootstrap`) | The Python data-pipeline bootstrap image |

Each tag is an OCI multi-platform index with required
`linux/amd64` and `linux/arm64` variants. The `publish-images` job installs
QEMU before Buildx, passes both values through each build action's
`platforms` input, and queries all three registry manifests after publication;
the job fails unless both Linux architectures are present for every tag.
This preserves native execution on Apple Silicon instead of forcing an
AMD64 `platform:` override and emulation in `docker-compose.yml`.

**Publication state at the time this contract was added:** the three public
GHCR tags had been published successfully, but registry inspection showed
that the pre-fix indexes contained only `linux/amd64` (plus provenance
attestations). The next successful `main` publication must replace them with
the two-architecture indexes described above. Until that happens, Apple
Silicon users can run `docker compose build bootstrap app` and receive native
ARM64 images from the checkout; this fallback has been executed successfully
on an ARM64 Docker daemon.

## 7. Test inventory (file/method → upstream `TEST-*`)

| `TEST-*` | Implementing file(s) |
|---|---|
| `TEST-DAT-01` | `prototype_baseline_0_1/scripts/prepare_subset.py --check-existing`, `validate_baseline.py` |
| `TEST-DAT-02` | `prototype_baseline_0_1/tests/test_mysql_persistence.py` |
| `TEST-ARC-01` | `app/tests/Integration/ArchitectureIsolationTest.php` (behavioural half); `prototype_baseline_0_1/scripts/runtime_data.py` allowlist (structural half) |
| `TEST-MAP-01` | `app/tests/Unit/RuleMapTest.php` |
| `TEST-GATE-01` | `app/tests/Unit/RuleGateTest.php` |
| `TEST-STATUS-01` | `app/tests/Unit/RuleStatusTest.php` |
| `TEST-DEPTH-01` | `app/tests/Unit/RuleDepthTest.php` |
| `TEST-EVID-01` | `app/tests/Unit/RuleEvidTest.php` |
| `TEST-SPEC-01` | `app/tests/Unit/RuleSpecTest.php` |
| `TEST-CORRECT-01` | `app/tests/Unit/RuleCorrectTest.php` |
| `TEST-PREC-01` | `app/tests/Unit/PrecedenceTest.php` |
| `TEST-API-01` | `app/tests/Integration/EvaluationApiTest.php` |
| `TEST-RC-01` | `app/tests/Integration/ReferenceResponseTest.php` |
| `TEST-DET-01` | `app/tests/Integration/DeterminismTest.php` |
| `TEST-E2E-01` | `app/tests/E2E/LearnerWorkflowTest.php` |
| `TEST-E2E-02` | `app/tests/E2E/VerificationOnlyCaseVisibilityTest.php` |
| `TEST-CFG-01` | version pins in `Dockerfile` (repo root), `prototype_stack/compose.yaml`, §8 below |

## 8. Exact tool/version pins observed in this implementation

| Component | Version | Where pinned |
|---|---|---|
| PHP (runtime image) | 8.4.24 | `php:8.4-apache` base image |
| PHP (host dev CLI) | 8.4.7 | host `php` binary (not shipped) |
| MySQL | deliberately unpinned below major version; observed as **26.7.0** at time of writing | `mysql:latest` image in `compose.yaml` — see §6.3 for the concrete cost of this choice |
| Node (build stage only) | 22-alpine | `Dockerfile` (repo root) |
| React | 19.2.8 | `app/frontend/package.json` |
| Vite | 8.2.x | `app/frontend/package.json` |
| PHPUnit | 11.5.56 | `app/composer.lock` |
| php-webdriver/webdriver | 1.16.0 | `app/composer.lock` |
| Selenium/browser (E2E only, not the app) | `seleniarm/standalone-chromium:latest` (arm64) / `selenium/standalone-chrome:latest` (amd64) | `app/tests/E2E/docker-compose.yml` — deliberately unpinned to an exact tag; not part of the deployed application |
| Composer | 2.10.2 | `app/composer.phar` (not committed) |
| Docker Engine (host) | 28.5.1 | host install |

An exact MySQL version is not "observed as frozen" here the way the other
rows are — it is expected to change whenever `mysql:latest` moves, by
design (§6.3, [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md)
§10.1). `TEST-CFG-01`'s eventual evaluation freeze is where an exact MySQL
version becomes a real pin, recorded at that time.

## 9. Explicit non-implementations

Mirrors `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §2/§11, stated here as "does
not exist in the code" rather than "is out of scope": no authentication/user
model anywhere in `src/`; no table or column for learner attempt history; no
route accepting more than one `submitted_code`; no extramural-specific rule
class; no LKF pricing/reimbursement logic; no client-side router in the
frontend.
