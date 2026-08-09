# MODELBASE-0.2 SQL and Loader Migration Contract

**Contract ID:** `DATAMIG-0.2`  
**Status:** implementation-ready forward specification; not evidence of implementation or verification  
**Date:** 8 August 2026  
**Clarified:** 9 August 2026, `APIBASE-0.1`  
**Upstream:** `SUBSET-0.2`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, `MODELBASE-0.2`, `RULEBASE-0.2`, `CASEPLAN-0.3`, requirements forward revision `0.7`, `APIBASE-0.1`  
**Candidate runtime baseline after implementation:** `PROTOBASE-0.3`  
**External verification baseline:** `RCBASE-0.3`, never a runtime input

## 1. Purpose

This contract is the next implementation boundary after forward data materialization. It defines what the application repository's MySQL schema and bootstrap loader must persist before PHP or React is migrated. It does not prescribe a particular Python class layout; it prescribes observable data, constraints, version behaviour, and runtime/oracle separation.

The old `case_definition`/`case_code_domain` model must not be stretched to represent patients with multiple questions. The new persistence shape follows `MODELBASE-0.2` directly. Historical `CASEBASE-0.2` fixtures enter the new model only as `verification_only` questions.

## 2. Exact runtime input set

The bootstrap path uses an explicit allowlist. It must not discover CSV files through a directory glob.

| Component | Runtime-authoring file(s) | Expected working count |
|---|---|---:|
| catalogue | `data/subset_0_2.csv` | 99 |
| patients | `data/patients_0_1.csv` | 6 |
| patient context | `data/patient_context_items_0_1.csv` | 32 |
| questions | `data/questions_0_1.csv` + `data/verification_questions_legacy_0_1.csv` | 33 = 25 learner + 8 verification |
| question facts | `data/question_facts_0_1.csv` + `data/verification_question_facts_legacy_0_1.csv` | 88 = 60 + 28 |
| question/code relations | `data/question_code_domain_0_1.csv` + `data/verification_question_code_domain_legacy_0_1.csv` | 118 = 100 + 18 |
| relation/fact links | `data/question_relation_facts_0_1.csv` + `data/verification_question_relation_facts_legacy_0_1.csv` | 182 = 142 + 40 |
| displayed options | `data/question_options_0_1.csv` | 120 = 95 codes + 25 `none_of_above` |

No file in `verification/`, `migration/`, or `review/` is a runtime import. In particular, `verification/reference_responses_0_3_candidate.csv` must not be read by the bootstrap loader, copied into the application runtime image, or represented by a database table.

## 3. Physical relational contract

The concrete SQL may use equivalent names, but the following identities and relations must remain recoverable.

### `prototype_baseline`

One row identifies the working runtime configuration. Minimum fields are `prototype_baseline_id`, `model_baseline_id`, requirements revision, source-register version, domain/rule/subset/patient/question baseline IDs, catalogue edition, and the 64-character DIAGLIST SHA-256. It deliberately contains no `RCBASE-*` identifier.

### `catalogue_code`

Retain the existing primary key `(subset_baseline_id, code)` and fields `marker`, `designation`, `short_designation`. `code` is the DIAGLIST `Diagnose` identifier. A marker such as `!` remains separate metadata and is never concatenated into the code identifier.

### `patient_definition`

Primary key `(patient_baseline_id, patient_id)`. Persist display name, age, sex, self-described background, history-availability category, difficulty role, general-health summary, and the synthetic flag. Age and canonical identifiers are typed values rather than embedded in prose.

### `patient_context_item`

Primary key `(patient_baseline_id, patient_id, context_item_id)` with a foreign key to `patient_definition`. Persist `item_type`, `information_source`, learner-visible `display_text`, and positive `canonical_position`. Positions must be unique within one patient.

`item_type` is controlled as `documented_condition`, `self_reported_history`, `current_exam_finding`, `social_context`, `information_boundary`, or `other`. The `information_boundary` value is required by `PATIENT-006/CTX-006-01` and is not an accidental free-text extension. Both loader validation and, where practical, a SQL CHECK should enforce this vocabulary.

### `coding_question`

Primary key `(question_baseline_id, question_id)`. Persist nullable patient identity, title, prompt, `intended_use`, canonical position, nullable `legacy_case_id`, and source/audit reference.

Integrity requirements:

- `intended_use` is limited to `learner_visible` or `verification_only`;
- every learner-visible question has a valid patient foreign key;
- the present eight `VQ-*` rows have no patient and are `verification_only`;
- learner question positions are unique within a patient; and
- no schema constraint encodes a maximum of three questions per patient.

### `question_fact`

Primary key `(question_baseline_id, question_id, fact_key)` with a foreign key to `coding_question`. Persist `value_type`, exactly one matching typed value, optional unit, learner label, and optional `source_context_item_id`.

Supported types are `text`, `integer`, `decimal`, `boolean`, `code`, and `enum`. The loader must reject a row with zero or more than one populated typed-value column, or a populated column inconsistent with `value_type`. If `source_context_item_id` is present for a learner question, the loader must verify that the referenced context item belongs to the same patient. This check may be loader-level if a concise SQL foreign key would require duplicating patient identity into every fact row.

### `question_code_domain`

Primary key `(question_baseline_id, question_id, subset_baseline_id, code)`. Foreign keys target `coding_question` and `catalogue_code`. Persist `relation_kind`, optional `reason_key`, optional `improvement_code`, and source/audit reference.

`relation_kind` is limited to:

- `accepted_reference`;
- `less_specific_supported`;
- `fact_conflict`;
- `temporal_context_conflict`; or
- `source_rule_resolved`.

`less_specific_supported` requires a non-null `improvement_code` that resolves to an `accepted_reference` for the same question. The catalogue foreign key alone does not establish this cross-row semantic condition; the loader must build/inspect the same-question accepted-reference set and reject an improvement code that merely exists in the catalogue or belongs to another question. `fact_conflict` and `temporal_context_conflict` require a controlled non-empty `reason_key`. The loader must reject undefined relation kinds and duplicate question/code relations.

### `question_relation_fact`

Primary key `(question_baseline_id, question_id, subset_baseline_id, code, fact_key)`. Foreign keys target both the parent question/code relation and the parent question fact. `relation_role` is limited to the roles defined by `MODELBASE-0.2`, including `supports_reference`, `conflicts_with_response`, `supports_specificity`, `supports_temporal_context`, and `supports_source_rule`.

Every generic hard relation must have at least one fact link. This prevents a generic `incorrect` relation from being explainable only by hidden author knowledge.

### `question_option`

Primary key `(question_baseline_id, question_id, option_id)` with a foreign key to `coding_question`. `option_kind` is either `code` or `none_of_above`; positions are unique within a question.

For a `code` option, `(subset_baseline_id, code)` is mandatory, must resolve to `catalogue_code`, and must also exist in that question's evaluation domain. For `none_of_above`, both catalogue fields are null. Every learner question has exactly one `none_of_above` row. Verification-only questions have no displayed-option rows.

## 4. Required loader preflight invariants

Before opening a database transaction, the loader must reject the input if any of the following is false:

1. the frozen DIAGLIST/source identity and `SUBSET-0.2` identity are the expected ones;
2. all runtime files have exactly the declared headers and baseline IDs;
3. catalogue identifiers are unique and all referenced codes exist in the 99-record subset;
4. patient and question primary identities are unique;
5. all 25 learner questions have one patient, at least one accepted reference, at least one displayed code option, and exactly one `none_of_above` option;
6. all eight `VQ-*` questions are `verification_only`, patientless, and optionless;
7. all 118 question/code relations have valid relation semantics, every `less_specific_supported.improvement_code` is an `accepted_reference` for that same question, and all generic hard relations have explicit fact links;
8. every displayed code option belongs to the corresponding closed evaluation domain;
9. `Q-004-05/M54.5` and `Q-005-05/I10` are accepted but non-displayed, while the other 23 learner questions display an accepted reference;
10. only `PATIENT-001` contains learner J44/COPD relations;
11. no runtime input header contains `expected_class`, expected determining rule/criterion fields, `required_explanation_elements`, or a reference-response-baseline identifier; and
12. the loader's explicit runtime-file allowlist contains no `verification/reference_responses_0_3_candidate.csv` path; and
13. every patient-context `item_type` belongs to the controlled vocabulary including `information_boundary`.

The current `validate_materialized_design.py` and `validate_forward_verification.py` already establish many of these authoring invariants. The repository loader should enforce the safety-critical subset again at its import boundary rather than assuming an earlier script was run.

## 5. DDL and DML sequence

MySQL schema creation remains separate from runtime data loading because DDL can cause implicit commits. Schema application therefore precedes the data transaction.

Within one DML transaction, insert in dependency order:

1. `catalogue_code`;
2. `patient_definition`;
3. `patient_context_item`;
4. `coding_question`;
5. `question_fact`;
6. `question_code_domain`;
7. `question_relation_fact`;
8. `question_option`;
9. `prototype_baseline` metadata last.

After inserting, read every component back in canonical key order and compare it with the validated input. Commit only if the persisted state is identical. Any failure rolls back all DML for the new baseline.

## 6. Versioning and re-import behaviour

The historical `PROTOBASE-0.2` remains historical evidence and is not silently mutated into the new shape. The new implementation may use working identifier `PROTOBASE-0.3`; the identifier does not become a final frozen baseline merely because an import succeeds.

For any component identifier:

- absent state plus valid input: insert;
- already-present byte/semantic-equivalent state: return `no_op`;
- already-present conflicting state under the same identifier: fail without mutation;
- intentional semantic change: increment the relevant baseline identifier first.

Do not use `INSERT ... ON DUPLICATE KEY UPDATE` to alter versioned scientific data silently.

For development, prefer a fresh database/volume for the new schema rather than destructively rewriting a database containing the old model. Ordinary stack startup must not delete a persistent volume automatically.

## 7. Post-import evidence required before PHP migration

The persistence increment is complete only when the implementation repository can produce fresh evidence for the new model:

- schema application succeeds on a clean supported MySQL instance;
- first import reports `inserted`;
- identical re-import reports `no_op`;
- exact persisted counts are `99 / 6 / 32 / 33 / 88 / 118 / 182 / 120` for catalogue/patient/context/question/fact/domain/relation-link/option rows;
- 25 questions are learner-visible and eight are verification-only;
- foreign-key/referential negative tests reject an orphan patient/question/code/fact relation and roll the transaction back;
- conflicting content under an existing baseline ID is rejected;
- database metadata contains no reference-response table or expected-output columns; and
- the bootstrap/runtime images contain no `RCBASE-0.3` oracle file.

These are new development executions. Historical `PROTOBASE-0.2` MySQL results cannot be cited as evidence that this migration passed.

Only after this persistence boundary is satisfied should the PHP evaluator/repository be changed to consume `coding_question`, typed facts, explicit relation semantics, and fixed option membership.

## 8. Prepared implementation candidate

A repository-ready candidate implementation now accompanies this contract in `prototype_baseline_0_2_design/persistence_candidate/`. It contains the nine-table MySQL DDL, runtime-only manifest with SHA-256 for every allowlisted input, typed preflight/normalization, transactional immutable-baseline loader, schema applicator, database-independent contract tests, and live-MySQL persistence tests.

The database-independent preflight and tests have been executed against the materialized forward data. The execution host used for this increment has no Docker daemon, MySQL server/client, or configured `mysql-connector-python`, so the live-MySQL suite is deliberately left pending and skips rather than substituting another database engine. The candidate therefore does **not** yet satisfy §7's live-persistence evidence boundary. The application-repository agent must integrate the candidate, apply the DDL to a clean supported MySQL instance, obtain `inserted` followed by `no_op`, execute the live persistence tests, and record that evidence before migrating PHP.

The later `UXBASE-0.1` interaction decision does **not** change this physical persistence contract. Immediate feedback, answer locking, patient-completion review and the bounded gameful presentation use evaluator output plus transient frontend playthrough state; no attempt-history, score or gamification table is introduced. The candidate runtime manifest is therefore advanced to requirements metadata `forward-0.7`, while the nine-table schema, runtime data files, row-count expectations and oracle-exclusion boundary remain unchanged.

`APIBASE-0.1` likewise changes no runtime row or table cardinality. It tightens the existing contract by making the current context-type vocabulary explicit, including `information_boundary`, and by restating that same-question accepted-reference validation for specificity improvements is a loader obligation beyond the catalogue foreign key. Its request/visibility/error decisions are implemented later in PHP/API and do not justify additional persistence tables.
