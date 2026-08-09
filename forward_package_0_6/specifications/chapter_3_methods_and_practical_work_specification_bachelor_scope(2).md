---
title: "Chapter 3 Specification: Methods and Practical Work - Bachelor Scope"
author: "Juno Marx - Bachelor Thesis Working Specification"
date: "August 2026"
lang: en-GB
papersize: a4
geometry: "margin=20mm"
fontsize: 10pt
linestretch: 1.08
toc: true
toc-depth: 3
numbersections: false
colorlinks: true
linkcolor: black
urlcolor: blue
header-includes:
  - |
    \usepackage{fancyhdr}
    \pagestyle{fancy}
    \fancyhf{}
    \fancyhead[L]{Bachelor Thesis Chapter Specification}
    \fancyhead[R]{Juno Marx}
    \fancyfoot[C]{\thepage}
    \setlength{\headheight}{14pt}
  - |
    \usepackage{enumitem}
    \setlist{leftmargin=*,itemsep=0.20em,topsep=0.35em}
  - |
    \usepackage{microtype}
  - |
    \usepackage{booktabs,longtable,array}
    \renewcommand{\arraystretch}{1.12}
---

**Recommended final-thesis page budget:** approximately 12-16 pages, excluding detailed appendices. This remains the principal practical chapter, but the advisor's approximate 50-page whole-thesis target requires a substantially flatter structure than the previous seven-section plan.

Chapter 3 documents **what was actually done** to develop and technically examine the artefact. General ICD, learning, software-engineering, testing, and Design Science Research (DSR) theory belongs in Chapter 2. Chapter 3 may cite a concrete framework, standard, or official source where required for provenance or procedure, but it should not reproduce the theoretical literature review.

Following the advisor call, the chapter is reorganised around two major practical activities only:

1. **Prototype Development**, containing the concrete development process, requirements, data basis/subset, artefact and rule models, architecture, technology choices, and implementation; and
2. **Verification and Testing**, containing predefined reference cases, targeted test design, execution procedure, and conformance criteria.

This hierarchy is intentionally flatter than the previous specification. Internal checkpoints remain detailed, but they must not automatically become additional numbered headings.

## Controlling research question and internal analytical prompts

**Main research question - current working formulation, pending explicit advisor confirmation**

> How can a web-based learning prototype for the Austrian ICD-10 catalogue be designed, implemented, and technically verified to classify selected coding responses as correct, suboptimal, or incorrect through explicit rule-based feedback?

The advisor stated that formal subquestions are unnecessary if they create additional work. The former subquestions are retained below only as **internal analytical prompts** for traceability and coverage. They should not automatically appear in the final thesis as formal research questions.

**Internal analytical prompt A - construction logic**

> Which selected coding-error patterns identified in the literature and official Austrian guidance can be represented within the defined prototype scope, and how can they be translated into explicit decision criteria for the three feedback classifications?

**Internal analytical prompt B - verification logic**

> To what extent does the implemented feedback behaviour conform to the predefined classifications and explanations across the fixed reference-case suite and targeted software tests?

The two prompts remain useful internally because they divide the work into construction and verification, which now corresponds directly to Sections 3.1 and 3.2.

## Fixed bachelor-level boundaries and current decision status

### Fixed boundaries

- The artefact uses a bounded and versioned subset of the Austrian ICD-10 catalogue rather than comprehensive catalogue coverage.
- All cases are synthetic. No real patient data are processed.
- The artefact evaluates represented code selections against represented case information and explicit rules. It does not determine a patient's true diagnosis.
- The software is an educational demonstrator, not a medical device, clinical decision-support, reimbursement, or production system.
- React, PHP, MySQL, and Python are implementation means, not the principal scientific contribution.
- The scientific core is the traceable relationship among source evidence, requirements, catalogue/patient/question model, error/feedback model, executable rules, reference responses, implementation, and verification evidence.
- The thesis does not establish usability, acceptance, learning effectiveness, knowledge retention, independent clinical correctness, or reduced real-world coding errors.
- No learner study, real-patient study, production hardening, comprehensive security evaluation, or exhaustive catalogue coverage should be introduced merely to enlarge the practical part.

### Methodological decisions fixed by the advisor call

- Generic DSR explanation belongs in Chapter 2. Chapter 3 should cross-reference it and document only the project's concrete application.
- The exploratory V-model/Lastenheft discussion was superseded after the DSR framing was clarified. Do not add a V-model, formal Lastenheft, or second lifecycle methodology unless independently justified by work actually performed.
- Requirements engineering is part of prototype development rather than a top-level section of equal rank.
- Prototype-specific artefact concept, system architecture, technology choices, and implementation belong under prototype development.
- Verification and Testing should form the second major section.
- Problem identification and objective definition already established in Chapter 1 should be cross-referenced rather than repeated as new Chapter 3 content.
- No user testing is required for the current thesis claim; its absence must be reported as a limitation rather than disguised.
- No fixed minimum number of cases/codes and no mandatory medical domain are required. The bounded catalogue and case selection must instead be justified by predefined coverage of the intended feedback classes, included error patterns, and both straightforward and at least some more difficult or ambiguous coding situations.
- A medical domain may be used if it improves coherence, but domain selection is an optional design decision rather than an adequacy criterion. Multiple deliberately selected case groups are equally permissible when they better exercise the intended rules.
- Representative cases are to be explained in the main text; the complete reference-case suite is to be documented in an appendix. The Austrian catalogue version must be explicit.
- No binding frontend, backend, or architectural technology has been prescribed. Technology choices remain project decisions and must be justified from the requirements.
- The architecture must keep reference data, evaluation logic, and presentation responsibilities clearly separated; explicit version control, reproducibility, understandable feedback, systematic testability, and appropriate documentation of architecture/data/interfaces are explicit design concerns.
- UI sophistication remains secondary to the traceable coding concept, reproducible evaluation logic, understandable feedback, and demonstrable technical function. Because core development is sufficiently advanced, a bounded pre-freeze UX/UI and gameful-presentation iteration is an accepted stretch goal. It may improve orientation, workflow clarity and presentational quality, but it does not add usability/engagement/learning-effect claims.
- The `suboptimal` category must be operationalised through explicit, objectively assessable criteria. It must not remain an intuitive middle category between `correct` and `incorrect`.

### Project decisions fixed during authoritative-source operationalisation

- `DOMBASE-0.1` fixes the initial response-pattern taxonomy to required coding depth (`PAT-DEPTH-01`), source-backed insufficient specificity (`PAT-SPEC-01`), explicit case-evidence conflict (`PAT-EVID-01`), and hospital context/status incompatibility (`PAT-STATUS-01`). This is a purposive implementation set, not a claim to exhaust Austrian coding errors.
- `PAT-SPEC-01` is the sole initial `suboptimal` trigger. It applies only after every hard condition has passed and only where the represented case already supplies the differentiating fact and an official Austrian rule makes the better-supported response deterministic. The initial proof family is COPD severity coding using the explicit FEV1 mapping and the official `Unzureichend abgeklärte Hauptdiagnose` warning criterion.
- Hard invalidating conditions remain `incorrect` and precede graded specificity. A version-absent, malformed, or merely out-of-prototype-subset input is treated as validation/scope handling rather than being silently labelled `incorrect`.
- A narrowly bounded hospital-sector `!` status rule is executable. Extramural-specific coding behaviour is not part of the current executable rule baseline; extramural sources remain necessary for source/context delimitation.

### Project decisions fixed by the subsequent learner-model and interaction redesign

- The learner-facing `CASEBASE-0.2` one-case/one-question model was found pedagogically inadequate because several technically useful fixtures exposed degenerate single-code response domains. Those historical cases remain regression evidence; they no longer define the learner experience.
- `SUBSET-0.2` contains 99 exact DIAGLIST records required by the redesigned learner domains plus retained historical regression obligations. `PATIENTBASE-0.1` contains six synthetic patients and `QUESTIONBASE-0.1` contains 25 atomic learner questions distributed `3/3/3/5/5/6`; only one learner patient contains J44/COPD content.
- `MODELBASE-0.2` separates patient context, atomic question facts, closed evaluation domains and displayed options. `RULEBASE-0.2` retains source-specific legacy rules and adds question-scoped semantic relations plus explicit `none_of_above` handling.
- `APIBASE-0.1` resolves the pre-implementation interface boundary: one tagged evaluation request is authoritative; raw question facts remain evaluator-internal before submission; learner GET routes hide `verification_only` questions while the common POST evaluator remains available to the regression harness; malformed/unsupported request shapes are rejected at the HTTP boundary rather than being represented as semantic `RULE-GATE-01` failures.
- Every learner question has fixed option membership including `none_of_above`; question order may be randomized between playthroughs, but randomization may never change semantic content or answer truth.
- `UXBASE-0.1` fixes the learner interaction: submission is followed immediately by a locked, elaborated feedback state; after the final question the learner receives a read-only patient review with raw `correct`/`suboptimal`/`incorrect` counts and per-question results. No arbitrary weighted score is introduced.
- The accepted UX/UI stretch goal adds orientation, patient case-file presentation, progress/completion cues, replay and restrained gameful presentation. Points, leaderboards, timers, lives and mechanics that alter coding/evaluation truth remain excluded. No usability or learning-effect evaluation is inferred from visual refinement.
- Candidate `RCBASE-0.3` contains 143 predefined response expectations: 125 learner option/domain expectations plus 18 retained historical regression obligations. Its existence is a design/verification input, not evidence that the redesigned application has already passed those expectations.

### Still unresolved and therefore treated conservatively

- The advisor has not explicitly confirmed the current main-research-question wording.
- The forward redesign is materialized but not frozen: `SUBSET-0.2`/`PATIENTBASE-0.1`/`QUESTIONBASE-0.1` and candidate `RCBASE-0.3` remain working identities until the migrated application, human/source oracle audit and legacy-row reconciliation are complete. Their current counts are design inputs, not a final `1.0` verification baseline.
- The advisor's latest reply discusses systematic testability but does not explicitly answer whether internal technical reference-case verification alone is sufficient. The possible need for additional external domain-expert review therefore remains unresolved and must not be inferred either way.
- The exact location of test results remains provisional. This specification adopts the clean working boundary that Chapter 3 defines the method and expected outcomes, while the Results chapter reports observed outcomes and deviations.

## Citation and evidence rule for Chapter 3

- Chapter 3 should contain substantially less literature than Chapter 2.
- Cite Chapter 2 rather than re-explaining DSR, requirements, feedback, testing, or rule-system theory.
- Cite official Austrian sources where exact source/version provenance is part of the concrete dataset or rule basis.
- Cite a methodological source in Chapter 3 only where the concrete procedure is explicitly mapped to it and the cross-reference to Chapter 2 is insufficient.
- Retain exact printed-page locators in the working source register, requirement/rule provenance, and source audits. In thesis-facing prose, follow the supervisor-requested HCW presentation convention and omit pinpoint pages from numbered citations. Presentation changes must not erase the underlying internal locator.
- For machine-readable sources, use native dataset/worksheet/record/field locators in the methodological record rather than inventing page references.
- Internal artefacts such as requirements, rule IDs, case IDs, tests, decision records, and version identifiers should provide the main evidence in this chapter.

## How to use this specification

The headings under **Recommended visible thesis structure** are the proposed numbered thesis headings. The bullets under **Internal content checkpoints** are drafting/review controls, not additional subsection titles.

The previous specification was intentionally granular while the project was still being scoped. The revised specification is deliberately flatter because the advisor identified excessive hierarchy as a risk. Do not re-create the former 3.2-3.7 structure by turning each internal artefact into a new heading.

## Bachelor-level proportionality rules

- Keep the visible hierarchy to chapter, section, and subsection level. Do not introduce a fourth numbered level.
- Use no more subsections than are needed to distinguish genuine conceptual/procedural tasks.
- Describe design-relevant implementation decisions, not routine framework boilerplate.
- Prefer one traceability table, one model diagram, and a few representative examples over many explanatory subsections.
- Move long requirement lists, rule catalogues, full reference-case matrices, detailed test inventories, SQL schemas, API listings, and logs to appendices or repository references.
- Do not repeat Chapter 1's problem statement or Chapter 2's theoretical foundations.
- Report deviations honestly. Do not rewrite the method retrospectively to make the implementation appear more linear or comprehensive than it was.
- Preserve the source-to-requirement-to-model/rule-to-implementation-to-test chain before peripheral technical detail.
- Keep the final full thesis near the advisor's recommended bachelor-level scale. If Chapter 3 expands, compress routine implementation description before reducing methodological traceability.

## Recommended visible thesis structure

```text
3 Methods and Practical Work
  3.1 Prototype Development
    3.1.1 Development Process and Requirements
    3.1.2 Data Basis and Prototype Subset
    3.1.3 Artefact and Rule Model
    3.1.4 Architecture and Implementation
  3.2 Verification and Testing
    3.2.1 Reference Cases and Test Design
    3.2.2 Verification Procedure and Conformance Criteria
```

This is the recommended working hierarchy. If the advisor later confirms a different test-results boundary or requires external expert review, modify the relevant internal checkpoints before adding another top-level section.

## Structural migration from the previous specification

This mapping is for revision control and should not appear in the final thesis.

| Previous location | Revised destination | Revision logic |
|---|---|---|
| 3.1 Research and Development Design | General DSR theory -> Chapter 2.3.4; actual process -> 3.1.1 | Chapter 3 applies DSR rather than explaining it. Problem/objective material already established in Chapter 1 is cross-referenced. |
| 3.2 Requirements Engineering | 3.1.1 Development Process and Requirements | Requirements are a concrete development activity, not a peer of prototype development. |
| 3.3 Data Basis and Catalogue Preparation | 3.1.2 Data Basis and Prototype Subset | Source/version, subset, preparation, and checks remain a coherent concrete development task. |
| 3.4 Artefact Concept | 3.1.3 Artefact and Rule Model | Intended use, data/case model, error/feedback model, and rule logic form the conceptual core of the developed artefact. |
| 3.5 System Architecture and Technology Selection | 3.1.4 Architecture and Implementation | Architecture and technology choices are presented together with their concrete software realisation. |
| 3.6 Prototypical Implementation | 3.1.4 Architecture and Implementation | Implementation is no longer a separate major methodological block. |
| 3.7.1 Reference-Case Construction | 3.2.1 Reference Cases and Test Design | Reference cases are part of the predefined verification design. |
| 3.7.2 Software-Test Design | 3.2.1 Reference Cases and Test Design | Reference-case and software-test coverage are designed together. |
| 3.7.3 Conformance, Deviations, and Methodological Limits | Method definition -> 3.2.2; observed deviations -> Results; interpretation/validity -> Discussion; thesis-level limitations -> Conclusion | Separates procedure from findings and prevents the methods chapter from absorbing later argumentative functions. |

## Chapter-level process logic

The chapter should read as one traceable sequence rather than as seven independent topics: Chapter 1 establishes the problem and objective; Chapter 2 supplies the evidence and foundations; 3.1.1 records the concrete process and requirements, including the intended feedback/error coverage; 3.1.2 fixes the authoritative Austrian data basis and the bounded subset needed by that scope; 3.1.3 specifies the patient/question, feedback, and rule models; 3.1.4 documents the architecture, implemented learner workflow and bounded UX/UI refinement; 3.2.1 fixes the coverage-driven reference responses and targeted tests; and 3.2.2 fixes the evaluated baseline, execution procedure, and conformance criteria. The Results chapter then reports the observed outcomes and deviations.

Use a process figure in the thesis only if it clarifies this sequence more efficiently than the prose and traceability table.

## 3.1 Prototype Development

**Section mission**

Document how the Chapter 1 problem and Chapter 2 foundations were transformed into a bounded, traceable artefact and working software prototype.

**What this section must achieve**

The reader must be able to reconstruct the development logic from evidence and requirements through the data/subset and artefact models to the implemented vertical workflow. The section should demonstrate purposeful design, not merely report that software was coded.

**Internal content checkpoints**

- Begin with a compact cross-reference to the DSR process established in Chapter 2 and state how the actual project activities map to it.
- Explain actual iterations/revisions honestly without presenting a chronological diary.
- Derive a bounded and testable requirement set from the identified sources, intended use, and constraints.
- Fix the official Austrian source/version and justify the prototype subset through its role in the selected feedback/error coverage rather than through size alone.
- Define the artefact's intended use, patient/question representation, three feedback classes, included error patterns, immediate-feedback/review interaction, explicit rule logic, explanation structure, and traceability.
- Allocate the model to a minimal architecture with distinct reference-data, evaluation-logic, and presentation responsibilities, and document only implementation decisions needed to understand or reproduce the behaviour.
- End at an identifiable implementation baseline that can be frozen for verification.

**Core exhibits**

- One actual development-process diagram.
- Compact requirements summary plus full requirement/traceability matrix in appendix if lengthy.
- Source/version and subset table.
- Patient/question model plus feedback/rule decision table.
- Logical architecture diagram.
- A few representative implementation screenshots or request/response examples.

**Non-goals**

- No re-explanation of DSR theory.
- No formal V-model or Lastenheft unless actually used for an independent reason.
- No exhaustive source-code, API, SQL, or UI documentation.
- No claim that the selected stack is scientifically novel.
- No attempt to model all Austrian ICD-10 or coding rules.

**Completion criteria**

- Every mandatory implemented capability has an identifiable requirement/rationale.
- Every included coding-response classification can be explained through explicit model/rule criteria.
- A technically competent reader can understand how the conceptual artefact became working software.
- The implementation baseline is defined before final verification results are produced.

### 3.1.1 Development Process and Requirements

**Purpose**

Describe the concrete project process and show how the evidence base and constraints were transformed into the bounded requirement set used for development.

**Required content and argument**

- Refer to the DSR framework/process already explained in Chapter 2 and map only the actual project activities to it.
- Cross-reference Chapter 1 for problem identification and objective definition instead of repeating them.
- Identify the actual development stages: evidence consolidation, requirements derivation, model construction, implementation, formative correction, baseline freeze, and technical verification.
- Explain that iteration/refinement occurred where requirements, model, implementation, or formative checks exposed inconsistencies; report meaningful revisions, not every coding change.
- Identify requirement sources: official Austrian catalogue/documentation material, coding-quality/error literature, feedback/design principles, intended-use boundaries, technical constraints, bachelor-scope limitations, and explicitly recorded project design decisions such as the patient/question redesign and `UXBASE-0.1` interaction/visual stretch goal.
- Treat supervisory coordination as an internal project/scope input where it fixes matters such as selection strategy, presentation expectations, architecture qualities, or UI priority. Do not present supervisory statements as independent scientific/domain evidence or expert validation.
- Explain the document/literature-based derivation procedure. Do not claim stakeholder elicitation if none occurred.
- Define the requirement record format: stable ID, requirement class, statement, rationale/source or internal decision, priority/scope status, acceptance criterion, related model/implementation element, and verification reference.
- Explain prioritisation, inclusion/exclusion/deferment, and change handling at the minimal level actually used.
- Distinguish core requirements from accepted stretch requirements. Immediate elaborated feedback and the end-of-patient review are part of the learner interaction contract; additional visual/gameful refinement remains lower priority and may not displace data/rule correctness or verification work.
- Preserve forward/backward traceability from evidence -> requirement -> model/rule -> implementation -> case/test.
- Include requirements for logical separation of reference data, evaluation logic and presentation; versioning of the catalogue/patient/question and reference-response baselines; reproducible classification behaviour; immediate criterion-specific feedback; patient-level review; bounded presentation/gameful behaviour; systematic testability; and sufficient architecture/data/interface documentation. The selected technologies implement these requirements rather than define them.
- If the formal research subquestions are removed from Chapter 1, do not build the visible chapter around them. The two internal analytical prompts may still be used privately to check construction and verification coverage.

**Evidence or artefacts to prepare**

- One actual process diagram.
- Requirements summary table; full list in appendix if long.
- Traceability matrix.
- Two representative worked derivations from source/design principle to requirement.
- Short decision/change record covering only scope-significant changes, including the supervisory decisions that alter project scope or methodological presentation.
- Include the pedagogical inspection that exposed degenerate one-option learner cases and the later pre-freeze feedback/UX iteration as scope-significant formative design changes, with their version transitions rather than as a retrospective claim that the design was linear from the outset.

**Keep within scope**

Do not create a full formal Lastenheft, stakeholder study, or heavyweight requirements-management process after the fact. Do not use supervisor discussions as independent validation evidence.

**Done when**

The development procedure reflects what was actually done, the final requirement set is inspectable, and each mandatory requirement has a defined downstream destination.

### 3.1.2 Data Basis and Prototype Subset

**Purpose**

Fix the authoritative Austrian data basis, define the bounded catalogue/case coverage, and document the transformations and quality checks needed to produce reproducible application data.

**Required content and argument**

- Name the exact Austrian ICD-10 catalogue version, associated official documents, input files, formats, and provenance.
- Use the maintained source-baseline register to distinguish the systematic catalogue and coding guidance from machine-readable input. `DIAGLIST 2026` is the broad machine-readable diagnosis list used for reproducible extraction; `ICD-10_Extramural.xlsx` is a reduced setting-specific subset and is not a second independent Austrian catalogue.
- Define the subset unit actually used: selected chapters, blocks, categories/codes, case-linked records, or another explicit unit.
- Define inclusion/exclusion criteria and explain how every retained code/record supports an expected/acceptable response, an included error pattern, a required hierarchy relation, or a deliberate boundary/negative test.
- Do not claim statistical or catalogue-wide representativeness unless demonstrated. The required claim is bounded coverage sufficient for the selected prototype behaviours.
- Apply the advisor-confirmed purposive selection principle: no minimum count and no compulsory medical domain. The final size follows from the coverage needed for all three feedback classes, multiple included coding-error patterns, straightforward cases, and at least some more difficult or ambiguous coding situations.
- Select cases/rules before treating subset size as an objective. The catalogue subset is the union of records needed to instantiate and test the declared behaviours, not a percentage sample of DIAGLIST or an entire ICD chapter chosen merely for scale.
- Report the selection history rather than freezing the original COPD-heavy proof set into the final narrative. `SUBSET-0.1`/`CASEBASE-0.2` demonstrated the first executable rules but the learner-design review exposed excessive concentration around J44 and technically useful single-option cases. The forward learner baseline therefore uses `SUBSET-0.2`: 99 DIAGLIST records forming the union of 92 learner-domain records and 13 retained historical records with six overlapping codes. Only `PATIENT-001` contains learner J44/COPD material.
- Use `MODELBASE-0.2` and `prototype_baseline_0_2_design/` as the current forward data contract: six `PATIENTBASE-0.1` records, 32 patient-context items, 25 `QUESTIONBASE-0.1` learner questions, 60 learner facts, 100 learner question/code relations, 142 learner relation/fact links and 120 displayed options, plus the transformed hidden legacy regression questions. Distinguish source-derived catalogue rows from synthetic patient/question authoring data and from the external reference-response oracle.
- Preserve the four-field DIAGLIST projection (`Diagnose`, `Kennzeichen`, `Bezeichnung`, `Kurzbezeichnung`) and the source-native distinction between a code identifier and `Kennzeichen` metadata. `subset_definition_0_2.json` externalizes the 99-code selection; `Z01.8` with marker `!` remains a deliberate excluded control and must not be written as a fictitious identifier `Z01.8!`.
- Describe the deterministic preparation path procedurally: verify the frozen workbook checksum and worksheet, verify source identifier uniqueness/count, resolve the fixed selected/control codes, project only the four whitelisted fields, apply only the declared normalization, and emit a deterministic derived subset. Describe persistence separately: validate an explicit runtime-input allowlist, apply schema DDL, then perform runtime-data insertion and post-insert equality checks in one data transaction. Do not claim the DDL and data load form one transaction because MySQL DDL has implicit-commit behaviour.
- State the immutable-baseline behaviour: an identical already-persisted baseline is a no-op, conflicting content under the same versioned identifier is an error, and an intentional semantic change receives a new identifier rather than being silently upserted.
- State deterministic data checks actually performed, such as duplicate identifiers, missing required fields, invalid parent references, status/version consistency, and record counts.
- Retain material formative data corrections in the development record where they demonstrate the value of those checks, including the corrected learner-code count/source-marker representation and the `PATIENT-004` column-shift defect caught by typed persistence preflight. Do not inflate routine corrections into Results unless they materially affect the final evaluation.
- Separate source-data preparation errors from later rule, case, or implementation errors.

**Evidence or artefacts to prepare**

- Source/version table.
- Historical `SUBSET-0.1`/`CASEBASE-0.2` records where useful to explain the formative redesign, followed by the adopted `SUBSET-0.2`/patient/question manifest and final frozen successor.
- `MODELBASE-0.2` authoring/data files, persistence-candidate manifest and the actual adopted/final versioned successor used by the implementation.
- Compact import/preprocessing flow.
- Data-quality check list with expected criteria; observed results belong in Results if they are material findings.

**Keep within scope**

Do not import the entire catalogue solely to appear comprehensive, document routine script code line by line, or claim that technical parsing validates clinical content.

**Done when**

A reader can identify exactly what catalogue/version the prototype covers, why that subset was selected, and how the usable dataset can be regenerated.

### 3.1.3 Artefact and Rule Model

**Purpose**

Specify the central intellectual design of the thesis: intended use, interaction, represented data/cases, coding-error distinctions, feedback classes, deterministic rules, explanations, and traceability links.

**Required content and argument**

- State the intended educational use and explicitly exclude clinical diagnosis, medical-device use, reimbursement decisions, and production operation.
- Define the learner workflow fixed by `MODELBASE-0.2` and `UXBASE-0.1`: orientation -> patient selection/dossier -> one atomic coding question -> one tagged response -> rule evaluation -> immediate locked classification/criterion/explanation -> next question -> patient-completion review -> replay/another patient.
- State that the patient is a learner/session container, not the atomic evaluation unit. Each `coding_question` defines one coding target and each evaluation request contains exactly one tagged response (`code_response` or `none_of_above_response`). The tagged request form fixed in `APIBASE-0.1` is shared by learner and verification calls; `option_id` remains presentation/local-state identity rather than a second API contract. Multi-code aggregation remains outside scope. The present patients contain 3, 5 or 6 questions, but no model/API/UI limit encodes those counts.
- Define the catalogue, patient, patient-context, coding-question, typed question-fact, evaluation-domain, relation/fact and displayed-option entities and their version linkage. Preserve the separation between learner-visible context and facts that the evaluator may consume. In the current baseline the raw `question_fact` collection is not exposed before submission; required learner information appears in the prompt/context and `learner_label` is not a visibility flag.
- Distinguish the evaluation domain from the displayed option set. Hidden evaluable relations are permissible for regression and for deliberate `none_of_above` exercises; an undefined response relation is not silently manufactured into `incorrect`.
- Report the final included coding-response taxonomy from `DOMBASE-0.1` using its evidence, observable conditions, exclusions, and scope rationale. Do not reopen the candidate list from Chapter 2 unless a later baseline change is explicitly justified.
- Operationally define **correct**, **suboptimal**, and **incorrect**. `Suboptimal` is an artefact-specific response category, not a general clinical judgement and not a residual bucket for uncertainty.
- Require a `suboptimal` outcome to be supported by represented case facts and an explicit source-backed improvement criterion, for example supported greater specificity where the applicable Austrian guidance makes that preference decidable. A catalogue-valid response is not therefore automatically `correct`, but neither is every less specific or hierarchy-related response automatically `suboptimal`.
- Treat an acceptable alternative as `correct` where the model/source permits it. Where the represented information and source basis cannot determine a three-class outcome without hidden expert judgement, either model the alternatives explicitly or exclude that situation from deterministic reference classification.
- Distinguish the four specified patterns from controls/boundaries: required depth, source-backed insufficient specificity, explicit case-evidence conflict, and hospital status/context incompatibility are modelled patterns; acceptable alternatives are explicit `correct` controls; unbounded ambiguity is excluded; version-invalid/out-of-subset responses are handled at the validation/scope boundary rather than being used as a generic pedagogical error pattern.
- Define rule fields: ID, required inputs/condition, output class, explanation elements, priority/precedence, rationale/source, and traceability links.
- Apply the fixed hard-before-graded precedence from `DOMBASE-0.1`. If several hard rules match and one primary criterion must be shown, preserve the stable `STATUS > DEPTH > EVID` priority while retaining secondary matches in the technical trace.
- Explain how source-specific rules or explicit question-scoped semantic relations produce criterion-specific explanations. `RULEBASE-0.2` must remain a small reusable evaluator vocabulary rather than 25 hard-coded answer branches.
- Define immediate feedback timing and content. After a successful evaluation the current answer is read-only; the learner sees classification, submitted response, criterion-specific explanation and supported reference/improvement information before proceeding. The `RULE-*` trace may be available behind technical details without dominating learner-facing copy.
- For `none_of_above`, state the now-fixed explanation contract compactly: every current learner question has one accepted reference, so both the correct and incorrect branches return whether an accepted response was displayed and the post-submission reference code. Treat `determining_rule` as rule provenance; `criterion` may legitimately be shared by different rules and must not be reverse-mapped to rule identity.
- Define the patient-completion review as a presentation aggregation of already-returned evaluator results: raw `correct`/`suboptimal`/`incorrect` counts plus per-question review. Do not define an arbitrary weighted score or treat the classes as an interval scale.
- Keep the historical `CASEBASE-0.2`/`RCBASE-0.2` distinction for regression provenance, but describe the forward verification unit as one predefined response expectation for one `coding_question`. The 18 historical obligations are transformed into hidden `verification_only` questions; actual coverage belongs in 3.2.1.
- Record assumptions explicitly so that expected outcomes do not rely on hidden expert judgement.

**Evidence or artefacts to prepare**

- Intended-use statement.
- End-to-end patient/playthrough workflow figure including immediate feedback and completion review.
- Compact patient/question data model or ER diagram.
- Error-taxonomy and feedback-classification decision table.
- Historical `RULEBASE-0.1` where necessary to explain the first implementation, followed by current forward `RULEBASE-0.2`; move the full catalogue to an appendix/repository reference if lengthy.
- Current `MODELBASE-0.2`, including patient/question separation, typed facts, option/domain distinction and runtime-versus-verification data boundary, `APIBASE-0.1` for request/visibility/error boundaries, plus `UXBASE-0.1` for feedback timing/playthrough presentation.
- Precedence/conflict table if necessary.
- At least one worked trace: source -> requirement -> model/rule -> expected classification/explanation.

**Keep within scope**

Do not include implementation code, attempt comprehensive Austrian coding-rule coverage, or allow the implementation's current output to define the conceptual rule model retrospectively.

**Done when**

The model is sufficiently explicit that the implementation and reference-case expectations can be derived from it without further conceptual invention.

### 3.1.4 Architecture and Implementation

**Purpose**

Show how the artefact model was allocated to system components and instantiated as the working prototype, while keeping routine coding detail subordinate to design-relevant decisions.

**Required content and argument**

- State first that no technology stack was externally prescribed. Present React frontend, PHP backend/API and rule-evaluation logic, MySQL persistence, and Python import/preparation tooling as the project's chosen means, assuming these remain the as-built technologies.
- Show a clear logical separation among (1) versioned catalogue/patient/question data, (2) deterministic evaluation and feedback logic, (3) transient learner-playthrough/presentation state, and (4) external verification-oracle data. This is a responsibility boundary and does not require separate deployable services.
- Make the verification-oracle boundary explicit: predefined `RC-*` expected classes/rules/criteria are test data, not runtime classification inputs. Runtime question relations may encode source-audited semantic relations required by `RULEBASE-0.2`, but the application must not read the external reference-response answer key to determine its output.
- Allocate responsibilities for catalogue/patient/question retrieval, transient playthrough state, submission handling, rule evaluation, feedback construction, persistence, trace information, version identification and final patient-review aggregation.
- Treat data import as an offline/reproducible build/deployment step rather than a learner-request responsibility. The normal application path reads already-versioned MySQL data; it does not open or re-parse the DIAGLIST workbook dynamically.
- If the current containerised deployment scaffold remains in the final artefact, describe Docker Compose as an implementation/reproducibility mechanism rather than an additional scientific contribution: MySQL and the application are long-running services, while schema/data bootstrap is a one-shot operation. Development may track a configured Git branch, but the software revision used for the principal verification must be bound to an exact commit and execution-environment versions under `REQ-CFG-01`; credentials are configuration secrets and never part of the reproducibility record.
- State that learner accounts, longitudinal histories and analytics are not persisted. The forward persistence scope is baseline metadata, the 99-record catalogue subset, patients/context, coding questions, typed facts, question/code relations, relation/fact links and fixed displayed options. React may hold transient playthrough progress/results; optional browser-session reload resilience would still not constitute a server-side learner-history model.
- Explain the principal data flow and interfaces so one learner interaction can be traced end to end, using the one-tagged-response-per-question cardinality fixed in `MODELBASE-0.2`.
- Preserve the intentional API asymmetry from `APIBASE-0.1`: learner discovery/detail cannot read hidden verification questions, while the regression harness can submit their predefined responses to the same POST evaluator. Separate HTTP input errors (`400` malformed/unsupported response kind and `404` unknown question) from normal `not_evaluated` semantic/scope gate results.
- Justify technology choices against project constraints, familiarity, separability, reproducibility, and existing requirements rather than claiming universal superiority.
- Reiterate only implementation-relevant intended-use limits: synthetic data, no clinical integration, no production hardening, no regulatory/medical-device claim.
- Describe the implemented data import/persistence only to the degree not already covered procedurally in 3.1.2.
- Explain backend request validation, data retrieval, rule evaluation, precedence, outcome creation, and response format at a reproducible conceptual level.
- Explain the React workflow from orientation and patient case-file selection through dossier access, randomized variable-length questions, fixed answer options, submission, immediate locked feedback, progress and final patient review. The per-question feedback view is part of the core interaction contract.
- Report the accepted `UXBASE-0.1` stretch iteration separately but compactly: professional visual hierarchy, patient/progress/completion presentation, replayability, responsive/accessibility-informed controls and restrained gameful cues. Emphasize that these features do not alter evaluator semantics and were not subjected to a learner/usability/effectiveness study.
- If useful, characterize the UI iteration as a formative DSR build/evaluate/refine episode: inspection of the technically functional interface exposed poor orientation/presentation, requirements were refined, and the frontend was improved before freeze while semantic rule/data baselines remained controlled. Do not invent user feedback that was never collected.
- Document meaningful implementation deviations from the artefact model or requirements and their rationale. Do not hide scope reductions.
- Identify the final implementation version/baseline passed to Section 3.2.

**Evidence or artefacts to prepare**

- Logical architecture diagram.
- Component-responsibility table if needed.
- One compact sequence/data-flow figure if the architecture diagram is insufficient.
- Example API request/response or rule-processing pseudocode.
- Two or three representative screenshots chosen to show different workflow states, preferably patient/dossier, immediate feedback and patient-completion review. A compact before/after figure is permissible if it demonstrates the formative interface change rather than aesthetic preference alone.
- Short implementation-deviation table.

**Keep within scope**

Do not provide framework tutorials, class-by-class walkthroughs, full endpoint references, SQL dumps, a design-style diary, or unsupported claims of scalability/security/usability. Visual discussion is justified only where it explains a requirement, workflow decision, accessibility safeguard, gameful mechanic or formative artefact revision.

**Done when**

The reader can trace one response from patient/question presentation through rule evaluation to immediate feedback and the patient-level review, link the implementation/UX decisions to the artefact model and requirements, and identify the frozen version to be verified.

## 3.2 Verification and Testing

**Section mission**

Define how the frozen prototype is technically examined against predefined, traceable expectations without conflating internal conformance with independent clinical or educational validation.

**What this section must achieve**

The reader must understand how reference cases and targeted software tests were defined, what behaviour they cover, how expected outcomes were fixed, how the final baseline is executed, how pass/fail and deviation categories are determined, and what the procedure can legitimately establish.

**Working boundary to the later chapters**

- **Chapter 3:** test/reference-case design, expected outcomes, execution procedure, environment/baseline, conformance/deviation definitions, rerun rules.
- **Results:** actual pass/fail outcomes, coverage summaries, observed deviations/defects, corrections/reruns where reported as findings.
- **Discussion:** interpretation of validity, consequences of internal/non-independent expectations, comparison with alternatives/literature.
- **Conclusion:** thesis-level limitations such as bounded subset, synthetic cases, absence of learner testing, and absence of external domain validation if no expert review is performed.

This separation is the recommended working interpretation of the advisor call and should be confirmed in the follow-up email before final chapter numbering is frozen.

**Internal content checkpoints**

- Fix the reference-case suite independently of final implementation output.
- Define sufficiency before finalising the number of cases. Cover all three feedback classes, every included error pattern, relevant rule boundaries, accepted alternatives where represented, and selected precedence/conflict and negative conditions.
- Include both straightforward cases and at least some more difficult or ambiguous coding situations that remain objectively decidable from represented information and rules. Difficulty must not be created by requiring unrepresented clinical judgement.
- Group software tests by responsibility rather than creating numerous testing subsections.
- Link cases/tests back to requirements and rules using stable identifiers.
- State baseline version, environment, expected result, comparison method, pass/fail criteria, and correction/rerun procedure before reporting observed results.
- State clearly that internal technical conformance does not independently establish clinical correctness or educational effectiveness.

**Core exhibits**

- Reference-case matrix, likely complete in appendix and summarised in the main text.
- Test inventory and requirement/rule coverage mapping.
- Example test/reference-case specification.
- Conformance/deviation classification table.
- Frozen-baseline/environment record.

**Non-goals**

- No learner/usability study.
- No performance, penetration, scalability, production acceptance, or regulatory testing unless actually required by an implemented requirement.
- No post-hoc revision of expected outcomes merely to make tests pass.
- No undifferentiated "accuracy" percentage that disguises internally authored expectations or untested behaviour.

**Completion criteria**

- Every result later reported has a predefined expectation and comparison method.
- Every central requirement/rule has a meaningful verification path or an explicitly declared coverage gap.
- The evaluated baseline and rerun conditions are identifiable.
- The limits of the evidence are explicit before the Results chapter interprets them.

### 3.2.1 Reference Cases and Test Design

**Purpose**

Define the fixed reference-case suite and targeted software-test set used to exercise the artefact's central behaviours.

**Required content and argument**

- State how the six synthetic patients and their atomic coding questions were authored/source-audited, and which official/literature/rule evidence supplies each deterministically evaluated relation.
- Apply the confirmed selection method historically as well as prospectively: the first four/eight-case technical baselines were coverage-driven proof sets, while the subsequent pedagogical review exposed learner-design weaknesses and led to the broader six-patient, 25-question model. The final count remains justified by represented behaviours rather than by catalogue percentage or a prescribed medical domain.
- Distinguish learner content from verification obligations. `PATIENTBASE-0.1`/`QUESTIONBASE-0.1` supplies the learner content; each candidate `RCBASE-0.3` row remains one independently predefined response expectation for one question/response. The eight historical `CASEBASE-0.2` cases survive as hidden verification fixtures rather than learner exercises.
- Describe candidate `RCBASE-0.3` accurately if it remains the adopted basis: 125 learner expectations (100 code-domain relations plus 25 `none_of_above` responses) and 18 historical regressions, for 143 predefined expectations. State that the 125 learner expectations require the planned human/source oracle audit and that four reconstructed historical additions require pre-freeze comparison against the original repository files.
- Define case-suite sufficiency as a coverage gate: every feedback class is exercised; every included error pattern has a triggering variant; important rule boundaries receive a control/negative variant where needed; central rule interactions/precedence are exercised where applicable; and every mandatory testable requirement/rule has a verification path or an explicit gap.
- Explain coverage across correct, suboptimal, incorrect, acceptable-alternative, precedence/conflict, invalid input, and boundary cases only where those situations are actually in scope. Include straightforward and more difficult/ambiguous but still objectively decidable cases.
- State that expected outcomes are derived from the specification/rule model and frozen before final execution, not copied from current implementation behaviour.
- Explain any consistency or review procedure used for expectations.
- If the advisor requires external expert validation, document its limited purpose and method separately rather than retroactively describing internal authorship as expert validation. If no expert review is required/performed, state that explicitly as an evaluation limitation.
- Define targeted software tests by responsibility:
  - unit tests for individual rules, validators, or data transformations;
  - integration tests for database/API/rule-engine interaction;
  - end-to-end tests for the complete learner workflow where feasible;
  - negative/boundary tests for malformed or unsupported inputs and rule boundaries;
  - regression tests that rerun frozen reference behaviour after changes.
- Extend interaction/E2E coverage for `UXBASE-0.1`: immediate feedback, answer locking, patient-context reopening, variable 3/5/6-question progress, final review/counts, replay/randomization, `none_of_above`, verification-only invisibility, and the specified accessibility/responsive smoke paths. These tests establish implemented interaction conformance, not human usability.
- Treat historical `TESTBASE-0.1` as evidence for the earlier implementation, not as sufficient coverage of the redesign. The forward test catalogue must preserve surviving rule/data regression obligations and add the patient/question, `none_of_above`, randomization, immediate-feedback/review and UX interaction tests required by revisions 0.6/0.7. Test-ID count is never itself a coverage or quality metric.
- Explain that `TEST-GATE-01` covers outside-subset, undefined-relation, and missing-required-fact behaviour; `TEST-MAP-01` covers all FEV1 partitions and exact thresholds; the direct rule tests cover each terminal predicate/control; and `TEST-PREC-01` exercises artificial rule-match combinations only at the controller level, without inventing an additional medical case.
- State explicitly that `not_in_frozen_version` is not a distinct runtime branch in the initial architecture because only the active subset, not a complete Austrian-version membership index, is retained by the runtime application. Unsupported responses therefore receive the weaker bounded-subset result unless the architecture is later expanded and versioned.
- Map tests/cases to requirements, rules, and feedback properties through stable IDs.

**Evidence or artefacts to prepare**

- A small number of representative worked cases in the main text.
- Forward patient/question coverage plan and the frozen full reference-response matrix in the appendix, including at minimum patient/question or legacy-fixture identity, tested response, expected classification, determining criterion/rule or relation, brief rationale/source and explicit Austrian catalogue version. Do not reproduce all 143 rows in the main text.
- Main-text coverage summary.
- Working technical test catalogue `TESTBASE-0.1` (`chapter3_test_catalogue.md`) and its traceability/coverage matrix, followed by the final frozen revision used for execution.
- One worked reference-case and one worked software-test specification.

**Keep within scope**

Do not pursue statistically representative clinical sampling, comprehensive catalogue coverage, or exhaustive production QA. The suite is purposive and bounded around the represented rule/error situations.

**Done when**

The expected behaviour is frozen independently of final execution, important branches have justified coverage, and missing coverage is visible rather than concealed.

### 3.2.2 Verification Procedure and Conformance Criteria

**Purpose**

Define the final execution, comparison, deviation, correction, and rerun procedure without pre-reporting the observed results.

**Required content and argument**

- Identify the final frozen implementation baseline and test/reference-case versions.
- Preserve the configuration-control obligation represented historically by `TEST-CFG-01`: the principal run must bind the actual source/subset, rule, patient/question model, UX interaction revision where relevant, oracle, test, software, database, and execution-environment identities before outcomes are interpreted.
- Record the relevant execution environment and repeatability conditions at a level sufficient to rerun the tests.
- Define comparison targets: expected classification, required explanation/criterion elements, rule trace where applicable, API/data-flow behaviour, immediate-feedback/review interaction requirements, and error handling. Visual smoke/accessibility checks remain separate from the reference-response truth oracle.
- Define pass/fail or conformance categories before presenting results. Distinguish exact conformance, explanation mismatch, unexpected rule/classification, execution failure, and untested outcome where useful.
- Distinguish implementation defect, specification/rule defect, reference-case expectation defect, data-preparation defect, and accepted limitation.
- Define how a discovered defect is corrected, how the baseline changes, and which affected regression/reference cases must be rerun.
- Carry forward the `TESTBASE-0.1` regression rule: rerun every directly affected test/reference response, and rerun the complete reference suite when shared evaluation, persistence, or API behaviour can have changed. Preserve previous failed observations rather than replacing them with the corrected run.
- Preserve an audit trail when an expected result changes for a justified specification/reference-case reason. Never silently alter expectations after seeing implementation output.
- State which summary measures will be reported in Results. Prefer transparent counts/coverage and named deviations over a single ambiguous "accuracy" figure.
- End with the methodological claim boundary: passing the defined suite demonstrates conformance of the implemented behaviour to the predefined model within the selected subset; it does not independently validate the clinical truth of the model, learner benefit, usability, or broader catalogue generalisability.

**Evidence or artefacts to prepare**

- Baseline/version table.
- Execution-environment record.
- Conformance/deviation classification table.
- Change-and-rerun log template.
- Planned results-summary schema, without observed values.

**Keep within scope**

Do not report substantive pass/fail results in this subsection under the working chapter boundary. Do not claim external validity from internal conformance.

**Done when**

Another technically competent reader could execute the defined checks and classify the observed outcomes using the same predefined criteria.

## Distribution of material to later chapters

This specification deliberately removes material from Chapter 3 that belongs elsewhere.

### Results chapter

Report, rather than define:

- executed reference-case/test counts and coverage;
- pass/fail/conformance outcomes;
- observed classification/explanation mismatches;
- implementation/data/specification defects discovered during final execution;
- corrections and rerun outcomes where methodologically relevant; and
- concise evidence that the final baseline behaves as reported.

### Discussion

Interpret, rather than merely repeat:

- what the technical verification does and does not establish;
- internal expectation authorship and resulting validity concerns;
- implications of the bounded catalogue/case selection;
- relation of the obtained artefact/evidence to prior learning/coding approaches;
- alternative technical or methodological approaches; and
- threats to validity tied to the actual results.

### Conclusion: Limitations and Outlook

State thesis-level limits such as:

- bounded Austrian ICD-10 subset;
- synthetic cases only;
- no learner/usability/acceptance/effectiveness study;
- no clinical or production use;
- no independent domain validation if external expert review is not performed;
- time/resource constraints where they materially shaped the artefact; and
- limited generalisability beyond the represented cases/rules.

Place further development in **Outlook**, after Limitations, rather than mixing future features into the evaluation claim.

Do not place an actually completed pre-freeze `UXBASE-0.1` refinement in Outlook merely because it began as a stretch goal. If implemented, it is part of the as-built development history in 3.1.4. Outlook is reserved for work not performed, such as learner/usability studies, persistent progress/accounts, broader catalogue/domain coverage, or further game mechanics.

## Chapter 3 exit checklist

- [ ] The visible chapter has only two major sections: **Prototype Development** and **Verification and Testing**.
- [ ] General DSR theory has been removed from Chapter 3 and cross-referenced to Chapter 2.
- [ ] The exploratory V-model/Lastenheft discussion has not been converted into an unperformed methodology.
- [ ] Chapter 1 problem/objective material is cross-referenced rather than repeated.
- [ ] Requirements, sources, models, implementation, reference cases, and tests are linked through stable identifiers.
- [ ] The exact Austrian data/version and bounded prototype subset are reproducible.
- [ ] The final prose reflects the patient/question redesign (`SUBSET-0.2`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, `MODELBASE-0.2`, `RULEBASE-0.2`) rather than reverting to the historical one-case/one-question architecture except when explaining its formative evolution.
- [ ] The final case/code selection follows the confirmed coverage-based method rather than an arbitrary count; all three feedback classes, multiple included error patterns, straightforward cases, and at least some objectively decidable difficult/ambiguous cases are represented.
- [ ] Representative cases are used in the main text and the complete versioned reference-case matrix is assigned to the appendix.
- [ ] The conceptual artefact and rule logic are understandable before implementation detail.
- [ ] React/PHP/MySQL/Python are described as implementation means rather than scientific contributions.
- [ ] Immediate post-submission elaborated feedback and the patient-completion review are described as interaction requirements; the review reports category counts rather than an arbitrary weighted score.
- [ ] If `UXBASE-0.1` is implemented, its orientation/progress/completion/replay and accessibility-informed visual refinements are reported as a bounded formative implementation iteration, without claiming measured usability, engagement or learning benefit.
- [ ] Reference data, evaluation logic, and presentation responsibilities are clearly separated, and version-control/reproducibility/testability requirements are visible in the architecture.
- [ ] `Suboptimal` has an explicit source-backed operational definition and is not used as a subjective residual category.
- [ ] Reference-case expectations are fixed independently of final implementation output.
- [ ] The evaluation boundary explicitly distinguishes technical conformance from clinical/educational validation.
- [ ] The external-expert-validation question is resolved and documented before final evaluation claims are frozen.
- [ ] Test procedure and expected criteria are separated from observed Results under the working chapter boundary.
- [ ] Implementation and evaluation deviations are documented rather than silently corrected or omitted.
- [ ] Full code listings, long requirements/rule/case/test tables, detailed logs, and extensive schemas are moved to appendices or repository references.
- [ ] The chapter remains within the revised page budget or has a documented reason to exceed it.
- [ ] No fourth-level numbered headings have been introduced.
