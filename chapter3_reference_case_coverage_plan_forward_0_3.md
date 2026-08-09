# Chapter 3 Reference-Response Coverage Plan, Forward Revision

**Plan ID:** `CASEPLAN-0.3`  
**Status:** working forward control artefact; not application verification evidence  
**Date:** 8 August 2026  
**Clarified:** 9 August 2026, `APIBASE-0.1`  
**Runtime design:** `SUBSET-0.2`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, `RULEBASE-0.2`, `MODELBASE-0.2`, `APIBASE-0.1`  
**Candidate external oracle:** `RCBASE-0.3`

## 1. Purpose

This revision adapts the reference-response plan to the six-patient, multi-question learner model while retaining the historical `RCBASE-0.2` regression obligations. The unit of verification is one submitted response to one atomic `coding_question`, not an entire patient record. The number of reference responses is therefore derived from the closed evaluation domains and the `none_of_above` interaction rule rather than chosen as an arbitrary case quota.

`RCBASE-0.3` is a candidate oracle, not an executed result set. Its expectations are specified before the new application implementation is tested against them and remain outside runtime data.

## 2. Learner-response coverage

The learner baseline contains 25 questions. Their closed evaluation domains contain 100 code relations. Each question also exposes exactly one `none_of_above` response, yielding 125 learner expectations.

| Response group | Expected rows | Correct | Suboptimal | Incorrect |
|---|---:|---:|---:|---:|
| Learner code responses | 100 | 25 | 18 | 57 |
| Learner `none_of_above` | 25 | 2 | 0 | 23 |
| **Learner total** | **125** | **27** | **18** | **80** |

`Q-004-05` and `Q-005-05` are the two positive `none_of_above` controls: their accepted references (`M54.5` and `I10`) belong to the evaluation domain but are deliberately absent from the fixed displayed code set. All other learner questions display an accepted reference and therefore expect `none_of_above` to be `incorrect`.

Under `APIBASE-0.1`, every current `none_of_above` expectation requires two post-submission explanation elements: `displayed_accepted_response_exists` and the question's unique `reference_code`. The 23 incorrect controls identify the accepted code that was displayed; the two correct controls identify the accepted code deliberately omitted from the display. This is already the literal requirement encoded in candidate `RCBASE-0.3`.

The design does not require every individual question to contain all three feedback classes. The supervisor requirement concerns coverage of `correct`, `suboptimal` and `incorrect` across the reference set. A `suboptimal` decoy is included only where a source-audited less-specific response or a source-specific rule supports that judgement. This prevents artificial `suboptimal` classifications from being introduced merely to equalize question composition.

## 3. Historical regression coverage

All 18 `RCBASE-0.2` obligations remain regression requirements under `REQ-VER-09`. In `MODELBASE-0.2` they are mapped to eight `verification_only` questions (`VQ-001` through `VQ-008`) with no learner patient and no displayed options.

| Legacy group | Rows | Provenance status |
|---|---:|---|
| `RC-001-*` through `RC-004-*` | 14 | exact semantic carry-forward from the available `RCBASE-0.1`; implementation documentation states these rows were unchanged apart from `0.2` baseline identifiers |
| `RC-005-01` through `RC-008-01` | 4 | provisional reconstruction from the implementation `CHANGELOG.md` and `DEVELOPMENT_DOCUMENTATION.md` |
| **Legacy total** | **18** | pre-freeze reconciliation required for the four reconstructed rows |

The four reconstructed additions preserve the documented integration obligations:

| Legacy case | Decisive condition | Response | Expected result |
|---|---|---|---|
| `CASE-005` | J44.0; stable-phase FEV1 20% | `J44.00` | `correct` / `RULE-CORRECT-01` |
| `CASE-006` | J44.1; stable-phase FEV1 exactly 35% | `J44.11` | `correct` / `RULE-CORRECT-01` |
| `CASE-007` | J44.0; stable-phase FEV1 exactly 70% | `J44.03` | `correct` / `RULE-CORRECT-01` |
| `CASE-008` | inpatient main diagnosis; `Z01.6` carries `Kennzeichen = !` | `Z01.6` | `incorrect` / `RULE-STATUS-01` |

The original raw `CASEBASE-0.2`/`RCBASE-0.2` files must be diffed against these four provisional rows when they become available. Until that comparison, the reconstruction is sufficient for development continuity but is not described as byte-identical historical data.

## 4. Candidate `RCBASE-0.3` coverage

Combining the learner and regression obligations yields:

| Oracle group | Rows | Correct | Suboptimal | Incorrect |
|---|---:|---:|---:|---:|
| Learner responses | 125 | 27 | 18 | 80 |
| Historical regressions | 18 | 6 | 2 | 10 |
| **`RCBASE-0.3` total** | **143** | **33** | **20** | **90** |

Rule-level expectation counts are:

| Determining rule | Learner | Legacy | Total |
|---|---:|---:|---:|
| `RULE-CORRECT-01` | 25 | 6 | 31 |
| `RULE-NOA-01` | 25 | 0 | 25 |
| `RULE-REL-HARD-01` | 53 | 0 | 53 |
| `RULE-REL-SPEC-01` | 17 | 0 | 17 |
| `RULE-DEPTH-01` | 1 | 2 | 3 |
| `RULE-EVID-01` | 3 | 6 | 9 |
| `RULE-SPEC-01` | 1 | 2 | 3 |
| `RULE-STATUS-01` | 0 | 2 | 2 |

The legacy rows deliberately retain the source-specific determining rules rather than being rewritten to the new generic relation rules. This is what preserves their regression value across the data-model migration.

## 5. Oracle independence and remaining gates

Runtime authoring files contain semantic relation inputs such as `relation_kind`, `reason_key` and explicit fact links, but contain no `expected_class`, expected determining rule, expected criterion or `RC-*` verdict. The candidate oracle resides under `verification/` and must not be imported into the runtime database.

The 125 learner expectations are specification-derived and are marked `forward_specification_derived_pending_human_oracle_audit`. This is sufficient to predefine expected software behaviour, but it is not independent clinical validation. Before final freeze, the following gates remain:

1. source/human audit of the 125 newly defined learner expectations and their explanation requirements;
2. comparison of the four provisionally reconstructed legacy additions with the original `0.2` CSVs;
3. implementation of `MODELBASE-0.2` and `RULEBASE-0.2` without giving runtime code access to `RCBASE-0.3`;
4. execution of the frozen oracle against the frozen application revision, with observed results stored separately from expected results.

The present structural validator checks completeness, class counts, response-kind coverage, legacy accounting and runtime/oracle field separation. A successful structural check is not application verification.
