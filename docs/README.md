# Implementation documentation set

This folder documents the **implementation**, as distinct from the
**specification** it implements. The control artefacts at the repository
root (`chapter3_input_source_baseline_register.md`,
`chapter3_requirements_catalogue.md`,
`chapter3_domain_error_taxonomy_and_classification_baseline.md`,
`chapter3_rule_catalogue.md`, `chapter3_reference_case_coverage_plan.md`,
`chapter3_data_model_and_interaction_baseline.md`,
`chapter3_test_catalogue.md`) remain the upstream authority for *what the
software is supposed to do and why*. Nothing in this folder overrides them;
where the two disagree, the control artefacts win and the discrepancy should
be logged in [CHANGELOG.md](CHANGELOG.md).

| File | Answers | Audience |
|---|---|---|
| [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) | *Why does the implementation look the way it does?* Technology choices, architectural decisions, UI/backend design principles, how the build fits the project's Design Science Research framing. | Thesis chapter drafting; anyone who needs the rationale, not just the result. |
| [IMPLEMENTATION_SPECIFICATION.md](IMPLEMENTATION_SPECIFICATION.md) | *What exactly was built?* Concrete data model, rule-engine contract, API contract, frontend structure, build/deploy contract, as it exists right now. | Anyone extending or auditing the code; the appendix-style reference. |
| [REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md) | *Does every requirement actually have a verification destination?* All 31 `REQ-*` entries audited against real evidence, not planned intent. | `REQ-TRC-01` compliance; a ready draft for the thesis appendix. |
| [CHANGELOG.md](CHANGELOG.md) | *What changed, and when?* Dated, chronological log of implementation increments. | Tracing how the current state was reached; thesis "development process" narrative. |

## Keeping this set current

This documentation is meant to track the implementation continuously, not to
be a one-time snapshot. The repository's `CLAUDE.md` records the concrete
rule for when each file must be touched. In short: **every material
implementation change gets a CHANGELOG entry; every change to a contract
(schema, rule, API, build) updates the specification; every new design
decision gets a rationale note in the development documentation.**
