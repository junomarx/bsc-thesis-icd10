# Forward Implementation Instruction

**Instruction ID:** `IMPL-HANDOFF-0.5`  
**Date:** 8 August 2026  
**Clarified:** 9 August 2026 through `APIBASE-0.1`  
**Audience:** coding agent working in the actual application repository  
**Status:** forward implementation instruction; do not treat it as evidence that the redesign has already been implemented

## Objective

Bring the existing Austrian ICD-10 learning prototype from the historical `CASEBASE-0.2` single-question learner interface to the current six-patient, 25-question forward design while preserving deterministic coding semantics, the historical regression obligations and a strict runtime/oracle separation. Once the functional redesign is stable, perform the accepted bounded UX/UI and gameful-presentation iteration before freeze.

The scientific priority remains the traceable coding/evaluation concept. The visual stretch goal is important for the demonstrator, but it must never change what input is classified, how it is classified or what the independent verification oracle expects.

## Read before editing

Use these forward control documents together with the repository's own current `docs/` history and traceability files:

1. `HANDOFF.md`, especially §8;
2. `chapter3_requirements_forward_revision_0_7.md`;
3. `chapter3_rule_catalogue_0_2.md` (`RULEBASE-0.2`);
4. `chapter3_data_model_and_interaction_baseline_0_2.md` (`MODELBASE-0.2`);
5. `chapter3_sql_loader_migration_contract_0_2.md` (`DATAMIG-0.2`);
6. `chapter3_patient_and_question_design_plan.md`;
7. `chapter3_reference_case_coverage_plan_forward_0_3.md`;
8. `chapter3_ux_ui_gamification_concept_0_1.md` (`UXBASE-0.1`);
9. `chapter3_api_and_feedback_contract_0_1.md` (`APIBASE-0.1`);
10. `prototype_baseline_0_2_design/README.md` and its versioned runtime/verification data.

Where an old repository document describes what was actually implemented and a forward document describes the new target, preserve both histories. Do not rewrite an old test result as though it applies to the redesign.

## Non-negotiable semantic boundaries

- The active learner content is six synthetic patients and 25 atomic questions with data-driven counts `3/3/3/5/5/6`.
- Only `PATIENT-001` contains learner-facing COPD/J44 content.
- A question accepts exactly one tagged response: an ICD code or `none_of_above`.
- Only typed `question_fact` data may affect classification. Patient-context prose is not an implicit evaluator input.
- Evaluation-domain membership and displayed-option membership are separate concepts.
- `none_of_above` is not an ICD code and follows `RULE-NOA-01` set semantics.
- Evaluation has one wire format: `{response:{type:"code",code:...}}` or `{response:{type:"none_of_above"}}`. `option_id` is frontend state/presentation identity, not an alternative POST body.
- Raw `question_fact` rows are evaluator-internal before submission. Learner GET responses use prompt/patient context; `learner_label` is not a visibility flag.
- Learner GET endpoints return `404` for `verification_only`; the regression harness may POST those known IDs to the common evaluator but receives no public hidden-fixture read route.
- HTTP syntax/tag failures are controller errors (`400`); only syntactically valid responses reach `RULE-GATE-01` and may become `not_evaluated`.
- The runtime database contains no expected feedback classes, expected rules/criteria or `RC-*` answer key.
- The eight historical technical cases remain hidden regression fixtures and preserve all 18 historical obligations.
- Candidate `RCBASE-0.3` is external verification data. The 125 new learner expectations remain pending their stated human/source oracle audit.
- Four reconstructed historical `0.2` rows require one pre-freeze diff against the original files when they become available.
- Every current `RULE-NOA-01` result returns `displayed_accepted_response_exists` and post-submission `reference_code`; use `determining_rule`, never `criterion`, as rule identity.

## Functional learner interaction to implement

The required learner lifecycle is:

```text
orientation -> patient roster -> dossier/question
            -> submit -> locked immediate feedback -> next
            -> patient completion review -> replay/another patient
```

After successful evaluation, the response is read-only for that playthrough. Feedback must expose the evaluator's `correct`/`suboptimal`/`incorrect` result, a concise task-focused explanation and, where the model defines it, the supported reference/improvement. Keep determining criterion/rule information available for technical inspection even if the primary learner view hides it behind details.

After the last question, show raw counts of the three evaluator classes and a read-only per-question review. Do not invent a weighted score, percentage grade or mastery measure.

Question order is randomized per playthrough. Option membership is fixed and versioned; presentation order may be permuted. Replaying creates a fresh transient state. No account, longitudinal attempt-history store or server-side scoring database is required.

## Bounded UX/UI and gameful stretch iteration

After the functional flow works, implement `UXBASE-0.1` as a separate, reviewable frontend increment. Aim for a polished clinical-learning demonstrator rather than an arcade game:

- self-explanatory home/orientation with purpose, workflow, three-class legend and educational-use notice;
- six patient case-file cards with age, question count, neutral `foundational`/`involved` cue and session-local completion;
- reopenable patient dossier during questions;
- clear `Question x of n` and patient-completion progress;
- deliberate feedback and completion states, coherent typography/surfaces/icons, responsive layout;
- classification conveyed by label/icon as well as colour, visible keyboard focus, keyboard-operable primary controls and reduced-motion handling;
- replay and restrained completion acknowledgement as the principal gameful mechanics.

Do not add weighted points, leaderboards, ranking, lives, timers/speed bonuses, daily streaks/accounts, random content rewards or badges that imply measured competence. Do not use stereotyped demographic patient imagery. Do not claim formal WCAG conformance, usability improvement, engagement improvement or learning effectiveness unless those outcomes are separately tested, which is not part of the present evaluation plan.

## Required implementation order

1. Inventory the actual repository and merge forward requirement revision 0.7 into its current requirements/traceability catalogue without overwriting historical evidence.
2. Integrate the `MODELBASE-0.2` persistence candidate into a clean MySQL development environment.
3. Obtain fresh persistence evidence: schema creation, first load `inserted`, identical re-import `no_op`, exact read-back/count checks, referential/conflict negatives and oracle absence.
4. Migrate PHP repositories/value objects/evaluator to `RULEBASE-0.2` and normalized patient/question inputs.
5. Replace/extend case-centric API contracts with the exact `APIBASE-0.1` patient/question boundary: learner GETs hide verification fixtures, while the shared POST evaluator accepts their known IDs for regression.
6. Implement the functional React lifecycle including immediate feedback, answer locking, patient review, randomization and replay.
7. Apply the `UXBASE-0.1` polish as a separate bounded frontend iteration.
8. Extend unit/integration/Selenium E2E coverage for the new data/rule/API/interaction obligations and rerun all 18 historical regressions.
9. Complete the stated oracle/source audit and historical-row reconciliation.
10. Only then freeze the application/data/rule/oracle/test identities and execute the principal verification run.

## Evidence and documentation rule

Record a result only when it has actually been executed in the repository/environment you are working in. Keep at least these states distinct: `specified`, `implemented`, `development-tested`, and `frozen/verified`. Historical `85/85` application tests describe the old implementation and must not be copied forward as evidence for the new one.

Update the repository's changelog/development documentation and requirements traceability as each increment lands. Preserve test commands and outputs needed for Chapter 3. If a UX request requires a semantic data/rule/API change, stop and version that change explicitly before implementing it.

For the later thesis prose, retain evidence for the actual development sequence. If the UX iteration is completed before freeze, it can be reported in Chapter 3 as a bounded formative Design Science build/evaluate/refine episode based on inspection of the functional interface. Do not invent learner/user feedback that was never collected.
