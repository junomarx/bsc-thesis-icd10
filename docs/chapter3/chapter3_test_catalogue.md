# Chapter 3 Technical Test Catalogue

**Document status:** Working implementation and verification control artefact  
**Test baseline:** `TESTBASE-0.1`  
**Date:** 6 August 2026  
**Applies to:** targeted software testing, reference-response execution, regression, and Chapter 3 verification planning  
**Upstream requirements:** `chapter3_requirements_catalogue.md`, catalogue version 0.5  
**Upstream domain baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`  
**Upstream rule baseline:** `chapter3_rule_catalogue.md`, `RULEBASE-0.1`  
**Upstream case/subset plan:** `chapter3_reference_case_coverage_plan.md`, `CASEPLAN-0.2` (supersedes `CASEPLAN-0.1`) / `SUBSET-0.1`  
**Upstream data/interaction model:** `chapter3_data_model_and_interaction_baseline.md`, `MODELBASE-0.1` / `CASEBASE-0.2` / `RCBASE-0.2`  
**Prototype working baseline:** `PROTOBASE-0.2` (supersedes `PROTOBASE-0.1`)

## 1. Purpose and claim boundary

This catalogue converts the technical coverage obligations already fixed by the requirements, rule, case, and data-model baselines into a finite test specification. It is deliberately targeted at the responsibilities that are central to the artefact: reproducible data preparation, rule predicates and boundaries, evaluation guards, precedence, persistence/API integration, reference-response conformance, deterministic behaviour, and the minimal learner workflow.

The catalogue does not validate Austrian coding rules independently. Source-derived expected behaviour enters through `RULEBASE-0.1` and the independently predefined `RCBASE-0.2`; the software is tested for conformance to those baselines. Passing this suite therefore supports a claim of technical/model conformance within the bounded prototype, not clinical validity, learning effectiveness, usability, acceptance, or comprehensive ICD-10 correctness.

No observed final result is recorded here. `TESTBASE-0.1` defines what is to be checked before the principal verification run. Execution records, deviations, corrections, reruns, and final verdicts belong to the later verification record/results.

## 2. Distinguishing `TEST-*`, `CASE-*`, and `RC-*`

The three identifiers serve different purposes:

| Identifier | Meaning | May define domain correctness? |
|---|---|---|
| `CASE-*` | Synthetic represented case facts and coding context | only through its predefined source/rule-linked model |
| `RC-*` | One submitted-code variant with an implementation-independent expected outcome | yes, within the bounded case/rule model and retained source basis |
| `TEST-*` | A software verification specification that exercises one responsibility or set of related branches | no; it compares implementation behaviour with upstream expectations |

`TEST-RC-01` therefore does not replace or duplicate the 14 `RC-*` records. It is one parameterised software test that supplies each frozen `RC-*` submission to the running evaluator and compares the observed response with the external oracle.

Pure technical fixtures used to isolate a branch, such as a case object with a deliberately removed FEV1 field, are not new clinical reference cases and must not be presented in the thesis as additional medical evidence.

## 3. Working test inventory

`TESTBASE-0.1` contains 17 test specifications. Several are parameterised and therefore produce more than one assertion. The number 17 is not a quality metric; the justification is the responsibility and branch coverage in Sections 4-8.

| Test ID | Level / technique | Responsibility | Principal trace |
|---|---|---|---|
| `TEST-DAT-01` | preparation / structural | frozen DIAGLIST source-to-subset reproducibility | `REQ-DAT-01` to `REQ-DAT-04`, `RULE-MAP-01` |
| `TEST-DAT-02` | persistence integration | runtime rows, relations, acceptable sets, and case-use flags | `REQ-DAT-03`, `REQ-DAT-04`, `REQ-MOD-02`, `REQ-ARC-01` |
| `TEST-ARC-01` | architecture inspection + integration | runtime/verification-oracle separation | `REQ-ARC-01`, `REQ-VER-03`, `REQ-TRC-01` |
| `TEST-MAP-01` | unit / boundary | FEV1 mapping and applicability | `RULE-MAP-01`, `REQ-MOD-01`, `REQ-ARC-02` |
| `TEST-GATE-01` | unit / negative boundary | evaluation eligibility and non-classification | `RULE-GATE-01`, `REQ-RUL-05` |
| `TEST-STATUS-01` | unit / decision table | hospital `!` status predicate | `RULE-STATUS-01`, `PAT-STATUS-01` |
| `TEST-DEPTH-01` | unit / decision table | mandatory COPD coding depth | `RULE-DEPTH-01`, `PAT-DEPTH-01` |
| `TEST-EVID-01` | unit / decision table | explicit FEV1/code contradiction | `RULE-EVID-01`, `PAT-EVID-01` |
| `TEST-SPEC-01` | unit / decision table | source-backed specificity rule | `RULE-SPEC-01`, `PAT-SPEC-01`, `REQ-FBK-02` |
| `TEST-CORRECT-01` | unit / decision table | predefined acceptance | `RULE-CORRECT-01`, `REQ-RUL-03` |
| `TEST-PREC-01` | unit / control | precedence, retained hard matches, terminal gap | `RULE-PREC-01`, `REQ-RUL-04` |
| `TEST-API-01` | API integration / negative | one-code request contract and validation boundary | `REQ-INT-01`, `REQ-RUL-05` |
| `TEST-RC-01` | API/service integration / parameterised reference | all 18 predefined reference responses | `RCBASE-0.2`, `REQ-VER-02`, `REQ-VER-03`, `REQ-FBK-01` |
| `TEST-DET-01` | integration / repeatability | deterministic evaluation on unchanged baseline | `REQ-ARC-02` |
| `TEST-E2E-01` | end-to-end / parameterised | learner case-to-feedback workflow across all three classes | `REQ-INT-01`, `REQ-FBK-01`, `REQ-FBK-02` |
| `TEST-E2E-02` | end-to-end / scope guard | exclusion of verification-only case from learner presentation | `MODELBASE-0.1`, `REQ-SCP-01`, `REQ-ARC-01` |
| `TEST-CFG-01` | configuration inspection / integration | frozen baseline and execution-identity consistency | `REQ-CFG-01`, `REQ-TRC-01` |

## 4. Data, model, and architecture tests

### `TEST-DAT-01` - frozen source-to-subset reproducibility

**Purpose.** Verify that the application subset is a reproducible projection of the frozen DIAGLIST source rather than a manually transcribed approximation.

**Inputs.** Frozen `DIAGLIST2026.xlsx`, worksheet `DIAGLIST2026`, `SUBSET-0.1`, and the four-field whitelist.

**Required assertions.** The source checksum equals `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b`; the source contains 13,298 unique `Diagnose` identifiers; the derived subset contains exactly the 13 predefined unique codes; `Diagnose`, `Bezeichnung`, and `Kurzbezeichnung` reproduce the source values; `Kennzeichen` follows the declared trim/blank-to-null normalisation and preserves `!` for `Z01.6`; no additional DIAGLIST field is silently imported; and the mapped targets required by the frozen COPD cases (`J44.02`, `J44.12`) exist in the subset.

**Pass condition.** Every assertion holds against the same frozen workbook and declared transformation. A source checksum or source-value mismatch is a data/baseline deviation, not a coding-classification failure.

The supplied candidate `prototype_baseline_0_1/validate_baseline.py` and deterministic `scripts/prepare_subset.py --check-existing` path provide preparatory implementations of these structural checks. They must be inspected and executed after adoption in the actual project; the handoff does not inherit an exploratory execution verdict.

### `TEST-DAT-02` - runtime persistence and relation integrity

**Purpose.** Verify that the MySQL state used by the evaluator is the intended physical representation of `SUBSET-0.1` and `CASEBASE-0.2`.

**Required assertions after import.** The runtime contains 13 catalogue rows, eight case rows, and 18 case-code-domain relations; each relation resolves to an existing case and catalogue record; response-domain sizes are 6/6/1/1/1/1/1/1 for `CASE-001` to `CASE-008`; accepted sets are exactly `{J44.02}`, `{J44.12}`, `{Z01.6}`, `{J44.00}`, `{J44.11}`, `{J44.03}`, and the empty set for `CASE-004` and `CASE-008`; only `CASE-004` and `CASE-008` are `verification_only`; the two original COPD cases retain their 55.00% and 50.00% stable-phase FEV1 values (the three cases added by the pre-freeze coverage review retain 20.00%, 35.00%, and 70.00% respectively); and the hospital-outpatient cases retain respectively `false` and `true` for the inpatient-LKF-scoring flag, while the inpatient cases (including the review-added `CASE-008`) carry no such flag.

**Pass condition.** The persisted state is value-equivalent to the versioned runtime input artefacts and satisfies the declared foreign-key/model relations.

**Candidate implementation binding.** `scripts/runtime_data.py` is intended to validate the explicit runtime-only input set, `scripts/apply_mysql_schema.py` to apply the DDL to an empty target, and `scripts/load_mysql.py` to implement the data transaction. The schema DDL is applied separately because MySQL DDL is not treated as part of the atomic data transaction. `tests/test_mysql_persistence.py` is intended to query the live persisted state independently and exercise foreign-key enforcement without reading `RCBASE-*`. These are supplied candidates, not inherited verification evidence. `TEST-DAT-02` remains unexecuted for project-evidence purposes until the adopted implementation is run and its observations are recorded; it must later be rerun against the final frozen environment in accordance with Section 11.

### `TEST-ARC-01` - verification-oracle isolation

**Purpose.** Demonstrate that the evaluator is not made trivially conformant by importing the expected `RC-*` results into its runtime classification path.

**Required assertions.** The runtime schema and import path contain no `expected_class`, expected determining rule, expected criterion, or reference-response table used by the classifier; the application can evaluate a supported response when the `verification/` oracle fixture is unavailable to the runtime process; and the test harness, not the evaluator, is the component that reads `RCBASE-*` expectations for comparison. The candidate importer is designed to enforce the structural half of this boundary through an explicit four-file runtime allowlist; that property still has to be confirmed after adoption, and the behavioural half remains to be demonstrated once the PHP evaluator exists.

`case_code_domain.is_acceptable` is explicitly permitted: it is an input to `RULE-CORRECT-01` and is part of the case model. A false acceptable flag must not itself be interpreted as `incorrect`.

**Pass condition.** Expected-output oracle data are absent from the runtime classification dependency path while normal rule evaluation remains functional.

## 5. Rule-unit and decision-boundary tests

The source-derived predicates in this section inherit their controlling printed-page locators from `RULEBASE-0.1`. No test fixture creates a new coding rule.

### `TEST-MAP-01` - FEV1 mapping and applicability

For an inpatient `J44.0` case with the corresponding target records present in `SUBSET-0.1`, the following boundary vectors are predefined:

| Vector | Stable-phase FEV1 (% predicted) | Expected suffix | Expected target |
|---|---:|---:|---|
| MAP-A | 34.99 | `0` | `J44.00` |
| MAP-B | 35.00 | `1` | `J44.01` |
| MAP-C | 49.99 | `1` | `J44.01` |
| MAP-D | 50.00 | `2` | `J44.02` |
| MAP-E | 69.99 | `2` | `J44.02` |
| MAP-F | 70.00 | `3` | `J44.03` |

Additional applicability controls are: `CASE-001` at 55.00% derives `J44.02`; `CASE-002` at exactly 50.00% derives `J44.12`; absent FEV1 produces no derived suffix/target; and a hospital-outpatient context does not activate this inpatient COPD helper. The test does not impose an uncited clinical plausibility range beyond the published threshold logic.

**Pass condition.** All interval and applicability results equal `RULE-MAP-01`, including exact 35%, 50%, and 70% boundaries.

### `TEST-GATE-01` - eligibility and scope boundaries

| Vector | Input relation | Expected gate behaviour |
|---|---|---|
| GATE-A | `CASE-001` + `J44.02` under the active baselines | eligible |
| GATE-B | `CASE-001` + `Z01.8` | `not_evaluated`, `classification = null`, reason `outside_active_subset` |
| GATE-C | `CASE-001` + active `J44.12` | `not_evaluated`, `classification = null`, reason `undefined_case_relation` |
| GATE-D | test-only copy of a COPD relation that requires FEV1, with FEV1 removed | `not_evaluated`, `classification = null`, reason `missing_required_case_fact` |
| GATE-E | test-only hospital-outpatient `!` main-diagnosis relation with the inpatient-LKF-scoring flag absent | `not_evaluated`, `classification = null`, reason `missing_required_case_fact` |

`Z01.8!` is intentionally a known Austrian 2026 source record outside `SUBSET-0.1`; the expected runtime message is nevertheless the weaker `outside_active_subset` result because the initial runtime model does not import a full-version membership index. `not_in_frozen_version` is therefore not activated as a distinct runtime test in `TESTBASE-0.1`.

**Pass condition.** Only GATE-A enters three-class evaluation; none of the negative vectors is relabelled `incorrect`.

### `TEST-STATUS-01` - `!` status predicate

The rule predicate is isolated from unrelated acceptance logic:

| Vector | Marker / role / setting | Expected rule match |
|---|---|---|
| STATUS-A | `!`, main, inpatient | yes, `context_status_incompatibility` |
| STATUS-B | `!`, main, hospital outpatient, `inpatient_lkf_scored = true` | yes, `context_status_incompatibility` |
| STATUS-C | `!`, main, hospital outpatient, `inpatient_lkf_scored = false` | no |
| STATUS-D | `!`, additional, inpatient | no |
| STATUS-E | no `!`, main, inpatient | no |

The missing hospital-outpatient scoring flag is handled by `TEST-GATE-01`; it must not be defaulted to `false` inside the status rule.

**Pass condition.** Matches are exactly those defined by `RULE-STATUS-01`. A non-match does not by itself assert that the response is otherwise correct.

### `TEST-DEPTH-01` - mandatory COPD coding depth

| Vector | Setting / submitted code | Expected rule match |
|---|---|---|
| DEPTH-A | inpatient / `J44.0` | yes, `mandatory_coding_depth_not_met` |
| DEPTH-B | inpatient / `J44.02` | no |
| DEPTH-C | hospital outpatient / `J44.0` | no under the deliberately inpatient-only initial rule |

Outside-subset handling is not tested through this rule because `RULE-GATE-01` must stop it first.

### `TEST-EVID-01` - explicit FEV1/code conflict

Using `CASE-001` (stable-phase FEV1 55%, expected suffix `2`), `J44.00`, `J44.01`, and `J44.03` must match `case_evidence_conflict`; `J44.02` must not match; and `J44.09` must not match this rule because suffix `9` represents unspecified FEV1 rather than a contradictory severity band. A different COPD base is a non-match at the isolated predicate level and is not thereby given a terminal class.

`CASE-002` and `RC-002-03` provide the integration-level exact-50% boundary check: `J44.11` conflicts because the source-defined suffix-1 interval ends below 50%.

### `TEST-SPEC-01` - source-backed specificity

| Vector | Relation | Expected rule behaviour |
|---|---|---|
| SPEC-A | `CASE-001` + `J44.09` | match; `suboptimal`; target `J44.02` |
| SPEC-B | `CASE-002` + `J44.19` | match; `suboptimal`; target `J44.12` |
| SPEC-C | `CASE-001` + `J44.02` | no specificity match |
| SPEC-D | otherwise matching test fixture with FEV1 absent | no `suboptimal` result; full evaluation is stopped by the missing-fact gate |
| SPEC-E | otherwise matching test fixture with diagnosis role `additional` | no specificity match |

For each positive vector, required feedback semantics include the unspecified response, represented FEV1, mapped specific target, and concrete improvement direction.

### `TEST-CORRECT-01` - declared acceptance

`CASE-001 + J44.02` and `CASE-003 + Z01.6` must reach `accepted_response` only after gate, hard, and graded rules clear. A non-accepted code must not match `RULE-CORRECT-01` merely because another rule happens not to classify it. No acceptable-alternative vector is added because `CASEBASE-0.2` deliberately contains no source-bounded alternative-equivalence case.

### `TEST-PREC-01` - precedence and terminal policy

This is a controller test over rule-match outputs, not a fabricated medical case. It may therefore exercise combinations that do not naturally occur in `SUBSET-0.1` without asserting that such a clinical/coding combination exists.

| Vector | Synthetic rule-stage state | Expected terminal behaviour |
|---|---|---|
| PREC-A | hard=`{EVID}`, specificity=true, accepted=true | `incorrect`, determining hard rule `EVID` |
| PREC-B | no hard; specificity=true; accepted=true | `suboptimal`, determining rule `SPEC` |
| PREC-C | no hard; specificity=false; accepted=true | `correct`, determining rule `CORRECT` |
| PREC-D | no hard; specificity=false; accepted=false | specification/conformance gap; no learner three-class result |
| PREC-E | hard=`{STATUS, DEPTH, EVID}` in varying iteration orders | `incorrect`; primary `STATUS`; all three retained in technical trace |
| PREC-F | hard=`{DEPTH, EVID}` in varying iteration orders | `incorrect`; primary `DEPTH`; both retained in technical trace |

**Pass condition.** The output depends on the declared priority policy, not storage or iteration order, and a no-terminal relation never defaults to `incorrect`.

## 6. API, reference, and repeatability tests

### `TEST-API-01` - one-code interaction and validation contract

The valid conceptual request is one `submitted_code` string for one case identifier. The test must verify at least:

1. a single non-empty code string enters normal evaluation;
2. a missing required submission is rejected before classification;
3. an empty/whitespace-only submission is rejected as malformed and is not given a feedback class;
4. an array/list of codes is rejected rather than implicitly aggregated; and
5. a gate failure such as `CASE-001 + Z01.8` returns a non-classified scope/validation response with `classification = null`.

The final PHP endpoint may bind these semantic outcomes to concrete HTTP status codes and response-field names during implementation, but it must not weaken the single-code or non-classification semantics fixed here.

### `TEST-RC-01` - complete reference-response conformance

The test harness reads `verification/reference_responses_0_1.csv`; the evaluator does not. For each of its 14 `RC-*` rows, the harness sends only the parent `case_id` and `submitted_code` through the implemented evaluation boundary and compares the returned result with the external expectation.

For each row, the following are mandatory comparisons:

- `evaluation_status`;
- feedback class;
- determining `RULE-*`;
- criterion key;
- improvement code where an expectation is defined;
- presence and non-empty content of every required explanation element recorded by the oracle; and
- a non-empty learner explanation for classified learner-facing results.

Free-text wording need not be byte-for-byte identical to a stored sentence. The test compares the specified semantic elements so that harmless phrasing changes do not redefine correctness.

The expected class distribution of the working oracle is 3 `correct`, 2 `suboptimal`, and 9 `incorrect`. That distribution is an integrity check on the fixture, not a population statistic and not an accuracy denominator by itself.

**Pass condition.** Every `RC-*` result conforms to all required fields/elements. Per-row verdicts must be retained for the final verification record rather than reporting only one aggregate percentage.

### `TEST-DET-01` - deterministic repeatability

Under an unchanged source/subset/case/rule/model/software baseline, repeat representative requests from different terminal paths, at minimum one `correct`, one `suboptimal`, one `incorrect`, and one gate failure. The repeated responses must retain identical evaluation status, class, determining rule/criterion, improvement target, required explanation semantics, and matched-rule trace. Request-specific transport metadata such as timestamps, if later introduced, is excluded from the equality comparison.

This finite test detects implementation nondeterminism; it is not presented as a mathematical proof that no nondeterminism can ever occur.

## 7. End-to-end tests

### `TEST-E2E-01` - learner workflow across the three feedback classes

Use learner-visible `CASE-001` and execute the complete React-to-PHP-to-MySQL/rule-engine path for three submissions:

| Submission | Expected learner-visible class | Essential feedback check |
|---|---|---|
| `J44.02` | `correct` | accepted response is explained |
| `J44.09` | `suboptimal` | feedback identifies the specificity issue and `J44.02` improvement |
| `J44.01` | `incorrect` | feedback identifies conflict with the represented FEV1 criterion |

For every vector, the learner can view the synthetic case, select/search and submit one code, and receive the resulting class plus criterion-specific explanation without manual alteration of the evaluation result.

### `TEST-E2E-02` - verification-only case boundary

`CASE-004` (and, since the pre-freeze coverage review, `CASE-008`) must not be offered through the learner-facing case list/navigation in the initial prototype. Both remain available to the technical verification harness/evaluation layer as required by `RC-004-01`/`RC-008-01`. The test therefore verifies UI intended-use filtering without deleting the underlying verification fixture.

## 8. Configuration and regression control

### `TEST-CFG-01` - evaluated baseline identity

Before the principal run, the evaluation record must bind the exact source register, catalogue/subset, domain, requirements, rule, model, case/reference-response, test specification, application revision, database state, and relevant execution-environment versions. Runtime identifiers must agree with the frozen evaluation manifest. The current working identifiers are `SUBSET-0.1`, `DOMBASE-0.1`, `RULEBASE-0.1`, `MODELBASE-0.1`, `CASEBASE-0.2`, `RCBASE-0.2`, `TESTBASE-0.1`, and `PROTOBASE-0.2`; final verification may promote these to frozen versions rather than silently changing their contents.

**Pass condition.** The executed software/data state can be unambiguously related to the recorded baseline. A material post-freeze change creates a new version and triggers impact-based reruns.

### Regression policy

Regression is a rerun policy over the tests above rather than an additional clinical case. After a material correction, every directly affected `TEST-*` and every linked `RC-*` must be rerun. Changes to shared rule-engine control, persistence, or API response construction require a broader rerun, including `TEST-RC-01`; changes capable of affecting the learner path also require `TEST-E2E-01`. Previous failed observations must be retained in the deviation/change record rather than overwritten by the successful rerun.

The exact deviation categories, run identifiers, correction workflow, and final verdict vocabulary belong to the verification-procedure baseline in Section 3.2.2 and are not duplicated here.

## 9. Coverage audit

### 9.1 Rule coverage

| Rule | Direct targeted test | Integration/reference coverage |
|---|---|---|
| `RULE-GATE-01` | `TEST-GATE-01`, `TEST-API-01` | gate paths in API/negative execution |
| `RULE-MAP-01` | `TEST-MAP-01` | `CASE-001/002` through `TEST-RC-01` |
| `RULE-STATUS-01` | `TEST-STATUS-01` | `RC-003-01`, `RC-004-01` via `TEST-RC-01` |
| `RULE-DEPTH-01` | `TEST-DEPTH-01` | `RC-001-01`, `RC-002-01` via `TEST-RC-01` |
| `RULE-EVID-01` | `TEST-EVID-01` | mismatch variants in both COPD cases via `TEST-RC-01` |
| `RULE-SPEC-01` | `TEST-SPEC-01` | `RC-001-06`, `RC-002-06` via `TEST-RC-01` |
| `RULE-CORRECT-01` | `TEST-CORRECT-01` | three correct RC variants via `TEST-RC-01` |
| `RULE-PREC-01` | `TEST-PREC-01` | terminal-path consistency through `TEST-RC-01` and `TEST-DET-01` |

Every rule therefore has a direct targeted path. Domain-level `RC-*` coverage remains complementary rather than being counted as a substitute for isolated rule/control tests.

### 9.2 Requirement coverage relevant to software verification

| Requirement group | Principal verification destination |
|---|---|
| `REQ-DAT-*` | `TEST-DAT-01`, `TEST-DAT-02`, `TEST-STATUS-01` |
| `REQ-MOD-*` | `TEST-DAT-02`, `TEST-MAP-01`, `TEST-GATE-01`, `TEST-RC-01` |
| `REQ-RUL-*` | direct `TEST-MAP/GATE/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-*` tests plus `TEST-RC-01` |
| `REQ-FBK-*` | `TEST-SPEC-01`, `TEST-RC-01`, `TEST-E2E-01` |
| `REQ-INT-01` | `TEST-API-01`, `TEST-E2E-01` |
| `REQ-ARC-01/02` | `TEST-ARC-01`, `TEST-DET-01`, integration/reference tests |
| `REQ-CFG-01`, `REQ-TRC-01` | `TEST-CFG-01` plus traceability/baseline inspection |
| `REQ-VER-01/02` | `CASEPLAN-0.2` coverage audit plus `TEST-RC-01`; these are methodological coverage requirements rather than standalone unit tests |
| `REQ-VER-03/04` | `TEST-ARC-01`, `TEST-RC-01`, and the complete `TESTBASE-*` inventory |
| `REQ-VER-05/06` | verification-procedure/conformance baseline and eventual execution/deviation records, not a software-test predicate |
| `REQ-VER-07` | thesis/appendix inspection, not a software test |
| `REQ-IMP-01`, `REQ-DOC-01` | as-built architecture/build/documentation inspection, not artificially converted into executable tests |

This distinction is intentional. A requirement should be verified by an appropriate technique; forcing documentary, configuration, or methodological requirements into artificial unit tests would weaken rather than improve traceability.

## 10. Deliberate exclusions and currently inapplicable tests

The following are not gaps in `TESTBASE-0.1` unless the corresponding scope changes:

- no learner/usability/acceptance or learning-effect test;
- no independent clinical-validity test; `OPEN-EVAL-01` remains a separate supervisory/evaluation decision;
- no performance, load, scalability, penetration, production-availability, or regulatory test without a corresponding prototype requirement;
- no exhaustive Austrian ICD-10 catalogue test beyond the frozen subset/source-preparation controls;
- no extramural coding-rule test because no extramural-specific executable rule is active;
- no acceptable-alternative equivalence test because no such source-bounded case is included in `CASEBASE-0.2`;
- no multi-code aggregation test because the interaction model explicitly excludes multi-code responses; `TEST-API-01` instead verifies rejection of multi-code request shapes;
- no `not_in_frozen_version` runtime distinction while the application retains only the active subset rather than a full-version membership index; and
- no natural multi-hard-rule clinical case because the frozen subset supplies none. `TEST-PREC-01` verifies the controller semantics without manufacturing a domain judgement.

## 11. Freeze conditions and downstream use

`TESTBASE-0.1` may be promoted to `TESTBASE-1.0` before the principal verification run when:

1. each test is bound to the actual implementation unit, endpoint, query/import path, or UI path that realizes its subject;
2. any implementation-specific API status/field names used in assertions are recorded without weakening the conceptual expectations above;
3. the final frozen `SUBSET-*`, `CASEBASE-*`, `RCBASE-*`, `RULEBASE-*`, `MODELBASE-*`, software revision, and execution environment are identified;
4. `TEST-RC-01` demonstrably reads its oracle only in the harness and not in the runtime evaluator;
5. every implemented rule branch and every central testable requirement has a linked test or an explicit justified omission;
6. expected outputs remain fixed before the principal run; and
7. any material test-specification change receives a new baseline version and an impact record.

For Chapter 3, Section 3.2.1 should summarise this catalogue by test responsibility and coverage rather than reproducing all vectors. The complete `TESTBASE-*` inventory can be placed in an appendix if required. Section 3.2.2 should then define the execution record, conformance/deviation categories, correction/rerun protocol, and final baseline-freeze procedure. Actual outcomes belong in Results under the current chapter boundary.

With this catalogue specified, the development work can proceed against explicit acceptance/verification targets. The next implementation dependency is to bind the data importer, PHP evaluation service/API, MySQL persistence, and React learner path to these contracts and then make the corresponding automated tests executable.
