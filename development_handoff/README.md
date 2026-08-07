# Austrian ICD-10 prototype development handoff

**Snapshot date:** 7 August 2026  
**Purpose:** portable input package for continuing implementation in the actual
application repository  
**State:** working specifications plus candidate implementation scaffolding;
not a verified software release

## Read this first

This package deliberately separates **what has been specified** from **what has
actually been implemented and tested in the project**. An earlier handoff
incorrectly promoted exploratory sandbox work to project implementation
evidence. The corrected documents in this package withdraw those claims.

No `PASS`, `5/5`, database insertion result, Git synchronization result or
Docker/Compose execution result is inherited by this bundle. Candidate scripts
and tests are supplied because they embody useful work, but their behaviour has
to be inspected and executed after adoption in the real repository.

Recommended reading order:

1. `handoff/CODEX_VSCODE_CONTINUATION_INSTRUCTION.md`
2. `handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md`
3. `control/chapter3_input_source_baseline_register.md`
4. `control/chapter3_requirements_catalogue.md`
5. `control/chapter3_domain_error_taxonomy_and_classification_baseline.md`
6. `control/chapter3_rule_catalogue.md`
7. `control/chapter3_reference_case_coverage_plan.md`
8. `control/chapter3_data_model_and_interaction_baseline.md`
9. `control/chapter3_test_catalogue.md`
10. `control/chapter_3_methods_and_practical_work_specification.md`
11. candidate implementation files under `candidate/`

If the receiving Codex session has already seen the erroneous older handoff,
paste `handoff/CODEX_HANDOFF_CORRECTION_PROMPT.md` into that session before
continuing.

## State of the work

### Specified and available as working project-control material

- authoritative-source roles and provenance boundaries;
- 31 requirements/constraints with acceptance and trace links;
- four coding-response patterns and the three artefact feedback classes;
- explicit rule catalogue and deterministic precedence;
- coverage-derived working 13-code subset proposal;
- four working synthetic base-case definitions;
- 14 working reference-response expectations kept outside runtime input;
- single-code interaction/data model;
- proposed four-table runtime relational model;
- 17 technical test specifications and required boundary vectors;
- intended React + PHP + MySQL + Python architecture; and
- intended Docker Compose/Git-based reproducibility approach.

These specifications are not final `1.0` frozen baselines. The current case
and reference suite still requires a pre-freeze coverage review.

### Supplied as candidate implementation material

`candidate/prototype_baseline_0_1/` contains the exploratory Python/SQL/data
pipeline material corresponding to the working model:

- machine-readable subset definition;
- candidate derived 13-record subset;
- candidate case and case-code-domain CSV records;
- candidate independent reference-response oracle;
- MySQL schema;
- deterministic subset-preparation script;
- runtime-data parser/validator;
- schema/bootstrap/load scripts; and
- candidate data-contract/persistence tests.

`candidate/prototype_stack/` contains the proposed Docker Compose and Git
synchronization scaffold, configuration examples and shell wrapper.

These directories are supplied for review and adoption. Their prior exploratory
execution is not verification evidence for the actual project.

### Not yet established by this package

- adoption of the candidate pipeline into the actual application repository;
- a successful data-pipeline run recorded in that repository/environment;
- a successful MySQL persistence integration recorded there;
- a successfully running Docker Compose stack;
- PHP repository/data-access implementation;
- executable `RULE-*` evaluator;
- PHP API;
- React learner interface;
- executable rule/API/reference/end-to-end suite;
- final case/reference coverage decision;
- frozen application commit/environment; and
- principal technical verification results.

## Authoritative source copies included

The bundle includes the source files needed to reconstruct or substantiate the
working executable-domain decisions, plus the contextual sources already used
to delimit hospital/LKF/extramural scope. The recorded SHA-256 values are
source-byte fingerprints, not software-test verdicts.

### Core

| Source | SHA-256 |
|---|---|
| `DIAGLIST2026.xlsx` | `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` |
| `ICD10_BMASGPK_2026_Systematisch.pdf` | `cc46dbd161c6d4d75f4196a25139b1b200dcb2f24858f2bedacb81295604de2d` |
| `BMASGPK_Medizinische_Dokumentation_2026.pdf` | `69b37f1879acb5cda63eca30086e61a1f17b058bb26fc629d6a64bd25736653b` |

### Contextual / conditional

| Source | SHA-256 |
|---|---|
| `BMASGPK_LKF_Systembeschreibung_2026.pdf` | `3b2645550b1c34fef7f382951430995b8e06cc6ff2599ee3b43c8a73fa741e0a` |
| `BMASGPK_LKF_Spitalsambulant_2026.pdf` | `fdfc884ea5bc583a9af39d02f105f295f572d290c30da0304a3f97afe4a2b724` |
| `BMASGPK_Handbuch_Extramural_2026.pdf` | `9dce0bc14c4836c6fb966621b683879d1546e5f27cfb79361432ad944763bc80` |
| `BMASGPK_FAQ_Extramural_2025.pdf` | `b827597088def7692c106ae6a85b2ceea2ae852d6667365c6064c647d8796912` |
| `ICD-10_Extramural.xlsx` | `ad305a23dbc038dd5cf136739fe90f5eb6af61f8bf07c569c358b909ca64436d` |

Recompute these hashes when the source files are placed under the actual
project's provenance procedure. A changed byte sequence must not silently
replace a working source identity.

## Package structure

```text
README_FIRST.md
handoff/
  CODEX_VSCODE_CONTINUATION_INSTRUCTION.md
  ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md
  CODEX_HANDOFF_CORRECTION_PROMPT.md
control/
  chapter3_input_source_baseline_register.md
  chapter3_requirements_catalogue.md
  chapter3_domain_error_taxonomy_and_classification_baseline.md
  chapter3_rule_catalogue.md
  chapter3_reference_case_coverage_plan.md
  chapter3_data_model_and_interaction_baseline.md
  chapter3_test_catalogue.md
  chapter_3_methods_and_practical_work_specification.md
candidate/
  prototype_baseline_0_1/
  prototype_stack/
sources/
  core/
  contextual/
  bibliography/
```

Sandbox-only execution notes, caches, rendered review images, temporary files,
and previous claimed development-check reports are intentionally excluded.

## First concrete development milestone

The first milestone in the real repository is not "implement React". It is to
establish a trustworthy lower layer:

```text
repository inventory
        -> candidate data-pipeline review/adoption
        -> reproduce candidate subset from bundled DIAGLIST
        -> execute/record structural checks
        -> establish and execute MySQL persistence path
        -> establish actual container/app build contract
```

Only after that evidence exists should development proceed to the PHP
repository/evaluator layer and then to the API and React interface.
