# Chapter 3 Reference-Case Coverage and Prototype-Subset Plan

**Document status:** Working case-selection and verification-planning artefact  
**Planning baseline:** `CASEPLAN-0.1`  
**Proposed catalogue subset:** `SUBSET-0.1`  
**Date:** 6 August 2026  
**Upstream source register:** `chapter3_input_source_baseline_register.md`, register version 0.4  
**Upstream domain baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`  
**Upstream requirements:** `chapter3_requirements_catalogue.md`, catalogue version 0.5  
**Upstream rule baseline:** `chapter3_rule_catalogue.md`, `RULEBASE-0.1`
**Downstream data/interaction model:** `chapter3_data_model_and_interaction_baseline.md`, `MODELBASE-0.1` / `CASEBASE-0.1` / `RCBASE-0.1`
**Downstream technical test baseline:** `chapter3_test_catalogue.md`, `TESTBASE-0.1`

## 1. Purpose and status

This document derives the first prototype subset and reference-case estimate from the coverage obligations already fixed by `DOMBASE-0.1`, the `REQ-*` catalogue and `RULEBASE-0.1`. The numbers are therefore outputs of the required behaviour, not target quotas. The plan is deliberately narrower than a representative sample of Austrian ICD-10 and must not be described as epidemiologically, clinically or catalogue-wide representative.

`CASEPLAN-0.1` is a planning baseline, not the final verification oracle. The case facts, response relations and expected outcomes below may be frozen as `CASEBASE-1.0` only after their source links, physical schema and implementation-independent expectations have been checked. Final expected results must be fixed before the final verification run and must not be copied from prototype output.

The atomic evaluation unit remains one submitted code against one case context. Downstream `MODELBASE-0.1` now fixes this as the initial interaction contract: one submitted code per case-defined coding target and evaluation request. A later multi-code capability would require a separate aggregation model and baseline revision rather than changing the meaning of these atomic `RC-*` expectations.

## 2. Selection logic

The initial plan must jointly satisfy the following constraints:

1. exercise `correct`, `suboptimal` and `incorrect`;
2. provide a trigger and a passing control for each of the four frozen `PAT-*` patterns;
3. include a source-defined quantitative boundary rather than only easy middle-of-band values;
4. demonstrate repeated rule application without broadening into comprehensive catalogue coverage;
5. include the selected hospital `!` context boundary without activating an extramural coding rule;
6. keep every learner/reference judgement derivable from represented facts and retained source evidence; and
7. move purely technical input, precedence and boundary checks to `TEST-*` where an additional clinical vignette would add no evidential value.

This produces two COPD base cases, a permitted hospital-outpatient status control and a paired prohibited-status fixture. Two COPD bases are preferable to one because they demonstrate that the fifth-character logic is not tied only to `J44.0`, while two complete six-record families remain small enough to enumerate exhaustively.

## 3. Proposed machine-readable subset: `SUBSET-0.1`

### 3.1 Frozen input identity

| Property | Working value |
|---|---|
| Source ID | `SRC-AT-DIAGLIST-2026` |
| File | `DIAGLIST2026.xlsx` |
| Worksheet | `DIAGLIST2026` |
| Austrian edition | ICD-10 BMASGPK 2026 |
| Source records | 13,298 unique `Diagnose` identifiers |
| SHA-256 | `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` |
| Working subset ID | `SUBSET-0.1` |
| Active subset size | 13 records |

### 3.2 Inclusion rule

`SUBSET-0.1` contains:

- the complete selectable `J44.0` family represented by `J44.0`, `J44.00`, `J44.01`, `J44.02`, `J44.03` and `J44.09`;
- the complete selectable `J44.1` family represented by `J44.1`, `J44.10`, `J44.11`, `J44.12`, `J44.13` and `J44.19`; and
- `Z01.6!` as the concrete hospital `!` status example.

The two COPD families provide complete parent/specific/unspecified response domains for the two planned COPD cases. `J44.8` and `J44.9` have the same source-defined fifth-character mechanism and therefore add no new rule behaviour needed by the initial coverage gate. They remain outside this working subset. `Z01.8!` is likewise not activated because it duplicates the selected status mechanism; it is retained as a useful known-valid 2026 outside-subset control for `RULE-GATE-01`.

### 3.3 Active records

| DIAGLIST row | Code | Marker | Short label / role in subset |
|---:|---|---|---|
| 3884 | `J44.0` |  | COPD with acute lower-respiratory infection; four-character parent |
| 3885 | `J44.00` |  | same base, FEV1 <35% |
| 3886 | `J44.01` |  | same base, FEV1 >=35% and <50% |
| 3887 | `J44.02` |  | same base, FEV1 >=50% and <70% |
| 3888 | `J44.03` |  | same base, FEV1 >=70% |
| 3889 | `J44.09` |  | same base, FEV1 not further specified |
| 3890 | `J44.1` |  | COPD with acute exacerbation; four-character parent |
| 3891 | `J44.10` |  | same base, FEV1 <35% |
| 3892 | `J44.11` |  | same base, FEV1 >=35% and <50% |
| 3893 | `J44.12` |  | same base, FEV1 >=50% and <70% |
| 3894 | `J44.13` |  | same base, FEV1 >=70% |
| 3895 | `J44.19` |  | same base, FEV1 not further specified |
| 12683 | `Z01.6` | `!` | radiography, not elsewhere classified; status/context fixture |

The row numbers are worksheet locators, not publication page numbers.

### 3.4 DIAGLIST field whitelist

The working import whitelist is deliberately limited to four source fields:

| Source field | Retained purpose |
|---|---|
| `Diagnose` | canonical code identifier and subset selection |
| `Kennzeichen` | machine-readable status marker required by `RULE-STATUS-01` |
| `Bezeichnung` | authoritative full catalogue label for inspectability |
| `Kurzbezeichnung` | concise search/display label |

Edition, source ID, file checksum, worksheet and subset-baseline identity are dataset-level metadata rather than duplicated DIAGLIST row fields. Other workbook columns are not imported into the initial application dataset because no frozen rule or required UI behaviour depends on them. If a later implementation requirement genuinely needs an additional field, the whitelist and subset baseline must be versioned rather than expanded silently.

This resolves the substance of `OPEN-DAT-01` for the working prototype. It does not imply that excluded DIAGLIST columns are unimportant generally; only that they are not required by this bounded artefact.

## 4. Base-case plan

Each `CASE-*` contains documented facts rather than symptoms from which the prototype must infer a diagnosis. The task is to code an already represented diagnosis/context. `CASE-004` is deliberately marked verification-only because its purpose is to prove a formal status prohibition; no replacement main diagnosis is invented merely to give that fixture a positive learner answer.

| Case ID | Represented facts and context | Case-specific response domain | Declared acceptable set | Principal purpose | Source basis |
|---|---|---|---|---|---|
| **`CASE-001`** | Inpatient; main diagnosis; documented COPD with acute lower-respiratory infection; stable-phase FEV1 = 55% predicted; `copd_base_code = J44.0` | the six active `J44.0` family records | `{J44.02}` | straightforward three-class proof; depth, evidence and specificity patterns | `SRC-AT-DOC-2026`, printed pp. 12, 26, 34; DIAGLIST rows 3884-3889 |
| **`CASE-002`** | Inpatient; main diagnosis; documented COPD with acute exacerbation; stable-phase FEV1 = exactly 50% predicted; `copd_base_code = J44.1` | the six active `J44.1` family records | `{J44.12}` | source-defined threshold case and repeat application on a second COPD base | `SRC-AT-DOC-2026`, printed pp. 12, 26, 34; DIAGLIST rows 3890-3895 |
| **`CASE-003`** | Hospital outpatient; main diagnosis; ordinary/non-inpatient-LKF-scored radiography/CT situation corresponding to the official `Z01.6!` example; `inpatient_lkf_scored = false` | `{Z01.6}` | `{Z01.6}` | permitted `!` control and context sensitivity | `SRC-AT-DOC-2026`, printed pp. 18, 22; DIAGLIST row 12683 |
| **`CASE-004`** | Hospital outpatient; main diagnosis; paired formal status fixture with `inpatient_lkf_scored = true`; otherwise supported `Z01.6!` candidate | `{Z01.6}` | none asserted; verification-only case | prohibited `!` trigger without inventing a substitute diagnosis | `SRC-AT-DOC-2026`, printed pp. 10-11, 18; DIAGLIST row 12683 |

`CASE-003` and `CASE-004` establish a permitted/prohibited context pair for the same marked code. Non-triggering `RULE-STATUS-01` in `CASE-003` is not by itself evidence of correctness; its accepted set is separately based on the official hospital-outpatient example on printed p. 22. Conversely, `CASE-004` makes no claim about which different diagnosis ought to replace `Z01.6!` as main diagnosis.

## 5. Reference-response matrix

The two COPD case response domains are enumerated exhaustively. This gives a predefined expectation for every candidate shown for those cases rather than selecting only convenient responses after observing implementation behaviour.

| RC ID | Case | Submitted code | Expected class | Determining rule | Pattern / criterion | Improvement target | Internal source locator |
|---|---|---|---|---|---|---|---|
| `RC-001-01` | `CASE-001` | `J44.0` | `incorrect` | `RULE-DEPTH-01` | `PAT-DEPTH-01` / `mandatory_coding_depth_not_met` | five-character level; mapped target may show `J44.02` | `SRC-AT-DOC-2026:pp.12,26` |
| `RC-001-02` | `CASE-001` | `J44.00` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict` | `J44.02` | `SRC-AT-DOC-2026:p.34` |
| `RC-001-03` | `CASE-001` | `J44.01` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict` | `J44.02` | `SRC-AT-DOC-2026:p.34` |
| `RC-001-04` | `CASE-001` | `J44.02` | `correct` | `RULE-CORRECT-01` | `accepted_response` |  | `SRC-AT-DOC-2026:pp.26,34`; DIAGLIST row 3887 |
| `RC-001-05` | `CASE-001` | `J44.03` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict` | `J44.02` | `SRC-AT-DOC-2026:p.34` |
| `RC-001-06` | `CASE-001` | `J44.09` | `suboptimal` | `RULE-SPEC-01` | `PAT-SPEC-01` / `supported_specificity_not_used` | `J44.02` | `SRC-AT-DOC-2026:pp.26,34` |
| `RC-002-01` | `CASE-002` | `J44.1` | `incorrect` | `RULE-DEPTH-01` | `PAT-DEPTH-01` / `mandatory_coding_depth_not_met` | five-character level; mapped target may show `J44.12` | `SRC-AT-DOC-2026:pp.12,26` |
| `RC-002-02` | `CASE-002` | `J44.10` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict` | `J44.12` | `SRC-AT-DOC-2026:p.34` |
| `RC-002-03` | `CASE-002` | `J44.11` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict`; 50% is outside the `<50%` band | `J44.12` | `SRC-AT-DOC-2026:p.34` |
| `RC-002-04` | `CASE-002` | `J44.12` | `correct` | `RULE-CORRECT-01` | `accepted_response`; exact 50% enters suffix `2` |  | `SRC-AT-DOC-2026:pp.26,34`; DIAGLIST row 3893 |
| `RC-002-05` | `CASE-002` | `J44.13` | `incorrect` | `RULE-EVID-01` | `PAT-EVID-01` / `case_evidence_conflict` | `J44.12` | `SRC-AT-DOC-2026:p.34` |
| `RC-002-06` | `CASE-002` | `J44.19` | `suboptimal` | `RULE-SPEC-01` | `PAT-SPEC-01` / `supported_specificity_not_used` | `J44.12` | `SRC-AT-DOC-2026:pp.26,34` |
| `RC-003-01` | `CASE-003` | `Z01.6` | `correct` | `RULE-CORRECT-01` | accepted marked-code response in represented permitted context |  | `SRC-AT-DOC-2026:pp.18,22`; DIAGLIST row 12683 |
| `RC-004-01` | `CASE-004` | `Z01.6` | `incorrect` | `RULE-STATUS-01` | `PAT-STATUS-01` / `context_status_incompatibility` | none; no alternative diagnosis inferred | `SRC-AT-DOC-2026:pp.10-11,18`; DIAGLIST row 12683 |

The working estimate is therefore **4 base cases and 14 atomic reference-response variants**. This count has no statistical interpretation. Twelve variants arise mechanically because each of the two selected COPD response families contains six supported candidate records; the remaining two form the permitted/prohibited status pair.

Expected-class distribution is 3 `correct`, 2 `suboptimal` and 9 `incorrect`. The imbalance is intentional and must not be used to calculate a clinically meaningful accuracy/prevalence statistic. Verification should report conformance against predefined expectations and coverage, not treat these purposive counts as a sampled population.

## 6. Coverage matrix

| Coverage obligation | Planned evidence | Status in `CASEPLAN-0.1` |
|---|---|---|
| all three feedback classes | `CASE-001` alone already contains correct/suboptimal/incorrect; whole suite contains all three | covered |
| `PAT-DEPTH-01` trigger + passing control | `RC-001-01` / `RC-001-04`; repeated in `CASE-002` | covered |
| `PAT-EVID-01` trigger + passing control | `RC-001-02/03/05` / `RC-001-04`; boundary-sensitive repeat in `CASE-002` | covered |
| `PAT-SPEC-01` trigger + passing control | `RC-001-06` / `RC-001-04`; repeated by `RC-002-06` / `RC-002-04` | covered |
| `PAT-STATUS-01` trigger + permitted control | `RC-004-01` / `RC-003-01` | covered |
| straightforward objectively decidable case | `CASE-001`, FEV1 55% | covered |
| harder/source-boundary case | `CASE-002`, FEV1 exactly 50%; `J44.11` versus `J44.12` tests the published inequality boundary | covered |
| repeat application beyond one COPD base | `CASE-001` uses J44.0; `CASE-002` uses J44.1 | covered |
| acceptable alternative | no selected case has a source-bounded alternative equivalence that can be asserted without additional judgement | deliberately excluded and documented |
| natural multi-hard-rule response | none exists in the selected subset: status, depth and evidence predicates do not overlap on a frozen response | not required at RC level; precedence remains a unit/control test |
| outside-subset handling | use valid DIAGLIST 2026 code `Z01.8!` (row 12685) while it remains outside `SUBSET-0.1` | `TEST-GATE-01`, `TEST-API-01`; not a learner case |
| undefined case-response relation | submit an active code from the other COPD family against a case whose response domain does not include it | `TEST-GATE-01`; must not become `incorrect` by default |
| all four FEV1 mapping bands and exact 35/50/70 boundaries | parameterised `RULE-MAP-01` unit checks; `CASE-002` additionally covers exact 50% at RC level | `TEST-MAP-01` plus RC boundary |
| inpatient `!` prohibition branch | `Z01.6!` + main diagnosis + inpatient context | `TEST-STATUS-01`; `CASE-004` already covers the other prohibited branch |
| missing rule-required case fact | remove FEV1 from a relation explicitly intended to exercise evidence/specificity | `TEST-GATE-01`; test-only fixture, not deterministic RC |
| rule-order/terminal-gap behaviour | controlled rule-engine tests for order independence and no-terminal specification gap | `TEST-PREC-01`, `TEST-DET-01` |

These obligations are represented in `TESTBASE-0.1`. Several are specified as parameterised tests with multiple assertions rather than separate named clinical cases, preserving the distinction between technical branch coverage and reference-case count. They do not become executed tests merely by appearing in the catalogue.

## 7. Case and RC schema to carry forward

The physical database/API schema may use different names, but the following semantics must remain recoverable.

### 7.1 `CASE-*`

- stable case ID and case-baseline version;
- short synthetic vignette/documented facts;
- Austrian catalogue and subset-baseline identity;
- encounter setting and diagnosis role where relevant;
- inpatient-LKF-scoring flag where relevant;
- explicit rule differentiators, initially `copd_base_code` and stable-phase FEV1;
- closed case-specific response domain;
- declared acceptable code set, which may be empty only for an explicitly verification-only negative fixture;
- source/rationale locators; and
- intended use (`learner_visible` or `verification_only`) where this distinction is implemented.

### 7.2 `RC-*`

- stable RC ID and parent `CASE-*` ID;
- submitted code;
- expected evaluation status;
- expected feedback class;
- determining `RULE-*` and `PAT-*` where applicable;
- criterion key;
- required explanation elements;
- improvement code where defined;
- source/rationale locator; and
- expectation/baseline version.

The complete final appendix table can be generated from this structure. Representative thesis prose should discuss only a small number of variants, most usefully the `CASE-001` three-class trace, the exact-50% boundary in `CASE-002`, and the `CASE-003/004` status contrast.

## 8. Remaining decisions before case freeze

`MODELBASE-0.1` has materialised this plan as candidate working `CASEBASE-0.1` and `RCBASE-0.1` files, fixes single-code interaction cardinality, and specifies `CASE-004` as `verification_only`. Those files still require adoption and pre-freeze review in the actual project. The remaining case/evaluation dependency is `OPEN-EVAL-01`: whether an additional independent domain-expert review is required. That decision affects evaluation procedure and claim strength, not the source-bound internal expectations already defined here.

`CASEBASE-1.0` / `RCBASE-1.0` should be frozen only after the candidate records have been adopted and passed the declared integrity checks in the actual project, `SUBSET-0.1` has been reproducibly regenerated from the frozen source, every expected response retains its rule/source trace, and the expectation fixture remains independent of implementation output.

## 9. Chapter 3 use

The methodological consequences are now concrete:

- **Section 3.1.2** can report the purposive selection criterion, `SUBSET-0.1`, its 13 records, four-field DIAGLIST whitelist and reproducible source checksum. The 13-record size should be presented as a consequence of the required behaviours, not as an achievement in itself.
- **Section 3.1.3** can use `RULEBASE-0.1` as the rule-model source and one worked case trace rather than restating the complete catalogue.
- **Section 3.2.1** can state the first planning estimate of four base cases and fourteen `RC-*` variants, explain why the two six-code COPD response domains are exhaustively enumerated, and summarise the 17 targeted specifications in `TESTBASE-0.1` by responsibility rather than reproducing every vector.
- **Section 3.2.2** should freeze the eventual `CASEBASE-*`, `RULEBASE-*`, `SUBSET-*` and implementation version before execution, then report conformance/deviations against those predefined expectations.
