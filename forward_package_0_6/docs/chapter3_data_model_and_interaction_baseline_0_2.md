# Chapter 3 Data Model and Interaction Baseline, Forward Revision

**Document status:** working design control artefact; not implemented or verification-frozen  
**Model baseline:** `MODELBASE-0.2`  
**Date:** 8 August 2026  
**Interface clarification:** 9 August 2026, `APIBASE-0.1`  
**Historical model retained:** `MODELBASE-0.1` and the recorded `CASEBASE-0.2` runtime/test state  
**Upstream learner design:** `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`, `QSAUDIT-0.1`  
**Upstream rule revision:** `RULEBASE-0.2`  
**Candidate downstream content:** `SUBSET-0.2`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, `RCBASE-0.3`  

## 1. Purpose and scope

`MODELBASE-0.2` replaces the one-question-per-case runtime shape with a normalized patient/question model while retaining the atomic one-response evaluator. The model is designed around the source-audited 25-question bank rather than around the original COPD-heavy verification fixtures.

The revision must simultaneously satisfy four constraints:

1. a learner case is a synthetic patient with a variable number of independently answerable coding questions;
2. patient history/background remains visible context but cannot silently become evaluator input;
3. each question has a closed evaluator response domain distinct from its fixed displayed option set; and
4. the verification oracle remains outside the runtime database and evaluator path.

This document specifies the logical contract. It does not claim that the new schema, seed data, API, PHP evaluator or React workflow already exist.

## 2. Core structural decisions

### 2.1 Patient is not the atomic evaluation unit

A patient establishes persistent identity, demographics and known context. A patient has zero or more context items and one or more learner coding questions. The evaluator never classifies a response “against a patient” in the abstract; it classifies one response against one `coding_question`.

```text
patient
  -> patient_context_item[]
  -> coding_question[]
       -> question_fact[]
       -> question_code_domain[]
       -> question_option[]
```

The present learner design uses question counts `3, 3, 3, 5, 5, 6`, but these values are **content cardinalities only**. No database constraint, API property or UI component may encode “three questions” as a structural limit.

### 2.2 Patient context and rule facts are different datasets

`patient_context_item` is learner-visible contextual information: documented chronic conditions, history, self-reported information, current examination findings and similar material. It is not an implicit rule-input bag.

`question_fact` is the only generic fact collection the evaluator may consume. If a piece of patient context matters to a coding decision, the authoring baseline must make that dependency explicit by representing the needed normalized value as a `question_fact`. A question fact may link back to the patient-context item from which it was drawn, but the evaluator follows only the explicit question-fact relation.

This deliberately permits some controlled duplication. It is preferable to duplicating a documented value explicitly into the question contract than allowing an evaluator to scan a patient's prose for medically meaningful cues.

### 2.3 Evaluation domain and displayed options are distinct

For question `q`:

- `question_code_domain(q)` is the closed set of code relations the evaluator is allowed to classify;
- `question_option(q)` is the fixed set the learner sees; and
- the displayed code set may be a strict subset of the evaluation domain.

This distinction is required by `Q-004-05` and `Q-005-05`, whose accepted reference codes (`M54.5` and `I10`) are deliberately evaluable but not displayed. It also lets the full J44 technical family remain evaluable without flooding the learner interface.

### 2.4 `none_of_above` is not an ICD code

`none_of_above` is represented as an option kind. It never receives a catalogue-code row and never appears in the DIAGLIST-derived subset. Its truth is computed by `RULE-NOA-01` from the intersection between displayed code options and accepted-reference domain relations.

### 2.5 Technical regression fixtures use the same evaluator but not the learner UI

The eight historical `CASEBASE-0.2` cases are not rewritten into learner patients. In the new runtime model they may be materialized as `verification_only` coding questions with `patient_id = NULL` and an optional `legacy_case_id`. Their old facts and response domains are transformed without changing the historical `RCBASE-0.2` expectations.

This creates one evaluator path for learner questions and technical regression while keeping technical coverage fixtures out of patient navigation.

## 3. Runtime/verification authority boundary

| Layer | Runtime contents | May influence classification? |
|---|---|---|
| Austrian catalogue subset | selected four-field DIAGLIST projection | yes, only through declared rules/relations |
| Patient presentation | demographics and `patient_context_item` rows | **no**, unless separately declared as a `question_fact` |
| Question model | prompt, explicit facts, response-domain semantic relations, displayed option membership | yes |
| Rule model | `RULEBASE-0.2` | yes |
| Verification oracle | expected class/rule/criterion/explanation assertions | **no runtime access** |

Runtime semantic relations do not remove the independent-oracle boundary. They describe the curated relationship the classifier is supposed to reason over, such as `fact_conflict`; the external oracle separately predicts what `RULEBASE-0.2` should output from that relation. The verification claim is therefore implementation/model conformance, not independent clinical validation.

## 4. Baseline identities

The eventual runtime baseline row should identify at least:

- `prototype_baseline_id`;
- `requirements_baseline_id`;
- `model_baseline_id` (`MODELBASE-0.2` while developing);
- `rule_baseline_id` (`RULEBASE-0.2` while developing);
- `subset_baseline_id` (`SUBSET-0.2` once materialized);
- `patient_baseline_id`;
- `question_baseline_id`;
- catalogue edition (`ICD-10 BMASGPK 2026`); and
- frozen DIAGLIST identity/checksum.

The runtime baseline does **not** need an `RCBASE-*` answer-key identifier. The final verification configuration/report can bind the runtime baseline to an external oracle without giving the application a path to that oracle.

## 5. Logical runtime entities

### 5.1 `catalogue_code`

The existing four-field transformation remains valid and should be retained unless a later implemented requirement proves another source field necessary.

| Runtime field | Source | Transformation |
|---|---|---|
| `code` | DIAGLIST `Diagnose` | trim surrounding whitespace, otherwise preserve |
| `marker` | `Kennzeichen` | trim; whitespace-only -> `NULL`; preserve source marker |
| `designation` | `Bezeichnung` | preserve Unicode text |
| `short_designation` | `Kurzbezeichnung` | preserve Unicode text |

Primary identity remains `(subset_baseline_id, code)`. The `SUBSET-0.2` union derived by `QSAUDIT-0.1` contains 99 unique DIAGLIST records and has now been materialized as a forward design dataset under `prototype_baseline_0_2_design/`. That materialization does not imply that the application database already consumes the new subset.

### 5.2 `patient_definition`

Primary identity: `(patient_baseline_id, patient_id)`.

Minimum fields:

- stable `patient_id`;
- synthetic display name;
- age in years;
- sex as represented by the synthetic vignette;
- synthetic ethnicity/background field where included by the learner design;
- `history_availability` (`established`, `partial`, `unavailable_from_patient`);
- `difficulty_role` (`foundational`, `involved`);
- general-health summary; and
- synthetic/intended-use marker.

These fields exist for learner orientation. None becomes a rule input merely by being present. If age, sex or ethnicity were ever made outcome-relevant, that change would require a question fact, source justification and a new question/rule baseline.

### 5.3 `patient_context_item`

Primary identity: `(patient_baseline_id, patient_id, context_item_id)`.

Suggested fields:

- stable `context_item_id`;
- `item_type` from the controlled vocabulary `documented_condition`, `self_reported_history`, `current_exam_finding`, `social_context`, `information_boundary`, `other`;
- `information_source`, such as `record`, `patient_report`, `physical_exam`, `synthetic_demographic`;
- learner-visible text;
- canonical authoring position.

The table lets the UI distinguish a transferred/documented diagnosis from a self-report or physical finding. `information_boundary` is an explicit model value rather than an undocumented exception. For `PATIENT-006`, it represents that an anamnesis cannot be obtained from the unconscious patient; current physical-examination findings are not mislabeled as anamnesis.

### 5.4 `coding_question`

Primary identity: `(question_baseline_id, question_id)`.

Minimum fields:

- stable `question_id`;
- nullable `(patient_baseline_id, patient_id)` foreign key;
- short title;
- learner/task prompt;
- `intended_use` (`learner_visible` or `verification_only`);
- canonical authoring position;
- optional `legacy_case_id` for transformed historical fixtures; and
- optional short authoring/source-audit reference.

Integrity rules:

- a `learner_visible` question must belong to a patient;
- a `verification_only` question may have no patient;
- no question-count column or maximum-per-patient constraint exists; and
- response cardinality is defined by the model as one response per evaluation request rather than stored independently on every question.

### 5.5 `question_fact`

Primary identity: `(question_baseline_id, question_id, fact_key)`.

The table is a typed fact collection rather than a collection of prose snippets. A physical MySQL realization may use:

- `fact_key`;
- `value_type` (`text`, `integer`, `decimal`, `boolean`, `code`, `enum`);
- one matching typed value column;
- optional unit;
- human-readable label usable in feedback/technical trace output; and
- optional `source_context_item_id` when the fact explicitly repeats a patient-context item.

A CHECK/loader invariant must ensure exactly one value representation agrees with `value_type`. Rule classes request facts by declared keys and validate their types. The evaluator never parses the question prompt or patient-context prose to manufacture a missing fact.

For the present baseline, raw `question_fact` rows are **internal evaluator data**, not a pre-submission learner payload. The existing `learner_label` column is a human-readable label for feedback/trace rendering and is not a visibility flag. Facts required to solve the learner task are conveyed through the question prompt and/or learner-visible patient context. If structured pre-submission fact chips are introduced later, visibility requires an explicit versioned model field; it must not be inferred from `fact_key`, `learner_label`, or rule family.

Example source-specific facts for `Q-001-01` are:

```text
encounter_setting = inpatient
diagnosis_role = main
copd_base_code = J44.0
fev1_stable_pct_predicted = 55.00
```

For a generic relation such as `Q-004-01 / E11.3`, question facts can represent the documented type-2 diabetes, absence of a documented diabetic complication, the separate glaucoma diagnosis, and absence of a documented diabetic causal link. `question_relation_fact` then identifies which of these is relevant to that response relation.

### 5.6 `question_code_domain`

Primary identity: `(question_baseline_id, question_id, subset_baseline_id, code)`.

Minimum fields:

- code foreign key into `catalogue_code`;
- `relation_kind` from `RULEBASE-0.2`:
  - `accepted_reference`;
  - `less_specific_supported`;
  - `fact_conflict`;
  - `temporal_context_conflict`;
  - `source_rule_resolved`;
- nullable controlled semantic `reason_key` used to explain the represented relation, not an expected-output criterion;
- nullable `improvement_code`.

The runtime relation deliberately does **not** store `expected_class`, `expected_determining_rule`, or the output `criterion` expected by verification. `RULEBASE-0.2` derives the criterion from `relation_kind`; `reason_key` supplies only the narrower semantic reason used in the explanation.

The former `is_acceptable` Boolean is removed. Acceptance is now one explicit semantic relation kind rather than a second truth flag that can contradict the relation type.

Cross-row validation must enforce:

- `less_specific_supported -> improvement_code IS NOT NULL`;
- the improvement code exists as an `accepted_reference` for the same question;
- `source_rule_resolved` does not carry a generic relation reason intended to bypass the source-specific rule; and
- no expected class or expected determining rule is stored here.

### 5.7 `question_relation_fact`

Primary identity can be `(question_baseline_id, question_id, subset_baseline_id, code, fact_key, relation_role)` so that the join has an exact foreign-key target in `question_code_domain`.

This join explicitly records which question facts substantiate or explain a code relation. `relation_role` can be constrained to a small vocabulary such as:

- `supports_reference`;
- `supports_specificity`;
- `conflicts_with_response`;
- `supports_temporal_context`; and
- `supports_source_rule`.

This table is important for explainability. A generic `fact_conflict` row cannot merely assert “wrong”; it must point to the represented fact(s) the feedback is allowed to cite.

### 5.8 `question_option`

Primary identity: `(question_baseline_id, question_id, option_id)`.

Minimum fields:

- stable `option_id`;
- `option_kind` (`code` or `none_of_above`);
- nullable `(subset_baseline_id, code)`;
- canonical authoring position.

Integrity rules:

- `option_kind = code` requires a code and that code must already belong to the same question's `question_code_domain`;
- `option_kind = none_of_above` requires `code IS NULL`;
- a learner question has exactly one `none_of_above` option in the present design;
- a code cannot be displayed twice for the same question; and
- no class, correctness Boolean, determining rule or improvement target appears in this table.

The present source-audited bank uses three or four displayed ICD codes plus `none_of_above`, but this is **not** a schema-level count constraint.

## 6. Relationship summary

| Parent | Child | Cardinality / rule |
|---|---|---|
| patient baseline | `patient_definition` | many patients |
| `patient_definition` | `patient_context_item` | zero-to-many |
| `patient_definition` | learner `coding_question` | one-to-many; no fixed maximum |
| `coding_question` | `question_fact` | zero-to-many, depending on rule needs |
| `coding_question` | `question_code_domain` | one-to-many closed evaluable code relations |
| `question_code_domain` | `question_relation_fact` | zero-to-many explicit fact links |
| `coding_question` | `question_option` | zero-to-many; learner questions use a fixed non-empty set |
| catalogue subset | `catalogue_code` | selected records only |
| `catalogue_code` | question domain/options | referenced, never copied semantically |

Technical verification questions are the only normal case where `coding_question.patient_id` is absent. They are excluded from learner navigation by `intended_use`, not by a separate evaluator implementation.

## 7. Learner-facing API boundary

The forward interface should separate presentation from evaluation.

### 7.1 Patient discovery/detail

Conceptually:

```text
GET /api/patients
GET /api/patients/{patient_id}
GET /api/questions/{question_id}
```

Learner responses may include patient demographics, general-health summary, context items, question prompts, and displayed options joined to catalogue designations. Raw `question_fact` rows are not returned before submission in the present baseline; `APIBASE-0.1` fixes this visibility boundary.

They must not expose before submission:

- acceptance membership;
- runtime relation semantics (`relation_kind` / `reason_key`);
- `improvement_code`;
- source-rule resolver flags; or
- verification expectations.

`verification_only` questions are excluded from all learner-discovery/detail endpoints. In particular, `GET /api/questions/{question_id}` returns `404` for a `verification_only` question even when its identifier is known. No public harness-only read route is introduced.

### 7.2 Evaluation

Conceptually:

```text
POST /api/questions/{question_id}/evaluate

{ "response": { "type": "code", "code": "I48.9" } }

or

{ "response": { "type": "none_of_above" } }
```

The evaluator accepts any defined code-domain relation, not merely displayed code options. This preserves technical evaluation of hidden references and full-family regression relations.

This tagged-response form is the **only** forward evaluation request contract. The earlier `{"option_id": ...}` proposal in the patient/question design plan is superseded for API submission. `option_id` remains a stable presentation/local-state identity, while a displayed code option supplies the code needed to construct the tagged request. The same request shape is therefore usable by the learner interface and by technical verification of non-displayed domain codes.

The response retains the current useful shape:

```text
evaluation_status
classification
criterion
explanation
explanation_elements
determining_rule
matched_rules
active baseline identifiers
```

The verification harness may call the evaluation route for `verification_only` questions by ID even though those questions are not discoverable or readable through learner navigation. This GET/POST asymmetry is intentional: the harness already owns versioned fixture/oracle inputs and needs only the common evaluation path. A compatibility alias for the old `/api/cases/{case_id}/evaluate` route is permissible during migration but must delegate to the same evaluator rather than maintain a second rule implementation.

Request-shape validation occurs before `RULE-GATE-01`. Malformed request structure yields HTTP `400` / `malformed_input`; an unrecognized response tag yields HTTP `400` / `unsupported_response_kind`; and an unresolved question ID on the evaluation route yields `404` / `question_not_found`. Only a syntactically valid tagged response for an existing question reaches the semantic eligibility gate. Gate failures remain normal evaluation responses with `evaluation_status = not_evaluated` and `classification = null`. The exact boundary is fixed by `APIBASE-0.1`.

## 8. Randomization boundary

Storage is deterministic; presentation order is not semantically meaningful.

- Questions are stored in a canonical authoring order for reproducible data comparison.
- Code/`none_of_above` option membership is frozen and versioned.
- A playthrough may permute question order and option order.
- Randomization may never add, remove or replace an option.
- Evaluation uses question/response identity, never presentation index.

The first implementation can perform the permutation in React when a patient playthrough starts. Tests should verify that the result is a permutation of the frozen membership and that three-question and six-question patients pass through the same generic rendering/evaluation code. They should not require two random runs always to differ, because a valid random permutation can repeat.

No learner-session or attempt-history persistence is required merely to randomize presentation.

`UXBASE-0.1` now fixes the presentation lifecycle around this model without altering its persistence semantics. A learner submission that evaluates successfully becomes immutable within the active playthrough; its evaluator result is shown immediately and retained in transient frontend state for the patient-completion review. Replay creates a fresh presentation state. The review may aggregate raw counts of the evaluator's existing `correct`, `suboptimal`, and `incorrect` classes, but it does not create a weighted score or a new domain judgement. No additional MySQL table is required for these transient concerns.

## 9. Candidate content cardinalities

`QSAUDIT-0.1` currently implies the following **design-derived** learner counts:

| Item | Candidate count |
|---|---:|
| synthetic learner patients | 6 |
| learner-visible questions | 25 |
| displayed ICD-code option relations | 95 |
| displayed `none_of_above` options | 25 |
| total displayed option rows | 120 |
| additional non-displayed learner code relations | 5 |
| learner question-to-code domain relations | 100 |
| unique catalogue records required by learner domains | 92 |
| candidate active catalogue records including legacy subset | 99 |

If all eight historical `CASEBASE-0.2` cases are transformed into verification-only questions, the new runtime would additionally contain eight technical questions and their 18 historical code-domain relations. That would yield **33 total coding questions and 118 total question-code relations** before any new technical-only test fixture is added.

These are consistency targets for data generation, not executed database assertions. Final loader/test counts must come from the actually materialized versioned files.

The planned independent learner oracle has 125 new question-response expectations if all 100 code-domain relations plus all 25 `none_of_above` relations are verified. Retaining the 18 historical expectations yields 143 oracle rows. None of those expected-output fields belongs in the runtime tables above.

## 10. Candidate persistence/import path

The deterministic data-build approach remains appropriate:

```text
frozen DIAGLIST + versioned subset definition
                  -> deterministic subset projection

versioned patient/question authoring files
                  -> structural/semantic validation

both runtime datasets
                  -> transactional MySQL DML load
                  -> exact post-load comparison
```

Likely authoring artefacts are:

```text
subset_definition_0_2.json
patients_0_1.csv
patient_context_items_0_1.csv
questions_0_1.csv
question_facts_0_1.csv
question_code_domain_0_1.csv
question_relation_facts_0_1.csv
question_options_0_1.csv
```

These filenames were proposed by this model and have subsequently been materialized under `prototype_baseline_0_2_design/`; this document still specifies the logical contract rather than an implemented database schema.

The DML load order should respect referential dependencies:

1. catalogue codes;
2. patients;
3. patient context items;
4. coding questions;
5. question facts;
6. question-code domain relations;
7. relation-to-fact links;
8. question options; and
9. prototype-baseline metadata last.

Schema DDL remains separate because MySQL DDL can commit implicitly. The runtime data insertion should remain transactional and immutable by baseline identifier: identical contents may be a no-op; conflicting contents under the same ID must fail; a material semantic change receives a new baseline ID.

## 11. Migration from the implemented 0.1/0.2 state

The current development documentation describes a four-table `MODELBASE-0.1` implementation with `CASEBASE-0.2` content. There is no learner account/history data that must be migrated as user state. Consequently the safest development migration is a **fresh schema/baseline load**, while retaining all old source/control/oracle files in version control as historical evidence.

Conceptually:

1. preserve `SUBSET-0.1`, `CASEBASE-0.2`, `RCBASE-0.2`, `RULEBASE-0.1`, and the recorded prior test evidence unchanged;
2. derive `SUBSET-0.2` from the frozen DIAGLIST using a new versioned definition;
3. materialize the six patients and 25 learner questions from the source-audited design;
4. transform the eight legacy cases into verification-only questions for regression, recording `legacy_case_id` but not importing their expected outputs;
5. create a new physical schema realizing `MODELBASE-0.2` on a clean development database;
6. load the new immutable runtime baseline;
7. adapt PHP repositories/value objects/evaluator inputs and learner APIs;
8. adapt React from case-list/single-question presentation to patient/question playthroughs; and
9. rerun all surviving old tests plus the new model/rule/API/UI/reference tests before considering a freeze.

This is a forward migration. The historical test record remains true of the historical implementation; it is not treated as evidence that `MODELBASE-0.2` passes anything.

## 12. Required integrity checks before implementation freeze

At minimum, the generated runtime data must be rejected unless:

1. the frozen DIAGLIST checksum matches and the candidate subset regenerates exactly;
2. all required catalogue codes are unique and every question-domain/option code resolves to the active subset;
3. exactly six learner patients and the versioned learner-question membership expected by the content baseline are present;
4. learner question counts are derived from rows rather than from a stored/hardcoded limit;
5. every learner question has at least one accepted reference in its **domain** and exactly one `none_of_above` option in the present content baseline;
6. every displayed code is in that question's evaluator domain;
7. every `less_specific_supported` relation has a same-question accepted `improvement_code`;
8. every generic hard relation that depends on a fact has the required `question_relation_fact` links;
9. every `source_rule_resolved` relation has all source-specific required fact keys and targeted coverage proving a terminal rule exists;
10. `Q-004-05` and `Q-005-05` have no displayed `accepted_reference`, while the other 23 learner questions do;
11. the runtime schema/import files contain no expected class, expected rule, `RC-*` answer-key or observed-verdict fields;
12. verification-only questions cannot be enumerated through learner endpoints; and
13. changing only presentation order cannot change an evaluation result.

## 13. Explicit non-modelled concerns

`MODELBASE-0.2` still does not introduce:

- user accounts or authentication;
- learner attempt/history persistence;
- scoring, leaderboards or analytics;
- multi-code answer aggregation;
- clinical diagnosis inference;
- dynamic catalogue-wide semantic matching;
- LKF pricing/reimbursement logic;
- extramural-specific executable coding rules; or
- a usability/learning-effect measurement model.

Any of these would require an explicit requirement and baseline revision rather than an opportunistic schema field.

The wording above excludes **persistent/semantic scoring infrastructure**. It does not exclude `UXBASE-0.1`'s bounded presentation layer: question/patient progress, completion acknowledgement, replay, and read-only raw feedback-class counts are permitted because they neither alter evaluator inputs nor create a new score. Competitive ranking, weighted points, lives, timers, streaks and content-changing rewards remain outside scope.

## 14. Immediate downstream dependency

The learner portion of this contract has now been instantiated under `prototype_baseline_0_2_design/` as a forward design baseline:

1. the current requirements changes are recorded in `chapter3_requirements_forward_revision_0_7.md`, with `UXBASE-0.1` defining the presentation/feedback lifecycle; revision 0.6 remains the materialization-era historical delta;
2. `subset_definition_0_2.json` selects 99 codes from the frozen DIAGLIST source and `subset_0_2.csv` contains their four-field projection;
3. six `PATIENTBASE-0.1` records and 32 learner-visible context items are materialized;
4. 25 `QUESTIONBASE-0.1` learner questions, 60 typed facts, 100 code-domain relations, 142 relation-to-fact links and 120 displayed options are materialized; and
5. the materialized learner data pass the specified local data-contract invariants.

The historical bridge and candidate oracle have subsequently been materialized: eight `verification_only` questions retain 18 regression obligations, and candidate `RCBASE-0.3` contains 143 expectations in total. Four additive historical rows are explicitly marked as provisional reconstructions from implementation documentation and must be compared with the original `0.2` CSVs before freeze. That reconciliation is not a current implementation blocker. The immediate dependency is now live integration/proof of the `MODELBASE-0.2` SQL/loader candidate, followed by PHP/API migration and then the React interaction/`UXBASE-0.1` work. This keeps implementation downstream of source/rule/content decisions rather than allowing persistence or presentation convenience to redefine them.

`UXBASE-0.1` is intentionally a **supplement**, not `MODELBASE-0.3`: it adds transient interaction and presentation requirements while leaving the normalized runtime entities, database cardinalities, rule inputs, option membership and verification-oracle separation defined here unchanged. A semantic/persistence change discovered during implementation would require a real model revision rather than being hidden under the UX label.
