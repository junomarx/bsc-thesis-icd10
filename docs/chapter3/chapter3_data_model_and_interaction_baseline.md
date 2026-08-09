# Chapter 3 Data Model and Interaction Baseline

**Document status:** Working implementation-facing control artefact  
**Model baseline:** `MODELBASE-0.1`  
**Date:** 6 August 2026; case/expectation baselines superseded 7 August 2026  
**Proposed working prototype baseline:** `PROTOBASE-0.2` (supersedes `PROTOBASE-0.1`)  
**Upstream source register:** `chapter3_input_source_baseline_register.md`, register version 0.4  
**Upstream domain baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`  
**Upstream requirements:** `chapter3_requirements_catalogue.md`, catalogue version 0.5  
**Upstream rule baseline:** `chapter3_rule_catalogue.md`, `RULEBASE-0.1`  
**Upstream case/subset plan:** `chapter3_reference_case_coverage_plan.md`, `CASEPLAN-0.2` (supersedes `CASEPLAN-0.1`) / `SUBSET-0.1`  
**Working candidate case/expectation baselines:** `CASEBASE-0.2` / `RCBASE-0.2` (supersede `CASEBASE-0.1` / `RCBASE-0.1`)
**Downstream technical test baseline:** `chapter3_test_catalogue.md`, `TESTBASE-0.1`

## 1. Purpose

This document fixes the first concrete data and interaction model that sits between the conceptual rule/case specifications and the implementation. It resolves response cardinality, maps the selected source fields and case facts into physical entities, and defines which information may be available to the running prototype versus only to verification.

The model is intentionally small. It supports the behaviours already justified by `RULEBASE-0.1`; it does not introduce new clinical concepts, coding rules, or feedback classes simply because they would be convenient to implement.

`MODELBASE-0.1` is a working baseline. It may be promoted only after the schema/import and implemented interfaces have been checked against this contract. Material semantic changes require a new model-baseline version.

## 2. Interaction decision: resolution of `OPEN-INT-01`

`OPEN-INT-01` is resolved as follows for the bounded prototype:

> **One evaluation request contains exactly one submitted ICD code for exactly one case-defined coding target.**

This is an artefact-scope decision, not an Austrian coding rule. A synthetic vignette may contain several documented facts, but each present `CASE-*` identifies one coding task and one diagnosis role. A learner may make further attempts as separate requests; the prototype does not aggregate several simultaneously submitted codes into one feedback class.

The decision is required by the current evidence/model boundary. `RULEBASE-0.1` classifies an atomic relation between one case and one submitted code. Supporting multi-code answers would require additional, presently unspecified semantics for partial correctness, code roles, duplicate/ordering behaviour, and combination-level precedence. Those semantics must not be invented by combining per-code outputs after the fact. Adding multi-code responses therefore requires an explicit later requirement/model/rule revision.

`CASE-004` is fixed as `verification_only` in `CASEBASE-0.1`; the pre-freeze coverage review (7 August 2026) added `CASE-008` under the same designation in the superseding `CASEBASE-0.2`, exercising the inpatient branch of the same status prohibition. Neither is exposed as a learner task in the initial prototype because their purpose is to exercise the formal prohibited-status branch and no alternative main diagnosis is asserted.

## 3. Authority and data-separation boundary

Three data layers must remain distinguishable.

| Layer | Contents | Runtime access | Authority role |
|---|---|---|---|
| Catalogue/reference data | the 13 records in `SUBSET-0.1` plus source/version metadata | yes | machine-readable representation of the selected Austrian source records |
| Case/model data | `CASEBASE-0.2` case facts, case-specific response domains, and explicitly declared acceptable codes | yes | inputs to the artefact rules; synthetic project specification |
| Verification oracle | `RCBASE-0.2`: predefined expected class, rule, criterion and explanation obligations for the 18 `RC-*` variants | **no** | independent test expectation against which runtime output is compared |

The case-specific `is_acceptable` relation is a legitimate runtime model input because `RULE-CORRECT-01` explicitly depends on a predefined acceptable set. It does **not** imply that every `is_acceptable = false` relation is incorrect: the classification still comes from the rule model. By contrast, `expected_class`, `determining_rule`, `pattern_id`, expected criterion and required explanation elements belong only to the verification oracle and must not be imported into the application database or read by the evaluation endpoint.

This separation prevents a circular test in which the application is given the answer key against which it is supposedly being verified.

## 4. Physical artefacts for `PROTOBASE-0.2`

The working files below were originally materialised as implementation-facing
candidates and were subsequently adopted, executed, and verified in the actual
application repository (`docs/CHANGELOG.md`, 2026-08-07 entries); their
presence is no longer merely candidate status. The `cases`/`case_code_domain`/
`reference_responses` files were superseded on 7 August 2026 by `_0_2`
counterparts after the pre-freeze coverage review — both generations remain
on disk (the project's immutable-baseline design keeps prior versions rather
than overwriting them), but the runtime loader (`scripts/runtime_data.py`)
and the current oracle test harness now point at the `_0_2` files:

| File | Baseline role |
|---|---|
| `prototype_baseline_0_1/baseline_manifest.json` | binds all working baseline/version identifiers and the frozen DIAGLIST checksum; currently identifies `PROTOBASE-0.2`/`CASEBASE-0.2`/`RCBASE-0.2` |
| `prototype_baseline_0_1/config/subset_definition_0_1.json` | machine-readable extraction contract: source identity, four-field whitelist, selected/control codes, and normalization policy |
| `prototype_baseline_0_1/data/subset_0_1.csv` | reproducible 13-record application subset from DIAGLIST (unchanged by the coverage review) |
| `prototype_baseline_0_1/data/cases_0_1.csv` | **superseded** — the original four synthetic case records (`CASEBASE-0.1`), retained as history only |
| `prototype_baseline_0_1/data/cases_0_2.csv` | **current** — eight synthetic case records (`CASEBASE-0.2`: the original four plus `CASE-005`-`CASE-008` from the pre-freeze coverage review) |
| `prototype_baseline_0_1/data/case_code_domain_0_1.csv` | **superseded** — the original 14 eligible case-code relations, retained as history only |
| `prototype_baseline_0_1/data/case_code_domain_0_2.csv` | **current** — 18 eligible case-code relations and the explicit acceptable-set membership |
| `prototype_baseline_0_1/mysql_schema.sql` | implementation-facing relational schema contract |
| `prototype_baseline_0_1/scripts/prepare_subset.py` | deterministic source-to-subset preparation and source-identity checks |
| `prototype_baseline_0_1/scripts/runtime_data.py` | runtime-only input allowlist, parsing, normalization, and structural validation; `RUNTIME_FILES` currently points at the `_0_2` case/domain CSVs |
| `prototype_baseline_0_1/scripts/apply_mysql_schema.py` | applies the runtime DDL to a deliberately empty MySQL target and checks the resulting table set |
| `prototype_baseline_0_1/scripts/load_mysql.py` | transactional, immutable-baseline MySQL data loader; never reads `RCBASE-*` |
| `prototype_baseline_0_1/tests/test_runtime_contract.py` | database-independent input-boundary and normalized-model checks |
| `prototype_baseline_0_1/tests/test_mysql_persistence.py` | live MySQL assertions for `TEST-DAT-02`; does not read `RCBASE-*` |
| `prototype_baseline_0_1/verification/reference_responses_0_1.csv` | **superseded** — the original independent 14-row verification oracle (`RCBASE-0.1`), retained as history only |
| `prototype_baseline_0_1/verification/reference_responses_0_2.csv` | **current** — independent 18-row verification oracle (`RCBASE-0.2`); excluded from runtime imports |

The DIAGLIST source workbook itself remains the authoritative frozen input. These files are derived project artefacts and must not be described as replacing the Austrian source.

## 5. Catalogue transformation contract

`SUBSET-0.1` retains only the four fields fixed by `REQ-DAT-04`:

| DIAGLIST field | Runtime field | Transformation |
|---|---|---|
| `Diagnose` | `code` | trim surrounding whitespace; preserve the source code string |
| `Kennzeichen` | `marker` | trim surrounding whitespace; a whitespace-only value becomes `NULL`; preserve `!` where present |
| `Bezeichnung` | `designation` | preserve source Unicode text |
| `Kurzbezeichnung` | `short_designation` | preserve source Unicode text |

The source edition, source ID, worksheet, SHA-256 checksum, and subset ID are dataset-level metadata rather than duplicated source columns. No semantic coding rule is inferred from labels or from excluded DIAGLIST fields.

The frozen source identity remains:

- source: `SRC-AT-DIAGLIST-2026`;
- file: `DIAGLIST2026.xlsx`;
- worksheet: `DIAGLIST2026`;
- edition: ICD-10 BMASGPK 2026; and
- SHA-256: `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b`.

## 6. Logical data model

### 6.1 `prototype_baseline`

One metadata row identifies the combination of source, subset, domain, requirements, rules, cases and model against which the application is running. It makes the version context inspectable without adding those identifiers to every catalogue field.

### 6.2 `catalogue_code`

The primary identity is `(subset_baseline_id, code)`. Each row contains the normalized four-field DIAGLIST projection. `marker = !` is data consumed by `RULE-STATUS-01`; a missing marker is represented as `NULL`.

### 6.3 `case_definition`

The primary identity is `(case_baseline_id, case_id)`. Each present case defines exactly one target response and contains only explicit rule-relevant facts:

- short synthetic description;
- subset baseline;
- encounter setting;
- diagnosis role;
- inpatient-LKF-scoring flag where the hospital-outpatient status rule needs it;
- `copd_base_code` and stable-phase FEV1 where the COPD rules need them;
- intended use (`learner_visible` or `verification_only`); and
- source/rationale locator.

For `CASE-001/002`, `inpatient_lkf_scored` is `NULL` because the cases are inpatient and that outpatient discriminator is not applicable. For `CASE-003/004`, the COPD fields are `NULL` and `inpatient_lkf_scored` is respectively `false` and `true`. Missing values therefore mean “not applicable/not represented”, not a hidden clinical fact.

### 6.4 `case_code_domain`

The composite relation `(case_baseline_id, case_id, subset_baseline_id, code)` defines the closed set of submitted codes for which the current case model has a deterministic response relation. `is_acceptable` defines membership in the case's predefined acceptable set and is consulted only by `RULE-CORRECT-01` after higher-priority rules clear.

The relation deliberately contains no expected feedback class or determining rule. A code may be in the response domain and have `is_acceptable = false` while still becoming `suboptimal` or `incorrect` depending on the applicable rule.

## 7. Evaluation boundary and flow

For a request `(case_id, submitted_code)`, the implementation-facing sequence is:

1. resolve the active `PROTOBASE-*`, `CASEBASE-*`, `SUBSET-*`, and `RULEBASE-*` identities;
2. validate the request shape and case identity;
3. confirm that the submitted code belongs to the active catalogue subset;
4. confirm that the case-code relation belongs to that case's closed response domain;
5. load only the represented case facts and catalogue record;
6. evaluate `RULEBASE-0.1` in the fixed control/precedence order; and
7. return either a classified result (`correct`, `suboptimal`, `incorrect`) with criterion/explanation or a non-classified validation/scope result.

An active-subset code outside a case's response domain is an undefined relation and must not fall through to `incorrect`. A valid Austrian 2026 code outside `SUBSET-0.1`, such as `Z01.8!`, is likewise an out-of-scope input for this prototype rather than evidence of invalid Austrian coding.

The conceptual API boundary is therefore:

```text
POST /api/cases/{case_id}/evaluate

request:
  submitted_code: one code string

classified response:
  evaluation_status
  classification
  criterion
  explanation
  determining_rule        [technical trace]
  matched_rules           [technical trace]
  applicable baseline IDs [technical trace]
```

For example, submitting `J44.09` to `CASE-001` should be evaluated from case/catalogue/rule inputs. The running application must not consult the `RC-001-06` row that already states the expected result.

## 8. Integrity and reproducibility checks

Before this working model is promoted, the following checks are mandatory:

1. `subset_0_1.csv` contains exactly 13 unique codes and matches the frozen DIAGLIST values after the declared normalization.
2. Every `case_code_domain` code exists in `SUBSET-0.1`, and every relation has an existing parent case.
3. `CASEBASE-0.2` contains eight cases; `CASE-004` and `CASE-008` are the only `verification_only` cases.
4. The two original COPD response domains contain exactly six codes each; `CASE-003/004/005/006/007/008` contain one relation each.
5. The declared acceptable sets are exactly `{J44.02}`, `{J44.12}`, `{Z01.6}`, `{J44.00}`, `{J44.11}`, `{J44.03}`, and the empty set for `CASE-004` and `CASE-008`.
6. `RCBASE-0.2` contains exactly 18 unique `RC-*` rows and each row maps to one runtime case-code relation.
7. Every oracle rule/pattern/criterion identifier exists in the frozen upstream specifications.
8. No verification-only expected-output column/table is present in the runtime MySQL schema or runtime import set.
9. The application can be configured from the baseline manifest without changing a source-derived rule silently.

The candidate import path is designed to operationalize these checks in two stages. `prepare_subset.py` regenerates the four-field subset from the checksum-identified workbook; `runtime_data.py` loads only an explicit four-file runtime allowlist; and `load_mysql.py` is intended to persist that normalized dataset. MySQL schema DDL is intentionally applied separately because MySQL DDL can commit implicitly. The intended runtime-data insertion semantics are transactional: existing identical versioned components may be reused, a complete identical prototype baseline should be a no-op, conflicting content under an existing baseline identifier should be rejected, and failure before the final equality check should cause rollback. An intentional semantic change therefore requires a new baseline identifier rather than an in-place upsert.

The source/subset and runtime-input checks can be executed without a database, but database-independent preflight is not a substitute for persistence integration. No previous exploratory execution result is inherited as project evidence. After these candidates are adopted, their structural checks, DDL, first load, identical re-import, rollback/constraint behaviour, and `TEST-DAT-02` assertions must be executed and recorded against the actual project environment.

## 9. Chapter 3 use

This baseline gives the later chapter prose a concrete separation of concerns:

- **Section 3.1.2** can report the exact source-to-subset projection after the candidate `SUBSET-0.1` data contract has actually been adopted and reproduced.
- **Section 3.1.3** can explain the single-target/single-code case model, acceptable-set relation, and rule-evaluation boundary without exposing implementation code.
- **Section 3.1.4** can map these entities to MySQL, PHP/API and React responsibilities and explain why verification expectations are external to the running system.
- **Section 3.2.1** can derive the appendix/reference matrix from `RCBASE-*` and state explicitly that its expectations are not application inputs.

That next layer is supplied by `TESTBASE-0.1`, which specifies unit, gate/boundary, persistence/API integration, reference-response, end-to-end, determinism, and configuration checks against these concrete model responsibilities. The remaining dependency is first to adopt and verify the candidate importer/persistence path and then to bind the PHP evaluator/API, MySQL persistence, and React learner path to those specifications.
