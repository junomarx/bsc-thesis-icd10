# Requirements traceability audit

**Re-audited 9 August 2026 against the forward model.** The previous
version of this document (superseded, not preserved verbatim below — see
`docs/CHANGELOG.md` for the historical record) audited the one-case/
one-question `CASEBASE-0.2` implementation against catalogue `0.5`. That
implementation no longer exists: `app/src/` was migrated wholesale to the
six-patient/25-question forward model (`PATIENTBASE-0.1`/`QUESTIONBASE-0.1`/
`MODELBASE-0.2`/`RULEBASE-0.2`) described in
`chapter3_requirements_catalogue.md` (now `0.6`, forward revision `0.7`
merged). This audit covers every `Accepted`/`Scope constraint` entry in
that current catalogue — the six revised requirements
(`REQ-MOD-01`/`02`, `REQ-INT-01`, `REQ-FBK-01`, `REQ-DAT-03`, `REQ-RUL-02`)
and the fourteen new ones (`REQ-MOD-03`–`06`, `REQ-DAT-06`–`09`,
`REQ-RUL-06`/`07`, `REQ-INT-02`–`05`, `REQ-UI-01`–`03`, `REQ-GAM-01`,
`REQ-VER-08`/`09`) together with the ones that carried over unchanged.

**Purpose:** `REQ-TRC-01` requires that "every mandatory implemented
requirement has at least one downstream implementation/model destination and
verification reference or an explicitly declared gap." This document is
that audit: every `Accepted`/`Scope constraint` entry in
`chapter3_requirements_catalogue.md`, checked against what actually exists
in the repository today, not what was planned.
**Not the principal verification run:** this confirms a verification
*destination* exists and produces a genuine result; it is not itself
`REQ-VER-05`'s final conformance report, which belongs to the freeze
procedure (§3 below).
**Companion documents:** [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md)
has an architecture-centric decision record; this document is
requirement-centric and exhaustive across every `REQ-*` entry.
[IMPLEMENTATION_SPECIFICATION.md](IMPLEMENTATION_SPECIFICATION.md) is the
precise as-built reference this audit's evidence column points into.

## 1. How to read this table

- **✅ Verified** — the property demonstrably holds against the current
  implementation, with a concrete, checkable pointer (file, live-system
  check, passing automated test, or doc section).
- **🕓 Deferred** — correctly *not yet done*, either because it is
  specifically about the principal verification/freeze procedure (which has
  not started) or because it depends on implementation-order steps 9–10
  (oracle audit, freeze — see `chapter3_forward_implementation_instruction_0_5.md`),
  which are explicitly sequenced after the current implementation phase.
- **📄 Thesis-text scope** — the acceptance criterion is about how the
  thesis document itself is written, not about anything in this repository.

### 1a. Historical note: this audit briefly carried a fourth status symbol

An earlier version of this same-day audit (before implementation-order
step 8 landed) marked five rows (`REQ-RUL-04`/`05`, `REQ-ARC-01`/`02`,
`REQ-VER-04`) with a ⚠ symbol: the property held by direct code/schema
inspection, but `app/tests/Unit`/`Integration`/`E2E` still targeted the
deleted case-centric classes, so the automated regression that was
*supposed* to prove it mechanically did not run. Step 8 (full test-suite
rewrite, `docs/CHANGELOG.md`'s "Step 8" entry) closed that gap the same
day — `php vendor/bin/phpunit --testsuite unit,integration` now passes
237/237, and the E2E suite originally passed 7/7 (now 8/8 after the
first-visit tutorial regression was added) — so all five rows below now read
plain ✅, citing the passing test as their evidence. This paragraph is kept
as a record that the distinction existed and why, not as a live caveat.

## 2. Full audit

### 2.1 Intended use and claim boundaries (catalogue §4)

| ID | Status | Evidence |
|---|---|---|
| `REQ-SCP-01` | ✅ Verified | `prototype_baseline/persistence_candidate/runtime_manifest_0_2.json` records `catalogue_edition: "ICD-10 BMASGPK 2026"`, `diaglist_source_id`, `diaglist_sha256`; `data/patients_0_1.csv` — all 6 patients `synthetic: true`. |
| `REQ-SCP-02` | ✅ Verified | No diagnosis/CDS/reporting/reimbursement code path exists in `app/src/`; the non-clinical disclaimer renders in `Header.jsx` on every view — confirmed on-screen via this project's Selenium infrastructure against the real running container (2026-08-09). |
| `REQ-SCP-03` | 📄 Thesis-text scope | Repository-side boundary maintained (`DEVELOPMENT_DOCUMENTATION.md` §2.2); whether the thesis text itself maintains it is not a repository question. |

### 2.2 Authoritative data and prototype subset (catalogue §5)

| ID | Status | Evidence |
|---|---|---|
| `REQ-DAT-01` | ✅ Verified | `runtime_manifest_0_2.json` fixes edition/source ID/checksum; `test_runtime_contract_0_2.py` (8/8 passing, re-run 2026-08-09 after the patient-rename edit) validates every allowlisted runtime file's SHA-256 against the manifest before any database write. |
| `REQ-DAT-02` | ✅ Verified | `data/subset_0_2.csv` (99 rows) traces to DIAGLIST; `RuleMap`/`RuleStatus`/`RuleDepth`/`RuleSpec` (`app/src/Rules/*.php`) docblocks cite exact `SRC-AT-DOC-2026` page locators for every semantic rule not derivable from DIAGLIST alone. |
| `REQ-DAT-03` (revised 0.6) | ✅ Verified | Cross-checked directly: every one of the 99 `SUBSET-0.2` codes appears in at least one `question_code_domain`/`improvement_code` relation across `question_code_domain_0_1.csv` + the legacy fixture file — zero unused records (verified via a direct set-difference check, 2026-08-09). |
| `REQ-DAT-06` | ✅ Verified | Same check as `REQ-DAT-03` above; the subset spans 10 ICD chapters (C, D, E, F, G, H, I, J, L, M, N, R), not just the original COPD/status families. |
| `REQ-DAT-07` | ✅ Verified | `data/questions_0_1.csv`: 25 learner questions span diabetes, glaucoma, depression, anaemia, schizophrenia, dermatology, lipid disorders, hypertension, atrial fibrillation, CKD, panic disorder, migraine, hypothyroidism, anxiety, epilepsy, dementia, prostate disease, stroke sequela, and one COPD question — genuine coding targets, not repeated background flavour. |
| `REQ-DAT-08` | ✅ Verified | Only `Q-001-01` (`PATIENT-001`) has a `J44`/COPD relation among the 25 learner questions; the other 5 learner patients contain none (checked directly against `question_code_domain_0_1.csv`). The remaining COPD content lives in the 8 `verification_only` legacy fixtures, never learner-visible (`QuestionController::show()` 404s them). |
| `REQ-DAT-09` | ✅ Verified | `runtime_manifest_0_2.json`'s `expected_counts`: 6 patients, 33 questions (25 learner + 8 verification_only), enforced by `test_runtime_contract_0_2.py`; `patient_baseline_id`/`question_baseline_id` (`PATIENTBASE-0.1`/`QUESTIONBASE-0.1`) are explicit versioned identifiers in every row. |
| `REQ-DAT-04` | ✅ Verified | `runtime_data_0_2.py` reads only the manifest's allowlisted files/columns; re-running `load_mysql_0_2.py` against unchanged input reports `no_op` (byte-identical re-derivation), confirmed live 2026-08-09. |
| `REQ-DAT-05` | ✅ Verified | `RuleStatus::matches()` (`app/src/Rules/RuleStatus.php`) reads exactly `diagnosis_role`/`encounter_setting`/`inpatient_lkf_scored` via typed `question_fact` getters; no extramural-specific rule exists anywhere in `app/src/Rules/`. |

### 2.3 Case, rule, and feedback model (catalogue §6)

| ID | Status | Evidence |
|---|---|---|
| `REQ-MOD-01` (revised 0.6) | ✅ Verified | Every `Rules/*.php` predicate takes only `CodingQuestion::$facts` (a `QuestionFacts` bag) and/or `CatalogueRecord` — never `$prompt` or a patient's context prose (`IMPLEMENTATION_SPECIFICATION.md` §3.2); `QuestionFacts` has no method that exposes prompt/context text to a rule. |
| `REQ-MOD-02` (revised 0.6) | ✅ Verified | `patient_definition`/`coding_question` are distinct tables with a one-to-many FK (`fk_question_patient`); every evaluation request addresses exactly one `question_id` (`POST /api/questions/{id}/evaluate`), never a whole patient. |
| `REQ-MOD-03` | ✅ Verified | Same schema fact as `REQ-MOD-02`; `Patient` (`app/src/Model/Patient.php`) has no evaluation method of its own — `CodingQuestion` is the sole evaluation unit consumed by `Evaluator::evaluate()`. |
| `REQ-MOD-04` | ✅ Verified | `EvaluationController`/`Evaluator` never read `patient_context_item.display_text` or `coding_question.prompt`; confirmed by direct code inspection of every `Rules/*.php` file (2026-08-09) — none imports `Patient` or references prompt/context fields. |
| `REQ-MOD-05` | ✅ Verified | Materialized question counts per patient are `3,3,3,5,5,6` (`runtime_manifest_0_2.json`, confirmed live via `GET /api/patients`); `QuestionRepository::listLearnerVisibleForPatient()` has no `LIMIT`/count cap, and `QuestionView`'s progress bar renders `totalQuestions` segments derived from the actual array length, never a hard-coded 3. |
| `REQ-MOD-06` | ✅ Verified | `question_option` (displayed) and `question_code_domain` (evaluable) are separate tables with independent membership; `Q-004-05`/`M54.5` and `Q-005-05`/`I10` are evaluable-but-undisplayed by design (confirmed against the CSV data), and `QuestionController::render()`'s `options` array is explicitly built from `question_option` only. |
| `REQ-RUL-01` | ✅ Verified | Every `RULE-*` class carries a docblock citing its rule ID and `chapter3_rule_catalogue_0_2.md` section; `IMPLEMENTATION_SPECIFICATION.md` §3 is the consolidated trace. |
| `REQ-RUL-02` (revised 0.6) | ✅ Verified | `RuleSpec` (source-specific) and `RuleRelSpec` (generic, `less_specific_supported` relation) are the only two `suboptimal`-producing paths; both require an explicit `improvement_code` (`QuestionRepository::assertImprovementCodesResolve()` enforces at hydration time that it resolves to an `accepted_reference` on the same question — throws `RuntimeException` otherwise, a real hydration-time guard, not just a comment). No rule keys off code length, a `.9` suffix, or designation text. |
| `REQ-RUL-03` | ✅ Verified (by documented absence) | No frozen question currently declares more than one `accepted_reference`; this is a data-authoring invariant, not separately re-derived here. |
| `REQ-RUL-04` | ✅ Verified | `Rules/Precedence.php`'s `HARD_PRIORITY`/`GRADED_PRIORITY` constants implement `STATUS > DEPTH > EVID > REL-HARD` and `SPEC > REL-SPEC` deterministically regardless of input array order (`firstByPriority()` scans the fixed list, not the input). `app/tests/Unit/PrecedenceTest.php` (rewritten step 8, including new vectors for the `REL-HARD-01`/`REL-SPEC-01` slots) passes as part of the 77/77 unit run. |
| `REQ-RUL-05` | ✅ Verified | `RuleGate::evaluate()` returns one of exactly 4 non-classified reasons before any rule runs; confirmed both live via `curl` and by `app/tests/Unit/RuleGateTest.php`/`Integration/EvaluationApiTest.php` (rewritten step 8), passing. |
| `REQ-RUL-06` | ✅ Verified | `RuleNoa::isCorrect()` implements the D(q)∩A(q) set logic exactly; confirmed live via `curl` against `Q-002-01` (`none_of_above` → `incorrect`, correctly, since `I48.0` is both accepted and displayed) and via Selenium for `Q-004-05`/`Q-005-05`-style negative controls during step 6/7 verification. |
| `REQ-RUL-07` | ✅ Verified | `RuleGate` rejects any code without a defined `question_code_domain` row (`undefined_case_relation`) before any rule runs; `RuleRelHard`/`RuleRelSpec` only match an explicit `relation_kind`, never a code-shape heuristic (confirmed by direct code reading, 2026-08-09). |
| `REQ-FBK-01` (revised 0.6) | ✅ Verified | Confirmed end-to-end via Selenium (multiple passes, 2026-08-09): submit → classification + criterion + explanation render immediately, before `Next question`/`Review patient` becomes reachable; `EvaluationResult` always carries `determiningRule`/`criterion` even when the "Technical details" `<details>` is collapsed. |
| `REQ-FBK-02` | ✅ Verified | Every `RULE-SPEC-01`/`RULE-REL-SPEC-01` result carries a non-null `improvement_code`, rendered as "Suggested improvement: {code}" in `QuestionView`; confirmed live via `curl` and Selenium. |
| `REQ-FBK-03` | ✅ Verified | `PatientReview` renders raw `correct`/`suboptimal`/`incorrect` counts (`lib/playthrough.js`'s `summarizeResults()`) with no weighted score anywhere in the component or its CSS; confirmed via a full 3-question Selenium playthrough. |

### 2.4 Interaction, architecture, and implementation (catalogue §7)

| ID | Status | Evidence |
|---|---|---|
| `REQ-INT-01` (revised 0.6) | ✅ Verified | Full roster→dossier→question→submit→feedback→next→review→replay/another-patient path confirmed via Selenium against the real running container; `ResponseInput` is a tagged union (`code`/`none_of_above`), never an aggregated array — `EvaluationController::parseResponse()` rejects anything else as `malformed_input`. |
| `REQ-INT-02` | ✅ Verified | `PatientDossier` is a collapsible panel rendered *inside* `QuestionView`, not a separate route; confirmed via Selenium that opening/closing it does not reset `selectedOptionId` or the active question. |
| `REQ-INT-03` | ✅ Verified | `lib/playthrough.js`'s `shuffledOrder()` is a Fisher–Yates permutation of question **ids** only — it never adds/removes an id; `App.jsx`'s `replay()` calls it again over the same `activePatient.questions` array. |
| `REQ-INT-04` | ✅ Verified | `runtime_manifest_0_2.json`'s `expected_counts`: exactly 25 `none_of_above_options` for 25 learner questions (one each), enforced by `test_runtime_contract_0_2.py`'s `test_learner_options_and_none_of_above_controls`. |
| `REQ-INT-05` | ✅ Verified | `results[questionId]` is only ever added to, never mutated, once set (`App.jsx::submitAnswer`); `replay()` clears `results`/resets `currentIndex` but never touches `orderedQuestionIds`' underlying membership. Session-local completion marks (`sessionStorage`, added 2026-08-09) are the only persistence anywhere in the frontend — no server-side attempt history exists in the schema (§2.2 above). |
| `REQ-ARC-01` | ✅ Verified | `mysql_schema_0_2.sql` has no table for expected classification/rule/criterion — the schema comment says so explicitly ("Deliberately absent: reference_response, expected_class, expected_rule..."). `app/tests/Integration/ArchitectureIsolationTest.php` (rewritten step 8, table-name assertion updated to the 9 `MODELBASE-0.2` tables) passes. |
| `REQ-ARC-02` | ✅ Verified | `Evaluator::evaluate()` is a pure function of its three arguments plus the PDO-loaded, immutable-per-request `CodingQuestion`; no rule reads wall-clock time, randomness, or session state. `app/tests/Integration/DeterminismTest.php` (rewritten step 8, covers correct/suboptimal/incorrect/`none_of_above`/gate-failure) passes. |
| `REQ-IMP-01` | ✅ Verified | Stack matches (React 19/Vite 8, PHP 8.4, MySQL, Python); the forward redesign itself is the one large documented departure from the original scope, and it is recorded with full rationale across `docs/CHANGELOG.md`'s 2026-08-08/09 entries, not silently. |
| `REQ-DOC-01` | ✅ Verified | This document, `IMPLEMENTATION_SPECIFICATION.md`, and `DEVELOPMENT_DOCUMENTATION.md` together trace architecture, schema, API, rule responsibility, and one full response end to end. |

#### 2.4.1 Bounded UX/UI and gameful presentation (`UXBASE-0.1`, catalogue §7.1)

| ID | Status | Evidence |
|---|---|---|
| `REQ-UI-01` | ✅ Verified | `components/Tutorial.jsx` replaces the former default-expanded roster `Orientation.jsx` with a four-step patient→dossier→answer→feedback modal. It auto-opens only while `icd10-prototype:tutorial-seen-v1` is absent, remains manually reopenable from `Header`, includes the same icon+text three-class legend used by feedback/review, traps focus, closes on Escape, and restores focus. `E2E/TutorialTest.php` verifies the first-visit flow, Back/Next progression, reload persistence, manual reopening, Escape, and focus restoration against the real Selenium stack (8/8 suite passing, 2026-08-09). |
| `REQ-UI-02` | ✅ Verified | `PatientCard` shows question count + `foundational`/`involved` badge; nothing in `selectPatient()`/`PatientRoster` blocks selection by difficulty. Session-local completion (`sessionStorage`, not `localStorage` — deliberately, so it's cleared at browser-session end rather than persisted indefinitely, matching this requirement's own "completion status is session-local" wording exactly) shown as a per-card badge plus an aggregate "N of 6 completed" line; the dossier reopens without losing question state (`REQ-INT-02` above). |
| `REQ-UI-03` | ✅ Verified | Every classification pairs an icon (`STATUS_ICONS`) with text — never colour alone; `:focus-visible` styling is global (`App.css`); the reduced-motion media query zeroes `--motion-duration` and the completion badge's `animation` explicitly, not just shortens it; a 375px-viewport Selenium pass showed no blocked control. No formal WCAG audit was performed (as the requirement itself allows — "no conformance claim unless separately tested"). |
| `REQ-GAM-01` | ✅ Verified | The full gameful-mechanics table in `chapter3_ux_ui_gamification_concept_0_1.md` §4 was checked item by item during implementation (`docs/CHANGELOG.md`, step 7 entry); no points/leaderboard/lives/timer exists anywhere in the frontend source, confirmed by direct inspection — there is no code that could implement one, not merely an absence of UI for one. |

### 2.5 Traceability and configuration control (catalogue §8)

| ID | Status | Evidence |
|---|---|---|
| `REQ-TRC-01` | ✅ Verified | This document *is* the traceability-matrix audit; every row has a destination or a declared, reasoned deferral. |
| `REQ-CFG-01` | 🕓 Deferred to freeze | No git commit has been pinned and no baseline has been promoted past `working_forward_implementation_candidate_not_frozen` (`runtime_manifest_0_2.json`'s own `status` field); freeze is implementation-order step 10, not requested. |

### 2.6 Reference-suite and verification requirements (catalogue §9)

| ID | Status | Evidence |
|---|---|---|
| `REQ-VER-01` | ✅ Verified | `chapter3_patient_and_question_design_plan.md`/`chapter3_reference_case_coverage_plan_forward_0_3.md` state the selection criteria; the six-patient/25-question count is a stated content decision (`REQ-DAT-09`), not a percentage-of-catalogue quota. |
| `REQ-VER-02` | ✅ Verified | `chapter3_reference_case_coverage_plan_forward_0_3.md`'s coverage matrix spans all three feedback classes across the new `RULE-REL-HARD-01`/`REL-SPEC-01`/`NOA-01` rules plus the four retained source-specific rules; no unexplained gap. |
| `REQ-VER-03` | ✅ Verified | `verification/reference_responses_0_3_candidate.csv`'s 143 rows each carry `expected_class`/`expected_determining_rule`/`expected_criterion` derived from `QSAUDIT-0.1`/the rule catalogue — authored before being checked against the implementation, per the file's own `provenance_status` column. `mysql_schema_0_2.sql` has no table this oracle could leak into at runtime (§2.4 `REQ-ARC-01` above). |
| `REQ-VER-04` | ✅ Verified | Rule/data unit tests (`Unit/*`, 77 tests), persistence/API/evaluation integration (`Integration/*`, 160 tests, live MySQL), an end-to-end learner path plus frontend-only progress/tutorial regressions (`E2E/*`, 8 tests, real Selenium against the real running stack), and negative/boundary coverage (`malformed_input`/`unsupported_response_kind`/gate failures) all exist and pass for the forward model (`docs/CHANGELOG.md`). Regression rerun after a material correction is demonstrated by this same audit's own history (§1a). |
| `REQ-VER-05` | 🕓 Deferred to freeze | The conformance categories exist in `chapter3_test_catalogue.md` §3.2.2; the final report applying them is the step-10 principal verification run. |
| `REQ-VER-06` | ✅ Verified as ongoing practice; final log is a freeze-phase artefact | Every deviation this session (the bootstrap/deployment-path gap, the `none_of_above` raw-token leak, the patient-rename hash-pin update, etc.) is logged in `docs/CHANGELOG.md` with cause, fix, and re-verification — the mechanism is demonstrably in use. |
| `REQ-VER-07` | 🕓 Deferred (data exists; not yet placed) | `verification/reference_responses_0_3_candidate.csv` already carries every mandatory field this requirement lists, and (as of step 9) every row is human-oracle-confirmed, not just present - the remaining work is purely which cases become main-text worked examples versus appendix-only, a thesis-writing decision, not a technical one. |
| `REQ-VER-08` | ✅ Verified | Step 9 (`docs/CHANGELOG.md`'s "Step 9" entry) cross-checked all 125 learner-question-domain/`none_of_above` expectations against `chapter3_question_bank_source_audit.md` (`QSAUDIT-0.1`) §4.1-4.6 - an independently-authored, source-cited design document written before and without reference to the evaluator's output, satisfying "created without copying classifier output" directly. Zero discrepancies found across all 25 questions, including the three deliberate counterexamples (`F03`, `N40`, `R40.2`) and both `none_of_above=correct` control questions. Every row's `provenance_status` now reads `forward_specification_derived_human_oracle_audit_confirmed_against_qsaudit_0_1`. |
| `REQ-VER-09` | ✅ Verified | All 18 historical expectations accounted for: 14 rows (`VQ-001`-`004`, `provenance_status = exact_semantic_carry_forward_from_rcbase_0_1`) were already an exact carry-forward from `RCBASE-0.1`, unchanged; the remaining 4 (`VQ-005`-`008`) were re-verified in step 9 by directly evaluating the documented case facts against the live `RuleMap`/`RuleStatus` predicates (not just citation-matching) and getting an exact match in every case - `provenance_status` now reads `reconstructed_from_implementation_documentation_human_oracle_audit_confirmed_via_rule_replay`. `Integration/ReferenceResponseTest.php` reruns all 18 against the live evaluator on every run; all 18 hold. |

## 3. What this audit found

**No undeclared gaps.** Every `Accepted`/`Scope constraint` requirement has
verified evidence or a reasoned freeze-phase deferral. The five rows that
briefly carried an honest ⚠ (§1a) - the property held by direct
inspection, but the automated regression meant to prove it didn't run
because `app/tests/*` still targeted deleted case-centric classes - are
resolved: implementation-order step 8 (full test-suite rewrite,
`docs/CHANGELOG.md`) landed the same day, and all five now read plain ✅
against a passing test. `REQ-VER-08`/`09` are likewise now ✅: step 9
(oracle/source audit, same day) cross-checked all 129 previously-unaudited
oracle rows against `QSAUDIT-0.1`'s source-cited design table and (for the
4 rows it doesn't cover) direct rule replay against documented case facts,
finding zero discrepancies. `REQ-VER-07` remains deferred, but for a
purely editorial reason now - the oracle content itself is audited and
ready; what's left is a thesis-writing decision about which cases appear
in the main text.

**What remains before `REQBASE-1.0`/`PROTOBASE-1.0` can be frozen**
(catalogue §12): implementation-order step 10 (freeze + principal
verification run), plus the two supervisor-level decisions unchanged since
the original brief — `OPEN-RQ-01` (final research-question wording) and
`OPEN-EVAL-01` (whether independent domain-expert review is required).
None of these are implementation gaps in the sense this audit checks for;
they are the project's own stated next steps.
