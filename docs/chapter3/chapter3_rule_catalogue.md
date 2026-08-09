# Chapter 3 Rule Catalogue

**Document status:** Working implementation-facing control artefact  
**Rule baseline:** `RULEBASE-0.1`  
**Date:** 6 August 2026  
**Applies to:** rule implementation, feedback construction, `CASE-*`/`RC-*` expectations, targeted software tests, and later verification  
**Upstream domain baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`  
**Upstream requirements:** `chapter3_requirements_catalogue.md`, catalogue version 0.5  
**Upstream source register:** `chapter3_input_source_baseline_register.md`, register version 0.4
**Downstream case/subset plan:** `chapter3_reference_case_coverage_plan.md`, `CASEPLAN-0.2` (supersedes `CASEPLAN-0.1`) / `SUBSET-0.1`
**Downstream data/interaction model:** `chapter3_data_model_and_interaction_baseline.md`, `MODELBASE-0.1`
**Downstream technical test baseline:** `chapter3_test_catalogue.md`, `TESTBASE-0.1`

## 1. Purpose and authority boundary

This catalogue translates the four response patterns fixed in `DOMBASE-0.1` into explicit rules that can be implemented and tested. It is the contract between the conceptual model and the software. It is not implementation code and does not make the rule engine an independent source of Austrian coding truth.

Three kinds of statements must remain distinct:

1. **Domain predicates** are derived from the frozen Austrian sources. They state conditions such as mandatory coding depth, FEV1-to-suffix mapping, or a `!` status restriction.
2. **Artefact classifications** map those predicates to `correct`, `suboptimal`, or `incorrect`. These labels are project-specific educational outputs.
3. **Control rules** govern evaluation eligibility, accepted-response handling and precedence. They are methodological/software decisions, not Austrian coding rules.

Only an evaluated relation that is explicitly within the bounded prototype model receives one of the three feedback classes. There is deliberately no catch-all rule stating that every unrecognised or unmodelled selection is `incorrect`.

`RULEBASE-0.1` is sufficiently specified to drive implementation and reference-case construction, but it is **not yet the final verification-frozen rule baseline**. `RULEBASE-1.0` should be assigned only after the active subset, case schema, reference expectations and rule implementation have been checked for traceability and consistency.

## 2. Atomic evaluation unit

The rules below classify one atomic submitted code against one represented case context. `MODELBASE-0.1` resolves the former `OPEN-INT-01` by fixing exactly one submitted ICD code per case-defined coding target and evaluation request. Multiple attempts remain possible as separate requests. Multi-code response aggregation is outside the initial prototype; adding it later would require explicit combination-level semantics and a new model/rule baseline rather than assuming that per-code classifications can simply be combined.

For the present rule layer, the evaluation tuple is:

```text
E = (
  case,
  submitted_code,
  catalogue_record,
  catalogue_version,
  active_subset_version,
  rule_baseline_version
)
```

The terminal result has the following conceptual shape:

```text
evaluation_status: classified | not_evaluated
classification: correct | suboptimal | incorrect | null
determining_rule: RULE-* | null
criterion: stable machine-readable criterion key
explanation_elements: structured payload
matched_rules: RULE-*[]
improvement_code: ICD code | null
baseline_versions: source / subset / rule / case versions
```

`matched_rules` is retained for traceability even when only one determining rule is shown to the learner.

## 3. Required normalized inputs

The final physical schema may use different column/property names, but it must supply the following semantics wherever an applicable rule needs them.

| Logical input | Type / domain | Origin | Used by |
|---|---|---|---|
| `submitted_code` | ICD/code identifier | learner response | all rules |
| `catalogue_record.code` | identifier | DIAGLIST `Diagnose` | gate, depth, evidence, specificity, correct |
| `catalogue_record.marker` | `!`, `#`, other/blank | DIAGLIST `Kennzeichen` | status |
| `case.response_relation_defined` | Boolean | case/rule specification | gate |
| `case.acceptable_codes` | closed, versioned set | predefined case oracle with source/rule trace | correct |
| `case.diagnosis_role` | `main` / `additional` where relevant | synthetic case task | status, specificity |
| `case.encounter_setting` | `inpatient` / `hospital_outpatient` for active setting rules | synthetic case | status, COPD rules |
| `case.inpatient_lkf_scored` | Boolean when required | explicit synthetic hospital-outpatient context | status |
| `case.copd_base_code` | represented four-character J44 base, e.g. `J44.0` | predefined synthetic case facts/source mapping | FEV1 derivation, evidence, specificity |
| `case.fev1_stable_pct_predicted` | numeric percentage or absent | explicit synthetic case fact | FEV1 derivation, evidence, specificity |
| `active_subset_membership` | Boolean | versioned prototype subset | gate |

No rule is permitted to manufacture a missing case value. If a rule requires a fact that the case does not contain, that rule cannot trigger. If the missing fact is necessary to decide an intended reference response, the case is incomplete and must not be frozen as a deterministic `RC-*`.

### 3.1 Source-derived constants used by `RULEBASE-0.1`

| Constant | Value | Source basis |
|---|---|---|
| Mandatory COPD fifth-character family | `J44.0` through `J44.9` within the applicable inpatient rule context | `SRC-AT-DOC-2026`, printed pp. 12, 26 |
| Explicit COPD unspecified-main-diagnosis warning codes | `J44.09`, `J44.19`, `J44.89`, `J44.99` | `SRC-AT-DOC-2026`, printed p. 26 |
| COPD severity suffix `0` | FEV1 < 35% of predicted | `SRC-AT-DOC-2026`, printed p. 34 |
| COPD severity suffix `1` | FEV1 >= 35% and < 50% | `SRC-AT-DOC-2026`, printed p. 34 |
| COPD severity suffix `2` | FEV1 >= 50% and < 70% | `SRC-AT-DOC-2026`, printed p. 34 |
| COPD severity suffix `3` | FEV1 >= 70% | `SRC-AT-DOC-2026`, printed p. 34 |
| COPD severity suffix `9` | FEV1 not further specified | `SRC-AT-DOC-2026`, printed p. 34 |
| `!` main-diagnosis prohibition | prohibited for inpatient stays and hospital-outpatient visits scored according to the inpatient LKF model; permitted as main diagnosis in hospital outpatient care when that scoring condition does not apply | `SRC-AT-DOC-2026`, printed pp. 10-11, 18 |

The initial COPD classification rules below are deliberately restricted to **inpatient** cases. This is narrower than attempting to generalise every statement in the source across hospital settings and is directly supported by the inpatient documentation/warning and stable-phase FEV1 passages on printed pp. 26 and 34.

## 4. Rule inventory

| Rule ID | Kind | Pattern | Effect | Authority |
|---|---|---|---|---|
| **`RULE-GATE-01`** | evaluation guard | none | eligible / `not_evaluated` | artefact control |
| **`RULE-MAP-01`** | deterministic derivation | supports `PAT-EVID-01`, `PAT-SPEC-01` | derive expected COPD FEV1 suffix/target | Austrian domain predicate |
| **`RULE-STATUS-01`** | hard classification | `PAT-STATUS-01` | `incorrect` | Austrian predicate + artefact class mapping |
| **`RULE-DEPTH-01`** | hard classification | `PAT-DEPTH-01` | `incorrect` | Austrian predicate + artefact class mapping |
| **`RULE-EVID-01`** | hard classification | `PAT-EVID-01` | `incorrect` | Austrian predicate + artefact class mapping |
| **`RULE-SPEC-01`** | graded classification | `PAT-SPEC-01` | `suboptimal` | Austrian predicate + artefact class mapping |
| **`RULE-CORRECT-01`** | terminal acceptance | control / acceptable set | `correct` | artefact control backed by case-specific oracle |
| **`RULE-PREC-01`** | conflict/terminal policy | all | deterministic determining result | artefact control |

The four `PAT-*` elements are therefore represented by four specified classification rules. `RULE-MAP-01` supplies the source-defined quantitative derivation used by two of them; the remaining rules control evaluation rather than add further coding-error categories. Software implementation of those rules remains a downstream task.

## 5. Detailed rule records

### `RULE-GATE-01` - bounded evaluation eligibility

| Field | Specification |
|---|---|
| **Kind** | Evaluation guard; not a three-class coding rule |
| **Implements** | `REQ-SCP-01`, `REQ-DAT-03`, `REQ-MOD-01`, `REQ-RUL-05`, `REQ-ARC-02` |
| **Inputs** | submitted code; frozen catalogue/subset identity; active subset membership; case-response relation; rule-required case fields |
| **Pass condition** | The submitted response is available to the supported prototype evaluation, the response-case relation is explicitly defined, and every case fact declared mandatory for that defined relation is represented. A fact used only by an optional/non-applicable rule does not become globally mandatory merely because that rule exists. |
| **Fail effect** | `evaluation_status = not_evaluated`, `classification = null`; return a validation/scope reason rather than `incorrect`. |
| **Permitted validation reasons** | `malformed_input`, `outside_active_subset`, `not_in_frozen_version` only where full frozen-version membership is actually checked, `undefined_case_relation`, `missing_required_case_fact` |
| **Source / rationale** | `SRC-AT-ICD-SYS-2026`, printed p. 14 for edition identity; frozen DIAGLIST membership for machine-readable catalogue support; bounded-subset distinction is the project decision in `DOMBASE-0.1` and `REQ-RUL-05`. |
| **Precedence** | Stage 0. A failed gate stops classification. |
| **Explanation payload** | Scope/validation reason only. It must not assert that an outside-subset code is wrong Austrian coding. |
| **Verification obligation** | Negative tests for at least outside-subset and undefined-relation handling; a missing-required-fact test for a relation that explicitly requires that fact; a `not_in_frozen_version` test only if full-version membership is implemented. Also verify that absence of an optional/non-applicable fact does not itself fail the gate. |

Implementation caution: if the deployed prototype retains only the active subset rather than a full membership index, it cannot distinguish “outside subset” from “absent from Austrian ICD-10 BMASGPK 2026” at runtime. In that architecture the correct message is the weaker `outside_active_subset`/unsupported result.

### `RULE-MAP-01` - stable-phase FEV1 to COPD fifth-character suffix

| Field | Specification |
|---|---|
| **Kind** | Deterministic source-derived helper; does not itself return a feedback class |
| **Supports** | `PAT-EVID-01`, `PAT-SPEC-01`; `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-ARC-02` |
| **Inputs** | `case.encounter_setting`, `case.copd_base_code`, `case.fev1_stable_pct_predicted` |
| **Applicability** | Initial rule baseline: inpatient COPD case; four-character base within J44.0-J44.9; explicit stable-phase FEV1 percentage is present. |
| **Derivation** | `<35 -> 0`; `>=35 and <50 -> 1`; `>=50 and <70 -> 2`; `>=70 -> 3`. |
| **Derived output** | `expected_fev1_suffix`; `expected_specific_code = case.copd_base_code + expected_fev1_suffix`, provided that this target exists in the frozen supported catalogue/subset used by the case. |
| **Source basis** | `SRC-AT-DOC-2026`, printed p. 34. The source also states that severity is to be determined in a stable phase during the inpatient stay. |
| **Failure behaviour** | If the case lacks a stable-phase FEV1 value, no suffix 0-3 is inferred. A case intended to exercise `RULE-EVID-01` or `RULE-SPEC-01` is then incomplete. |
| **Verification obligation** | Unit tests for representative values and exact boundaries 35%, 50% and 70%; data-integrity test that a derived target used by a frozen case exists in the selected catalogue subset. |

Synthetic FEV1 values must themselves be valid percentages for the intended case. `RULE-MAP-01` does not invent a clinical plausibility range that the cited source does not specify.

### `RULE-STATUS-01` - prohibited `!` main-diagnosis use

| Field | Specification |
|---|---|
| **Kind / pattern** | Hard classification; `PAT-STATUS-01` |
| **Implements** | `REQ-DAT-05`, `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-RUL-04`, `REQ-FBK-01` |
| **Inputs** | `catalogue_record.marker`, `case.diagnosis_role`, `case.encounter_setting`, and `case.inpatient_lkf_scored` for relevant hospital-outpatient cases |
| **Trigger** | `marker = !` AND `diagnosis_role = main` AND (`encounter_setting = inpatient` OR (`encounter_setting = hospital_outpatient` AND `inpatient_lkf_scored = true`)). |
| **Outcome** | `incorrect` |
| **Criterion key** | `context_status_incompatibility` |
| **Source basis** | `SRC-AT-DOC-2026`, printed pp. 10-11 and 18. DIAGLIST `Kennzeichen` supplies the marker. Printed p. 22 supplies concrete ordinary hospital-outpatient examples `Z01.6!` and `Z01.8!`, demonstrating that the marker is context-dependent. |
| **Precedence** | Hard stage; primary hard-error priority 1 (`STATUS`). |
| **Required explanation elements** | identify the `!` status; identify the represented prohibited role/setting; state the applicable use restriction. Do **not** invent an alternative clinical diagnosis. |
| **Non-trigger control** | `!` + main diagnosis + hospital outpatient + `inpatient_lkf_scored = false` does not trigger this rule. Correctness still requires `RULE-CORRECT-01`; non-triggering alone does not prove that the code is otherwise appropriate. |
| **Verification obligation** | Trigger tests for inpatient and stationary-LKF-scored outpatient context; non-trigger test for ordinary hospital-outpatient context; missing scoring flag must not silently default to `false` when it is needed. |

### `RULE-DEPTH-01` - mandatory five-character COPD coding

| Field | Specification |
|---|---|
| **Kind / pattern** | Hard classification; `PAT-DEPTH-01` |
| **Implements** | `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-RUL-04`, `REQ-FBK-01` |
| **Inputs** | submitted code; represented inpatient setting; active catalogue/subset record |
| **Trigger** | `encounter_setting = inpatient` AND submitted response is an active four-character parent in J44.0-J44.9 for which the represented Austrian rule requires the fifth character. |
| **Outcome** | `incorrect` |
| **Criterion key** | `mandatory_coding_depth_not_met` |
| **Source basis** | `SRC-AT-DOC-2026`, printed p. 12 (J44.0-J44.9 among diagnoses recorded with five characters in Austrian hospitals) and printed p. 26 (five-character COPD coding mandatory in the inpatient documentation context). Initial DIAGLIST proof family includes `J44.0` and its five-character records at worksheet rows 3884-3889. |
| **Precedence** | Hard stage; primary hard-error priority 2 (`DEPTH`). |
| **Required explanation elements** | state that the submitted four-character form does not satisfy the required coding depth; identify the required five-character level. If `RULE-MAP-01` has a valid target, that target may be shown as corrective direction. |
| **Verification obligation** | Trigger with an active four-character J44 parent; non-trigger with an applicable five-character response; ensure a merely out-of-subset code is stopped by `RULE-GATE-01`, not misclassified here. |

This rule intentionally does not use an unselectable three-character category such as `G40` as a learner-response surrogate. That candidate was rejected in `DOMBASE-0.1` after the DIAGLIST membership check.

### `RULE-EVID-01` - COPD severity detail contradicts represented FEV1

| Field | Specification |
|---|---|
| **Kind / pattern** | Hard classification; `PAT-EVID-01` |
| **Implements** | `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-RUL-04`, `REQ-FBK-01` |
| **Inputs** | submitted code; `case.copd_base_code`; stable-phase FEV1; `RULE-MAP-01.expected_fev1_suffix` |
| **Trigger** | In an eligible inpatient COPD relation, the submitted five-character code has the same represented four-character COPD base as the case, uses a severity suffix 0-3, and that suffix differs from the suffix derived by `RULE-MAP-01`. |
| **Outcome** | `incorrect` |
| **Criterion key** | `case_evidence_conflict` |
| **Source basis** | `SRC-AT-DOC-2026`, printed p. 34 for the FEV1 intervals, plus the applicable frozen catalogue entry/DIAGLIST record for the submitted and expected codes. |
| **Precedence** | Hard stage; primary hard-error priority 3 (`EVID`). It is checked before graded specificity. |
| **Required explanation elements** | represented FEV1 value; submitted suffix/range; source-derived expected suffix; expected specific code if present in the frozen case subset. |
| **Verification obligation** | At least one mismatching band plus exact-boundary tests inherited from `RULE-MAP-01`; a matching band must not trigger. |

This rule does not classify a different COPD base or unrelated diagnosis merely because it differs from the expected response. Such relations require their own explicit evidence criterion or remain outside the deterministic response relation. This prevents a generic expected-code inequality from masquerading as a coding rule.

### `RULE-SPEC-01` - known FEV1 left unspecified in a warning-listed main diagnosis

| Field | Specification |
|---|---|
| **Kind / pattern** | Graded classification; `PAT-SPEC-01` |
| **Implements** | `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-RUL-04`, `REQ-FBK-01`, `REQ-FBK-02` |
| **Inputs** | submitted code; diagnosis role; inpatient setting; `case.copd_base_code`; stable-phase FEV1; `RULE-MAP-01.expected_specific_code` |
| **Trigger** | All hard rules are false AND `encounter_setting = inpatient` AND `diagnosis_role = main` AND submitted code is one of the active warning-listed unspecified forms `{J44.09, J44.19, J44.89, J44.99}` AND its four-character base equals the represented COPD base AND `RULE-MAP-01` derives an available suffix 0-3 and a supported more specific target code. |
| **Outcome** | `suboptimal` |
| **Criterion key** | `supported_specificity_not_used` |
| **Improvement target** | `RULE-MAP-01.expected_specific_code` |
| **Source basis** | `SRC-AT-DOC-2026`, printed p. 26 for the `Unzureichend abgeklärte Hauptdiagnose` warning and listed J44.x9 forms; printed p. 34 for the deterministic FEV1 mapping. |
| **Precedence** | Graded stage. It is evaluated only after every hard condition has cleared and before generic acceptance. |
| **Required explanation elements** | state that the response uses an unspecified FEV1 form; identify the case-provided stable-phase FEV1; identify the mapped suffix/more specific target; explain the concrete improvement. |
| **Verification obligation** | Trigger with a known FEV1 and warning-listed x9 main-diagnosis response; correct-specific control; no `suboptimal` result when the required FEV1 fact is absent; no `suboptimal` result where a hard rule is true. |

The Ministry supplies the warning and the code-selection mapping. The term `suboptimal` and the choice to use that warning-level situation as the middle educational class remain artefact-specific decisions.

### `RULE-CORRECT-01` - declared acceptable response after all applicable rules clear

| Field | Specification |
|---|---|
| **Kind** | Terminal acceptance/control rule |
| **Implements** | `REQ-RUL-02`, `REQ-RUL-03`, `REQ-FBK-01`, `REQ-VER-03` |
| **Inputs** | submitted code; versioned `case.acceptable_codes`; results of hard and graded rules |
| **Trigger** | `RULE-GATE-01` passes AND no hard classification rule matches AND `RULE-SPEC-01` does not match AND submitted code belongs to the case's predefined acceptable-code set. |
| **Outcome** | `correct` |
| **Criterion key** | `accepted_response` |
| **Source basis** | The generic acceptance mechanism is an artefact-control rule. Every concrete `case.acceptable_codes` entry must independently trace to the applicable catalogue/rule/source locator before the case is frozen. It must not be copied from implementation output. |
| **Precedence** | Terminal stage after hard and graded rules. |
| **Required explanation elements** | indicate that the response matches a declared supported/acceptable response; case-specific elaboration may identify the determining coding criterion. |
| **Verification obligation** | Exact accepted response; at least one declared acceptable-alternative response if such a case is included; prove that a code in the acceptable set cannot override a simultaneously triggered hard or `suboptimal` rule. |

An acceptable set is therefore part of the predefined case oracle, not a shortcut for making the implementation self-validating. Each accepted alternative needs its own recorded rationale.

### `RULE-PREC-01` - deterministic precedence and terminal-result policy

| Field | Specification |
|---|---|
| **Kind** | Conflict/terminal control policy |
| **Implements** | `REQ-RUL-04`, `REQ-ARC-02`, `REQ-TRC-01` |
| **Inputs** | all rule-match results for an eligible response |
| **Policy** | (1) evaluate/retain all applicable hard matches; if any exist, class = `incorrect`; (2) otherwise, if `RULE-SPEC-01` matches, class = `suboptimal`; (3) otherwise, if `RULE-CORRECT-01` matches, class = `correct`; (4) otherwise, the relation is incompletely specified and must not be silently forced into a class. |
| **Primary hard-rule priority** | `RULE-STATUS-01 > RULE-DEPTH-01 > RULE-EVID-01` for the single determining criterion, while all hard matches remain in `matched_rules`. |
| **Source / rationale** | Artefact-control decision fixed in `DOMBASE-0.1` and `REQ-RUL-04`; not an Austrian coding rule. |
| **Effect of no terminal rule** | specification/conformance failure for a relation declared evaluable; it is not a learner `incorrect` result. |
| **Verification obligation** | Rule-order independence; hard-over-graded test; graded-over-acceptance test; no-terminal specification-gap test. A multi-hard-match domain case is required only if the frozen domain subset actually permits such a combination. |

## 6. Evaluation algorithm

The following pseudocode is normative for behaviour but deliberately independent of PHP/SQL/React implementation details:

```text
evaluate(case, response, baseline):
    gate = RULE-GATE-01(case, response, baseline)
    if gate fails:
        return not_evaluated(gate.reason)

    derived = RULE-MAP-01(case) if applicable

    hard_matches = []
    if RULE-STATUS-01(case, response): hard_matches += STATUS
    if RULE-DEPTH-01(case, response):  hard_matches += DEPTH
    if RULE-EVID-01(case, response, derived): hard_matches += EVID

    if hard_matches is not empty:
        primary = first match by STATUS > DEPTH > EVID
        return incorrect(primary, hard_matches)

    if RULE-SPEC-01(case, response, derived):
        return suboptimal(SPEC, improvement_code = derived.expected_specific_code)

    if RULE-CORRECT-01(case, response):
        return correct(CORRECT)

    return specification_gap
```

The final line is intentionally not `return incorrect`. A declared evaluable relation reaching that branch indicates an incomplete rule/case specification and should fail the relevant development or verification check.

## 7. Required explanation payload

Feedback text may be phrased for readability, but the underlying payload must be structured enough for deterministic tests. At minimum:

| Determining rule | Required payload elements |
|---|---|
| `RULE-STATUS-01` | class; criterion key; code/marker; role; relevant setting; restriction explanation |
| `RULE-DEPTH-01` | class; criterion key; submitted code; required coding level; optional mapped target if independently available |
| `RULE-EVID-01` | class; criterion key; submitted code; represented FEV1; submitted suffix meaning; expected suffix/target |
| `RULE-SPEC-01` | class; criterion key; submitted unspecified code; represented FEV1; more specific target; explicit improvement direction |
| `RULE-CORRECT-01` | class; criterion key; accepted code; optional case-specific rationale |

The technical trace additionally retains `RULE-*`, `PAT-*`, `REQ-*`, source locator(s), and baseline versions. These identifiers need not all be exposed in the learner UI.

## 8. Worked rule traces

### 8.1 COPD case with FEV1 = 55%

For the design-proof case already established in `DOMBASE-0.1` (inpatient, main diagnosis, COPD with acute lower-respiratory infection, stable-phase FEV1 55%):

1. `RULE-MAP-01` derives suffix `2` and target `J44.02`.
2. The response variants then resolve as follows.

| Response | Hard match | Graded match | Acceptance | Terminal result |
|---|---|---|---|---|
| `J44.02` | none | none | accepted | `correct` via `RULE-CORRECT-01` |
| `J44.09` | none | `RULE-SPEC-01` | irrelevant after graded match | `suboptimal` |
| `J44.0` | `RULE-DEPTH-01` | not evaluated for terminal result | irrelevant | `incorrect` |
| `J44.01` | `RULE-EVID-01` | none | irrelevant | `incorrect` |

The 50% and 70% exact boundaries must later appear in unit/reference coverage because the source-defined inequalities change at those values; 35% is likewise a required mapping boundary.

### 8.2 `!` context boundary

For an otherwise source-supported `!` response used as a main diagnosis:

| Represented context | `RULE-STATUS-01` |
|---|---|
| inpatient stay | triggers -> `incorrect` |
| hospital outpatient, inpatient-LKF scoring = `true` | triggers -> `incorrect` |
| hospital outpatient, inpatient-LKF scoring = `false` | does not trigger; response may be `correct` only if the case accepted set independently supports it |

This last row is a control, not an automatic correctness rule. Printed p. 22 supplies `Z01.6!`/`Z01.8!` examples from which a source-supported outpatient control case can later be constructed.

## 9. Rule-to-pattern-to-requirement traceability

| Rule | Pattern / role | Principal requirements | External source basis |
|---|---|---|---|
| `RULE-GATE-01` | scope/control | `REQ-SCP-01`, `REQ-DAT-03`, `REQ-MOD-01`, `REQ-RUL-05` | edition identity: `SRC-AT-ICD-SYS-2026`, p. 14; DIAGLIST membership where checked |
| `RULE-MAP-01` | derivation for `PAT-EVID-01`, `PAT-SPEC-01` | `REQ-MOD-01`, `REQ-RUL-01`, `REQ-ARC-02` | `SRC-AT-DOC-2026`, p. 34 |
| `RULE-STATUS-01` | `PAT-STATUS-01` | `REQ-DAT-05`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-FBK-01` | `SRC-AT-DOC-2026`, pp. 10-11, 18; p. 22 control example |
| `RULE-DEPTH-01` | `PAT-DEPTH-01` | `REQ-RUL-01`, `REQ-RUL-02`, `REQ-FBK-01` | `SRC-AT-DOC-2026`, pp. 12, 26 |
| `RULE-EVID-01` | `PAT-EVID-01` | `REQ-MOD-01`, `REQ-RUL-01`, `REQ-RUL-02`, `REQ-FBK-01` | `SRC-AT-DOC-2026`, p. 34 + catalogue entry |
| `RULE-SPEC-01` | `PAT-SPEC-01` | `REQ-MOD-01`, `REQ-RUL-02`, `REQ-FBK-01`, `REQ-FBK-02` | `SRC-AT-DOC-2026`, pp. 26, 34 |
| `RULE-CORRECT-01` | accepted-response control | `REQ-RUL-02`, `REQ-RUL-03`, `REQ-VER-03` | case-specific source/oracle trace |
| `RULE-PREC-01` | precedence/control | `REQ-RUL-04`, `REQ-ARC-02`, `REQ-TRC-01` | internal methodology; no Austrian coding claim |

Every one of the four `PAT-*` elements has exactly one terminal classification rule. `Correct` has an explicit terminal rule, and out-of-scope input has an explicit non-classification guard. No terminal behaviour is therefore supplied by accidental evaluation order or by a default `else = incorrect` branch.

## 10. Coverage obligations and downstream planning

The rule catalogue determines the coverage cells from which case count must be derived. `CASEPLAN-0.2` (which superseded the first estimate, `CASEPLAN-0.1`, via the pre-freeze coverage review) now maps these obligations to the `CASE-*`/`RC-*` suite and to separate targeted-test obligations:

| Rule / boundary | Required evidence | Working test destination |
|---|---|---|
| `RULE-GATE-01` | eligible control; outside-subset/undefined-relation negative handling | `TEST-GATE-01`, `TEST-API-01` |
| `RULE-MAP-01` | values inside all four FEV1 bands plus exact 35%, 50%, 70% boundaries | `TEST-MAP-01` |
| `RULE-STATUS-01` | inpatient trigger; hospital-outpatient stationary-LKF trigger; ordinary hospital-outpatient non-trigger | `TEST-STATUS-01`, `TEST-RC-01` |
| `RULE-DEPTH-01` | four-character trigger; five-character non-trigger | `TEST-DEPTH-01`, `TEST-RC-01` |
| `RULE-EVID-01` | mismatching severity suffix; matching non-trigger | `TEST-EVID-01`, `TEST-RC-01` |
| `RULE-SPEC-01` | known-FEV1 x9 trigger; specific-code control; missing-information case must not be forced into `suboptimal` | `TEST-SPEC-01`, `TEST-RC-01` |
| `RULE-CORRECT-01` | accepted exact response; accepted alternative if such a case is deliberately selected | `TEST-CORRECT-01`, `TEST-RC-01` |
| `RULE-PREC-01` | hard-over-graded; graded-over-acceptance; order-independence; specification-gap handling | `TEST-PREC-01`, `TEST-DET-01` |

These entries are coverage requirements, not eight mandatory base cases. The COPD case can generate several independent `RC-*` variants, and pure derivation/boundary checks may be better represented as `TEST-*` rather than additional learner vignettes.

`CASEPLAN-0.2` operationalises this distinction with a 13-record `SUBSET-0.1`, eight base cases and eighteen atomic `RC-*` variants (the original `CASEPLAN-0.1` plan had four base cases and fourteen variants, before the pre-freeze coverage review added the remaining FEV1-band and inpatient-status coverage). Pure mapping, gate, additional status-branch and terminal-policy checks remain targeted `TEST-*` obligations rather than being converted into redundant clinical vignettes.

## 11. Explicit non-rules and exclusions

The following must **not** be introduced into `RULEBASE-0.1` without a versioned change to the upstream domain baseline:

- generic clinical-diagnosis inference;
- a default `submitted_code != expected_code -> incorrect` rule;
- a generic three-character hierarchy error based on non-selectable category headings such as `G40`;
- historical inactive-code logic without appropriate version metadata;
- extramural-specific executable coding rules;
- LKF reimbursement or ICD-to-price logic;
- learner intent, fraud/upcoding inference, usability or learning-effect judgements.

An acceptable alternative is not an error rule. It is represented in the predefined accepted set and reaches `correct` through `RULE-CORRECT-01` only after the higher-priority rules clear.

## 12. Freeze conditions and downstream step

`RULEBASE-0.1` can be promoted to the evaluation-frozen `RULEBASE-1.0` only when:

- every executable branch in the implementation maps to one of the rule records above or to a documented later baseline revision;
- every rule input maps to a concrete case/catalogue/context field;
- the active DIAGLIST subset contains every record required by the selected cases, rule comparisons and improvement targets;
- every source-derived predicate retains the exact internal source locator shown here;
- reference expectations have been defined independently of implementation output;
- the coverage matrix has no unexplained gap for the implemented rule branches and boundaries;
- rule/unit/reference tests demonstrate the fixed precedence and deterministic behaviour; and
- any later rule change increments the rule baseline and triggers impact analysis over linked `RC-*`, `TEST-*` and results.

The coverage-driven planning step is represented by `CASEPLAN-0.2` (supersedes `CASEPLAN-0.1`), and its subset/case records have been materialised, adopted, and executed as `MODELBASE-0.1` files for working `SUBSET-0.1`, `CASEBASE-0.2`, and `RCBASE-0.2` artefacts. `TESTBASE-0.1` binds every rule/control obligation above to a targeted test specification, now bound to the actual PHP implementation (`app/src/Rules/*.php`, `app/tests/`) rather than remaining a planned mapping. The independent `RCBASE-*` oracle remains outside the runtime classification-data path (verified by `TEST-ARC-01`).
