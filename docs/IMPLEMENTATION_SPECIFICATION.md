# Implementation specification

**Rewritten in full 9 August 2026** for the forward patient/question model.
The previous version of this document described the superseded one-case/
one-question implementation (`CASEBASE-0.2`/`MODELBASE-0.1`/`RULEBASE-0.1`);
that implementation no longer exists in `app/src/` or `app/frontend/src/`.
See `docs/CHANGELOG.md`'s 2026-08-08/09 entries for the full change history
this rewrite consolidates.

**Scope:** precise, as-built description of the software in `app/` and its
relationship to `prototype_baseline/` and `prototype_stack/`.
This is a reference document — if the code and this document disagree, the
code is correct and this document is stale; fix the document (see
[CHANGELOG.md](CHANGELOG.md) discipline in `CLAUDE.md`).
**Rationale for these choices:** see [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md).
**Rule/data semantics authority:** `chapter3_rule_catalogue_0_2.md`
(`RULEBASE-0.2`) and `chapter3_data_model_and_interaction_baseline_0_2.md`
(`MODELBASE-0.2`). This document describes *how* those semantics are
realized in code, not their justification. The `_0_1`-suffixed predecessors
of both files are historical, superseded authority.

## 1. Repository layout

```text
Dockerfile                      multi-stage build: node build → composer install → php:8.4-apache runtime
                                 lives at the repo root (not app/) so prototype_stack/stack.sh's
                                 `sync` can pull this repository as the app source and find it at
                                 the checkout root — see docs/DEVELOPMENT_DOCUMENTATION.md §10.2.
                                 Two build targets: `runtime` (default) and `dev` (adds dev
                                 Composer deps + app/tests/, published as the :dev image tag — §6.5)
.dockerignore                   also at repo root, matching the Dockerfile's build context
docker-compose.yml               self-contained publishable bundle (db+bootstrap+app by default,
                                 +selenium+test behind a `test` Compose profile) — see §6.5.
                                 Distinct from prototype_stack/compose.yaml (the stack.sh-managed
                                 deployment scaffold) — both point their `bootstrap` service at
                                 prototype_baseline/ (§6.3)
.github/workflows/ci.yml         5 jobs: python-checks, php-unit, backend-integration, e2e,
                                 publish-images (builds+pushes 3 images to GHCR, master only,
                                 gated on the other 4 passing) — bootstrap-wiring fixed, the
                                 suite it runs now passes (§7), and a housekeeping pass fixed
                                 three latent bugs `publish-images`/`python-checks` had (§6.5);
                                 confirmed live at the time: a real GitHub-hosted run completed
                                 all 5 jobs successfully (run 31314862118, 2026-08-09 13:04-13:09
                                 UTC, when the branch was still named `main`). After a later
                                 branch rename to `master`, both the top-level `push` trigger and
                                 `publish-images`'s own `if:` condition were left pointing at the
                                 nonexistent `main`, so every push since silently stopped
                                 triggering CI/publishing at all - found and fixed the same day
                                 (`docs/CHANGELOG.md`'s "CI's push trigger silently stopped
                                 firing" and "publish-images" entries); GHCR's three tags were
                                 stale until the next successful run after that fix
app/
  composer.json / composer.lock PHP dependencies (runtime: none beyond ext-pdo/ext-json; dev:
                                 phpunit/phpunit, php-webdriver/webdriver)
  phpunit.xml                   three suites: unit, integration, e2e (all passing, §7)
  router.php                    dev-only front controller for `php -S`; NOT copied into the image
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
      BaselineIdentity.php        the single `prototype_baseline` row (PROTOBASE-1.0 identity, frozen §6.3)
      CatalogueRecord.php          one catalogue_code (SUBSET-0.2) row
      Patient.php                  one patient_definition row + ordered context items
      PatientContextItem.php       one patient_context_item row
      CodingQuestion.php           the atomic evaluation unit — replaces CaseFacts
      QuestionFact.php             one typed question_fact row
      QuestionFacts.php             keyed bag over QuestionFact with typed getters (getEnum/
                                    getCode/getDecimal/getBool/getText); missing key → null,
                                    never throws
      QuestionCodeDomainRelation.php  one question_code_domain row (relation_kind vocabulary)
      QuestionRelationFact.php     one question_relation_fact row (links a relation to a fact)
      QuestionOption.php           one displayed question_option row
      ResponseInput.php            tagged union: code(string) | noneOfAbove()
    Repository/
      BaselineRepository.php
      CatalogueRepository.php
      PatientRepository.php         replaces CaseRepository
      QuestionRepository.php        replaces CaseRepository; hydrates facts/domain/
                                     relation-facts/options in one pass, see §2.2
    Rules/
      RuleGate.php, GateResult.php           RULE-GATE-01
      RuleMap.php, MapResult.php              RULE-MAP-01
      RuleStatus.php                           RULE-STATUS-01
      RuleDepth.php                            RULE-DEPTH-01
      RuleEvid.php                             RULE-EVID-01
      RuleSpec.php                             RULE-SPEC-01
      RuleRelHard.php                          RULE-REL-HARD-01 (net new)
      RuleRelSpec.php                          RULE-REL-SPEC-01 (net new)
      RuleNoa.php                              RULE-NOA-01 (net new)
      RuleCorrect.php                          RULE-CORRECT-01
      Precedence.php                           RULE-PREC-01 (policy extracted as a pure function)
    Evaluation/
      Evaluator.php                orchestrates the above in RULEBASE-0.2 §6 order
      EvaluationResult.php          terminal result value object (now bilingual, §3.3)
      SpecificationGapException.php thrown if an eligible relation reaches no terminal rule
    Http/
      ApiResult.php                 {status, body} — HTTP-independent controller return type
      JsonResponse.php               writes ApiResult to the actual HTTP response
      PatientController.php          GET /api/patients, GET /api/patients/{id} — replaces CaseController
      QuestionController.php         GET /api/questions/{id}
      EvaluationController.php       POST /api/questions/{id}/evaluate
  tests/                          migrated to the forward model, step 8 — §7
  frontend/
    package.json, vite.config.js    React 19 + Vite 8; version/build-date baked in via `define`
                                     (§5.8); build output → ../public (emptyOutDir: false)
    src/
      main.jsx                      applies the stored/OS-derived theme, then wraps <App/> in
                                     <LocaleProvider>
      App.jsx                       owns playthrough/tutorial state; renders Header + one of
                                     three views + Footer + conditional Tutorial modal
      App.css, index.css
      api.js                        fetch wrappers for the four endpoints
      lib/
        playthrough.js               shuffledOrder() (Fisher–Yates over ids), summarizeResults()
        classification.js            STATUS_LABEL_KEYS/STATUS_ICONS (i18n keys, not strings)
        i18n.jsx                     LocaleProvider/useLocale() — EN/DE UI-chrome dictionary,
                                      browser-language default, localStorage-persisted choice
        theme.js                     light/dark preference bootstrap, application, persistence
        contentTranslations.js       German translations of patient/question *content*
                                      (summaries, context items, question title/prompt)
        catalogueTranslations.js     English titles for the 87 displayed ICD-10 codes (the
                                      runtime catalogue is German-only data)
      components/
        Header.jsx                   title, disclaimer, tutorial re-entry, ThemeSwitch, LanguageSwitch
        LanguageSwitch.jsx           EN | DE toggle
        ThemeSwitch.jsx              compact persisted light/dark toggle
        Tutorial.jsx                 REQ-UI-01 four-step first-visit modal, manually reopenable
        PatientRoster.jsx            heading, progress summary, reset-progress control, patient-list grid
        PatientCard.jsx               one patient per card (fixed height, §5.7)
        PatientDossier.jsx            collapsible patient identity/context panel
        QuestionView.jsx              progress bar, prompt, options, submit, feedback + technical details
        PatientReview.jsx             counts, per-question list, completion badge, replay/another-patient
        Footer.jsx                    version/build date/copyright
        icons.jsx                    hand-authored inline SVGs, no icon-font/library

prototype_baseline/                 active Python data pipeline + MODELBASE-0.2 persistence
                                    candidate, wired into the real deployment path (§6.3)
prototype_stack/                   Docker Compose scaffold (db, bootstrap, app services)
archived/                          superseded `_0_1` pipeline, pre-implementation planning docs,
                                    a one-time delivery drop — nothing here is live; see
                                    docs/DEVELOPMENT_DOCUMENTATION.md §17 for what moved and why
```

## 2. Data model

### 2.1 Physical schema

Defined in `prototype_baseline/persistence_candidate/mysql_schema_0_2.sql`;
nine tables, no others. All `InnoDB`, `utf8mb4_unicode_ci`.

| Table | Primary key | Notable columns | FK to |
|---|---|---|---|
| `prototype_baseline` | `prototype_baseline_id` | `model_baseline_id`, `patient_baseline_id`, `question_baseline_id`, `subset_baseline_id`, `catalogue_edition`, `diaglist_sha256` | — |
| `catalogue_code` | `(subset_baseline_id, code)` | `marker` (nullable, `!`), `designation`, `short_designation` | — |
| `patient_definition` | `(patient_baseline_id, patient_id)` | `display_name`, `age_years`, `sex`, `self_described_background`, `history_availability`, `difficulty_role` (`foundational`\|`involved`), `general_health_summary`, `synthetic` | — |
| `patient_context_item` | `(patient_baseline_id, patient_id, context_item_id)` | `item_type` (6-value CHECK, incl. `information_boundary`), `information_source`, `display_text`, `canonical_position` | `patient_definition` |
| `coding_question` | `(question_baseline_id, question_id)` | `patient_id` (nullable), `title`, `prompt`, `intended_use` (`learner_visible`\|`verification_only`), `canonical_position`, `legacy_case_id` | `patient_definition` |
| `question_fact` | `(question_baseline_id, question_id, fact_key)` | one-of-six typed value columns (`value_text`/`_integer`/`_decimal`/`_boolean`/`_code`/`_enum`), `learner_label`, `source_context_item_id` | `coding_question` |
| `question_code_domain` | `(question_baseline_id, question_id, subset_baseline_id, code)` | `relation_kind` (5-value CHECK), `reason_key`, `improvement_code` | `coding_question`, `catalogue_code` (twice: `code` and `improvement_code`) |
| `question_relation_fact` | `(question_baseline_id, question_id, subset_baseline_id, code, fact_key)` | `relation_role` (5-value CHECK) | `question_code_domain`, `question_fact` |
| `question_option` | `(question_baseline_id, question_id, option_id)` | `option_kind` (`code`\|`none_of_above`), `code` (nullable) | `coding_question`, `catalogue_code`, `question_code_domain` |

CHECK constraints of note beyond the vocabularies above:
`ck_fact_one_typed_value` (exactly one of the six value columns is non-null,
matching `value_type`); `ck_relation_reason` (`fact_conflict`/
`temporal_context_conflict` rows must carry a non-empty `reason_key`);
`ck_relation_improvement` (`less_specific_supported` rows must carry a
non-null `improvement_code` — the schema only checks the code *exists*;
that it resolves to an `accepted_reference` on the same question is
enforced at hydration time, `QuestionRepository::assertImprovementCodesResolve()`,
§2.2); `ck_option_payload` (a `code` option has both `subset_baseline_id`
and `code`, a `none_of_above` option has neither).

**No table stores an expected classification, determining rule, criterion,
or any other verification-oracle field** — the schema file's own trailing
comment states this explicitly ("Deliberately absent: reference_response,
expected_class, expected_rule...").

### 2.2 PHP value objects and repository hydration (`src/Model/`, `src/Repository/`)

`QuestionRepository::findById()`/`listLearnerVisibleForPatient()` hydrate a
full `CodingQuestion` in one pass: the question row, its `question_fact`
rows into a `QuestionFacts` bag, its `question_code_domain` rows into a
`code => QuestionCodeDomainRelation` map (`$domain`), its
`question_relation_fact` rows into a `code => list<QuestionRelationFact>`
map, and its `question_option` rows into an ordered `list<QuestionOption>`.
Evaluation-domain membership (`$domain`) and displayed-option membership
(`$options`) are deliberately separate arrays — `relationFor($code)` can
return a relation for a code absent from `$options` (`REQ-MOD-06`).

`findById()` does **not** filter by `intended_use` — both learner-visible
and the 8 hidden `verification_only` legacy questions resolve by ID,
because the verification path must be able to evaluate them
(`REQ-VER-09`). The learner-facing visibility boundary is enforced by
callers (`listLearnerVisibleForPatient()`'s own `WHERE`, and
`QuestionController::show()`'s explicit `isLearnerVisible()` check on a
direct fetch) — mirroring how `EvaluationController` never filters by
`intended_use` either.

`PatientRepository` is simpler: every patient is learner-facing
(`REQ-MOD-03`), so `findById()`/`listAll()` have no visibility filter of
their own.

## 3. Rule engine

### 3.1 Evaluation algorithm

`Evaluator::evaluate(CodingQuestion $question, ResponseInput $response, ?CatalogueRecord $record): EvaluationResult`
implements the pseudocode in `chapter3_rule_catalogue_0_2.md` §6:

```text
gate = RuleGate::evaluate(question, response, record)
if not gate.eligible:  return notEvaluated(gate.reason)

if response.isNoneOfAbove():
    return buildNoaResult(question)                    # RULE-NOA-01, terminal

map = RuleMap::evaluate(question)

hardMatches = []
if RuleStatus::matches(question, record):             hardMatches += RULE-STATUS-01
if RuleDepth::matches(question, code):                 hardMatches += RULE-DEPTH-01
if RuleEvid::matches(question, code, map):             hardMatches += RULE-EVID-01
if RuleRelHard::matches(question, code):               hardMatches += RULE-REL-HARD-01

if hardMatches not empty:
    primary = Precedence::primaryHardRule(hardMatches)   # STATUS > DEPTH > EVID > REL-HARD
    return classified('incorrect', primary, ...)

gradedMatches = []
if RuleSpec::matches(question, code, map):             gradedMatches += RULE-SPEC-01
if RuleRelSpec::matches(question, code):               gradedMatches += RULE-REL-SPEC-01

if gradedMatches not empty:
    primary = Precedence::primaryGradedRule(gradedMatches)  # SPEC > REL-SPEC
    return classified('suboptimal', primary, ...)

if RuleCorrect::matches(question, code):
    return classified('correct', RULE-CORRECT-01, ...)

throw SpecificationGapException(...)   # never `incorrect` by default
```

The `none_of_above` branch is terminal immediately after the gate: no
catalogue-code rule ever runs for it, because there is no submitted code.

### 3.2 Per-rule contract

| Rule class | Static method signature | Predicate |
|---|---|---|
| `RuleGate` | `evaluate(CodingQuestion, ResponseInput, ?CatalogueRecord): GateResult` | `none_of_above` with no such option → `none_option_not_defined`; code not in catalogue → `outside_active_subset`; code without a defined `question_code_domain` relation → `undefined_case_relation`; a COPD question with no FEV1 fact, or a `!`-marked main-diagnosis hospital-outpatient question with no LKF flag → `missing_required_case_fact`; else eligible |
| `RuleMap` | `evaluate(CodingQuestion): MapResult` | Inpatient + 4-char COPD base (`J44.[0-9]`) + FEV1 present → suffix `0/1/2/3` by `<35 / <50 / <70 / else`, target = base+suffix; else not applicable. Applies to exactly one of the 25 learner questions (`Q-001-01`) |
| `RuleStatus` | `matches(CodingQuestion, CatalogueRecord): bool` | `marker === '!' && role === 'main' && (inpatient \|\| (hospital_outpatient && lkfScored === true))` |
| `RuleDepth` | `matches(CodingQuestion, string): bool` | `inpatient && submittedCode` matches `/^J44\.[0-9]$/` (a bare 4-char parent) |
| `RuleEvid` | `matches(CodingQuestion, string, MapResult): bool` | 6-char code, same 4-char base as the question's COPD base, suffix ∈ `{0,1,2,3}`, and that suffix ≠ `MapResult::expectedSuffix` |
| `RuleSpec` | `matches(CodingQuestion, string, MapResult): bool` | inpatient + main + `MapResult` applicable + `submittedCode === copdBaseCode . '9'` (one of the four source-listed warning forms) |
| `RuleRelHard` | `matches(CodingQuestion, string): bool` | relation is explicitly `fact_conflict` or `temporal_context_conflict` — never `submitted != accepted` by itself |
| `RuleRelSpec` | `matches(CodingQuestion, string): bool` | relation is explicitly `less_specific_supported`; shares `RULE-SPEC-01`'s `CRITERION` string by design (same feedback reason, one source-specific, one generic — callers key off `determining_rule`) |
| `RuleNoa` | `isCorrect(CodingQuestion): bool` | `none_of_above` is `correct` iff the displayed code set contains no `accepted_reference` code — a pure set-membership check, D(q)∩A(q)=∅ |
| `RuleCorrect` | `matches(CodingQuestion, string): bool` | `relationFor(code)?->relationKind === accepted_reference` |
| `Precedence` | `primaryHardRule(array): ?string`; `primaryGradedRule(array): ?string`; `terminalClass(hard, graded, accept): ?string` | Hard priority `STATUS > DEPTH > EVID > REL-HARD`; graded priority `SPEC > REL-SPEC`; terminal policy hard→incorrect, else graded→suboptimal, else accept→correct, else `null` (specification gap) |

### 3.3 `EvaluationResult` shape

```php
final class EvaluationResult {
    public readonly string $evaluationStatus;      // 'classified' | 'not_evaluated'
    public readonly ?string $classification;        // 'correct' | 'suboptimal' | 'incorrect' | null
    public readonly ?string $reason;                 // gate/HTTP-boundary reason, only when not_evaluated
    public readonly ?string $determiningRule;        // 'RULE-*'
    public readonly ?string $criterion;               // stable machine-readable key
    public readonly ?string $explanation;             // learner-readable sentence (English)
    public readonly ?string $explanationDe;           // the same sentence in German - added
                                                        // 9 August 2026; every classified() call
                                                        // site must supply it (constructor
                                                        // parameter, not optional)
    public readonly ?array  $explanationElements;      // structured payload
    public readonly ?array  $matchedRules;             // all matched RULE-* ids, e.g. every hard match
    public readonly ?string $improvementCode;          // ICD code, only for DEPTH/EVID/SPEC/REL-SPEC
}
```

Interpolated dynamic content inside both explanation strings comes from two
places: computed values (codes, percentages — translated inline, e.g. via
`Evaluator`'s private `encounterSettingDe()`/`suffixMeaningDe()` helpers)
and `question_fact.learner_label` (data, authored in English only — no
German-authored variant exists yet, so a cited fact label stays English
inside an otherwise-German sentence when a `RULE-REL-HARD-01`/
`RULE-REL-SPEC-01` explanation cites one; a known, logged limitation, not
an oversight).

### 3.4 `none_of_above` and the cross-question leakage safeguard

`RuleNoa` treats `none_of_above` as an interaction response kind, never an
ICD catalogue record — it has no `catalogue_code` row and is never
confused with one downstream. Because immediate feedback (`REQ-FBK-01`)
means a learner sees one question's answer before attempting a sibling
question from the same patient, the authoring/audit process
(`chapter3_ux_ui_gamification_concept_0_1.md` §7) checked that no accepted
code for one learner question appears as a *displayed* option for a
different question of the same patient — confirmed clean for the current
25-question bank, not re-derived here.

## 4. HTTP API

### 4.1 `GET /api/health`

Handled directly in `public/index.php`, before the database is even
connected. `200 {"status":"ok"}` always.

### 4.2 `GET /api/patients`

Returns all 6 patients, ordered by `patient_id`.

```json
{
  "patients": [
    {
      "patient_id": "PATIENT-001",
      "display_name": "Anna Berger",
      "age_years": 68,
      "sex": "female",
      "self_described_background": "Central European (Austrian)",
      "history_availability": "established",
      "difficulty_role": "foundational",
      "general_health_summary": "Established primary-care and hospital records are available...",
      "question_count": 3,
      "context_items": [
        {"context_item_id": "CTX-001-01", "item_type": "documented_condition", "information_source": "record", "display_text": "..."}
      ]
    }
  ]
}
```

`question_count` is computed live via
`count($this->questions->listLearnerVisibleForPatient(...))` — never a
stored/cached column, so it cannot drift from the actual visible set.

### 4.3 `GET /api/patients/{patient_id}`

`404 {"error":"patient_not_found"}` if the patient does not exist. On
success, the same fields as one list entry, plus:

```json
{
  "...": "as above",
  "questions": [
    {"question_id": "Q-001-01", "title": "Respiratory coding task", "canonical_position": 1}
  ]
}
```

Only `learner_visible` questions appear here — this is the one place a
`verification_only` question is deliberately excluded, mirroring the old
`CaseController::show()`'s asymmetry with the evaluate endpoint.

### 4.4 `GET /api/questions/{question_id}`

`404 {"error":"question_not_found"}` if the question does not exist **or**
is not `learner_visible` — the 8 hidden legacy fixtures 404 here exactly
like a nonexistent question would. On success:

```json
{
  "question_id": "Q-001-01",
  "patient_id": "PATIENT-001",
  "title": "Respiratory coding task",
  "prompt": "The represented inpatient record documents COPD with an acute lower-respiratory infection. Stable-phase FEV1 is 55% of predicted. Select the best supported Austrian ICD-10 response.",
  "canonical_position": 1,
  "options": [
    {"option_id": "Q-001-01-O01", "option_kind": "code", "code": "J44.02", "designation": "...", "short_designation": "..."},
    {"option_id": "Q-001-01-O05", "option_kind": "none_of_above"}
  ]
}
```

**Deliberately absent: raw `question_fact` rows.** `APIBASE-0.1` §5 fixes
these as evaluator-internal, pre-submission data — `learner_label` is a
post-submission explanation label, not a visibility flag. Confirmed against
the materialized data that this is safe: every fact a learner needs is
already stated in `prompt` and/or the patient's `context_items` (e.g.
`Q-001-01`'s prompt states the FEV1 value directly). `options` here is the
*displayed* set, not the evaluation domain (`REQ-MOD-06`) — a question may
accept a code that never appears in this response (`Q-004-05`/`M54.5`,
`Q-005-05`/`I10`).

### 4.5 `POST /api/questions/{question_id}/evaluate`

Request body — the **tagged-response contract** (`APIBASE-0.1`), not
`{"option_id": "..."}`: a displayed-option-only shape could not address an
evaluable-but-undisplayed code.

```json
{"response": {"type": "code", "code": "J44.02"}}
{"response": {"type": "none_of_above"}}
```

| Condition | Status | Body |
|---|---|---|
| Question does not exist (any `intended_use`) | 404 | `{"error":"question_not_found"}` |
| Body isn't `{"response": {...}}`, or `code` missing/blank | 400 | `{"evaluation_status":"not_evaluated","classification":null,"reason":"malformed_input"}` |
| `type` is neither `code` nor `none_of_above` | 400 | `{"evaluation_status":"not_evaluated","classification":null,"reason":"unsupported_response_kind"}` |
| Gate fails (4 possible reasons, §3.2) | 200 | `{"evaluation_status":"not_evaluated","classification":null,"reason":"<reason>"}` |
| Classified | 200 | see below |
| Eligible but no terminal rule matched (should not occur) | 500 | `{"error":"specification_gap","message":"..."}` |

`malformed_input`/`unsupported_response_kind` are HTTP-boundary errors,
produced by `EvaluationController::parseResponse()` before a `ResponseInput`
is even constructed — they are **not** part of `RuleGate`'s reason
vocabulary (`APIBASE-0.1` §4, `GateResult`'s docblock states this
explicitly).

Classified response shape:

```json
{
  "evaluation_status": "classified",
  "classification": "suboptimal",
  "criterion": "supported_specificity_not_used",
  "explanation": "J44.09 leaves the FEV1 severity unspecified. The question already states a stable-phase FEV1 of 55%, which supports the more specific code J44.02.",
  "explanation_de": "J44.09 lässt den FEV1-Schweregrad unspezifiziert. Die Frage gibt bereits eine stabile FEV1 von 55 % an, die den spezifischeren Code J44.02 unterstützt.",
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

**`intended_use` is never filtered here**, for either request or response —
the verification harness must be able to evaluate all 8 hidden legacy
fixtures by ID (`REQ-VER-09`), exactly as the old endpoint never filtered
`CASE-004`/`CASE-008`. Only *navigating to* a `verification_only` question
via §4.4 is blocked.

## 5. Frontend

UX/UI polish rationale (`UXBASE-0.1`, step 7): `docs/DEVELOPMENT_DOCUMENTATION.md` §7.
This section describes only the resulting structure and contracts.

### 5.1 Component tree and state (`frontend/src/`)

`App.jsx` owns all playthrough/tutorial state and renders `Header` + exactly
one of three mutually exclusive views + `Footer`, with `Tutorial` layered
conditionally above any of them:

```text
patients, loadingPatients, patientsError    — GET /api/patients, once on mount
completedPatientIds                          — Set, sessionStorage-backed (§5.6)
tutorialOpen                                 — first-visit localStorage flag + transient modal state (§5.5)
view                                          — 'roster' | 'playthrough' | 'review'
activePatient, orderedQuestionIds, currentIndex, questionsById, results, submitting
```

`view === 'roster'` → `PatientRoster` (patient-card grid + reset-progress
control). `Tutorial` is modal and independent of those three views, so the
persistent header can reopen it from the roster, a question, or a review.
`view === 'playthrough'` →
`QuestionView` for `questionsById[orderedQuestionIds[currentIndex]]`.
`view === 'review'` → `PatientReview`. There is no client-side router;
these are plain conditional renders inside `App.jsx`, unchanged in spirit
from the original three-view case-centric model.

State transitions: roster → (select a patient) → playthrough (question 1)
→ (submit → feedback → next) × N → review → (`Play again` → playthrough,
reshuffled; `Choose another patient` → roster). `QuestionView` also exposes
an `Exit to patient list` control reachable at any point mid-question
(confirms via `window.confirm()`, then behaves exactly like `Choose another
patient`) — added 2026-08-09 in response to there being no other way back
to the roster before finishing every question.

### 5.2 `api.js` — the only place `fetch()` is called

```js
listPatients()                           // GET  /api/patients
getPatient(patientId)                     // GET  /api/patients/{patientId}
getQuestion(questionId)                    // GET  /api/questions/{questionId}
evaluate(questionId, response)             // POST /api/questions/{questionId}/evaluate
                                            // response: {type:'code', code} | {type:'none_of_above'}
```

Each returns `{status, body}` from the parsed JSON response; components
branch on `status`/`body` rather than on thrown exceptions for expected
(4xx) outcomes.

### 5.3 i18n architecture (`lib/i18n.jsx`, added 2026-08-09)

`LocaleProvider`/`useLocale()` (React Context) hold `locale` (`'en'`\|`'de'`),
a `setLocale()` that persists the choice to `localStorage`
(`icd10-prototype:locale`), and `t(key, vars)` resolving a flat
key→template dictionary with `{placeholder}` interpolation. Default locale
is detected from `navigator.languages` (first `de`/`en`-prefixed entry
wins, else `en`) on first load, before any stored preference exists.

This covers **UI chrome only** (buttons, labels, headings, the three-class
legend, gate-reason messages). Two separate, additive lookups handle
content the backend itself doesn't translate:

- `lib/contentTranslations.js` — German translations of `general_health_summary`,
  `patient_context_item.display_text`, and question `title`/`prompt`,
  keyed by the same `patient_id`/`context_item_id`/`question_id` the API
  returns. Used only when `locale === 'de'`; falls back to the API's own
  (English) text on any lookup miss.
- `lib/catalogueTranslations.js` — English titles for the 87 distinct
  ICD-10 codes actually displayed as a `question_option` (not the full
  99-row catalogue subset). Used only when `locale === 'en'`, for the
  reverse reason: the runtime catalogue is authored in German only (the
  Austrian BMASGPK edition), so English mode would otherwise show German
  code names inside an English interface.

Both are deliberately kept in the frontend, not the database or API — a
`REQ-ARC-01` presentation concern, not a data-model change. Evaluator
explanations are handled differently (§3.3 above): they come from the
backend already bilingual (`explanation`/`explanation_de`), because they
contain rule-derived content the frontend cannot safely paraphrase.

### 5.4 Explicit light/dark setting (`lib/theme.js`, `ThemeSwitch.jsx`)

`lib/theme.js` owns the browser-only appearance contract. It accepts the
values `light` and `dark` under the `localStorage` key
`icd10-prototype:theme`. When that key is absent or unavailable, the first
value comes from `matchMedia('(prefers-color-scheme: dark)')` (falling back
to light where that API is unavailable). The module applies the resolved
value to `<html data-theme="…">` during module initialization, before React
mounts, and `main.jsx` imports it explicitly to keep that early bootstrap
order visible.

`ThemeSwitch` is an icon-only 44×44 px toggle in the existing header action
group, between tutorial re-entry and the EN/DE switch. A sun/moon icon keeps
the control visually compact; its localized accessible name, action title,
visually hidden text, and `aria-pressed` state preserve its meaning without
adding a permanent text label to the header. Selecting a value applies it
immediately and writes it to the same browser preference store used for the
locale/tutorial preferences. There is no account, cookie, API call, schema
field, or server-side theme state.

`App.css` keeps light values as the base design tokens and overrides the
same token set under `:root[data-theme='dark']`; setting `color-scheme` on
each resolved theme also aligns native form controls. Consequently the
roster, question flow, feedback colours, review, tutorial, and footer all
consume one palette contract rather than maintaining component-specific
dark styles.

### 5.5 First-visit tutorial (`App.jsx`, `Header.jsx`, `Tutorial.jsx`)

The current tutorial is a new patient/question-model implementation, not
the deleted case-centric `Tutorial.jsx` recorded in the historical UX
iteration. It replaces the forward model's always-expanded
`Orientation.jsx` panel with a four-step modal that guides the learner
through patient choice, dossier review, one-response submission, and
feedback/review. Back/Next controls and a step indicator make it a directed
walkthrough; the final step includes the same icon + text feedback legend
used by the application.

`App.jsx` initializes `tutorialOpen` by checking the versioned
`localStorage` key `icd10-prototype:tutorial-seen-v1`. An absent key opens
the modal automatically; close button, skip, finish, Escape, and backdrop
dismissal all write `true`, so later page loads do not reopen it. `Header`
supplies a persistent
"How this works" / "So funktioniert es" button that opens it manually
from any view. Clearing the site's browser storage makes the next load a
first visit again.

The modal has `role="dialog"`/`aria-modal="true"`, moves focus into itself,
traps Tab/Shift+Tab, closes on Escape or a click/tap on the dimmed backdrop
(but not on bubbled clicks inside the dialog), prevents background scrolling,
and restores focus to the manual trigger on close. No account, cookie,
backend call, schema field, or server-side learner state is involved.

### 5.6 Session-local patient completion (`App.jsx`, added 2026-08-09)

`completedPatientIds` is seeded from and written to `sessionStorage`
(key `icd10-prototype:completed-patients`, a JSON array of patient ids) —
**not** `localStorage`. This is deliberate: `REQ-UI-02` specifies
"completion status is session-local," and `REQ-INT-05` prohibits
*server-side* learner history, not a client-side, session-scoped marker.
`sessionStorage` is cleared when the browser session ends, satisfying both
without a requirements amendment. Marked complete when a playthrough
reaches the `review` view (which already implies every question in that
patient was submitted). Surfaced as a per-`PatientCard` "Completed" badge
and an aggregate "N of 6 patients completed" line on the roster, with a
"Reset progress" control (confirms, then clears both the state and the
`sessionStorage` entry) shown whenever there is something to reset.

### 5.7 Patient-card sizing

`.patient-card` has a fixed height (`15.5rem`), not `height: 100%` under a
stretched grid row: CSS Grid's default row-stretch only equalizes cards
within the same row, and a 6-card/3-row roster still looked uneven across
rows. The heading reserves a 2-line `min-height` (whether or not the
"Completed" badge is present, since that badge can push the heading onto a
second line) and the summary is `-webkit-line-clamp: 4` — both
deterministic regardless of language or content length, verified
pixel-identical across all 6 cards via Selenium.

### 5.8 Build-time version/build-date injection (`vite.config.js`, added 2026-08-09)

```js
define: {
  __APP_VERSION__: JSON.stringify(pkg.version),        // from package.json, currently "0.9.9"
  __BUILD_DATE__: JSON.stringify(new Date().toISOString().slice(0, 10)),
}
```

Both are static once built — not live values — and rendered by
`Footer.jsx` as `v{version} · build {date} · © {year} Juno Anna Marx`,
where the copyright year alone is computed at render time
(`new Date().getFullYear()`) so it never goes stale between builds.
Deliberately not a git commit SHA: the Docker build context excludes
`.git` (root `.dockerignore`), and wiring a real SHA through as a build
arg would touch the Dockerfile, both Compose files, and CI — judged out of
proportion to a footer.

### 5.9 Build output contract

`vite.config.js` sets `build.outDir = '../public'` with `emptyOutDir: false`
— `npm run build` (run from `frontend/`) writes `index.html` and a
content-hashed `assets/` directory directly into `app/public/`, alongside
the hand-written `index.php` and `.htaccess`, without deleting them. In the
Docker build, the frontend build runs in its own clean stage; the runtime
stage explicitly copies only `app/public/index.php` and
`app/public/.htaccess` by name from the source tree, then copies the
frontend build stage's output on top.

## 6. Build, environment, and deployment contract

### 6.1 Environment variables (read by `src/Config.php`)

| Variable | Required | Default | Meaning |
|---|---|---|---|
| `ICD_DB_HOST` | no | `127.0.0.1` | MySQL host |
| `ICD_DB_PORT` | no | `3306` | MySQL port |
| `ICD_DB_NAME` | **yes** | — | Database name |
| `ICD_DB_USER` | **yes** | — | Database user |
| `ICD_DB_PASSWORD` | no | `''` | Database password |

The Python bootstrap (`prototype_baseline/persistence_candidate/`)
reads the identical five variables — same names, same defaults.

### 6.2 `Dockerfile` stages (repo root)

| Stage | Base image | Produces |
|---|---|---|
| `frontend-build` | `node:22-alpine` | `/app/public/{index.html,assets/*}` |
| `vendor` | `composer:2` | `/app/vendor` (`--no-dev --optimize-autoloader`) |
| `runtime` (final) | `php:8.4-apache` | `pdo_mysql` + `rewrite` enabled; `vendor/`, `src/`, `public/index.php`, `public/.htaccess`, and the frontend build output copied into `/var/www/html` |

Build stages themselves are unchanged since the case-centric implementation
— the migration touched `app/src`/`app/frontend/src` contents, not the
stage structure. **Base-image references did change at the `PROTOBASE-1.0`
freeze** (§6.3): on `master`, all three `FROM` lines above resolve by
manifest-list digest, not floating tag — exact digests in §8. The
`develop` branch's copy of this `Dockerfile` intentionally keeps the
floating tags (`node:22-alpine`, `composer:2`, `php:8.4-apache`) for
ongoing development; see `docs/DEVELOPMENT_DOCUMENTATION.md` §19.

### 6.3 Bootstrap: wired to `MODELBASE-0.2`, not the historical pipeline

**This is the one part of the deployment contract that materially changed
during the forward migration, and it changed twice** — see
`docs/CHANGELOG.md`'s "steps 2-3 completed for real" entry for the full
story. The `bootstrap` service in both `docker-compose.yml` and
`prototype_stack/compose.yaml` now builds from
`prototype_baseline/Dockerfile.bootstrap`
(context: `prototype_baseline/`), running
`persistence_candidate/bootstrap_mysql_0_2.py` — which applies
`mysql_schema_0_2.sql` to an empty database, then runs the idempotent
`load_mysql_0_2.py` loader. `archived/prototype_baseline_0_1/Dockerfile.bootstrap`
(the historical `CASEBASE-0.2` pipeline) still exists on disk, kept for
reference, but is no longer referenced by either Compose file or CI's
`publish-images` job (a housekeeping-pass fix — it was, until then;
`docs/DEVELOPMENT_DOCUMENTATION.md` §17).

`prototype_baseline/Dockerfile.bootstrap`'s own base image
(`python:3.12-slim-bookworm`) is pinned by manifest-list digest on
`master` as well, since the `PROTOBASE-1.0` freeze's post-tag correction
(§8; `docs/DEVELOPMENT_DOCUMENTATION.md` §19.4) — it was missed in the
original pinning pass because it is one Dockerfile-reference-hop removed
from `docker-compose.yml`/the root `Dockerfile`/`ci.yml`, not named
directly in any of them. `develop`'s copy stays on the floating tag, same
split as everything else in §6.2.

| Service | Role | Lifecycle |
|---|---|---|
| `db` | `mysql` — pinned to a manifest-list digest on `master` as of the `PROTOBASE-1.0` freeze (§8); `develop` keeps `mysql:latest`, named volume `mysql_data` | long-running; healthcheck via `mysqladmin ping`; the underlying policy is still deliberately unpinned below the major version (`docs/DEVELOPMENT_DOCUMENTATION.md` §10.1) — the digest pin freezes *which* version currently satisfies that policy, not the policy itself; re-resolving after an intentional unfreeze would still follow it |
| `bootstrap` | built from `prototype_baseline/Dockerfile.bootstrap` | one-shot (`restart: "no"`); applies schema on an empty DB, then runs the idempotent loader; reports `inserted` on first run, `no_op` on every identical re-run |
| `app` | built from `Dockerfile` (repo root) | long-running; published on `${APP_HTTP_PORT:-5860}` |

**A real gap this rewrite exists partly to close on paper:** every
"verified" claim for steps 2–3 up to 8 August 2026 was checked against a
scratch `docker run` MySQL container, not this actual bootstrap path — so
the repository's own `docker compose up` served the old model end to end
until this was fixed 9 August 2026. Don't trust a "verified" claim
anywhere in this project's history without checking it was against the
*real* Compose path, not an isolated one; this document intentionally
survives that lesson learned rather than quietly re-describing only the
fixed end state.

**`mysql:latest` and the named volume — a real operational consequence:**
`mysql:latest` refuses to open a data directory across a major-line jump
(e.g. `8.4.8` → `26.7.0`, "`Invalid MySQL server upgrade`"). In practice:
every time `mysql:latest` moves to a materially newer release line, or the
runtime schema itself changes shape (as it did for this migration),
`docker compose down -v` (removing `mysql_data`) is required before a
fresh `up` succeeds. The loader's own read-before-write conflict check
adds a second reason a stale volume can block a legitimate pre-freeze
content edit: reloading *changed* content under an *existing* versioned
`patient_baseline_id`/`question_baseline_id` is refused by design (correct
behaviour, not a bug) — a full `down -v` + rebuild is the correct response,
not a workaround.

### 6.4 Local development workflows

| Task | Command |
|---|---|
| Run PHP tests | `php vendor/bin/phpunit --testsuite unit` (no DB); `--testsuite integration` needs `ICD_DB_*` against a `MODELBASE-0.2`-loaded MySQL; `--testsuite e2e` needs the app + Selenium running — §7 |
| Serve the API + built frontend without Docker | `ICD_DB_*=... php -S 127.0.0.1:PORT -t public router.php` (from `app/`) |
| Rebuild the frontend into `app/public/` | `cd app/frontend && npm run build` |
| Frontend dev server with API proxy | `cd app/frontend && npm run dev` (proxies `/api` to `http://127.0.0.1:8080` per `vite.config.js`) |
| Full stack via the self-contained bundle | `docker compose build bootstrap app && docker compose up -d --wait app` |
| Full stack via `prototype_stack` (local, no git-sync) | `cd prototype_stack && APP_SOURCE_DIR=.. docker compose --env-file .env -f compose.yaml up -d --wait db && docker compose ... run --rm --no-deps bootstrap && docker compose ... up -d --wait app` |
| Sanity-check the real running stack | `curl http://127.0.0.1:5860/api/patients` — expect 6 patients with `display_name`, not `CASE-*` ids |
| Ad hoc browser verification | This project's own Selenium infrastructure (`app/tests/E2E/docker-compose.yml`, `php-webdriver/webdriver`) — **not Playwright**, an explicit, repeated project rule |

### 6.5 Self-contained bundle (`docker-compose.yml`, repo root) and CI

`docker compose up -d --wait app` brings up `db → bootstrap → app`,
correctly ordered. `docker compose --profile test up -d --wait selenium &&
docker compose --profile test run --rm test` runs the full suite fully
containerized — the suite it runs now passes (§7), though this container
form has not itself been separately re-run since the step 8 rewrite (each
suite was verified directly against equivalent live infrastructure
instead; see `docs/CHANGELOG.md`'s "Step 8" entry). Published image tags
(`.github/workflows/ci.yml`'s `publish-images` job, `main` only, gated on
the other four jobs passing) **have** been rebuilt against the forward
model: a real GitHub-hosted run (31314862118, 2026-08-09 13:04-13:09 UTC,
against commit `acb2ca6`) completed all 5 jobs successfully, including a
multi-arch (`linux/amd64,linux/arm64`) build of all three images - the
same run that confirmed §10.8's `--platform=$BUILDPLATFORM` fix for a
build that had previously hung for 1.5+ hours (`docs/DEVELOPMENT_DOCUMENTATION.md`
§10.8). Anyone pulling `ghcr.io/junomarx/bsc-thesis-icd10:latest` now gets
the current forward model; `docker compose build bootstrap app` (native
source build) remains a valid alternative but is no longer the only
reliable path.

## 7. Test inventory (file → coverage), rewritten for the forward model (step 8)

`app/tests/Support/Fixtures.php` and every file under `Unit`/`Integration`/
`E2E` were rewritten against `CodingQuestion`/`ResponseInput`/the
tagged-response contract. Current status, each figure independently
re-verified while writing this section, not carried over from memory:

| Suite | Command | Result |
|---|---|---|
| Unit | `php vendor/bin/phpunit --testsuite unit` (no DB) | **77/77 passing** |
| Integration | `ICD_DB_*=... php vendor/bin/phpunit --testsuite integration` (needs a `MODELBASE-0.2`-loaded MySQL) | **160/160 passing, 2173 assertions** |
| Unit + Integration | `--testsuite unit,integration` | **237/237 passing, 2290 assertions** |
| E2E | `php vendor/bin/phpunit --testsuite e2e` (needs the app + Selenium running) | **9/9 passing, 59 assertions** |

| `TEST-*` | Implementing file(s) |
|---|---|
| `TEST-GATE-01` | `Unit/RuleGateTest.php` |
| `TEST-MAP-01` | `Unit/RuleMapTest.php` |
| `TEST-STATUS-01` | `Unit/RuleStatusTest.php` |
| `TEST-DEPTH-01` | `Unit/RuleDepthTest.php` |
| `TEST-EVID-01` | `Unit/RuleEvidTest.php` |
| `TEST-SPEC-01` | `Unit/RuleSpecTest.php` |
| `TEST-CORRECT-01` | `Unit/RuleCorrectTest.php` |
| `TEST-PREC-01` | `Unit/PrecedenceTest.php` |
| *(no upstream ID yet — net new)* | `Unit/RuleRelHardTest.php`, `Unit/RuleRelSpecTest.php`, `Unit/RuleNoaTest.php` — zero coverage existed for `RULE-REL-HARD-01`/`REL-SPEC-01`/`NOA-01` before this rewrite |
| `TEST-ARC-01` | `Integration/ArchitectureIsolationTest.php` |
| `TEST-DET-01` | `Integration/DeterminismTest.php` |
| `TEST-API-01` | `Integration/EvaluationApiTest.php` |
| `TEST-RC-01` | `Integration/ReferenceResponseTest.php` — reads the 143-row `RCBASE-0.3` oracle (frozen, `reference_responses_0_3.csv`) directly |
| `TEST-E2E-01` | `E2E/LearnerWorkflowTest.php` |
| `TEST-E2E-02` | `E2E/VerificationOnlyQuestionVisibilityTest.php` (renamed from `VerificationOnlyCaseVisibilityTest.php`) |
| *(none — frontend-only)* | `E2E/ProgressBadgeTest.php`: the `sessionStorage` per-patient completion badge (§5.6) has no backend equivalent, so it has no upstream `TEST-*` identifier |
| *(none — frontend-only)* | `E2E/TutorialTest.php`: first-visit auto-show, four-step Back/Next flow, dismissal persistence across reload, manual reopening, Escape/backdrop close, and focus restoration (§5.5) |
| *(none — frontend-only)* | `E2E/ThemeTest.php`: deterministic light start, dark-mode application across the roster/tutorial, browser-storage persistence across reload, and switching back to light (§5.4) |

**Provenance carried by `TEST-RC-01` specifically:** all 143 rows are now
confirmed against genuine source, not reconstruction alone. 125 learner rows
were audited (implementation-order step 9, `docs/CHANGELOG.md`'s "Step 9"
entry) against `chapter3_question_bank_source_audit.md` (`QSAUDIT-0.1`)
§4.1-4.6's source-cited table, zero discrepancies. The 4 legacy rows
(`VQ-005..008`) were first re-verified in step 9 by direct rule replay
against their documented case facts, then reconciled for real: the
archived raw `RCBASE-0.2` file
(`archived/prototype_baseline_0_1/verification/reference_responses_0_2.csv`)
was located and diffed field by field against all 4 rows - exact match on
every field, no reconstruction gap remains. `provenance_status` reflects
this (`..._human_oracle_audit_confirmed_against_qsaudit_0_1` for the 125
learner rows, `exact_semantic_carry_forward_confirmed_against_rcbase_0_2`
for the 4 legacy rows). Step 10's formal conformance claim (`REQ-VER-05`)
has since been made: the file dropped its `_candidate` name
(`reference_responses_0_3.csv`), and the clean-environment principal
verification run confirmed all 143/143 rows exact conformance, zero
defects (`docs/CONFORMANCE_REPORT.md`). See `ReferenceResponseTest.php`'s
own docblock and `docs/REQUIREMENTS_TRACEABILITY.md` (`REQ-VER-08`/`09`)
for the exact distinction between this audit and that freeze run.

## 8. Exact tool/version pins observed in this implementation

**On `master`, pinned at the `PROTOBASE-1.0` freeze (step 10) — see
`docs/CONFORMANCE_REPORT.md` §2 and `docs/DEVELOPMENT_DOCUMENTATION.md`
§19. The `develop` branch's copies of the same files intentionally keep
every image below on its floating tag for ongoing development.**

| Component | Version | Where pinned |
|---|---|---|
| PHP (runtime image) | 8.4.24 | `php:8.4-apache` base image — pinned by manifest-list digest `sha256:5f8050825b2f3de4efb0d81149c86643a9ee9c0a74ed4595ca2ad69ebfeb35fb` in `Dockerfile` (repo root) |
| MySQL | 26.7.0 | pinned by manifest-list digest `sha256:66aec17cd21a956029b83f083b813073859e8355dc1a00e55df6ba02f0e32345` in `docker-compose.yml`, `prototype_stack/compose.yaml`, and `.github/workflows/ci.yml`'s `backend-integration` job — §6.3's major-version-unpinning policy still governs *which* version gets pinned at each freeze, it just no longer floats between freezes |
| Node (build stage only) | 22.23.2 | `node:22-alpine`, pinned by manifest-list digest `sha256:c610fcdfb1d5b4740dd70c284ed3cb16bb857e0f7166196e36a5501df7a3aa32` in `Dockerfile` (repo root) |
| Composer (build stage only) | 2.10.2 | `composer:2`, pinned by manifest-list digest `sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040` in `Dockerfile` (repo root) |
| React | 19.2.8 | `app/frontend/package.json` |
| Vite | 8.2.x | `app/frontend/package.json` |
| Frontend app version | 0.9.9 | `app/frontend/package.json`, rendered in the footer (§5.8) |
| PHPUnit | 11.5.x | `app/composer.lock` |
| php-webdriver/webdriver | 1.16.0 | `app/composer.lock` |
| Selenium/browser (ad hoc verification only, not the app) | `selenium/standalone-chrome` (amd64, CI/E2E default) pinned by manifest-list digest `sha256:9569014786466376d3e5cf8a7758562368cd9637f783dcd3abdb2eaf3a0d5cd7`; `seleniarm/standalone-chromium` (arm64, local dev default) pinned by `sha256:d644a5f679e83e63344cee11c08fc2c7bf4acf43217434a8621a2bc85f7473a5` | `app/tests/E2E/docker-compose.yml`, `.github/workflows/ci.yml` |
| Python (bootstrap image) | 3.12.13 | `python:3.12-slim-bookworm`, pinned by manifest-list digest `sha256:4766d8b510c428e595d74b9cc5bbb2fae8e26316fffb4adc89908d79aacd58a2` in `prototype_baseline/Dockerfile.bootstrap` — added in the post-`dev-freeze` correction (§6.3, `docs/CONFORMANCE_REPORT.md` §9), not part of the original six |

**`docs/environment_manifest_0_1.json`** records the exact resolved
version and manifest-list digest for every image above (frozen status),
the `REQ-CFG-01` execution-environment evidence the `PROTOBASE-1.0` freeze
run bound in `docs/CONFORMANCE_REPORT.md` §2/§9.

**Not pinned, deliberately, and not equivalent to the table above:**
`docker-compose.yml`'s own default image references —
`ghcr.io/junomarx/bsc-thesis-icd10:latest`, `:dev`, and
`bsc-thesis-icd10-bootstrap:latest` — stay on mutable tags, because they
are this repository's *own* published output, not a third-party
dependency; their identity is the git commit they were built from, not a
version to pin. The practical consequence: **`docker compose pull`
against those defaults does not reproduce this frozen result** once
`master` moves past the frozen commit, because `.github/workflows/ci.yml`'s
`publish-images` job overwrites `:latest`/`:dev` on every subsequent push.
The only reproducible path to the exact `PROTOBASE-1.0` state is
`docker compose build` from the pinned `Dockerfile`/`Dockerfile.bootstrap`
at the frozen/tagged commit, not a pull of the published tags. See
`docs/environment_manifest_0_1.json`'s `deliberately_not_recorded` note
and `docs/CONFORMANCE_REPORT.md` §9.

## 9. Explicit non-implementations

No authentication/user model anywhere in `src/`; no table or column for
learner attempt history (session-local completion marks live in the
browser's `sessionStorage`, never in `app/src/` or the schema — §5.6); no
route accepting more than one response per request; no extramural-specific
rule class; no LKF pricing/reimbursement logic; no client-side router in
the frontend (three plain conditional views in `App.jsx`); no points,
leaderboard, timer, or lives anywhere in the gameful-presentation layer
(`REQ-GAM-01`, checked by direct code inspection, not merely absent from
the UI).
