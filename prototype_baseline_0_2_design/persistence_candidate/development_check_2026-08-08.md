# MODELBASE-0.2 persistence-candidate development check

**Date:** 8 August 2026  
**Scope:** candidate data boundary, DDL/loader construction and database-independent execution  
**Not claimed:** live MySQL persistence success, PHP/API migration, React migration, `RCBASE-0.3` application verification or final baseline freeze

## Correction surfaced by persistence preflight

The first typed preflight rejected `PATIENT-004` because its materialized row had one field fewer than the declared patient schema. Inspection traced this to the authoring row: `difficulty_role` was omitted, so the health summary occupied that column and the synthetic flag occupied the summary column. The row was corrected to `difficulty_role = involved`; the dataset/review workbook was regenerated; and both the authoring validator and runtime preflight now validate the patient schema semantically. No question or ICD-domain content changed.

## Executed checks

The regenerated materialized data passed its existing forward-design validator with `99` catalogue records, `6` patients, `25` learner questions, `60` learner facts, `100` learner question/code relations, `142` learner relation/fact links and `120` displayed options.

The runtime preflight then normalized the combined learner plus legacy runtime model as:

| Component | Rows |
|---|---:|
| catalogue | 99 |
| patients | 6 |
| patient context | 32 |
| questions | 33 |
| question facts | 88 |
| question/code relations | 118 |
| relation/fact links | 182 |
| displayed options | 120 |

Canonical normalized-runtime SHA-256 after advancing the requirements metadata from forward revision `0.6` to `0.7`: `2b20a3f336ed3106749eb34020a52499c22800a72e456d992288ae073f0d1f51`. The digest changes because baseline metadata is part of the canonical runtime representation; the catalogue, patient/question files, row counts, schema and semantic relations are unchanged by `UXBASE-0.1`.

The database-independent unit suite executed all eight contract tests successfully. These cover the exact hash-bound runtime allowlist/oracle exclusion, normalized counts, variable patient question cardinalities, `none_of_above` controls, J44 learner confinement, legacy regression-fixture shape, canonical runtime digest and the nine-table/oracle-free executable DDL structure.

## Live database boundary

The current execution host exposes no Docker executable, MySQL server/client, configured MySQL database, or usable `mysql-connector-python` integration environment. The six live-MySQL tests therefore all report `skipped` by design. No alternative database was substituted.

Consequently, the correct status is:

- runtime-data/materialization validation: **executed successfully**;
- database-independent persistence-contract tests: **8/8 executed successfully**;
- live MySQL schema/load/read-back/re-import tests: **pending in the real application environment**;
- application verification: **not performed**.

The next agent must obtain fresh MySQL evidence for this exact candidate before changing the PHP evaluator/API.

## 9 August interface-contract clarification re-check

`APIBASE-0.1` resolved several API/model ambiguities without changing the materialized runtime rows or canonical runtime digest. The persistence candidate was tightened in two places: `information_boundary` is now part of an explicitly enforced patient-context type vocabulary in both DDL and runtime preflight, and the existing same-question `accepted_reference` check for every specificity `improvement_code` is now called out explicitly in the contract/tests.

The materialized-design validator, forward-oracle validator, runtime preflight, and the eight database-independent persistence-contract tests were re-executed successfully on 9 August. The oracle check additionally confirms that all 25 `none_of_above` expectations require `displayed_accepted_response_exists|reference_code`. The canonical runtime digest remains `2b20a3f336ed3106749eb34020a52499c22800a72e456d992288ae073f0d1f51` because no runtime row or baseline metadata value changed. Live MySQL execution remains pending and is not converted into a pass by this re-check.
