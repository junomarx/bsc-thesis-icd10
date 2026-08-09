# Forward data materialization for the redesigned learner model

**Status:** materialized forward design plus a repository-ready persistence candidate. The SQL/loader candidate has passed database-independent checks but has not yet been integrated/proven against live MySQL in the actual application repository. PHP, API and React have not been migrated, and this directory contains no new application-verification result.

## Baseline identities

- catalogue subset: `SUBSET-0.2`
- learner patient data: `PATIENTBASE-0.1`
- learner question data: `QUESTIONBASE-0.1`
- upstream source audit: `QSAUDIT-0.1`
- rule design: `RULEBASE-0.2`
- model design: `MODELBASE-0.2`
- interaction/presentation supplement: `UXBASE-0.1`
- API/feedback clarification: `APIBASE-0.1`
- current forward requirements delta: `0.7`
- candidate external verification oracle: `RCBASE-0.3`

The frozen machine-readable source is `DIAGLIST2026.xlsx`, worksheet `DIAGLIST2026`, SHA-256 `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b`.

## Materialized runtime-authoring data

`config/subset_definition_0_2.json` contains the explicit 99-code selection and source identity. `data/subset_0_2.csv` is the exact four-field DIAGLIST projection for those codes.

The learner model is represented by:

- `data/patients_0_1.csv`
- `data/patient_context_items_0_1.csv`
- `data/questions_0_1.csv`
- `data/question_facts_0_1.csv`
- `data/question_code_domain_0_1.csv`
- `data/question_relation_facts_0_1.csv`
- `data/question_options_0_1.csv`

`review/materialized_dataset_review.xlsx` is a human-review convenience view of the same data. It is not a runtime input.

`APIBASE-0.1` does not change these rows. It fixes their application-facing interpretation: raw `question_fact` rows remain evaluator-internal before submission, `information_boundary` is a controlled patient-context item type, and evaluation uses one tagged response contract rather than the earlier planning-stage `option_id` POST proposal.

`scripts/prepare_subset_0_2.py` is the reproducible source-preparation entry point. It reads the 99 selected codes from `config/subset_definition_0_2.json`, verifies the frozen workbook checksum/worksheet/identifier count, and can either regenerate the CSV or check an existing projection byte-for-byte. Code selection therefore remains parameterized by the versioned definition rather than hard-coded into Python.

## Intended counts

| Item | Count |
|---|---:|
| DIAGLIST subset records | 99 |
| patients | 6 |
| patient context items | 32 |
| learner questions | 25 |
| typed question facts | 60 |
| evaluable question-to-code relations | 100 |
| relation-to-fact links | 142 |
| displayed code options | 95 |
| displayed `none_of_above` options | 25 |
| total displayed options | 120 |

Patient question counts are `3, 3, 3, 5, 5, 6`. These are content counts, not schema limits.

## Authority and safety boundary

All patients are synthetic. The data encode documented diagnoses/conditions/findings for an educational coding demonstrator; they do not constitute clinical diagnosis or clinical decision support. Patient context is learner-facing presentation data. Only typed `question_fact` values may be consumed by the evaluator.

`question_code_domain` contains semantic relations such as `accepted_reference`, `less_specific_supported` and `fact_conflict`. It deliberately does not contain verification expected classes or determining-rule answers. The future `RCBASE-0.3` oracle must remain outside the runtime database and evaluator path.

## Important controls

- Only `PATIENT-001` contains COPD/J44 learner content.
- `Q-004-05` has hidden accepted code `M54.5`; therefore the displayed `none_of_above` response is correct.
- `Q-005-05` has hidden accepted code `I10`; therefore the displayed `none_of_above` response is correct.
- Every other learner question displays its accepted reference, so `none_of_above` is incorrect.
- `Q-001-01` retains the full six-code J44.0 evaluation family, while only three code options are shown.
- DIAGLIST code `Z01.8` with marker `!` remains outside `SUBSET-0.2` as a deliberate out-of-scope/status control. The `!` is `Kennzeichen` metadata, not part of the code identifier.

## Legacy regression bridge and candidate oracle

The eight historical `CASEBASE-0.2` fixtures are now represented as `verification_only` questions in four separate runtime-authoring files named `verification_*_legacy_0_1.csv`. They have no patient and no learner-facing options. Their 18 historical response obligations remain external to runtime data.

Fourteen of those obligations are exact semantic carry-forwards from the available `RCBASE-0.1`; the implementation documentation explicitly records that `RC-001-*` through `RC-004-*` were unchanged apart from the `0.2` baseline identifiers. The four rows added by `RCBASE-0.2` (`RC-005-01` through `RC-008-01`) are temporarily reconstructed from `CHANGELOG.md` and `DEVELOPMENT_DOCUMENTATION.md`, which record the decisive facts, response codes, expected results and integration spot checks. The reconstruction files live under `migration/` and are explicitly marked `reconstructed_from_implementation_documentation`. They must be diffed against the original `0.2` CSVs before a final freeze; this is a reconciliation gate, not a development blocker.

`verification/reference_responses_0_3_candidate.csv` is the candidate external `RCBASE-0.3` oracle. It contains 143 predefined expectations: 125 learner responses (100 code-domain relations plus 25 `none_of_above` responses) and 18 legacy regressions. Its expected-class distribution is 33 `correct`, 20 `suboptimal` and 90 `incorrect`. The file is deliberately outside the runtime `data/` path.

The candidate oracle has passed structural/data-contract checks only. Its 125 new learner expectations remain marked `forward_specification_derived_pending_human_oracle_audit`; this status must not be represented as independent expert validation or application verification.

`persistence_candidate/` now implements the proposed nine-table `MODELBASE-0.2` DDL, hash-bound runtime preflight, immutable transactional loader and pure/live test harnesses. Its database-independent contract suite passes; the live-MySQL suite remains pending and deliberately skips on this host. Requirements revision 0.7/`UXBASE-0.1` does not alter this materialized clinical/runtime dataset: immediate feedback, completion review, progress and replay are downstream interaction concerns and introduce no learner-history or scoring persistence. The next implementation increment is therefore to integrate and prove the candidate against a clean MySQL instance in the actual application repository. Only after that boundary succeeds should PHP evaluator/API changes begin, followed by the functional React feedback/review workflow, the bounded UX/gameful refinement and finally `RCBASE-0.3` execution against a frozen application revision.
