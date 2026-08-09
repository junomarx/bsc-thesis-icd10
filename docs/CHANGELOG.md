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

## 2026-08-09 — Step 8 documentation propagation: user guide and handoff brought current

Follow-up to the Step 8 test-suite migration below: the guide and handoff
still described the test suite as broken/pending after it had actually
started passing, which this entry closes out.

### Changed

- `docs/USER_GUIDE.tex`/`.pdf` (recompiled, 10 pages, clean) — removed the
  "Optional: run the automated tests" section's warning box ("Currently
  broken, pending an in-progress test-suite update") and replaced it with
  a plain statement of the current, passing counts (77 unit / 160
  integration / 7 e2e); simplified the following paragraph to state
  plainly that a successful run ends with no PHPUnit failures, instead of
  conditioning that on the now-completed update. Guide revision left at
  0.3 - this is a same-day correction of that revision's own content, not
  a new milestone.
- `HANDOFF.md` §1's reading-order notes for `docs/IMPLEMENTATION_SPECIFICATION.md`
  and `docs/REQUIREMENTS_TRACEABILITY.md` — both previously phrased the
  ⚠→✅ status-symbol cleanup as something that "should" happen "once
  refreshed"; both files had already been refreshed, so the wording was
  stale the moment it was written. Restated as fact.

### Verified

- `latexmk -pdf -interaction=nonstopmode -halt-on-error USER_GUIDE.tex`:
  clean compile, 10 pages, no undefined references or LaTeX warnings.
- Repository-wide grep for `"47 of 49"`, `"currently broken"`, and
  `"step 8...not started"`-shaped phrasing across `docs/`, `HANDOFF.md`,
  and `README.md`: every remaining hit is a legitimate historical
  reference (describing what was replaced, or a dated CHANGELOG record of
  past state), not a live claim.

## 2026-08-09 — Step 8: full test-suite migration to the patient/question model

Implementation-order step 8. `app/tests/Support/Fixtures.php` and every
`Unit`/`Integration`/`E2E` file were rewritten against `MODELBASE-0.2`/
`RULEBASE-0.2` - none of it had been touched since the forward migration
began, so this was a genuine rewrite, not a patch. Triggered directly by
the project owner pasting a real CI failure log after the previous CI
infrastructure fix, then pasting it again unchanged as confirmation to
proceed with the full rewrite rather than stopping at the smaller fix.

### Changed

- `tests/Support/Fixtures.php`: replaced the `CaseFacts`-based builders
  (`copdCase()`/`statusCase()`) with `CodingQuestion`-based equivalents
  (`copdQuestion()`/`statusQuestion()`) plus a general-purpose `question()`
  builder and typed-fact/relation/option helpers
  (`enumFact`/`decimalFact`/`acceptedReference`/`factConflict`/
  `lessSpecificSupported`/`codeOption`/`noneOfAboveOption`/...), needed
  because the net-new rules require relation kinds and linked facts the
  old boolean `responseDomain` map had no way to express.
- Every `Unit/Rule*Test.php` and `PrecedenceTest.php`: updated call sites
  for the new predicate signatures (`CodingQuestion` instead of
  `CaseFacts`; `RuleGate::evaluate()`'s new `(question, response, record)`
  order and `ResponseInput` parameter; `Precedence::terminalClass()`'s
  second argument now a graded-matches array, not a bool).
- `Integration/ArchitectureIsolationTest.php`: table-name assertion updated
  to the 9 `MODELBASE-0.2` tables; smoke-test call updated to
  `Q-001-01`/tagged response.
- `Integration/DeterminismTest.php`, `EvaluationApiTest.php`: updated to
  question ids and the tagged-response contract; `EvaluationApiTest`
  gained cases for `unsupported_response_kind` (HTTP boundary, not a gate
  reason) and for a `verification_only` question remaining evaluable by ID
  (`REQ-VER-09`) despite 404ing on the learner-facing detail read.
- `Integration/ReferenceResponseTest.php`: now reads
  `prototype_baseline_0_2_design/verification/reference_responses_0_3_candidate.csv`
  (143 rows: 125 new + the 18 historical `legacy_rc_id` regression
  obligations, mapped onto the 8 hidden `VQ-*` questions) instead of the
  historical 18-row file, with tagged-response construction from each
  row's `response_kind`. Its docblock states the real provenance caveat
  rather than implying a frozen conformance claim: every row's own
  `provenance_status` column still reads
  `forward_specification_derived_pending_human_oracle_audit` (step 9, not
  done) - running them now is deliberate early signal, not a claim that
  step 9 is complete.
- `E2E/SeleniumTestCase.php`: rewritten for the new markup
  (`.patient-list`/`.patient-card[data-patient-id]`/`.question-view`/
  `.code-list`/`.question-feedback`/`.improvement`), the tagged-response
  API contract, and a `navigateToQuestionWithOption()` helper - see
  *Fixed* below for why that helper exists.
- `E2E/LearnerWorkflowTest.php`: now drives `PATIENT-001`'s `Q-001-01`
  through the three feedback classes (was `CASE-001`).
- `E2E/VerificationOnlyCaseVisibilityTest.php` → renamed
  `VerificationOnlyQuestionVisibilityTest.php` (`git mv`, matching the
  project's question-centric vocabulary): the "absent from the one
  navigation surface" check no longer greps button labels for `CASE-004`/
  `CASE-008` (no such flat list exists in the new UI) - it instead asserts
  the roster's total "N questions" badge sum is exactly 25, which cannot
  hold if any of the 8 hidden `VQ-*` questions were reachable through it.
  Added a third check that `GET /api/questions/{VQ-*}` 404s, matching
  `QuestionController::show()`'s explicit boundary.
- `E2E/ProgressBadgeTest.php`: rewritten against the session-local
  per-patient completion badge (`REQ-UI-02`, added in step 7) - the
  per-case `localStorage` attempt/last-classification badge it used to
  test (`lib/progress.js`) was deleted during the forward migration.

### Fixed

Three real bugs found only by actually running the new E2E tests against
the real Selenium/Chrome stack, not by writing code that merely compiled:

- **Roster race condition**: `openRoster()` waited for `.patient-list`
  (the `<ul>` container), which renders immediately, before
  `GET /api/patients` resolves - so a test could observe zero patient
  cards despite the roster "being open." Fixed to wait for an actual
  `.patient-card`.
- **Hardcoded first-question assumption**: `LearnerWorkflowTest` assumed
  opening `PATIENT-001` always shows `Q-001-01` (the COPD question) first.
  It doesn't - question order is shuffled per playthrough (`REQ-INT-03`),
  and `PATIENT-001` has three questions. All three data-set submissions
  timed out waiting for a `J44.*` option that was on a different,
  not-yet-reached question. Fixed with `navigateToQuestionWithOption()`:
  submits an arbitrary valid answer to whatever question is on-screen
  until one containing the target code family appears.
- **Age-badge false match**: `rosterTotalQuestionCount()`'s regex
  (`/(\d+)\s+\p{L}+/u`) matched *any* "number followed by a word" badge,
  including the age badge ("68 yrs") - summed across all 6 patients this
  added 384 to the real total of 25, asserting 409. Fixed by anchoring on
  the literal word "questions".

### Verified

- `php vendor/bin/phpunit --testsuite unit`: **77/77 passing** (up from 49
  - added dedicated coverage for `RULE-REL-HARD-01`/`RULE-REL-SPEC-01`/
  `RULE-NOA-01`, which had zero unit tests of their own before this).
- `php vendor/bin/phpunit --testsuite integration` against a freshly
  bootstrapped `MODELBASE-0.2` MySQL instance (throwaway `docker run`
  container, torn down after): **160/160 passing, 2173 assertions** -
  every one of the 143 reference-response rows included.
- `php vendor/bin/phpunit --testsuite unit,integration` combined: **237/237
  passing, 2290 assertions**.
- `php vendor/bin/phpunit --testsuite e2e`, real Selenium against the
  actual running `app` container (not a substitute): **7/7 passing**,
  after the three fixes above were found and corrected by this exact run.
- The self-contained bundle's `docker compose --profile test run --rm
  test` path was not separately re-run in container form this pass (the
  three suites above were each verified directly against equivalent
  live infrastructure); it now points at a suite that passes, where
  before it didn't, but a container-form confirmation is worth doing
  before relying on it for a real CI publish.

### Deviations

- `docs/DEVELOPMENT_DOCUMENTATION.md` §13.4 (written earlier this session)
  said test-suite migration was deliberately deferred to "its own
  dedicated pass, reviewed... not scattered across a dozen unrelated
  diffs." This entry *is* that dedicated pass - not a contradiction, the
  thing being deferred has now arrived.
- Step 9 (oracle/source audit reconciliation) remains explicitly
  unstarted: this entry makes the 125 new reference-response rows
  *exercised*, not *human-audited*. See `ReferenceResponseTest.php`'s own
  docblock and `REQUIREMENTS_TRACEABILITY.md` for the exact distinction.

## 2026-08-09 — CI's `backend-integration` job fixed a second instance of the bootstrap-wiring bug

The project owner ran the CI workflow manually (`workflow_dispatch` — push
triggers are deliberately disabled) and reported the failure log.
`php-unit` erroring 47/49 is the already-tracked, not-yet-started step 8
gap (`HANDOFF.md`) - expected, not new. `backend-integration` erroring
differently was new and real: `BaselineIdentity::__construct(): Argument
#8 ($patientBaselineId) must be of type string, null given`. This is the
exact same bug class as the app-deployment bootstrap gap fixed earlier
this session (`docs/CHANGELOG.md`'s "steps 2-3 completed for real" entry) -
just a second, independent occurrence: `backend-integration`'s own steps
still applied `prototype_baseline_0_1/scripts/apply_mysql_schema.py` and
`load_mysql.py` (the historical `CASEBASE-0.2` pipeline) directly, never
routed through `prototype_stack/compose.yaml` at all, so the earlier
bootstrap-repoint fix never touched it. (The `e2e` job *does* go through
`prototype_stack/compose.yaml` and was already correct.)

### Fixed

- `.github/workflows/ci.yml`'s `backend-integration` job now applies
  `prototype_baseline_0_2_design/persistence_candidate/apply_mysql_schema_0_2.py`,
  loads via `load_mysql_0_2.py`, and runs `test_mysql_persistence_0_2`
  (working directory set to `persistence_candidate/`, required for that
  test module's sibling imports to resolve - the same cwd quirk noted
  earlier this session).

### Verified

- Reproduced the exact CI step sequence locally against a throwaway MySQL
  container (`docker run`, port 3307, torn down after): schema apply →
  load → `python -m unittest -v test_mysql_persistence_0_2` — all 6 tests
  pass, `MySQL baseline load: inserted`, correct `PROTOBASE-0.3` counts.
- Then ran `vendor/bin/phpunit --testsuite integration` against that same,
  now-correctly-loaded database to see what CI would hit next: the
  `TypeError` is gone, replaced by 25 assertion failures in
  `ReferenceResponseTest` (querying `CASE-001`/`Z01.6`-style fixtures that
  no longer exist in `MODELBASE-0.2`) plus the same `ArchitectureIsolationTest`/
  `DeterminismTest` failures already known. Confirms this fix is real and
  necessary but not sufficient - the remaining redness is entirely the
  already-tracked step 8 (test-suite rewrite), not a second infrastructure
  bug.

### Deviations

- Did not proceed to the full step 8 test-suite rewrite in this same
  pass - that's a substantially larger, separately-scoped task (rewriting
  `app/tests/Support/Fixtures.php` and every `Unit`/`Integration`/`E2E`
  file for the patient/question model, then re-proving all 18 historical
  regression expectations), left for an explicit go/no-go rather than
  folded silently into an infrastructure bugfix.

## 2026-08-09 — Default learner-facing port changed 8080 → 5860

Project-owner request, to avoid conflicting with other applications on a
shared deployment host. The self-contained bundle's `docker-compose.yml`
(`APP_HTTP_PORT` default) was changed directly by the project owner; this
entry covers bringing the rest of the port's own references into line with
it, found via a repository-wide search rather than guessed at.

### Changed

- `prototype_stack/compose.yaml`'s `APP_HTTP_PORT` default and
  `prototype_stack/README.md`'s architecture diagram — the separate
  `stack.sh`-managed deployment path, kept consistent with the bundle's new
  default even though it has its own independent env var.
- `app/tests/E2E/{docker-compose.yml,README.md,SeleniumTestCase.php}`'s
  documented/hardcoded `8080` fallbacks (`ICD_E2E_BROWSER_BASE_URL`/
  `ICD_E2E_BASE_URL` defaults and the usage-comment example) — these exist
  specifically to match the Compose bundle's default so an E2E run against
  a freshly `docker compose up`'d stack works without extra env vars.
- `README.md`, `HANDOFF.md`, `docs/IMPLEMENTATION_SPECIFICATION.md`,
  `docs/USER_GUIDE.tex`/`.pdf` (recompiled, 10 pages, clean) — every
  example `curl`/browser URL and the two troubleshooting section titles
  naming the port explicitly.

### Deviations (intentional, not overlooked)

- `.github/workflows/ci.yml` explicitly sets its own `APP_HTTP_PORT=8080`
  for its Compose invocation and curls/configures E2E base URLs against
  that same explicit value — self-consistent regardless of the Compose
  file's *default*, so there is no functional need to change it, and CI
  automation was left alone rather than touched without a concrete reason.
- `app/frontend/vite.config.js`'s dev-server proxy target (`8080`) is an
  unrelated, host-native local-dev convention (pointing at a `php -S
  127.0.0.1:8080` you'd start yourself per the user guide's dev workflow
  section), not the Docker-published port this change is about.
- `docs/CHANGELOG.md`'s own prior dated entries were not rewritten — they
  are a historical record of commands actually run at the time, not a
  living reference.

## 2026-08-09 — Documentation consolidation: all five living documents brought current for the forward model

Project-owner request: "update/put all the documentation about the
implementation together." Every document `CLAUDE.md`'s documentation-upkeep
table names was either fully rewritten or substantially extended in this
pass — none of the "stale by construction" flags left over from the
mid-migration snapshot survive.

### Changed

- `docs/IMPLEMENTATION_SPECIFICATION.md` — full rewrite. Repository layout,
  the 9-table `MODELBASE-0.2` schema, every rule's exact predicate, the
  full API contract (all 5 endpoints, exact request/response shapes
  including `explanation_de`), the frontend component tree and its
  9 August additions (i18n architecture, session-local completion,
  card-sizing fix, build-time version injection), the bootstrap/deployment
  contract (§6.3 records the persistence-integration gap and fix as a
  permanent lesson, not just a changelog line), and an honest §7 stating
  plainly that `app/tests/*` is broken rather than reproducing a stale
  `TEST-*` mapping that would misrepresent current reality.
- `docs/REQUIREMENTS_TRACEABILITY.md` — full re-audit against every
  `REQ-*` in the current (forward-revision-0.7-merged) catalogue, not the
  historical 31-row table. Introduces a fourth status symbol (⚠ "verified
  by inspection, automated test currently broken") for five requirements
  where the underlying property demonstrably holds but the PHPUnit test
  that's supposed to prove it mechanically doesn't run yet (step 8) —
  distinguishing that honestly from a plain ✅ was judged more useful than
  either overclaiming or lumping it in with genuinely unverified rows.
  `REQ-DAT-03`/`06` were checked directly (a full set-difference over the
  99 catalogue codes against every `question_code_domain`/`improvement_code`
  reference, confirming zero unused records) rather than asserted.
  `REQ-VER-08`/`09` are honestly deferred to step 9, citing the oracle
  candidate file's own `provenance_status` column.
- `docs/DEVELOPMENT_DOCUMENTATION.md` — appended §13 ("Forward redesign:
  patient/question model" — why the case-centric model was replaced rather
  than extended, the persistence-integration gap as a named lesson, and why
  the test-suite migration was deliberately deferred to its own pass) and
  §14 ("UXBASE-0.1 visual/gameful polish" — the `Must`-only scoping
  decision, the two-directional content-translation architecture, why the
  completion marker needed no requirements amendment, and the patient-name
  override's exact scope). Appended rather than renumbered: §11's status
  section and §12's table both still describe the historical
  implementation, now with an explicit pointer to §13/14 for current
  status, so no existing cross-reference from another document broke.
- `docs/USER_GUIDE.tex`/`docs/USER_GUIDE.pdf` — Section 3 ("Using the
  prototype") fully rewritten for the patient/question workflow: language
  switch, orientation panel, patient roster with completion tracking and
  reset control, patient dossier, question view with progress bar and
  exit control, technical-details disclosure, patient review. Added a
  prominent warning in the installation section that `docker compose pull`
  currently fetches the pre-migration images (the exact bug the project
  owner found by using the app) with the local-build workaround given
  inline; added a similar warning to the automated-tests section instead
  of letting a reader discover the broken suite themselves. Recompiled
  with `latexmk -pdf` (10 pages, clean — no undefined references,
  no LaTeX warnings).
- `HANDOFF.md` — revised again (same day) to record step 7's completion,
  the three bugs found by using the running app, the patient-name
  override, and this documentation pass itself; §1's reading order no
  longer tells a reader to distrust the four documents above.
- Two small in-code documentation-accuracy fixes surfaced while gathering
  facts for the above: `CatalogueRepository`'s docblock still said "13
  SUBSET-0.1 records" (now 99, `SUBSET-0.2`); `composer.json`'s
  description still said `RULEBASE-0.1 / MODELBASE-0.1`.

### Verified

- `docs/USER_GUIDE.tex` compiles cleanly via `latexmk -pdf
  -interaction=nonstopmode -halt-on-error` — 10 pages, zero undefined
  references, zero LaTeX warnings; `latexmk -c` cleaned all auxiliary
  files afterward.
- Every schema/API/rule fact asserted in `IMPLEMENTATION_SPECIFICATION.md`
  was taken from reading the actual current source
  (`app/src/`, `mysql_schema_0_2.sql`) during this pass, not recalled from
  memory of writing it originally.

## 2026-08-09 — Footer: version/build date and author/year

### Added

- `components/Footer.jsx`, rendered once at the bottom of every view:
  "v{version} · build {date} · © {year} Juno Anna Marx". `version` and
  `build date` are baked in at `npm run build` time via `vite.config.js`'s
  `define` (`__APP_VERSION__` from `package.json`, now `0.7.0` and
  `"author": "Juno Anna Marx"`; `__BUILD_DATE__` from the build's own
  clock) - static until the next build, not a live value. The copyright
  year is computed at render time (`new Date().getFullYear()`) so it never
  goes stale between builds, unlike the other two.
- Deliberately not a git commit SHA: the Docker build context excludes
  `.git` (root `.dockerignore`), and wiring a real SHA through as a build
  arg would touch the Dockerfile, both Compose files, and CI - out of
  proportion to what was asked for a footer.

### Verified

- `npm run build`/`npm run lint` clean; confirmed `0.7.0`/`Juno Anna Marx`
  present in the built JS bundle, both on disk and re-fetched from the
  actual running container after a rebuild.
- Real browser, this project's own Selenium infrastructure: footer text
  reads exactly `v0.7.0 · build 2026-08-09 · © 2026 Juno Anna Marx` against
  the real running stack.

## 2026-08-09 — Evaluator explanations now bilingual; fixed two raw-token leaks into learner-facing text

Project owner reported German mode still showed an English sentence in the
per-question feedback panel, and separately spotted the literal machine
token `none_of_above` inside that same sentence regardless of language.
Both are the same underlying defect class: internal identifiers leaking
into prose meant for a learner to read, not a developer.

### Fixed

- `Evaluator.php::buildNoaResult()` interpolated the raw enum value
  `none_of_above` directly into its explanation string (e.g. "...so
  `none_of_above` is not correct here."). Now reads "...so "None of the
  above" is not correct here." in both languages.
- `buildRelHardResult()`/`buildGradedResult()`'s `REL-SPEC` branch had the
  same bug class one level deeper: if a cited fact's `learner_label` was
  ever missing, or no fact was cited at all, the fallback embedded the raw
  `fact_key`/`reason_key` column value verbatim (snake_case, e.g.
  `wrong_condition_state`). Not reachable by the current 25-question data
  (verified: every `fact_conflict`/`temporal_context_conflict` row has a
  matching cited fact), but live, reachable code - now falls back to a
  humanized `str_replace('_', ' ', ...)` form instead, matching the pattern
  `RULE-STATUS-01`'s `encounter_setting` interpolation already used.
- Frontend: `QuestionView`'s `not_evaluated` panel interpolated the raw
  gate `reason` enum (`outside_active_subset`, etc.) straight into
  "This submission could not be classified ({reason})." - same leak, one
  layer up. Added `gateReason.*` i18n keys (EN/DE) for all 6 possible
  values (`outside_active_subset`/`undefined_case_relation`/
  `missing_required_case_fact`/`none_option_not_defined`/`malformed_input`/
  `unsupported_response_kind`) and resolve through those instead.

### Added

- `EvaluationResult`/`EvaluationController` gained `explanation_de`,
  additive alongside the existing `explanation` (EN) - every
  `EvaluationResult::classified()` call site in `Evaluator.php` (8 of them)
  now supplies both languages; the constructor takes it as a required
  parameter specifically so a future rule can't ship English-only by
  omission. `QuestionView` selects `explanation_de` when `locale === 'de'`,
  falling back to `explanation` if absent.
- New `Evaluator` helpers `diagnosisRoleDe()`/`encounterSettingDe()`/
  `codingLevelDe()`/`suffixMeaningDe()` translate the small enums
  interpolated into `RULE-STATUS-01`/`DEPTH-01`/`EVID-01`'s explanation
  templates (`main`/`additional`, `inpatient`/`hospital_outpatient`,
  `five-character`, the four FEV1 suffix bands).

### Verified

- `GET /api/questions/Q-002-01/evaluate` (both a `none_of_above` submission
  against `PATIENT-002` and a `code` submission) via `curl` against the
  real running container: `explanation`/`explanation_de` both present,
  correct, and free of the raw token.
- Real browser, this project's own Selenium infrastructure: a full
  3-question `PATIENT-002` playthrough in German mode, always answering
  `none_of_above` to exercise `RULE-NOA-01` (the exact rule the bug was
  in) - every feedback panel's text contains no `none_of_above` substring,
  no stray snake_case token, and reads as German prose.

### Deviations

- Interpolated *fact labels* sourced from `question_fact.learner_label`
  (data, not code) remain English inside an otherwise-German sentence when
  a `RULE-REL-HARD-01`/`RULE-REL-SPEC-01` explanation cites one - same
  dataset-authoring-language limitation already logged for catalogue code
  designations and patient/question content, not newly introduced here.

## 2026-08-09 — Step 7: UXBASE-0.1 visual/gameful polish, plus roster fixes from live use

Implementation-order step 7 (`chapter3_ux_ui_gamification_concept_0_1.md`).
Scoped to the concept's `Must`-priority mechanics plus accessibility-critical
presentation per §10's own fallback guidance, not the full `Should`/`Could`
list. Three additional fixes came directly out of using the running app
(equal card sizing, a progress reset control, and the English-mode code
designations) rather than from the concept document.

### Added

- `components/Orientation.jsx` (`REQ-UI-01`): a collapsible, default-open
  block above the roster heading stating the demonstrator's educational
  purpose and non-clinical boundary, the choose→answer→feedback workflow,
  and a three-class legend (`Correct`/`Suboptimal`/`Incorrect`) with the
  same icon+colour+text pairing used everywhere else. Wording for the
  legend was taken from `chapter3_rule_catalogue_0_2.md` §2's actual
  `relation_kind`→class mapping, not freely paraphrased, since a wrong
  gloss here would misrepresent what the classes mean.
- Visual question-progress bar (`REQ-UI-02`, "progress... plus a visual
  progress indicator") - a row of segments in `QuestionView`, filled up to
  the current question index, `aria-hidden` since the adjacent "Question N
  of M" text already conveys the same information to assistive tech.
  Segment count is `totalQuestions`-derived, never hard-coded (3/5/6 vary
  per patient).
- Collapsed-by-default "Technical details" `<details>` in the feedback
  panel (§3.4 item 5, "optional... disclosure containing criterion,
  determining rule and other trace elements") - determining rule,
  criterion, matched rules, using data the API already returned but the
  UI never surfaced. Native `<details>`/`<summary>` for free keyboard
  operability (`REQ-UI-03`).
- Restrained completion acknowledgment on `PatientReview` (§4 "Could"): a
  small checkmark badge next to the "patient completed" heading, a brief
  scale/fade-in respecting `prefers-reduced-motion` (explicit `animation:
  none` override, not just a shortened duration).
- Aggregate "all 6 patients completed" roster message (§4 "Could") once
  `completedPatientIds.size >= patients.length`, reusing state already
  tracked for the per-card badge.
- **Reset-progress control** (project-owner request, not in the concept
  document): a "Reset progress" link on the roster, shown only when there
  is something to reset, confirms before clearing `completedPatientIds`
  and its `sessionStorage` entry. Session-local by the same `REQ-UI-02`
  reasoning as the completion marks themselves - this doesn't touch
  anything server-side, there being nothing server-side to touch.
- `lib/catalogueTranslations.js` (`CODE_DESIGNATION_EN`): English titles
  for the 87 distinct ICD-10 codes actually displayed as a question option
  (not the full 99-row catalogue subset). **Project-owner-flagged
  limitation, not a design choice**: the runtime catalogue is authored in
  German only (`SUBSET-0.2`, the Austrian BMASGPK edition), so EN mode was
  showing German code names inside an otherwise-English interface.
  Standard English ICD-10 titles, wired into `QuestionView` alongside the
  existing DE content-translation lookup from the previous entry.

### Fixed

- **Patient cards were different heights** depending on `general_health_summary`
  length and whether a `Completed` badge caused the heading to wrap - CSS
  Grid's default row-stretch only equalizes cards within the same grid
  row, not across all rows, so a 6-card/3-row roster still looked uneven.
  Fixed with a fixed `.patient-card` height, a `min-height` on the heading
  (reserves 2 lines whether or not the badge is present) and a 4-line
  `-webkit-line-clamp` on the summary - deterministic regardless of
  language or content length, verified pixel-identical (248px × 6) via
  Selenium, not just visually plausible.

### Verified

- `npm run build`/`npm run lint` clean.
- Real browser, this project's own Selenium infrastructure, against the
  actual running `app` container: orientation text and legend visible by
  default; all 6 roster cards report identical `getSize().height`; a
  3-segment progress bar for a 3-question patient; the E11.9 option shows
  "Without complications" in EN mode with no German leaking through;
  technical details expand to show `RULE-CORRECT-01`/`accepted_response`;
  a full `PATIENT-001` playthrough followed by "Choose another patient"
  shows "1 of 6 patients completed" and a completion badge; "Reset
  progress" (confirmed via native dialog) returns the roster to "0 of 6".

### Deviations

- Not implemented from the concept document: code-option display-order
  permutation (§3.3, explicitly a "may", not required - current canonical-
  position order already places `none_of_above` last); a separate literal
  "Home" screen distinct from the roster (§5 lists them separately, but
  `REQ-UI-01`'s actual requirement - purpose/workflow/legend visible before
  the first question - is satisfied by the roster-mounted orientation
  block without the added navigation-state complexity of a distinct view).
  Both are `Should`/informational, not `Must`, per §4's own priority table.

## 2026-08-09 — German content translation, Austrian patient-name override

Follow-up to the previous entry's EN/DE switch: the project owner correctly
pointed out it only translated interface chrome, leaving patient/question
*content* (summaries, context items, question titles/prompts) in English
regardless of locale - exactly the limitation flagged (but left unresolved)
in that entry. Also carries an explicit, separate project-owner override on
patient naming.

### Added

- `lib/contentTranslations.js`: German translations of all learner-facing
  content - 6 `general_health_summary` strings, all 32
  `patient_context_item.display_text` strings, and both `title`/`prompt`
  for all 25 learner-facing questions (the 8 hidden `verification_only`
  legacy fixtures are never rendered, so weren't translated). Each was
  translated for exact clinical/logical equivalence to the English source,
  not just fluency - these prompts state the facts a learner's answer is
  judged against, so a loose translation would change the task, not just
  its wording. Wired into `PatientCard`, `PatientDossier` and
  `QuestionView`/`PatientReview` as a presentation-layer lookup keyed by
  `patient_id`/`context_item_id`/`question_id`, falling back to the
  backend's own (English) text on any miss. Deliberately kept out of the
  database/API: this is a `REQ-ARC-01` presentation concern, not a
  `QUESTIONBASE-0.1`/`PATIENTBASE-0.1` data change, and doesn't require a
  persistence-layer or evaluator change.

### Changed

- **Project-owner override, patient naming**: `PATIENT-002`/`003`/`004`
  display names changed from `Michael Novak`/`Lea Horvat`/`Sofia Marin` to
  `Michael Bauer`/`Lea Wagner`/`Sophie Mayer` - "patient names should be
  common names that one might encounter in an Austrian setting for
  realism reasons when demoing the prototype." `PATIENT-001/005/006`
  (`Anna Berger`/`Daniel Weiss`/`Peter Gruber`) already satisfied this and
  were left unchanged. Scope note: only `display_name` changed;
  `self_described_background` (e.g. `Slovak-Austrian` for `PATIENT-002`)
  was intentionally left as-is - narrower in scope than the instruction,
  which named only "patient names," and a self-described heritage
  differing from a person's surname isn't itself inconsistent. Flagging
  this explicitly in case the project owner wants that reconciled too.
  This is a `PATIENTBASE-0.1` content edit (`data/patients_0_1.csv`), not
  a version bump - same 6 patients/structure, unfrozen baseline, a pure
  value correction. Updated `runtime_manifest_0_2.json`'s
  `runtime_file_sha256["data/patients_0_1.csv"]` and
  `test_runtime_contract_0_2.py`'s pinned canonical-digest expectation to
  match (both would otherwise fail-closed on the very next check, by
  design - the loader/contract-test hash pinning is meant to catch
  exactly this kind of drift when it's *not* deliberate).

### Verified

- `python3 persistence_candidate/load_mysql_0_2.py --check-only` and
  `python3 -m unittest -v test_runtime_contract_0_2` (run from
  `prototype_baseline_0_2_design/persistence_candidate/`): all 8
  database-independent checks pass against the renamed data and updated
  digest.
- `docker compose build bootstrap app`, `docker compose down -v` (the
  loader's read-before-write conflict check would otherwise reject
  re-importing changed content under the same `PATIENTBASE-0.1` id into
  the previous volume - correct behaviour, not a bug, and exactly why a
  full reload was needed here), `docker compose up -d`: bootstrap reports
  `inserted` with the new digest; `curl /api/patients` confirms all 3
  renamed patients on the real running container.
- Real browser, this project's own Selenium infrastructure, against the
  actual running `app` container: switching to DE now shows translated
  `general_health_summary` on both the roster card and the dossier panel,
  translated context items, and a translated question title/prompt
  (checked on `PATIENT-003`'s neurological question, the same one visible
  in the project owner's screenshot); confirmed no English fragments
  remain in any of those four spots; confirmed the renamed patients render
  correctly.

### Deviations

- Question *options* (ICD-10 code + `short_designation`) are not
  translated because they're already German (`ICD-10 BMASGPK 2026` source
  designations) in both locales - there is nothing to switch.

## 2026-08-09 — Learner UI: option-list truncation fix, EN/DE language switch, session-local patient completion, mid-playthrough exit

Four project-owner-requested fixes/features against the now-correctly-deployed
forward model (previous entry), found by actually using the running app.

### Fixed

- `.code-list` (the answer-option container in `QuestionView`) had a
  `max-height: 20rem; overflow-y: auto`, which clipped the option list and
  forced an internal scrollbar for any question with more than ~4 options
  (e.g. a 6-option neurological question) - the container "didn't fit" and
  cut off `None of the above`/`Keine der genannten` at the bottom. Removed
  the cap; the list now grows with its content and the page scrolls
  normally, matching every other scrollable-by-default area in the app.

### Added

- Minimal EN/DE UI-chrome translation: `lib/i18n.jsx` (`LocaleProvider`/
  `useLocale()`, two flat string dictionaries, `navigator.languages`-based
  default locale detection, `localStorage`-persisted user choice) and
  `components/LanguageSwitch.jsx` (an "EN | DE" toggle rendered once in
  `Header`, which is mounted on every view, so it's reachable at any point).
  Every component's static chrome (`Header`, `PatientRoster`, `PatientCard`,
  `PatientDossier`, `QuestionView`, `PatientReview`) now resolves its text
  through `t()`; `lib/classification.js`'s `STATUS_LABELS` became
  `STATUS_LABEL_KEYS` (i18n keys, not display strings) accordingly.
  **Scope boundary, deliberate**: only interface chrome is translated.
  Patient/question *content* (prompts, context items, general health
  summaries) comes from the `QUESTIONBASE-0.1`/`PATIENTBASE-0.1` runtime
  dataset as authored (English) and has no German-authored variant to
  switch to; catalogue code designations are already German
  (`ICD-10 BMASGPK 2026` source). Switching locale does not touch either.
- Session-local per-patient completion tracking (`REQ-UI-02`: "...show
  question-level and patient-level progress/completion...; completion
  status is session-local"). `App.jsx` tracks `completedPatientIds`,
  seeded from and written to `sessionStorage` (not `localStorage` -
  deliberately cleared when the browser session ends, so this stays
  consistent with `REQ-INT-05`'s "no server-side learner account,
  longitudinal attempt history" and doesn't need a requirements
  amendment: `REQ-INT-05` prohibits *server-side* persistence, not a
  session-scoped client-side marker, and `REQ-UI-02` already specifies
  exactly this feature as an accepted `UXBASE-0.1` stretch goal). Marked
  complete when a playthrough reaches the `review` view (which already
  implies every question was submitted). Surfaced on `PatientRoster` as a
  per-card "Completed"/"Abgeschlossen" badge plus an aggregate
  "`{n} of {total} patients completed`" line.
- An "Exit to patient list"/"Zur Patientenliste zurückkehren" control in
  `QuestionView`'s toolbar, visible throughout a question (before and
  after submitting) - previously the only way back to the roster was
  finishing every question in the patient. Confirms via `window.confirm()`
  before discarding the in-progress playthrough (nothing was persisted for
  it anyway, so this only prevents an accidental click) and reuses the
  existing `chooseAnother()` reset path.

### Verified

- `npm run build` and `npm run lint` (oxlint) clean.
- Real browser, this project's own Selenium infrastructure
  (`app/tests/E2E/docker-compose.yml`, not Playwright), against the actual
  running `app` container (rebuilt from current source) on port 8080: the
  language switch changes the roster heading and all learner-facing
  strings including the option-list vocabulary (`None of the above` ↔
  `Keine der genannten`); the neurological question's answer-option list
  (6 codes + `none_of_above`) no longer internally clips
  (`scrollHeight === clientHeight`, `overflow-y: visible`); the exit
  button raises and accepts a native confirm dialog and returns to the
  roster; a full 3-question playthrough of `PATIENT-001` produces a
  "Completed" badge and an updated "1 of 6 patients completed" line on
  return to the roster. Also spot-checked at a 375px mobile viewport - the
  toolbar wraps correctly, nothing overlaps.

## 2026-08-09 — Forward redesign steps 2-3 completed for real: MODELBASE-0.2 wired into the actual deployment path

Found and fixed a real gap, not a documentation one: everything logged as
"verified" for steps 2-3 and step 6 so far was verified against throwaway
infrastructure (a scratch `docker run` MySQL container, the Vite dev
server) that was torn down after each check. The repository's actual
`docker compose up` path - `docker-compose.yml`'s `bootstrap` service - was
never repointed away from `prototype_baseline_0_1/Dockerfile.bootstrap`,
which still applies the historical `mysql_schema.sql` and loads
`cases_0_2.csv`/`case_code_domain_0_2.csv` (CASEBASE-0.2, 8 COPD-only
single-question cases). So the migrated PHP/React source was real and
correct on disk, but the actual running stack the user checks the app
against was still serving the old model end to end - old schema, old data,
old `CaseController` routes baked into the last-published GHCR image. This
is why the browser showed "Choose a case" / `CASE-001..007` / COPD-only
prompts despite steps 1-6 being genuinely implemented in source.

### Added

- `prototype_baseline_0_2_design/Dockerfile.bootstrap` and
  `persistence_candidate/bootstrap_mysql_0_2.py` (mirrors
  `prototype_baseline_0_1/scripts/bootstrap_mysql.py`'s empty-database-only
  schema application + idempotent load, against `apply_mysql_schema_0_2.py`/
  `load_mysql_0_2.py` instead): the previously standalone-verified
  MODELBASE-0.2 persistence candidate is now an actual bootstrap image, not
  just a checked candidate directory.
- `prototype_baseline_0_2_design/.dockerignore`, mirroring the old
  baseline's allowlist pattern - keeps `verification/reference_responses_0_3_candidate.csv`
  (the RCBASE oracle) out of the build context entirely, matching
  `runtime_manifest_0_2.json`'s `verification_oracle_runtime_access: false`.

### Changed

- `docker-compose.yml` and `prototype_stack/compose.yaml`: `bootstrap`
  service `build.context` repointed from `./prototype_baseline_0_1` to
  `./prototype_baseline_0_2_design` (and `../prototype_baseline_0_2_design`
  respectively).

### Verified

- `docker compose build bootstrap app` (local source, not a GHCR pull),
  `docker compose down -v` (dropped the stale CASEBASE-0.2 volume - there
  is no production data in this prototype's dev database) and
  `docker compose up -d` against this repository's own compose file.
- Bootstrap log: `MODELBASE-0.2 MySQL schema application: PASS` (9 tables),
  `MODELBASE-0.2 runtime input validation: PASS` with the exact expected
  counts (catalogue 99, patients 6, questions 33, question_facts 88,
  question_code_domain 118, question_relation_facts 182, question_options
  120), `MySQL baseline load: inserted`.
- `curl http://127.0.0.1:8080/api/patients` returns all 6 real patients
  (Anna Berger, Michael Novak, Lea Horvat, Sofia Marin, Daniel Weiss, Peter
  Gruber) with correct `question_count` (3/3/3/5/5/6).
- Real browser, this project's own Selenium infrastructure
  (`app/tests/E2E/docker-compose.yml`), against the actual running
  `bsc-thesis-icd10-app-1` container on port 8080 (not a dev server this
  time): patient roster renders all 6 patients, selecting `PATIENT-001`
  renders a non-COPD question (`Q-001-0x` osteoarthritis prompt for that
  patient's other question), and the patient dossier panel opens correctly.

### Deviations

- This does not itself complete step 8 (test suite rewrite) - the E2E
  Selenium check above was an ad hoc verification script, not the
  committed `app/tests/E2E/*` suite, which still targets deleted classes
  per the entry below.

## 2026-08-09 — Forward redesign step 6: functional React patient/question lifecycle

The application is functional end-to-end again on the forward model
(it was not, between the previous two entries and this one: the frontend
still called the deleted `/api/cases*` routes). This entry is
implementation-order step 6 - the *functional* lifecycle only, no
`UXBASE-0.1` visual/gameful polish yet (that's step 7).

### Added

- `app/frontend/src/components/{PatientRoster,PatientCard,PatientDossier,
  QuestionView,PatientReview}.jsx` implement the full
  `orientation(header) -> patient roster -> dossier/question -> submit ->
  locked immediate feedback -> next -> patient review -> replay/another
  patient` lifecycle from `chapter3_forward_implementation_instruction_0_5.md`.
  `PatientDossier` is an inline collapsible panel (REQ-INT-02: reopenable
  without losing question state, never a navigation away from the active
  question). `QuestionView` renders the tagged-response options (code
  options + `none_of_above`) and, once a result exists, an inline
  `QuestionFeedback` panel - "immediate feedback" is a state change within
  the same screen, not a route change. `PatientReview` reports raw
  `correct`/`suboptimal`/`incorrect` counts (REQ-FBK-03) with no weighted
  score.
- `app/frontend/src/lib/playthrough.js`: `shuffledOrder()` (REQ-INT-03 -
  Fisher-Yates over question *ids*, membership is never touched) and
  `summarizeResults()` for the review counts.
- `App.jsx` rewritten as the state owner for the new lifecycle: `patients`
  (roster), `activePatient`/`orderedQuestionIds`/`currentIndex`/
  `questionsById`/`results` (one active playthrough, REQ-INT-05 - entirely
  transient React state, no persistence, no server-side attempt history).
  `replay()` reshuffles order and clears `results` without refetching
  patient/question data; `chooseAnother()` returns to the roster.
- `api.js` updated to the new endpoints and the `APIBASE-0.1` tagged
  request shape (`evaluate(questionId, {type, code?})`).

### Removed

- `components/{CaseList,CaseCard,CaseDetail,ResultView,Tutorial}.jsx` and
  `lib/progress.js` - the prior session's case-centric UX/UI redesign
  (design tokens, case naming, tutorial, `localStorage` gamification).
  Built for a model that no longer exists; deleted outright rather than
  kept dead. The design tokens themselves (`App.css`'s `:root` custom
  properties, icon components) survive and were reused directly.

### Verified

- Real browser, real stack, this project's own Selenium infrastructure
  (`app/tests/E2E/docker-compose.yml`, not Playwright - corrected mid-session
  after reaching for the latter out of habit) against a freshly loaded
  MODELBASE-0.2 MySQL instance and the PHP dev server: full playthrough for
  `PATIENT-001` (3 questions, all answered correct, matching real evaluator
  output including the `E11.9` countercontrol and the COPD `Q-001-01`
  question), dossier open/close without losing question state, patient
  review showing "3 correct · 0 suboptimal · 0 incorrect" with per-question
  detail, replay, return to roster, and a 375px mobile viewport pass - no
  browser console errors throughout.
- One real bug found and fixed along the way: Vite's dev server needed
  `--host 0.0.0.0` (default binds to a loopback-only address the Selenium
  container's `host.docker.internal` route can't reach) - a local
  dev-verification-only issue, not a change to any committed config.
- `npm run build` and `npm run lint` (oxlint) clean.

### Deviations

- `app/tests/Unit`/`Integration`/`E2E` still target the deleted
  case-centric model and do not run - unchanged from the previous entry,
  still implementation-order step 8, not this one.
- No `UXBASE-0.1` visual/gameful treatment yet: current styling is the
  previous session's design tokens applied directly to the new markup, not
  a considered visual pass. Orientation/self-explanatory-entry (`REQ-UI-01`)
  is also step 7 - the header currently states only the disclaimer, not the
  workflow/legend `REQ-UI-01` asks for.

## 2026-08-09 — `APIBASE-0.1` API/feedback contract clarification: two corrections to steps 4-5

The project owner formally resolved the nine implementation-detail
ambiguities flagged in the previous entry via a new control document,
`chapter3_api_and_feedback_contract_0_1.md` (`APIBASE-0.1`), delivered in
an updated `forward_package_0_6/`. Seven of the nine resolutions matched
what was already implemented (tagged-response shape, the `GET`/`POST`
verification-only asymmetry, `RULE-NOA-01`'s explanation elements on both
branches, `determining_rule` as the sole rule-identity field, generic
handling of the still-provisional legacy rows, the `improvement_code`
cross-row check, and `information_boundary` in the context vocabulary).
**Two were genuine corrections**, applied in this entry.

### Fixed

- `Http/QuestionController.php`: `GET /api/questions/{id}` no longer
  returns raw `question_fact` rows. `APIBASE-0.1` §5 fixes these as
  evaluator-internal, pre-submission data - `learner_label` is a
  post-submission explanation label, not a visibility flag, and the
  previous entry's deviation #6 ("expose every fact, since every fact has
  a label") is superseded by this explicit resolution. Confirmed against
  the actual materialized data before removing it: question prompts
  already state what a learner needs directly (e.g. `Q-001-01`'s prompt
  states the FEV1 value in the text itself), so nothing becomes
  unsolvable by this change. `Model/QuestionFacts::all()` removed as
  dead code alongside it - nothing else called it.
- `Rules/RuleGate.php`, `Rules/GateResult.php`:
  `GateResult::REASON_UNSUPPORTED_RESPONSE_KIND` and its corresponding
  branch in `RuleGate::evaluate()` removed. `APIBASE-0.1` §4 fixes
  `RULE-GATE-01`'s complete reason vocabulary as exactly four values
  (`outside_active_subset`, `undefined_question_relation`,
  `missing_required_question_fact`, `none_option_not_defined`);
  `unsupported_response_kind` is HTTP-only. The removed branch was already
  unreachable dead code in practice - `ResponseInput` can only ever be
  constructed as `code` or `none_of_above` via its two factory methods -
  but it's now confirmed dead by the contract rather than just by
  inspection.

### Changed

- Synced `prototype_baseline_0_2_design/persistence_candidate/` from
  `forward_package_0_6/`: `mysql_schema_0_2.sql` gains a `CHECK` constraint
  formally closing `patient_context_item.item_type`'s vocabulary
  (including `information_boundary`); `runtime_data_0_2.py` validates
  against the same closed set. No data file changed - same 99/6/32/33/
  88/118/182/120 counts, same canonical runtime digest
  (`2b20a3f336ed3106749eb34020a52499c22800a72e456d992288ae073f0d1f51`).
  `chapter3_api_and_feedback_contract_0_1.md` and the other updated
  `chapter3_*_0_2.md`/`_0_3.md`/`_0_7.md` control documents copied to the
  repository root alongside the previous entry's set.

### Verified

- Database-independent: `test_runtime_contract_0_2` 8/8 (matches the
  project owner's own reported "25/25 NOA expectations... all 8
  database-independent persistence tests").
- Live MySQL (fresh container, schema/loader re-applied with the new
  `CHECK` constraint): `test_mysql_persistence_0_2` 6/6.
- PHP/API re-run against the corrected code: the same 14/14 live-database
  integration checks from the previous entry, plus a direct inspection of
  `GET /api/questions/Q-001-01`'s JSON body confirming no `facts` key is
  present and that `prompt` alone already carries the decision-relevant
  detail.

## 2026-08-09 — Forward redesign steps 4-5: PHP rule engine + API migrated to RULEBASE-0.2/MODELBASE-0.2

Continues the forward implementation order from the previous entry.
`app/src/` is now fully migrated from the historical case-centric model to
the patient/question model; `app/src/Http/CaseController.php`,
`Repository/CaseRepository.php`, and `Model/CaseFacts.php` are deleted, not
deprecated in place.

### Added

- `Model/{Patient,PatientContextItem,CodingQuestion,QuestionFact,
  QuestionFacts,QuestionCodeDomainRelation,QuestionRelationFact,
  QuestionOption,ResponseInput}.php`: the new value-object layer over the
  9-table MODELBASE-0.2 schema. `QuestionFacts` replaces `CaseFacts`'s named
  scalar properties with a keyed, typed-getter bag (`getEnum`/`getCode`/
  `getDecimal`/`getBool`) that returns `null` for an absent key rather than
  throwing - most of the 25 forward learner questions carry no COPD/status
  facts at all, and the five source-specific rules must degrade to
  "does not match" for them, not error.
- `Repository/PatientRepository.php`, `Repository/QuestionRepository.php`
  (replaces `CaseRepository`). `QuestionRepository::findById()` deliberately
  does not filter by `intended_use` - the verification path must resolve
  both learner and `verification_only` questions by ID (REQ-VER-09);
  visibility filtering happens in `listLearnerVisibleForPatient()` and at
  the controller layer instead. Also enforces, at hydration time, that
  every `less_specific_supported` relation's `improvement_code` resolves to
  an `accepted_reference` on the same question - the schema's FK checks the
  code exists in `catalogue_code`, not that it carries the right relation
  kind, so bad authoring data would otherwise reach the evaluator silently.
- `Rules/RuleRelHard.php` (`RULE-REL-HARD-01`), `Rules/RuleRelSpec.php`
  (`RULE-REL-SPEC-01`), `Rules/RuleNoa.php` (`RULE-NOA-01`) - net new,
  generic relation-kind-driven rules alongside the five retained
  source-specific rules (`RULE-STATUS/DEPTH/EVID/SPEC/MAP-01`, unchanged
  predicates, only their fact-access path changed). `RuleCorrect` now checks
  `relation_kind === 'accepted_reference'` instead of a boolean
  `is_acceptable` column, which no longer exists in the schema at all.
- `Http/PatientController.php` (`GET /api/patients`, `GET
  /api/patients/{id}`), `Http/QuestionController.php` (`GET
  /api/questions/{id}`, 404s for a `verification_only` question - mirrors
  the old `CaseController::show()`'s asymmetry with evaluate). Routes in
  `public/index.php` updated accordingly; `EvaluationController` now takes
  `{"response": {"type": "code"|"none_of_above", "code": "..."}}` (chosen
  over `chapter3_patient_and_question_design_plan.md`'s `{"option_id":...}`
  shape because the evaluator must accept any evaluation-domain relation,
  not only a displayed option - `M54.5`/`I10`/the hidden J44 family members
  could not otherwise be addressed).
- `Rules/Precedence.php` extended: hard priority gains a fourth slot
  (`RULE-REL-HARD-01`, after the three source-specific hard rules); graded
  matches are now a priority list (`RULE-SPEC-01` before `RULE-REL-SPEC-01`)
  rather than a single boolean, since a question can now match either the
  source-specific or generic graded rule.

### Deviations from the migration's own spec-extraction pass

Nine implementation-detail ambiguities were flagged by the RULEBASE-0.2/
MODELBASE-0.2 extraction pass before coding began; each is resolved below
with a documented default rather than silently picked:

1. Evaluate-request body shape: tagged `{"response":{"type":...}}`, not
   `{"option_id":...}` (see `Added` above).
2. `GET /api/questions/{id}` 404s for `verification_only`; only `POST
   .../evaluate` stays unfiltered - preserves the old asymmetry exactly.
3. `malformed_input`/`unsupported_response_kind` are both produced at the
   HTTP boundary (`EvaluationController::parseResponse()`), before the
   evaluator ever runs - consistent with where `malformed_input` already
   lived in RULEBASE-0.1.
4. `RULE-NOA-01`'s `reference_code` explanation element is populated on
   *both* branches (correct and incorrect), matching the candidate oracle's
   own required-elements column rather than the rule catalogue's prose,
   which only explicitly licenses it for the correct branch.
5. `patient_context_item.item_type` is stored/read as a plain string, not a
   closed PHP enum - the schema itself has no CHECK constraint, and the
   materialized data uses `information_boundary` for `PATIENT-006`, a value
   not in `MODELBASE-0.2`'s own suggested list.
6. ~~"Learner-visible question facts" (MODELBASE-0.2 §7.1) is read as
   "every fact" - every `question_fact` row carries a `learner_label` in
   the materialized data and there is no separate visibility column to
   narrow this further.~~ **Superseded by the next entry**: `APIBASE-0.1`
   §5 resolves this explicitly the other way - no facts are exposed
   pre-submission at all.
7. The four provisional legacy-reconstruction rows (`VQ-005`-`VQ-008`) are
   used as delivered; their audit against the original `0.2` CSVs remains
   explicitly open, tracked at implementation-order step 9 - not resolved
   by this migration.
8. `less_specific_supported`'s cross-row `improvement_code` semantic check
   is enforced at repository-hydration time (see `Added` above), since the
   schema's own FK/CHECK constraints can't express it.
9. `RULE-SPEC-01` and `RULE-REL-SPEC-01` intentionally share the literal
   criterion string `supported_specificity_not_used`; nothing in the new
   code derives which rule fired from that string - only from
   `determining_rule`.

### Verified

- **Logic smoke test** (hand-built `CodingQuestion` fixtures, no database):
  13/13 checks covering every rule (`CORRECT`, `SPEC`, `EVID`, `DEPTH`,
  `NOA` both branches, `REL-HARD`, `REL-SPEC`, the `.9`-suffix
  countercontrol `E11.9`, and three gate-failure reasons including a
  verification-only question with zero displayed options).
- **Live integration** against the actual materialized MODELBASE-0.2
  database (fresh `mysql:latest` container, schema applied, baseline
  loaded - same procedure as the previous entry): booted the real
  `Bootstrap` and drove `PatientController`/`QuestionController`/
  `EvaluationController` directly. 14/14 checks passed, including both
  `none_of_above`-correct positive controls (`Q-004-05`→`M54.5`,
  `Q-005-05`→`I10`), the non-displayed-but-evaluable code path, all three
  `RULE-STATUS-01` legacy fixtures (`VQ-003`/`004`/`008`, matching the
  extraction report's predicted outcomes exactly), and a
  `verification_only` question (`VQ-001`) evaluating correctly despite
  having zero `question_option` rows.
- `php -l` clean across every file in `app/src/`, `app/public/index.php`,
  `app/router.php`.

### Deviations

- Per the instruction's evidence rule: this is `development-tested`
  evidence for the PHP/API layer specifically, via ad hoc smoke scripts,
  not the project's own PHPUnit suite - `app/tests/Unit/*` and
  `app/tests/Integration/*` still target the deleted case-centric classes
  and do not currently run at all (they reference removed classes). Their
  rewrite is implementation-order step 8, not done in this entry. The
  frontend (`app/frontend/`) is untouched by this entry and still calls the
  old `/api/cases*` routes, which no longer exist - **the running
  application is not currently functional end-to-end**; that is step 6.

## 2026-08-08/09 — Forward redesign begins: requirements merge + MODELBASE-0.2 persistence integration

Project-owner instruction (`chapter3_forward_implementation_instruction_0_5.md`,
`IMPL-HANDOFF-0.5`, delivered via `forward_package_0_5/`): migrate from the
historical one-case/one-question `CASEBASE-0.2` learner model to a
six-patient/25-question forward model (`PATIENTBASE-0.1`/`QUESTIONBASE-0.1`/
`RULEBASE-0.2`/`MODELBASE-0.2`), following the instruction's own required
implementation order. This entry covers steps 1-3; PHP/API/React migration
(steps 4-6) and the UX/UI iteration (step 7) follow in later entries. This
is `specified`/`implemented`/`development-tested` evidence per the
instruction's evidence rule — not yet `frozen/verified`.

### Added

- `chapter3_rule_catalogue_0_2.md` (`RULEBASE-0.2`),
  `chapter3_data_model_and_interaction_baseline_0_2.md` (`MODELBASE-0.2`),
  `chapter3_sql_loader_migration_contract_0_2.md` (`DATAMIG-0.2`),
  `chapter3_patient_and_question_design_plan.md`,
  `chapter3_reference_case_coverage_plan_forward_0_3.md`,
  `chapter3_requirements_forward_revision_0_7.md`,
  `chapter3_ux_ui_gamification_concept_0_1.md` (`UXBASE-0.1`),
  `chapter3_question_bank_source_audit.md`, and
  `chapter3_forward_implementation_instruction_0_5.md` copied from
  `forward_package_0_5/docs/` to the repository root, alongside the
  existing `chapter3_*.md` control documents they extend rather than
  replace.
- `prototype_baseline_0_2_design/` copied from the package to the
  repository root (sibling of `prototype_baseline_0_1/`, which is
  untouched): the `MODELBASE-0.2` persistence candidate (9-table schema,
  hash-verified runtime data allowlist, transactional immutable loader,
  database-independent and live-MySQL test suites) plus the materialized
  `PATIENTBASE-0.1`/`QUESTIONBASE-0.1` authoring data and the candidate
  `RCBASE-0.3` external oracle (143 rows: 125 new learner expectations
  pending human/source audit, 18 retained historical regressions, 4 of
  which are provisional reconstructions pending a diff against the
  original `0.2` CSVs — both audits remain open, tracked at step 9 of the
  implementation order, not silently assumed done).

### Changed

- `chapter3_requirements_catalogue.md` bumped `0.5` → `0.6`, merging
  `chapter3_requirements_forward_revision_0_7.md`: six requirements
  revised in place (`REQ-MOD-01`/`02`, `REQ-INT-01`, `REQ-FBK-01`,
  `REQ-DAT-03`, `REQ-RUL-02`), fourteen added (`REQ-MOD-03`-`06`,
  `REQ-DAT-06`-`09`, `REQ-RUL-06`-`07`, `REQ-INT-02`-`05`, `REQ-UI-01`-`03`,
  `REQ-GAM-01`, `REQ-VER-08`-`09`), no ID collisions. §12 freeze criteria
  updated to reference the forward baselines. The `0.5` wording is not
  restated inline (avoids two authoritative copies drifting apart); it
  remains readable via this file's git history and
  `chapter3_requirements_forward_revision_0_7.md` §2.
- `docs/REQUIREMENTS_TRACEABILITY.md`: added a forward-design note at the
  top flagging that its existing 31-row audit describes the *historical*
  `CASEBASE-0.2` implementation and has not been re-run against the
  forward model — deliberately not re-audited yet, since the forward
  implementation doesn't exist yet either (re-auditing now would record
  verification destinations with no executed evidence behind them).

### Verified

- **Step 1 (database-independent, `check-only`)**: `python3
  persistence_candidate/load_mysql_0_2.py --check-only` → `PASS`, exact
  manifest counts (catalogue 99, patients 6, patient_context 32, questions
  33, question_facts 88, question_code_domain 118, question_relation_facts
  182, question_options 120). `python3 -m unittest -v
  test_runtime_contract_0_2` (run from inside `persistence_candidate/` -
  its sibling-import style needs that cwd) → 8/8 passed, including the
  `PATIENT-001`-only-COPD invariant, the `none_of_above` control check, and
  the runtime-allowlist-is-oracle-free check.
- **Steps 2-3 (live MySQL, fresh evidence, not reused from the package's
  own prior claims)**: throwaway `mysql:latest` container (version
  observed: **26.7.0**, consistent with this project's existing unpinned
  MySQL decision), port 3307 to avoid clashing with the running app stack.
  `apply_mysql_schema_0_2.py` → 9 runtime tables created. First
  `load_mysql_0_2.py` → `inserted`; identical second run → `no_op`. Direct
  `SELECT COUNT(*)` against all 9 tables matches the manifest exactly
  (spot-checked independently of the loader's own report). `python3 -m
  unittest -v test_mysql_persistence_0_2` → 6/6 passed, including the
  conflict-on-differing-content-under-same-ID negative test, the
  foreign-key-rejects-outside-domain negative test, and confirmation that
  no oracle table/column exists in the applied schema. Container removed
  after evidence capture — nothing from this candidate is part of the
  running application yet.

### Deviations

- Per the instruction's own evidence rule, this is `development-tested`
  evidence for the **persistence candidate in isolation**, not evidence
  that the PHP application has been migrated — `app/src/` and the running
  `prototype_stack`/bundle deployment still target the historical
  `PROTOBASE-0.2` schema as of this entry. Historical `85/85`/`86/86`
  application-test results are not carried forward as evidence for this
  baseline (`PROTOBASE-0.3` candidate).
- The 125 new learner oracle expectations and the 4 provisional legacy
  reconstructions remain unaudited, as flagged by the package itself; not
  resolved in this entry, tracked at implementation-order step 9.

## 2026-08-08 — Frontend UX/UI redesign: design system, case naming, tutorial, gamification

Project-owner stretch-goal request: the core prototype and CI/bundle work
were done, and there was schedule room before the freeze to make the
frontend look and function like a deliberately designed product rather
than a functional-but-bare scaffold. Planned in
`docs/UX_UI_BRAINSTORM.md`/`docs/UX_UI_SPECIFICATION.md` (both now
historical planning records, folded into this entry and
`docs/DEVELOPMENT_DOCUMENTATION.md` §7); the one scope boundary held
throughout: no case-data/content or data-model change of any kind.

### Added

- **Design tokens** (`app/frontend/src/App.css`): a `:root` custom-property
  palette/type/spacing/motion scale, with a `prefers-color-scheme: dark`
  override and a `prefers-reduced-motion: reduce` override, replacing the
  handful of ad hoc values the original stylesheet had.
- **Case naming**: case cards and the case-detail heading now show
  `short_description` (e.g. *"Documented COPD with acute lower-respiratory
  infection; stable-phase FEV1 = 55% predicted"*) as the primary label,
  with `case_id` demoted to a secondary badge. `short_description` was
  already returned by the API and simply never rendered - zero backend or
  data change.
- **Case cards** (`app/frontend/src/components/CaseCard.jsx`,
  `CaseList.jsx`): a responsive grid (`repeat(auto-fill, minmax(18rem,
  1fr))` - 1/2/3 columns depending on viewport) replaces the plain button
  list, with setting/diagnosis-role badges and an icon.
- **Gamification / progress layer** (`app/frontend/src/lib/progress.js`):
  per the project owner's explicit instruction ("essential... but not an
  elaborate concept"), a `localStorage`-backed record of each case's last
  classification, surfaced as a badge per case card (icon + colour + text,
  consistent with the existing result-view vocabulary) and a "`n` of `m`
  cases attempted" summary line, with a one-time congratulatory banner once
  every case has been answered correctly. Entirely client-side - no
  backend, session, or data-layer change; see
  `docs/UX_UI_SPECIFICATION.md` §3.7 for why this is safe within the
  project's existing runtime/oracle isolation.
- **First-visit tutorial** (`app/frontend/src/components/Tutorial.jsx`): a
  four-step modal mirroring `docs/USER_GUIDE.tex`'s "Using the prototype"
  narrative, auto-shown once per browser (`localStorage` flag) and
  re-openable via a persistent header "Help" button. Traps focus, closes
  on `Escape` or overlay click, returns focus to the button that reopened
  it.
- **Result-view icons**: a check/warning/cross icon now accompanies the
  existing colour+text classification signal - a third redundant channel,
  strengthening (not replacing) the "colour is never the only signal"
  principle from `docs/DEVELOPMENT_DOCUMENTATION.md` §7.
- Small polish throughout `CaseDetail`: a "no codes match" empty state for
  the search filter, a persistent "Selected: `<code>`" summary line above
  the submit button, and a submit bar that stays `position: sticky` to
  the viewport bottom on narrow/mobile viewports.
- `app/frontend/src/components/icons.jsx`: hand-authored inline SVGs (no
  icon-font/library dependency) for all of the above.
- `app/tests/E2E/ProgressBadgeTest.php`: new Selenium coverage asserting
  the `localStorage` round-trip actually reaches the DOM (submits a code,
  navigates back to the case list, asserts the badge updated) - frontend-
  only supplementary coverage with no upstream `TEST-*` identifier, since
  it has no backend equivalent (`docs/UX_UI_SPECIFICATION.md` §3.7).

### Changed

- `app/frontend/src/App.jsx` split into `components/` (`Header`,
  `CaseList`, `CaseCard`, `CaseDetail`, `ResultView`, `Tutorial`, `icons`)
  and `lib/` (`progress.js`, `classification.js`); `App.jsx` itself is now
  a thin state-owning orchestrator. No change to the three-view state
  model (`docs/DEVELOPMENT_DOCUMENTATION.md` §7's "no client-side router"
  reasoning was reconsidered, given the project owner explicitly lifted
  that restriction, and still not adopted - see
  `docs/UX_UI_SPECIFICATION.md` §2.6 for why, on its own merits).
- `app/tests/E2E/SeleniumTestCase.php`: `openCaseList()` now dismisses the
  first-visit tutorial modal if present (a fresh WebDriver session has no
  prior visit, so it would otherwise cover the case list); `wait()`
  visibility widened from `private` to `protected` so subclasses (e.g. the
  new `ProgressBadgeTest`) can use it.

### Verified

- Visually, via a scratch Playwright session against the Vite dev server
  (not a project dependency - nothing added to `package.json`, run
  entirely outside the repo): tutorial modal, case list/cards, case
  detail, code selection, result view, and a 375px mobile viewport, all
  screenshotted and inspected. Caught and fixed one real bug this way: the
  sticky submit bar "unstuck" ~2rem before the true viewport bottom at
  full scroll, because of `<section>`'s default bottom margin; fixed with
  `margin-bottom: 0` on `.case-detail` specifically.
- Functionally, via the self-contained bundle
  (`docker compose build bootstrap app`, `docker compose --profile test
  build test`, `docker compose --profile test run --rm test`): **86/86
  tests, 495 assertions**, fully containerized - the pre-existing 85 plus
  the new `ProgressBadgeTest`. No existing E2E selector needed to change;
  the redesign was built against the selector contract documented in
  `docs/UX_UI_SPECIFICATION.md` §3 from the start.

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
