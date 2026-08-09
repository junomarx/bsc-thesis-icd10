# Forward Data Materialization Check — 8 August 2026

**Scope:** design-data preparation and integrity checks only  
**Not covered:** MySQL schema/loader migration, PHP evaluator/API, React UI, `RCBASE-0.3` execution, usability or learning effectiveness

## Inputs

- `QSAUDIT-0.1`
- `RULEBASE-0.2`
- `MODELBASE-0.2`
- frozen `DIAGLIST2026.xlsx`
- DIAGLIST SHA-256: `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b`
- worksheet: `DIAGLIST2026`
- unique `Diagnose` identifiers observed: 13,298

## Materialized forward data

| Dataset property | Result |
|---|---:|
| `SUBSET-0.2` records | 99 |
| unique learner-domain codes | 92 |
| retained historical `SUBSET-0.1` codes | 13 |
| overlap between learner domain and historical subset | 6 |
| union | 99 |
| patients | 6 |
| patient-context rows | 32 |
| learner questions | 25 |
| typed question facts | 60 |
| question-code-domain relations | 100 |
| relation-to-fact links | 142 |
| displayed code options | 95 |
| displayed `none_of_above` options | 25 |
| displayed options total | 120 |

The deterministic source-preparation check regenerated the 99 selected records from the frozen workbook and found the stored `subset_0_2.csv` byte-identical. The resulting CSV SHA-256 is `1ae5c0c7704cbf1b026fee09dea4db26955ec506def024dce709a0541cd40637`.

## Data-contract checks executed

The local validator checked, among other invariants:

- patient question counts exactly `3,3,3,5,5,6` for this content baseline;
- unique/contiguous question and option positions within their parents;
- correct typed-value representation for every question fact;
- every fact's context reference belongs to the same patient as the question;
- every question-domain code belongs to `SUBSET-0.2`;
- exactly one `accepted_reference` in each current learner question domain;
- every `less_specific_supported` relation identifies a same-question accepted improvement code;
- every generic hard/source-specific relation has its required explicit fact linkage;
- every displayed code has a question-domain relation;
- exactly one `none_of_above` option exists per learner question;
- only `Q-004-05` and `Q-005-05` have no displayed accepted reference and therefore form the two positive `none_of_above` controls;
- all J44 learner relations belong exclusively to `Q-001-01` / `PATIENT-001`;
- the complete six-code `J44.0` evaluator family remains present for `Q-001-01`; and
- runtime authoring tables contain no expected-class, expected-rule, expected-criterion or observed-verdict fields.

These checks passed after three forward-design/documentation defects were identified and corrected during materialization and persistence preflight:

1. `QSAUDIT-0.1` had stated 89 unique learner-domain records. Materialization showed that this count omitted three non-displayed J44 evaluator relations. The correct count is 92; the 99-record union was already correct because those records were part of the retained historical subset.
2. forward prose/configuration had used `Z01.8!` as if it were a DIAGLIST identifier. The source-native record is `Diagnose = Z01.8` with `Kennzeichen = !`. The forward artefacts now preserve this distinction.
3. the `PATIENT-004` authoring row initially omitted the `difficulty_role` cell, shifting the general-health summary and synthetic flag one column to the left. Persistence preflight exposed the malformed typed row before SQL import. The authoring source was corrected to `difficulty_role = involved`, the review workbook was regenerated, and validation now checks the exact patient header plus the permitted history/difficulty values, synthetic flag, age, and required text. No patient, question, condition, option, or catalogue selection changed.

## Interpretation

The final local result is **PASS for the stated authoring/data-contract checks only**. It must not be cited as evidence that the redesigned application works. `SUBSET-0.2`, `PATIENTBASE-0.1` and `QUESTIONBASE-0.1` are now concrete forward-design data inputs; a new application baseline can only be claimed after the historical regression fixtures are migrated exactly, the schema/evaluator/UI changes are implemented, an external `RCBASE-0.3` oracle is frozen, and the relevant tests are executed against that software revision.
