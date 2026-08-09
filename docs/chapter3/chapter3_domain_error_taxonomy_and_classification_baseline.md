# Chapter 3 Domain Error Taxonomy and Classification Baseline

**Document status:** Working design-control baseline  
**Baseline ID:** `DOMBASE-0.1`  
**Date:** 6 August 2026  
**Applies to:** `REQ-RUL-*`, `REQ-FBK-*`, prototype subset selection, `CASE-*`/`RC-*` construction, rule implementation, and technical verification  
**Primary upstream controls:** `chapter3_input_source_baseline_register.md`, `chapter3_requirements_catalogue.md`

## 1. Purpose and decision

This document resolves `OPEN-DOM-01` by fixing a deliberately small set of coding-response patterns that can be decided from represented case facts and retained Austrian source evidence. It is an internal methodological control artefact, not thesis prose and not a claim that the three feedback labels are official Austrian coding categories.

The prototype will implement four principal response patterns:

1. `PAT-DEPTH-01` - required coding depth not met;
2. `PAT-SPEC-01` - source-backed insufficient specificity despite sufficient represented information;
3. `PAT-EVID-01` - submitted code encodes detail that conflicts with an explicit case fact;
4. `PAT-STATUS-01` - code status/use is incompatible with the represented hospital context.

Only `PAT-SPEC-01` produces the artefact-specific feedback class `suboptimal`. The other three patterns are hard invalidating conditions and therefore produce `incorrect` when triggered. `Correct` is reserved for a declared acceptable response that passes every applicable hard and graded condition.

The design therefore does **not** define `suboptimal` as whatever lies between correct and incorrect. It has a positive trigger of its own.

## 2. Authority and interpretation rule

The Austrian sources establish catalogue meaning, mandatory coding depth, warning conditions, status restrictions, and the data needed for selected deterministic distinctions. The mapping of those externally established distinctions to the educational labels `correct`, `suboptimal`, and `incorrect` is a project design decision.

In particular, the Ministry does not label a response `suboptimal`. For this prototype:

- an explicit mandatory or prohibited condition, or a contradiction between the selected code and a represented case fact, is mapped to `incorrect`;
- a response that remains compatible with the documented condition but satisfies an explicit quality-improvement/warning condition, while the case already supplies the information needed for a better supported choice, is mapped to `suboptimal`;
- a response that is declared acceptable and triggers neither condition is mapped to `correct`.

This distinction preserves the authority boundary required by the source-baseline register. The source supplies the coding criterion. The artefact supplies the pedagogical severity label.

## 3. Verified source basis

All PDF locators in this table are the page numbers printed on the source pages.

| Source / locator | Verified proposition used by the model |
|---|---|
| `SRC-AT-DOC-2026`, pp. 11-13 | Austrian coding normally requires four-character keys where a three-character category is subdivided; selected diagnoses require five characters; three-character coding generally loses clinical information; the specific four-character code should be used when available. |
| `SRC-AT-DOC-2026`, p. 12 | D68.2, I50.0, I50.1, J44.0-J44.9 and K91.8 are listed as diagnoses that must be recorded with five characters in Austrian hospitals. Other WHO fifth-character subdivisions can be optional. |
| `SRC-AT-DOC-2026`, p. 26 | Discharge diagnoses are to be coded as exactly as possible. Five-character coding is mandatory for the stated heart-failure/COPD families. The plausibility check emits the warning `Unzureichend abgeklärte Hauptdiagnose` for specified `nicht näher bezeichnet` main-diagnosis codes, including J44.09, J44.19, J44.89 and J44.99. |
| `SRC-AT-DOC-2026`, p. 34 | For J44.0-J44.9, the fifth character represents FEV1: `0` for <35%, `1` for >=35% and <50%, `2` for >=50% and <70%, `3` for >=70%, and `9` for FEV1 not further specified. The measurement is to reflect a stable phase during the inpatient stay. |
| `SRC-AT-DOC-2026`, pp. 10-11, 18 | `!` codes may not be used as a main diagnosis for inpatient stays or ambulatory visits scored according to the inpatient LKF model. In hospital outpatient visits without such scoring, `!` codes may be used as the main diagnosis. |
| `SRC-AT-DOC-2026`, p. 22 | The hospital-outpatient examples explicitly use `Z01.6!` for radiography/CT and `Z01.8!` for MRI in the described circumstances, confirming that `!` is context-dependent rather than an intrinsically invalid-code marker. |
| `SRC-AT-ICD-SYS-2026`, p. 14 | ICD-10 BMASGPK 2026 is the current Austrian adaptation for the represented reporting period and contains the Austrian `!` and `#` status concepts. |
| `SRC-AT-ICD-SYS-2026`, pp. 255-256 | `G40` is a three-character category with four-character subcategories including `G40.3` and `G40.9`; this is used only to check the hierarchy example, not as a learner-selectable DIAGLIST response. |
| `SRC-AT-DIAGLIST-2026`, worksheet `DIAGLIST2026`, rows 3884-3889, fields `Diagnose`, `Kennzeichen`, `Bezeichnung` | The machine-readable source contains `J44.0` and the five-character records `J44.00`, `J44.01`, `J44.02`, `J44.03`, and `J44.09`, so the COPD depth/specificity distinctions can be represented by selectable records. |
| `SRC-AT-DIAGLIST-2026`, worksheet `DIAGLIST2026`, rows 12669, 12683, 12685 | `Z00.0`, `Z01.6`, and `Z01.8` carry `Kennzeichen = !`, making the represented status rule machine-readable. |
| `SRC-AT-DIAGLIST-2026`, worksheet `DIAGLIST2026`, rows 2746 and 2752 plus complete `Diagnose` membership check | `G40.3` and `G40.9` occur as records, while `G40` itself does not. A generic three-character `G40` response is therefore not used as a learner-facing hierarchy-error example when the UI is based on DIAGLIST records. |

The DIAGLIST row numbers above are native worksheet locators, not page citations.

## 4. Frozen response-pattern taxonomy

| Pattern ID | Operational trigger | Feedback class | Required observables | Source basis | Scope note |
|---|---|---|---|---|---|
| **`PAT-DEPTH-01` Required coding depth** | The submitted record is a broader form for which the applicable Austrian hospital rule expressly requires a deeper represented code, and the required child form is within the modelled family. | `incorrect` | submitted code; represented setting; applicable code family/depth rule | `SRC-AT-DOC-2026`, pp. 12, 26 | Initial concrete family: J44.0-J44.9, for which five-character coding is mandatory. This replaces the weaker idea of letting an unselectable three-character heading stand in for a learner response. |
| **`PAT-SPEC-01` Supported specificity not used** | All hard conditions pass, the response uses a source-identified unspecified/warning form, the case explicitly supplies the missing differentiating fact, and an applicable official rule maps that fact to a more specific supported code. | `suboptimal` | submitted code; diagnosis role; explicit differentiating case fact; source-defined mapping; improvement target | `SRC-AT-DOC-2026`, pp. 26, 34 | Initial concrete family: COPD `J44.x9` as main diagnosis when a stable-phase FEV1 value in the case deterministically selects suffix 0-3. The source establishes the warning/mapping; `suboptimal` is the artefact's educational label. |
| **`PAT-EVID-01` Unsupported or contradictory detail** | The selected code expresses a source-defined value/attribute that conflicts with an explicit represented case fact. | `incorrect` | submitted code; explicit case fact; source-defined code-to-attribute mapping | `SRC-AT-DOC-2026`, p. 34; applicable catalogue entry | Initial concrete family: a COPD fifth-character FEV1 band that does not contain the represented stable-phase FEV1. This does not infer unobserved clinical truth. |
| **`PAT-STATUS-01` Context/status incompatibility** | A `!` code is submitted as the main diagnosis in a represented context in which the Austrian rule prohibits that use. | `incorrect` | `Kennzeichen`; diagnosis role; encounter setting; whether the ambulatory visit is scored by the inpatient LKF model where applicable | `SRC-AT-DOC-2026`, pp. 10-11, 18 | Hospital-sector rule only. A `!` code is not intrinsically incorrect; ordinary hospital-outpatient use can be permitted. Extramural-specific rules are not activated. |

### 4.1 Why the four patterns are sufficient for the prototype claim

The selection is purposive rather than epidemiological. It demonstrates four materially different decision mechanisms:

- a mandatory structural/coding-depth constraint;
- a graded quality condition that produces the required middle class;
- a direct comparison between documented evidence and a code's encoded attribute;
- a context-dependent status restriction.

Together they cover all three feedback classes and multiple error mechanisms without requiring the prototype to diagnose a patient, reconstruct missing documentation, or claim comprehensive Austrian coding-rule coverage. Additional patterns are admitted only if they add a distinct, source-backed decision mechanism needed for coverage.

## 5. Exact operationalisation of `suboptimal`

Within the frozen model, `suboptimal` is necessary and sufficient only when **all** of the following hold:

1. **In-scope response:** the submitted code exists in the frozen Austrian 2026 source and is an active response supported by the prototype subset/case model.
2. **Mandatory coding depth satisfied:** no `PAT-DEPTH-01` condition is true.
3. **Status/context satisfied:** no `PAT-STATUS-01` condition is true.
4. **Case compatibility satisfied:** the selected code does not contradict an explicit represented fact, so `PAT-EVID-01` is false.
5. **Source-backed improvement condition true:** `PAT-SPEC-01` identifies a specific warning/insufficient-specificity condition from the active Austrian source.
6. **Improvement is decidable from the case:** the case already contains the differentiating information and the official mapping identifies the corresponding more specific response without an additional clinical inference.

For the first implementation family, this can be written compactly as:

```text
suboptimal(response, case) :=
    response_is_supported
AND mandatory_depth_ok
AND status_context_ok
AND evidence_compatible
AND response_is_COPD_unspecified_warning_form
AND case_has_stable_phase_FEV1
AND FEV1_maps_to_a_more_specific_supported_suffix_0_to_3
```

This definition deliberately rejects three tempting but invalid shortcuts:

- an arbitrary related code is not `suboptimal` merely because it is in the same ICD family;
- a code that violates a mandatory depth or status rule is `incorrect`, not `suboptimal`;
- if the case does not supply the missing differentiating information, the learner is not penalised for failing to invent it. The response must instead be handled as an accepted/bounded alternative where justified or the case must be excluded from that `suboptimal` rule.

## 6. Three-class decision and precedence model

### 6.1 Pre-classification gate

The three feedback labels apply only to evaluated, in-scope responses. The following are input/scope conditions rather than coding-quality classes:

- malformed input;
- a code that is absent from the frozen Austrian version;
- a valid Austrian code that lies outside the declared prototype subset and has no defined case-response relation.

Such input must be prevented by the UI or returned as a distinct validation/out-of-scope result. It must not be silently labelled `incorrect`, because absence from a deliberately bounded subset is not evidence of wrong Austrian coding.

### 6.2 Classification precedence

For an in-scope response, the decision model is:

| Stage | Decision | Result |
|---:|---|---|
| 1 | Any applicable hard invalidating pattern (`PAT-STATUS-01`, `PAT-DEPTH-01`, `PAT-EVID-01`) is true | `incorrect` |
| 2 | No hard invalidating pattern is true and `PAT-SPEC-01` is true | `suboptimal` |
| 3 | No prior condition is true and the response belongs to the declared acceptable response set | `correct` |
| 4 | No defined rule/accepted-set relation can decide the response | not eligible for the frozen deterministic suite until specified or excluded |

All matching rule reasons should be retained in the technical trace. If the UI needs one primary determining criterion when several hard rules match, use the stable priority `STATUS > DEPTH > EVID`; secondary matches remain available for debugging and verification. This ordering changes only the reported determining criterion, not the `incorrect` class.

## 7. Worked proof of operability: COPD response family

This is a design proof, not yet a frozen `CASE-*`/`RC-*` set.

Assume a synthetic inpatient discharge case explicitly states:

- COPD with acute infection of the lower respiratory tract;
- main-diagnosis response is requested;
- FEV1 measured in a stable phase is **55% of predicted**.

The official rule on printed p. 34 maps 55% to fifth-character suffix `2` (>=50% and <70%). The DIAGLIST contains all response records below.

| Submitted response | Expected model outcome | Determining reason |
|---|---|---|
| `J44.02` | `correct` | Five-character form is present and suffix `2` agrees with the represented 55% FEV1 value. |
| `J44.09` | `suboptimal` | The code remains the unspecified FEV1 form, is listed among the p. 26 warning codes for a main diagnosis, and the case already supplies the value needed to select `J44.02`. |
| `J44.0` | `incorrect` | The Austrian hospital rule makes five-character coding mandatory for J44.0-J44.9. |
| `J44.01` | `incorrect` | Suffix `1` represents >=35% and <50%, which conflicts with the explicit 55% case fact. |

This single base case can later produce multiple `RC-*` response variants without duplicating the vignette. It demonstrates why the case and response variant must remain distinct entities.

A useful boundary companion case is FEV1 = **50%**, because the published intervals place the boundary in suffix `2`. That supplies a non-subjective harder test without creating clinical ambiguity.

## 8. Context/status proof and setting decision

`PAT-STATUS-01` activates a narrowly defined **hospital-sector** context rule. The required case model must be able to distinguish at least:

- inpatient stay;
- hospital-outpatient visit;
- main versus additional diagnosis where a rule depends on that role;
- for hospital-outpatient cases that exercise the status boundary, whether inpatient-LKF scoring applies.

The official p. 18 rule is sufficient if this scoring attribute is supplied as an explicit synthetic case fact. The prototype need not independently derive the scoring status of every hospital service.

`Z01.6!` and `Z01.8!` on printed p. 22 provide concrete evidence that a `!` code can be appropriate in an ordinary hospital-outpatient context. The same marker is prohibited as main diagnosis in the contexts stated on pp. 10-11 and 18. This makes status a context-dependent rule rather than a permanent error property of the code.

This decision resolves `OPEN-SET-01` as follows:

- hospital-setting-specific status behaviour is **in scope**;
- `SRC-AT-DOC-2026` is the controlling source for the selected status rule;
- no extramural-specific executable coding rule is included in `DOMBASE-0.1`;
- the extramural workbook/handbook remain source and scope context unless a later, explicitly justified baseline revision adds an extramural behaviour.

## 9. Candidate situations deliberately not implemented as error patterns

| Candidate | Treatment in `DOMBASE-0.1` | Reason |
|---|---|---|
| Generic three-character hierarchy selection such as `G40` | Not a learner-facing pattern | `G40` is a category heading in the systematic directory but is not a DIAGLIST diagnosis record. A DIAGLIST-driven selector cannot honestly present it as an ordinary selectable response. Required-depth behaviour is instead exercised with a real selectable four-to-five-character family such as J44.0. |
| Version-invalid/inactive historical code | Input/version validation, not a teaching pattern | The frozen 2026 baseline establishes current membership but does not by itself supply complete historical activation/deactivation semantics. A code outside the prototype subset must also not be confused with a code absent from the Austrian edition. |
| Acceptable alternative | Control case, normally `correct` if explicitly declared | Disagreement is not itself evidence of error. The accepted set must be closed before verification. |
| Unbounded ambiguity | Excluded from deterministic reference classification | If the source/case information cannot decide the outcome without hidden expert judgement, assigning one of three labels would manufacture certainty. |
| Deliberate upcoding/fraud | Out of scope | Intent cannot be inferred from a code response and is outside the educational artefact's claim. |
| Diagnostic error / true disease inference | Out of scope | The prototype evaluates represented coding responses, not clinical truth. |

## 10. Downstream requirements and data implications

The taxonomy has immediate consequences for the next design artefacts.

### 10.1 Required rule catalogue fields

Each executable `RULE-*` derived from a pattern must record at least:

```text
RULE-ID
pattern: PAT-*
implements: REQ-ID(s)
inputs / condition
outcome class
primary criterion
explanation payload
source basis with internal locator
precedence / conflict relation
verified_by: RC-* / TEST-*
```

### 10.2 Minimum case-model facts implied by this baseline

The final schema must be able to represent, where applicable:

- case ID/version and Austrian source baseline version;
- documented diagnosis/concept facts needed for the selected code family;
- diagnosis role when a rule distinguishes main/additional diagnosis;
- hospital encounter setting;
- inpatient-LKF-scoring flag for the selected hospital-outpatient boundary cases;
- explicit quantitative or categorical differentiators used by rules, initially stable-phase FEV1 for the COPD family;
- accepted code(s) and rule-linked response variants.

### 10.3 Minimum DIAGLIST implications

The downstream `CASEPLAN-0.1` resolved `OPEN-DAT-01` for the working prototype on 6 August 2026 (unchanged by `CASEPLAN-0.2`'s later pre-freeze case/RC expansion, which added no new field). Four DIAGLIST source fields are retained:

- `Diagnose` for the code identifier;
- `Kennzeichen` for `PAT-STATUS-01`;
- `Bezeichnung` as the authoritative full label for inspectable meaning/feedback; and
- `Kurzbezeichnung` as the concise search/display label.

Additional hierarchy/navigation fields should be added only if a later implementation requirement genuinely needs them, with the whitelist change versioned explicitly. The semantic rules on pp. 26 and 34 remain external rule metadata; they must not be inferred from the absence or presence of a DIAGLIST field.

## 11. Coverage obligations created by this baseline

The reference-suite size still follows coverage rather than a quota. At minimum, later `RC-*` construction must provide:

- a trigger and a passing control for each implemented `PAT-*`;
- at least one `suboptimal` response satisfying every condition in Section 5;
- correct, suboptimal, and incorrect outcomes overall;
- at least one source-defined boundary case, such as FEV1 exactly 50%;
- a permitted versus prohibited context control for `PAT-STATUS-01` if the same status family is used to demonstrate context sensitivity;
- a multi-match/precedence test if any frozen response can trigger more than one hard condition;
- explicit acceptable alternatives wherever a selected case genuinely permits them, or documented exclusion where the ambiguity cannot be bounded.

These are coverage cells, not a prescribed number of base cases. One `CASE-*` may satisfy several cells through multiple independently specified `RC-*` variants.

## 12. Requirements trace

| Baseline decision | Principal requirement links |
|---|---|
| Four-pattern taxonomy | `REQ-RUL-01`, `REQ-RUL-02`, `REQ-VER-02` |
| Positive `suboptimal` predicate | `REQ-RUL-02`, `REQ-FBK-02`, `REQ-MOD-01` |
| Hard-before-graded precedence | `REQ-RUL-04`, `REQ-ARC-02` |
| Explicit acceptable alternatives / ambiguity exclusion | `REQ-RUL-03`, `REQ-VER-03` |
| Hospital status rule activation | `REQ-DAT-05`, `REQ-MOD-01`, `REQ-RUL-01` |
| Bounded-subset input gate | `REQ-SCP-01`, `REQ-DAT-03`, `REQ-RUL-02`, `REQ-FBK-01` |
| COPD proof family and source-defined boundaries | `REQ-VER-01`, `REQ-VER-02`, `REQ-VER-03` |

## 13. Downstream status and remaining decisions after `DOMBASE-0.1`

`OPEN-DOM-01` and `OPEN-SET-01` are resolved by this baseline. Its four patterns have been operationalised in `chapter3_rule_catalogue.md` as `RULEBASE-0.1`; `chapter3_reference_case_coverage_plan.md` supplies working `SUBSET-0.1` and the coverage-derived case estimate, resolving `OPEN-DAT-01`. `chapter3_data_model_and_interaction_baseline.md` now instantiates those records and resolves `OPEN-INT-01` as one submitted ICD code per case-defined coding target and evaluation request. The following remain open where relevant:

1. `OPEN-EVAL-01` - whether the eventual technical verification requires independent external domain-expert evaluation;
2. final research-question wording and result-placement decisions.

The next immediate work is therefore to bind targeted `TEST-*` specifications to the concrete model/API responsibilities without changing the domain taxonomy or importing the independent reference-response oracle into runtime classification data.
