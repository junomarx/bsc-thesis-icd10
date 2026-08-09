# Chapter 3 Requirements Catalogue

**Document status:** Working methodological control document  
**Catalogue version:** 0.6 (merges `chapter3_requirements_forward_revision_0_7.md` into the previously-frozen `0.5` catalogue; see §0)  
**Date:** 6 August 2026, revised 8 August 2026  
**Applies to:** Austrian ICD-10 educational prototype, Chapter 3 development work, and later technical verification  
**Upstream baseline:** `chapter3_input_source_baseline_register.md`, register version 0.4  
**Domain/classification baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`
**Rule baseline:** `chapter3_rule_catalogue_0_2.md`, `RULEBASE-0.2` (supersedes `RULEBASE-0.1`, `chapter3_rule_catalogue.md`, retained as history)
**Case/subset planning baseline:** `chapter3_reference_case_coverage_plan_forward_0_3.md`, forward design (supersedes `CASEPLAN-0.2`/`chapter3_reference_case_coverage_plan.md`, retained as history) / `SUBSET-0.2`
**Data/interaction baseline:** `chapter3_data_model_and_interaction_baseline_0_2.md`, `MODELBASE-0.2` (supersedes `MODELBASE-0.1`, retained as history) / `PATIENTBASE-0.1` / `QUESTIONBASE-0.1` / candidate `RCBASE-0.3`
**Technical test baseline:** `chapter3_test_catalogue.md`, `TESTBASE-0.1` (working baseline; forward extension pending, see §0)

## 0. Forward revision 0.7 merge note (8 August 2026)

This catalogue was frozen at version `0.5` on 6 August 2026 against the
historical one-case/one-question `CASEBASE-0.2` learner model. A
pedagogical review of the implemented prototype (documented in
`HANDOFF.md` §4.7 and `docs/CHANGELOG.md`) found that several
learner-visible cases had single-code response domains and provided
little real discrimination. `chapter3_requirements_forward_revision_0_7.md`
was produced as a working delta against this catalogue to replace the
one-case/one-question model with a six-patient/25-question model
(`PATIENTBASE-0.1`/`QUESTIONBASE-0.1`), add an immediate-feedback/patient-review
interaction contract, and add a bounded UX/UI and gamification stretch
goal (`UXBASE-0.1`). This version (`0.6`) merges that delta:

- Six existing requirements (`REQ-MOD-01`, `REQ-MOD-02`, `REQ-INT-01`,
  `REQ-FBK-01`, `REQ-DAT-03`, `REQ-RUL-02`) are **revised in place** below.
  Their `0.5` wording described the historical `CASEBASE-0.2` model
  correctly for its time and remains readable in
  `chapter3_requirements_forward_revision_0_7.md` §2 and in this file's
  own git history; it is not restated verbatim here to avoid two
  authoritative copies drifting apart. Nothing below should be read as a
  retroactive claim that the historical implementation already satisfied
  the revised wording.
- Fourteen new requirements are added (`REQ-MOD-03`-`06`, `REQ-DAT-06`-`09`,
  `REQ-RUL-06`-`07`, `REQ-INT-02`-`05`, `REQ-UI-01`-`03`, `REQ-GAM-01`,
  `REQ-VER-08`-`09`) — no ID collisions with the `0.5` baseline.
- The historical `CASEBASE-0.2`/`RCBASE-0.2` requirements and evidence
  remain valid regression history; they are not deleted, only superseded
  as the *learner-facing* model. The eight historical cases continue as
  hidden `verification_only` regression fixtures under the forward design
  (see `chapter3_requirements_forward_revision_0_7.md` §1).
- `chapter3_test_catalogue.md` (`TESTBASE-0.1`) has **not yet** been
  revised for the forward model as of this merge; that is tracked as
  outstanding work in `docs/REQUIREMENTS_TRACEABILITY.md`, not silently
  assumed covered.

## 1. Purpose and control rule

This catalogue translates the research objective, Chapter 2 design foundations, authoritative Austrian source baseline, project boundaries, and confirmed supervisory constraints into inspectable requirements. It is a development control artefact, not a claim that every entry is an externally imposed requirement.

The required traceability chain is:

> source/evidence/internal decision -> `REQ-*` -> model or `RULE-*` -> implementation element -> `CASE-*`/`RC-*` or `TEST-*` -> verification result

A downstream element must not acquire stronger authority than its basis. In particular, supervisory decisions can define project scope, coverage, architecture expectations, and presentation, but they cannot establish Austrian code correctness. Code-level and coding-rule truth must remain traceable to applicable official Austrian sources.

## 2. Provenance classes used here

| Prefix | Meaning | Examples |
|---|---|---|
| `SRC-*` | Authoritative Austrian domain/source baseline | `SRC-AT-ICD-SYS-2026`, `SRC-AT-DOC-2026`, `SRC-AT-DIAGLIST-2026` |
| `EVID-*` | Research or methodological evidence | `EVID-SE-01`, `EVID-FB-01`, `EVID-RULE-01` |
| `INT-*` | Internal research, scope, technology, or supervisory project input | `INT-RQ-01`, `INT-SCOPE-03`, `INT-SUP-01` |
| `REQ-*` | Requirement derived for this project | Records below |
| `PAT-*` | Frozen coding-response pattern used to derive executable decision rules | `PAT-DEPTH-01`, `PAT-SPEC-01`, `PAT-EVID-01`, `PAT-STATUS-01` |
| `RULE-*` | Executable or inspectable decision rule specified in `RULEBASE-0.1` | Working baseline; not yet verification-frozen |
| `CASE-*` | Synthetic base case | Working IDs in `CASEPLAN-0.2` (first estimate was `CASEPLAN-0.1`, expanded by the pre-freeze coverage review); not yet verification-frozen |
| `RC-*` | Submitted-code/reference-response variant belonging to a base case | Working expectations in `CASEPLAN-0.2`; not yet verification-frozen |
| `TEST-*` | Targeted software test | Working specifications in `TESTBASE-0.1`; not yet verification-frozen |

Exact printed-page and dataset locators remain in the working source register and must be carried into rules/reference expectations where a concrete source claim depends on them. The eventual thesis-facing citation can omit the pinpoint locator according to the supervisor-requested HCW presentation convention without deleting that internal provenance.

## 3. Requirement status

- **Accepted:** the requirement belongs to the working prototype baseline. Details explicitly marked as open must still be resolved before `REQBASE-1.0` is frozen.
- **Conditional:** required only if the corresponding optional setting/feature is activated.
- **Scope constraint:** a prohibited capability or claim that must remain absent.

The catalogue deliberately does not use an arbitrary case/code count as a requirement. Reference-suite size is an output of the coverage criteria in Section 9.

### 3.1 Trace of the latest supervisory decisions

| Internal decision | Principal requirement destinations |
|---|---|
| `INT-SUP-01`: coverage-driven selection; no case/code quota or compulsory medical domain | `REQ-DAT-03`, `REQ-VER-01`, `REQ-VER-02` |
| `INT-SUP-02`: representative cases in the main text; complete versioned suite in the appendix | `REQ-VER-07` |
| `INT-SUP-03`: technology freedom with separation, explicit version control, reproducibility, testability and documentation; UI secondary | `REQ-FBK-01`, `REQ-ARC-01`, `REQ-ARC-02`, `REQ-IMP-01`, `REQ-DOC-01` |
| `INT-SUP-04`: `suboptimal` requires explicit, defensible criteria | `REQ-RUL-02`, `REQ-RUL-03`, `REQ-FBK-02`, `REQ-VER-02` |
| `INT-MOD-01`: one submitted ICD code per case-defined coding target and evaluation request; no multi-code aggregation in the initial prototype | `REQ-INT-01`, `REQ-MOD-02`, `REQ-ARC-01` |

## 4. Intended use and claim boundaries

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-SCP-01** | Accepted | The artefact shall be a web-based educational demonstrator using synthetic coding cases and a bounded, versioned subset of Austrian ICD-10 BMASGPK 2026. | `INT-RQ-01`, `INT-SCOPE-01`, `INT-SCOPE-02` | The frozen case set contains only synthetic cases; the data manifest identifies the bounded Austrian catalogue subset and edition. | Case/data manifest inspection |
| **REQ-SCP-02** | Scope constraint | The artefact shall not infer a patient's true diagnosis, provide clinical decision support or treatment recommendations, perform official reporting or reimbursement decisions, or be represented as a production/medical-device system. | `INT-SCOPE-03` | No implemented workflow or output performs the excluded functions; intended-use/disclaimer text states the boundary. | Feature/UI/API and thesis inspection |
| **REQ-SCP-03** | Scope constraint | Evaluation claims shall be limited to the technical/model conformance actually examined; no usability, acceptance, learning-effectiveness, knowledge-retention, clinical-validity, or real-world error-reduction claim shall be inferred from technical verification. | `INT-EVAL-01`, `EVID-EVAL-01` | Methods, Results, Discussion, and Conclusion distinguish technical conformance from the excluded validation claims. | Thesis/evaluation-report inspection |

## 5. Authoritative data and prototype subset

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-DAT-01** | Accepted | The active catalogue baseline shall be explicitly fixed to ICD-10 BMASGPK 2026 and to the exact source files recorded in the source-baseline register. | `INT-SCOPE-01`, `SRC-AT-ICD-SYS-2026`, `SRC-AT-DIAGLIST-2026` | Source manifest records edition, source ID/file, retrieval/provenance information and frozen checksum where applicable. | Source-manifest inspection/checksum comparison |
| **REQ-DAT-02** | Accepted | Machine-readable catalogue extraction shall use the frozen `DIAGLIST 2026` workbook, while semantic catalogue notes and represented coding instructions shall be obtained from the applicable systematic catalogue/guidance rather than inferred from spreadsheet absence. | `SRC-AT-DIAGLIST-2026`, `SRC-AT-ICD-SYS-2026`, `SRC-AT-DOC-2026` | Imported records trace to DIAGLIST; every semantic/coding rule that requires information absent from DIAGLIST cites the appropriate controlling source. | Import audit plus rule/source trace inspection |
| **REQ-DAT-03** | Accepted, revised 0.6 | The active catalogue subset shall be derived from the source-audited question/evaluation domains and retained regression obligations. It shall not be restricted to one ICD family, one medical domain, or an arbitrary code count. | `INT-SUP-01`, `QSAUDIT-0.1`, `SRC-AT-DIAGLIST-2026` | Every subset record has a learner-domain or retained-regression purpose; every code referenced by a question relation exists in the active subset. | Subset-to-question trace audit and deterministic DIAGLIST extraction check |
| **REQ-DAT-06** | Accepted forward design | The active catalogue subset shall be derived from the adopted multi-domain question bank and retained regression needs; it shall not remain restricted to the original COPD/status families. | `PATIENTPLAN-0.4`, `QSAUDIT-0.1`, `SRC-AT-DIAGLIST-2026` | Every learner-domain code exists in the active subset and the subset spans all adopted coding families. | Deterministic extraction and dataset audit |
| **REQ-DAT-07** | Accepted forward design | Multiple represented conditions across the patient set shall function as genuine coding targets rather than serving only as cosmetic background diversity. | Project pedagogical decision; `PATIENTPLAN-0.4` | The question bank materially targets the physiological and mental-health conditions declared by its design rather than repeating one diagnosis family. | Patient-to-question content audit |
| **REQ-DAT-08** | Accepted forward design | COPD-related learner content shall be confined to at most one patient; any additional COPD cases needed for technical regression shall remain verification fixtures rather than learner patients. | Project design decision after pedagogical review; `PATIENTPLAN-0.4`, `QSAUDIT-0.1` | Exactly one learner patient contains J44/COPD content; the remaining five learner patients contain no J44 question relation. | Dataset audit |
| **REQ-DAT-09** | Accepted forward content baseline | The present learner content baseline shall contain six synthetic patients and 25 atomic coding questions with versioned patient-to-question membership; these counts are a project content choice, not a catalogue-coverage quota or schema maximum. | `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`, `QSAUDIT-0.1` | Materialized counts and membership match the versioned baseline; later changes increment the patient/question baseline rather than silently changing these IDs. | Manifest and referential-integrity checks |
| **REQ-DAT-04** | Accepted, working whitelist fixed | Imported fields and transformations shall be explicitly whitelisted and reproducible; source fields shall not become prototype requirements merely because DIAGLIST contains them. `SUBSET-0.1` retains `Diagnose`, `Kennzeichen`, `Bezeichnung` and `Kurzbezeichnung`, with catalogue/subset identity and checksum held as dataset-level metadata. Deterministic preprocessing checks shall cover the fields/relations actually relied upon. | `SRC-AT-DIAGLIST-2026`, `EVID-SE-01` | Import specification names retained fields, transformations and checks; repeated import from the frozen source produces the same active records and values. | Import unit/integration tests and manifest comparison |
| **REQ-DAT-05** | Accepted, hospital rule activated | The active rule baseline shall represent the hospital-sector context needed by `PAT-STATUS-01`. Cases exercising that pattern shall explicitly encode the applicable encounter setting, diagnosis role and, where required, whether a hospital-outpatient visit is scored by the inpatient LKF model. No extramural-specific executable coding rule is included in `DOMBASE-0.1`; `ICD-10_Extramural.xlsx` remains a reduced setting-specific source/context aid rather than a replacement for the full Austrian catalogue baseline. | `SRC-AT-DOC-2026`, `SRC-AT-ICD-EXT-XLSX-2026`, `INT-SCOPE-01` | Every status-rule case contains the setting inputs on which the rule actually depends and traces to the applicable printed Austrian criterion; no executable branch applies an extramural rule. | Rule/data inspection plus hospital-setting boundary tests |

## 6. Case, rule, and feedback model

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-MOD-01** | Accepted, revised 0.6 | Rule evaluation shall use only facts explicitly represented for the atomic `coding_question`. Patient demographics, history and contextual prose may be learner-visible, but shall not become evaluator input unless the relevant value is separately represented as a typed `question_fact`. | `INT-SCOPE-02`, `INT-SCOPE-03`, `QSAUDIT-0.1`, `MODELBASE-0.2` | Every fact consumed by a rule is addressable by a declared `fact_key`; changing unrelated patient-context prose cannot change the classification result. | Rule-input trace inspection and context-independence tests |
| **REQ-MOD-02** | Accepted, revised 0.6 | A learner patient and an atomic coding question shall be distinctly versioned entities. A patient may contain multiple independently evaluable questions. A verification expectation remains one response to one question; the evaluator shall not classify an entire patient record as one aggregate response. | `INT-SUP-01`, `INT-MOD-01`, `MODELBASE-0.2` | Each learner question belongs to exactly one patient and each evaluation request contains exactly one tagged response for one question. | Schema/API inspection and reference-response tests |
| **REQ-MOD-03** | Accepted forward design | A learner case shall represent one synthetic patient that can contain multiple separately identified coding questions; the patient is not itself the atomic evaluation unit. | `PATIENTPLAN-0.4`, `MODELBASE-0.2` | Every learner question belongs to one patient and evaluation addresses one `coding_question` at a time. | Schema/API inspection |
| **REQ-MOD-04** | Accepted forward design | Patient context and question rule facts shall be represented separately. Context is presentation data; only typed `question_fact` rows may be consumed by the evaluator. | `MODELBASE-0.2`, `INT-SCOPE-03` | No evaluator query or rule scans `patient_context_item.display_text` or the question prompt to derive medical facts. | Architecture/code inspection plus mutation test |
| **REQ-MOD-05** | Accepted forward design | Learner question cardinality shall be data-driven; no database, API or UI constraint shall hard-code three questions per patient. | `PATIENTPLAN-0.4`, `MODELBASE-0.2` | The materialized learner set contains question counts `3,3,3,5,5,6`, and the implementation renders these from data without a fixed maximum of three. | Data invariant plus API/UI tests |
| **REQ-MOD-06** | Accepted forward design | Evaluation-domain membership and displayed option membership shall be distinct. A code may be evaluable without being displayed. | `QSAUDIT-0.1`, `MODELBASE-0.2` | `Q-004-05/M54.5`, `Q-005-05/I10`, and the non-displayed J44 technical relations remain evaluable while absent from their displayed code sets. | Data invariant and API tests |
| **REQ-RUL-01** | Accepted | Every implemented classification rule shall have a stable `RULE-*` identifier and record its required inputs/condition, effect/output, rationale/source basis, explanation payload, precedence/conflict relation where relevant, and verification links. | `INT-TRACE-01`, `EVID-RULE-01`, `EVID-SE-01` | No executable classification branch lacks a corresponding inspectable rule record and source/rationale link. | Rule-catalogue/implementation trace audit |
| **REQ-RUL-02** | Accepted, revised 0.6 | `suboptimal` shall be assigned only by an explicit source-specific rule or a source-audited `less_specific_supported` question relation whose represented facts justify an identified `improvement_code`. Code morphology, a `.9` suffix, designation wording or code length shall never independently determine `suboptimal`. | `INT-SUP-04`, `SRC-AT-DOC-2026`, `QSAUDIT-0.1`, `RULEBASE-0.2` | Every generic `suboptimal` relation names an accepted improvement code and a source-audited specificity basis; `E11.9`, `F03` and `N40` act as countercontrols against lexical/code-shape heuristics. | Rule-data validation and countercontrol tests |
| **REQ-RUL-06** | Accepted forward design | `none_of_above` shall be an interface response kind, not an ICD catalogue record. It is `correct` exactly when the fixed displayed code set contains no `accepted_reference`; otherwise it is `incorrect`. | Project interaction decision; `RULE-NOA-01` | Set-intersection logic is deterministic for all 25 learner questions; `Q-004-05` and `Q-005-05` are positive controls and all other learner questions are negative controls. | Rule unit tests plus full learner-oracle sweep |
| **REQ-RUL-07** | Accepted forward design | Every classified code response shall have an explicit question-scoped semantic relation. The evaluator shall not manufacture `incorrect` or `suboptimal` from `submitted_code != reference_code`, lexical similarity, code depth or catalogue label alone. | `QSAUDIT-0.1`, `RULEBASE-0.2` | Undefined relations are rejected by the eligibility gate; every hard generic relation links to at least one explicit question fact. | Data-contract validation and negative-boundary tests |
| **REQ-RUL-03** | Accepted | Acceptable alternatives shall be modelled explicitly where the source/case specification permits them. A situation whose three-class outcome cannot be determined without hidden expert judgement shall not be forced into the deterministic reference suite. | `EVID-CQ-02`, `INT-SUP-04` | Each included alternative has an explicit expected treatment; no frozen `RC-*` expectation depends on an undocumented subjective choice. | Reference-case/source audit |
| **REQ-RUL-04** | Accepted | Where multiple rules can match, hard invalidating conditions shall precede graded specificity, so an `incorrect` condition cannot be downgraded to `suboptimal`. All matched reasons shall remain traceable; where one primary hard-error criterion is required, the current stable priority is `STATUS > DEPTH > EVID`. | `EVID-RULE-01`, `INT-TRACE-01`, `INT-SUP-04` | Multi-match behaviour implements `DOMBASE-0.1` independently of incidental rule-storage/order effects and retains secondary matches in the technical trace. | Unit tests for precedence/multi-match cases |
| **REQ-RUL-05** | Accepted | The three feedback classes shall apply only to evaluated responses for which the frozen version/subset and case model define an in-scope relation. Malformed input, an identifier absent from the frozen Austrian version, or a valid Austrian code outside the supported prototype subset shall be prevented or reported as validation/out-of-scope rather than being silently labelled `incorrect`. | `INT-SCOPE-01`, `INT-SCOPE-03`, `SRC-AT-ICD-SYS-2026`, `SRC-AT-DIAGLIST-2026` | UI/API tests distinguish unsupported input from a modelled `incorrect` coding response; a deliberately bounded subset is never used as proof that an omitted Austrian code is invalid. | Input-validation, API and negative-boundary tests |
| **REQ-FBK-01** | Accepted, revised 0.6 | Every classified learner response shall return and display the feedback class, determining criterion and a concise task-focused explanation immediately after submission, before the learner proceeds to the next question. The submitted response is locked for that playthrough once evaluation succeeds. Where the model defines a better-supported/reference code, the feedback shall expose it or the corresponding corrective direction. The determining `RULE-*` identifier shall remain available in the technical trace even if it is placed behind an optional details view rather than foregrounded for the learner. | Existing `REQ-FBK-01`/`02`, `DP-F2`-`DP-F6`, `RULEBASE-0.2`, `UXBASE-0.1` | Every learner option can reach a stable post-submission state containing the submitted response and classification; classified results expose criterion/explanation, `suboptimal` results expose their supported improvement, and navigation to the next question is possible only after the current result has been rendered. | Evaluator/API assertions plus end-to-end UI tests for one response from each feedback class and `none_of_above` |
| **REQ-FBK-02** | Accepted | A `suboptimal` result shall identify the source-backed respect in which the response can be improved, rather than displaying the middle-category label alone. | `INT-SUP-04`, `EVID-FB-01` | Every `suboptimal` reference expectation specifies the required improvement/explanation element and the observed output can be compared against it. | `suboptimal` reference-case tests |
| **REQ-FBK-03** | Accepted forward core interaction | After the final question of a patient playthrough, the interface shall present a review containing raw counts of `correct`, `suboptimal`, and `incorrect` outcomes plus the result of each answered question. The prototype shall not invent a weighted composite score that treats the three classes as an unvalidated numerical scale. | `REQ-FBK-01`/`02`, `UXBASE-0.1`; project interaction decision | A completed 3-, 5-, or 6-question playthrough yields category counts whose sum equals its question count and a read-only per-question review that agrees with the previously returned evaluator results. | UI state/unit test plus end-to-end completion tests |

## 7. Interaction, architecture, and implementation

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-INT-01** | Accepted, revised 0.6 | The learner workflow shall present a synthetic patient, permit navigation through that patient's variable-length question set, accept exactly one response per question, and return feedback. A response is either `code_response(code)` or `none_of_above_response`; multi-code aggregation remains outside the prototype. | `INT-RQ-01`, `INT-MOD-01`, `RULEBASE-0.2`, `MODELBASE-0.2` | The API/UI supports both response kinds without representing `none_of_above` as an ICD code and without introducing multi-code scoring. | API and end-to-end interaction tests |
| **REQ-INT-02** | Accepted forward design | Patient identity, demographics, history-availability boundary and learner-visible context shall remain accessible while the learner works through that patient's questions. | Project pedagogical decision; `PATIENTPLAN-0.4` | Learner can reopen the patient summary/context without losing current question state. | UI/end-to-end test |
| **REQ-INT-03** | Accepted forward design | Question order shall be randomized between playthroughs without changing question membership, option membership, facts, or expected evaluation semantics. | Project pedagogical decision | Repeated starts can produce different question order while a frozen question ID always resolves to identical semantic content and option membership. | Deterministic-content and random-order UI/service tests |
| **REQ-INT-04** | Accepted forward design | Every learner question shall have a fixed, versioned option set containing a dedicated `none_of_above` option. Only presentation order may be randomized. | `RULE-NOA-01`, `MODELBASE-0.2` | All 25 questions have exactly one `none_of_above` row; option membership is stable for a frozen `QUESTIONBASE-*`. | Data invariant and UI tests |
| **REQ-INT-05** | Accepted forward core interaction | Playthrough progress shall be transient presentation state. Within one playthrough an evaluated answer is read-only; replay starts a new attempt and may reshuffle questions/options without modifying frozen question/option membership or prior evaluator semantics. No server-side learner account, longitudinal attempt history, or analytics store is required. | `MODELBASE-0.2`, `REQ-INT-03`/`04`, `UXBASE-0.1` | Reviewing an answered question cannot overwrite its recorded result; replay produces a fresh state over the same versioned patient/question set; evaluation remains independent of presentation order. | React state tests and end-to-end replay test |
| **REQ-ARC-01** | Accepted | Reference data/cases, evaluation/feedback logic, and presentation/UI responsibilities shall be logically separated so that classification behaviour is not embedded solely in the interface. Predefined verification expectations shall remain outside the runtime classification-data path. | `INT-SUP-03`, `EVID-SE-01`, `INT-MOD-01` | Architecture/component documentation allocates the three responsibilities distinctly; evaluation can be exercised independently of UI state, and the runtime database/evaluation endpoint does not consume `RC-*` expected classes/rules as classification inputs. | Architecture/schema inspection and integration test |
| **REQ-ARC-02** | Accepted | For identical case facts, submitted code, rules, catalogue/reference data, and version baseline, evaluation shall be deterministic and reproducible. | `INT-SUP-03`, `EVID-RULE-01` | Repeated executions against an unchanged baseline yield identical class, determining rule/criterion, and required explanation elements. | Repeatability/regression tests |
| **REQ-IMP-01** | Accepted project constraint | The working implementation shall use the selected web stack (React frontend, PHP backend/API, MySQL persistence, and Python preparation/import tooling) unless a documented requirement-driven change is made before implementation freeze. | `INT-TECH-01`, `INT-SUP-03` | As-built architecture records actual technologies and versions; any departure is recorded with its rationale and affected requirements. | Architecture/build manifest inspection |
| **REQ-DOC-01** | Accepted | The thesis/project artefacts shall document the logical architecture, relevant data structures, principal interfaces/data flow, and rule-evaluation responsibility sufficiently to relate implementation to the conceptual model. | `INT-SUP-03`, `EVID-SE-01` | Chapter 3 and/or appendix contains the agreed architecture/model exhibits and enough interface/data description to trace one response end to end. | Documentation inspection |

### 7.1 Bounded UX/UI and gameful presentation (`UXBASE-0.1`, accepted stretch goal)

Added in catalogue `0.6`. This subordinate stretch goal never overrides
`REQ-RUL-*`/`REQ-MOD-*`/`REQ-VER-*` semantics; see `UXBASE-0.1` §1 for the
boundary statement.

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-UI-01** | Accepted stretch goal | The learner-facing entry and patient-selection views shall make the prototype self-explanatory without external verbal instruction: identify its educational purpose, summarize the choose-patient → answer-coding-task → receive-feedback workflow, explain the three feedback classes, and retain the non-clinical-use boundary. | `UXBASE-0.1`; project presentation decision | A first-time user can reach a patient playthrough and has visible access to the workflow/feedback legend and educational-use notice from the interface. | UI inspection and browser end-to-end navigation test |
| **REQ-UI-02** | Accepted stretch goal | The learner interface shall present patients as distinct case files, expose the known patient context throughout the playthrough, and show question-level and patient-level progress/completion without hard-locking patients by difficulty. | `PATIENTBASE-0.1`, `REQ-INT-02`/`03`, `UXBASE-0.1` | Patient cards expose question count and a neutral complexity cue; the active view exposes current/total progress and can reopen patient information without losing the current response state; completion status is session-local. | UI component tests and end-to-end 3-/6-question paths |
| **REQ-UI-03** | Accepted stretch goal | Visual polish shall preserve basic accessibility and semantic clarity: feedback is identifiable by text/iconography rather than colour alone, keyboard focus remains visible, primary controls are keyboard operable, layout remains usable at supported desktop/mobile widths, and nonessential motion respects reduced-motion preferences. No formal WCAG conformance claim is made unless separately tested. | `UXBASE-0.1`; WCAG 2.2-informed implementation practice | Classification remains understandable without colour; tab/focus path reaches all answer/navigation controls; reduced-motion mode disables nonessential transition/celebration animation; responsive smoke tests show no blocked learner action. | Accessibility/UI smoke tests and browser E2E checks |
| **REQ-GAM-01** | Accepted stretch goal | Gamification shall be limited to task-compatible progress, completion, replay and restrained completion feedback. Gameful presentation shall never alter evaluator inputs, question/option membership, classification truth, reference expectations or rule precedence. Points, weighted scores, leaderboards, competitive ranking, lives, countdown pressure and content-changing random rewards are excluded from the current prototype. | `UXBASE-0.1`; CODIFICO comparison and bounded project decision | Disabling animations/progress decoration leaves all API requests and evaluator results unchanged; question/option randomization is a permutation only; no excluded mechanic appears in the learner flow. | Architecture inspection, deterministic evaluator tests and UI inspection |

## 8. Traceability and configuration control

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-TRC-01** | Accepted | Stable identifiers and backward/forward links shall preserve the chain from source/evidence/internal decision through requirement, model/rule, implementation and reference case/test. | `INT-TRACE-01`, `EVID-SE-01` | Every mandatory implemented requirement has at least one downstream implementation/model destination and verification reference or an explicitly declared gap; every executable classification rule traces back to a requirement and basis. | Traceability-matrix audit |
| **REQ-CFG-01** | Accepted | The final evaluation baseline shall identify/freeze the relevant source set, catalogue subset, rule model, reference-case suite, test specification, software revision, and execution environment. A later material change creates a new baseline/version rather than silently replacing the evaluated state. | `EVID-SE-01`, `INT-TRACE-01`, source register Section 9 | Final verification record identifies all listed versions; material post-freeze changes are recorded and trigger an updated baseline/rerun decision. | Baseline/change-log inspection |

## 9. Reference-suite and verification requirements

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-VER-01** | Accepted | The number and identity of cases/codes shall be derived from predefined coverage needs, not from a fixed quota, percentage of the catalogue, or compulsory medical domain. | `INT-SUP-01` | Chapter 3 states the selection criteria and an initial planning estimate derived from the rule/error coverage matrix; the frozen suite records any material change from that estimate. | Method/reference-suite inspection |
| **REQ-VER-02** | Accepted | Before final verification, the reference-suite coverage gate shall demonstrate all three feedback classes, multiple implemented coding-error patterns, straightforward cases, and at least some more difficult or ambiguous but objectively decidable coding situations. Each included error pattern shall have a triggering variant; important rule boundaries/interactions shall receive control or boundary coverage where applicable. | `INT-SUP-01`, `INT-SUP-04`, `EVID-SE-01` | Coverage matrix has no unexplained gap against the stated dimensions. Any intentionally uncovered requirement/rule/branch is explicitly declared rather than counted as covered. | Coverage-matrix audit |
| **REQ-VER-03** | Accepted | Each reference response shall have a predefined expected class and required criterion/explanation elements derived from the rule/source baseline before final execution; expected results shall not be copied from current implementation output or supplied to the running classifier as its answer key. | `INT-RQ-03`, `INT-TRACE-01`, `EVID-SE-01` | Every frozen `RC-*` has an expectation/source/rule record timestamped/versioned before the final run; changes retain reasons/version history; the verification oracle is stored outside the runtime classification-data path. | Baseline/reference-matrix and runtime-schema audit |
| **REQ-VER-04** | Accepted | Targeted software testing shall exercise the responsibilities materially relevant to the prototype, including rule/data unit tests, integration across persistence/API/evaluation, an end-to-end learner path where feasible, relevant negative/boundary tests, and regression reruns after material corrections. | `EVID-SE-01`, `INT-RQ-03` | Test inventory maps implemented central responsibilities to at least one appropriate test or records a justified omission. | Test inventory and execution records |
| **REQ-VER-05** | Accepted | Final execution shall compare observed results with the frozen expectations using predefined conformance categories that distinguish at least classification/rule mismatch, explanation/criterion mismatch, execution failure, and unexecuted/blocked checks where relevant. | `EVID-SE-01`, `INT-EVAL-01` | Conformance categories are defined before final result reporting and every executed test/reference variant receives a reproducible verdict. | Procedure inspection and final test report |
| **REQ-VER-06** | Accepted | Deviations shall be classified by cause at an appropriate level, distinguishing implementation, specification/rule, reference-expectation, data-preparation, infrastructure/execution, and accepted limitation where applicable. Corrections shall trigger impact analysis and affected regression/reference reruns. | `EVID-SE-01`, `INT-TRACE-01` | Deviation/change log records category, correction or disposition, affected identifiers and rerun status; previous observations are retained. | Change/deviation-log inspection |
| **REQ-VER-07** | Accepted | Representative cases shall be explained in the main text and the complete versioned reference-case matrix shall be placed in the appendix. The full record shall contain at minimum case ID, short case description, reference/expected code or accepted set, tested coding variant, expected feedback class, underlying error pattern, brief rationale, and Austrian catalogue version; rule/source identifiers shall be retained for traceability. | `INT-SUP-02`, `INT-TRACE-01` | Main text contains representative worked examples; appendix contains every frozen `RC-*` with all mandatory fields and version identifier. | Thesis/appendix inspection |
| **REQ-VER-08** | Accepted forward design | Before the next final verification run, every learner question-domain code relation and every learner `none_of_above` response shall have an independently predefined expected output in the external verification oracle. | `REQ-VER-03`, `QSAUDIT-0.1`, `MODELBASE-0.2` | The new oracle contains 100 learner code-domain expectations plus 25 learner `none_of_above` expectations, created without copying classifier output. | Oracle completeness audit |
| **REQ-VER-09** | Accepted forward design | The 18 historical `RCBASE-0.2` expectations shall remain regression obligations when the new evaluator/data model is implemented, or any intentionally retired expectation shall be explicitly justified rather than silently removed. | Configuration-control decision; historical `RCBASE-0.2` | Regression mapping accounts for all 18 historical expectations before the new software baseline is frozen. | Regression trace audit |

## 10. Open decisions before `REQBASE-1.0`

These are not silently converted into requirements until resolved. A decision may result in a requirement revision, a conditional requirement becoming active/inactive, or an explicit scope exclusion.

| ID | Open decision | Why it matters | Resolution point |
|---|---|---|---|
| **OPEN-RQ-01** | Final wording of the main research question remains unconfirmed by the supervisor. | Requirements must remain capable of answering the working RQ without being overfitted to wording that may change. | Before final Introduction/traceability freeze |
| **OPEN-EVAL-01** | The latest supervisory reply does not explicitly answer whether internal technical verification is sufficient without external domain-expert review. | Determines whether an additional evaluation activity is required; does not alter the present technical-conformance claim boundary. | Before final evaluation plan is frozen |
| **OPEN-RES-01** | Final institutional placement of observed test results remains provisional. | Affects thesis organisation only, not software behaviour. | Before Chapter 3/Results finalisation |

The absence of a fixed case count or compulsory medical domain is **not** an open decision. The supervisor has explicitly delegated those choices to the coverage-based project method.

### 10.1 Resolved decisions retained for history

| ID | Resolution | Baseline |
|---|---|---|
| **OPEN-DOM-01** | Resolved. The included response-pattern taxonomy is `PAT-DEPTH-01`, `PAT-SPEC-01`, `PAT-EVID-01`, and `PAT-STATUS-01`. `PAT-SPEC-01` is the sole initial source-backed `suboptimal` trigger; hard conditions remain `incorrect`. | `DOMBASE-0.1`, 6 August 2026 |
| **OPEN-SET-01** | Resolved. A narrowly defined hospital-sector `!` status rule is executable; no extramural-specific executable coding rule is included in the current baseline. | `DOMBASE-0.1`, 6 August 2026 |
| **OPEN-DAT-01** | Resolved for the working prototype. `SUBSET-0.1` retains the DIAGLIST fields `Diagnose`, `Kennzeichen`, `Bezeichnung`, and `Kurzbezeichnung`; source/version/checksum/subset identity are dataset-level metadata. Any later field addition requires a versioned rationale. | `CASEPLAN-0.1`, 6 August 2026 |
| **OPEN-INT-01** | Resolved. Each case defines one coding target and each evaluation request contains exactly one submitted ICD code. Multiple learner attempts are separate requests. Multi-code response aggregation is outside the initial prototype and requires an explicit later model/rule revision. | `MODELBASE-0.1`, 6 August 2026 |

## 11. Requirements-to-Chapter-3 mapping

| Chapter 3 location | Principal requirement groups |
|---|---|
| 3.1.1 Development Process and Requirements | `REQ-SCP-*`, `REQ-TRC-01`, requirement derivation/change handling, open-decision control |
| 3.1.2 Data Basis and Prototype Subset | `REQ-DAT-*`, relevant parts of `REQ-CFG-01` |
| 3.1.3 Artefact and Rule Model | `REQ-MOD-*`, `REQ-RUL-*`, `REQ-FBK-*` |
| 3.1.4 Architecture and Implementation | `REQ-INT-*`, `REQ-ARC-*`, `REQ-IMP-01`, `REQ-DOC-01`, `REQ-UI-*`, `REQ-GAM-01` |
| 3.2.1 Reference Cases and Test Design | `REQ-VER-01` through `REQ-VER-04`, `REQ-VER-07` through `REQ-VER-09` |
| 3.2.2 Verification Procedure and Conformance Criteria | `REQ-CFG-01`, `REQ-VER-03` through `REQ-VER-06`, claim boundary from `REQ-SCP-03` |

## 12. Freeze criteria for `REQBASE-1.0`

The requirement baseline is ready to freeze when:

- every Accepted requirement has a concrete acceptance criterion and a downstream model/implementation destination;
- the final included error-pattern taxonomy and source-backed `suboptimal` criteria remain fixed to a versioned domain baseline (`DOMBASE-0.1` currently satisfies this condition);
- the `SUBSET-0.2` 99-record DIAGLIST selection (92 learner-domain records plus 13 retained historical records, six overlapping) is reproducibly regenerated from the frozen source, or any later change is explicitly versioned; hospital-setting rule activation is already fixed by `DOMBASE-0.1`;
- the tagged-single-response interaction cardinality fixed in `MODELBASE-0.2` (one `code_response` or `none_of_above_response` per question) remains reflected by the API/UI and reference-response schema;
- the forward `PATIENTBASE-0.1`/`QUESTIONBASE-0.1` suite of six patients and 25 atomic learner questions, plus the eight historical cases retained as hidden `verification_only` regression fixtures (superseding the historical `CASEBASE-0.2`/`CASEPLAN-0.2` learner-facing suite per the pedagogical review recorded in `HANDOFF.md` §4.7), is adopted for the frozen suite, or any further material change is justified against the declared coverage matrix in `chapter3_reference_case_coverage_plan_forward_0_3.md`;
- candidate `RCBASE-0.3`'s 125 new learner expectations have completed their stated human/source oracle audit, and the four reconstructed historical `0.2` rows have been diffed against the original files;
- the targeted technical coverage in `TESTBASE-0.1`, extended for the forward patient/question/`none_of_above`/immediate-feedback/review/UX obligations, is bound to the as-built implementation before the principal run;
- every mandatory requirement has a planned verification path or an explicitly documented gap;
- the unresolved external-expert-review question has either been answered or is conservatively handled without expanding the evaluation claim; and
- any material change to these records increments the catalogue version and preserves the previous decision history.

At that point the catalogue should be assigned `REQBASE-1.0`. `RULEBASE-0.2` already cites the relevant `REQ-*` identifiers rather than restating their rationales from scratch; any later rule-baseline revision must preserve that linkage. Historical `RULEBASE-0.1`/`CASEPLAN-0.2` remain valid as the record of what the first implementation increment actually satisfied — they are superseded as the learner-facing target, not deleted as history.
