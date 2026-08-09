# Correction prompt for an existing Codex / VSCode session

Use the following prompt if a Codex session has already seen the earlier
handoff or conversation that described the data/persistence and Docker material
as implemented and tested.

---

Important correction to the project handoff:

An earlier handoff omitted a crucial provenance distinction. It described
`prototype_baseline_0_1/` and `prototype_stack/`, together with exploratory
`PASS`, `5/5`, MySQL `inserted`/`no_op`, and similar observations, as though
they were established results from the actual application project. Do not rely
on those claims.

The correct state is:

- the methodological control artefacts (`REQ-*`, `PAT-*`, `RULE-*`,
  `CASEPLAN-*`, `MODELBASE-*`, `TEST-*`) are working specifications;
- the proposed subset, case records, reference-response oracle, Python/SQL data
  pipeline and Docker/shell stack material are candidate artefacts prepared
  during exploratory work outside the actual application repository;
- their presence does not prove that they have been integrated, executed or
  verified in this repository;
- no historical sandbox test verdict is inherited as project evidence; and
- final verification evidence must come from tests actually executed and
  recorded against the adopted and ultimately frozen project state.

Treat the corrected `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` and
`CODEX_VSCODE_CONTINUATION_INSTRUCTION.md` as superseding the earlier handoff
where project state is concerned. Do not discard the candidate pipeline/stack
files merely because their previous status was overstated. Inspect them as
potential implementation inputs and compare them with the specifications.

Your first action must be a read-only inventory of the actual repository.
Report what genuinely exists before adopting or writing implementation code.
Then proceed from the earliest unmet dependency: candidate data-pipeline
adoption/reproduction, persistence integration, application/Compose contract,
PHP repository and evaluator, API, React UI, and executable verification.

For every test or check from this point forward, distinguish:

1. specified but not executed;
2. executed in development, with the actual command/environment/result
   recorded; and
3. principal verification against the final frozen baseline.

Never convert an old sandbox result into category 2 or 3. Never modify a
predefined expected result simply because the implementation disagrees with
it; investigate the source, specification, fixture and implementation layers
separately.

---
