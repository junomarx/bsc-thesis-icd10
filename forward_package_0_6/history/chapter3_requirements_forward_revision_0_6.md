# Chapter 3 Requirements Catalogue, Forward Revision 0.6

**Document status:** working requirements delta; not implementation-frozen and not verification evidence  
**Date:** 8 August 2026  
**Base catalogue:** `chapter3_requirements_catalogue.md`, catalogue version 0.5  
**Forward design inputs:** `QSAUDIT-0.1`, `RULEBASE-0.2`, `MODELBASE-0.2`, `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`  

## 1. Purpose and version rule

This document records the requirements changes caused by replacing the one-case/one-question learner model with a six-patient, multi-question model. It is deliberately a **delta** against catalogue 0.5 rather than a rewritten claim that the external implementation already satisfies these requirements. The entries below are to be rebased into the implementation repository's current requirements catalogue before the next software freeze.

The historical `CASEBASE-0.2` / `RCBASE-0.2` state remains regression evidence. New learner requirements do not retroactively change what that historical baseline demonstrated.

## 2. Existing requirements requiring revision

### `REQ-MOD-01` — explicit question facts are the evaluator boundary

**Revised requirement.** Rule evaluation shall use only facts explicitly represented for the atomic `coding_question`. Patient demographics, history and contextual prose may be learner-visible, but shall not become evaluator input unless the relevant value is separately represented as a typed `question_fact`.

**Basis:** `INT-SCOPE-02`, `INT-SCOPE-03`, `QSAUDIT-0.1`, `MODELBASE-0.2`.  
**Acceptance criterion:** every fact consumed by a rule is addressable by a declared `fact_key`; changing unrelated patient-context prose cannot change the classification result.  
**Verification:** rule-input trace inspection and context-independence tests.

### `REQ-MOD-02` — patient, question and response are distinct units

**Revised requirement.** A learner patient and an atomic coding question shall be distinctly versioned entities. A patient may contain multiple independently evaluable questions. A verification expectation remains one response to one question; the evaluator shall not classify an entire patient record as one aggregate response.

**Basis:** `INT-SUP-01`, `INT-MOD-01`, `MODELBASE-0.2`.  
**Acceptance criterion:** each learner question belongs to exactly one patient and each evaluation request contains exactly one tagged response for one question.  
**Verification:** schema/API inspection and reference-response tests.

### `REQ-INT-01` — tagged single-response interaction

**Revised requirement.** The learner workflow shall present a synthetic patient, permit navigation through that patient's variable-length question set, accept exactly one response per question, and return feedback. A response is either `code_response(code)` or `none_of_above_response`; multi-code aggregation remains outside the prototype.

**Basis:** `INT-RQ-01`, `INT-MOD-01`, `RULEBASE-0.2`, `MODELBASE-0.2`.  
**Acceptance criterion:** the API/UI supports both response kinds without representing `none_of_above` as an ICD code and without introducing multi-code scoring.  
**Verification:** API and end-to-end interaction tests.

### `REQ-DAT-03` — question-driven subset selection

**Revised requirement.** The active catalogue subset shall be derived from the source-audited question/evaluation domains and retained regression obligations. It shall not be restricted to one ICD family, one medical domain, or an arbitrary code count.

**Basis:** `INT-SUP-01`, `QSAUDIT-0.1`, `SRC-AT-DIAGLIST-2026`.  
**Acceptance criterion:** every subset record has a learner-domain or retained-regression purpose; every code referenced by a question relation exists in the active subset.  
**Verification:** subset-to-question trace audit and deterministic DIAGLIST extraction check.

### `REQ-RUL-02` — generalized but source-bounded suboptimal classification

**Revised requirement.** `suboptimal` shall be assigned only by an explicit source-specific rule or a source-audited `less_specific_supported` question relation whose represented facts justify an identified `improvement_code`. Code morphology, a `.9` suffix, designation wording or code length shall never independently determine `suboptimal`.

**Basis:** `INT-SUP-04`, `SRC-AT-DOC-2026`, `QSAUDIT-0.1`, `RULEBASE-0.2`.  
**Acceptance criterion:** every generic `suboptimal` relation names an accepted improvement code and a source-audited specificity basis; `E11.9`, `F03` and `N40` act as countercontrols against lexical/code-shape heuristics.  
**Verification:** rule-data validation and countercontrol tests.

## 3. New requirements

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-MOD-03** | Accepted forward design | A learner case shall represent one synthetic patient that can contain multiple separately identified coding questions; the patient is not itself the atomic evaluation unit. | `PATIENTPLAN-0.4`, `MODELBASE-0.2` | Every learner question belongs to one patient and evaluation addresses one `coding_question` at a time. | Schema/API inspection |
| **REQ-MOD-04** | Accepted forward design | Patient context and question rule facts shall be represented separately. Context is presentation data; only typed `question_fact` rows may be consumed by the evaluator. | `MODELBASE-0.2`, `INT-SCOPE-03` | No evaluator query or rule scans `patient_context_item.display_text` or the question prompt to derive medical facts. | Architecture/code inspection plus mutation test |
| **REQ-MOD-05** | Accepted forward design | Learner question cardinality shall be data-driven; no database, API or UI constraint shall hard-code three questions per patient. | `PATIENTPLAN-0.4`, `MODELBASE-0.2` | The materialized learner set contains question counts `3,3,3,5,5,6`, and the implementation renders these from data without a fixed maximum of three. | Data invariant plus API/UI tests |
| **REQ-MOD-06** | Accepted forward design | Evaluation-domain membership and displayed option membership shall be distinct. A code may be evaluable without being displayed. | `QSAUDIT-0.1`, `MODELBASE-0.2` | `Q-004-05/M54.5`, `Q-005-05/I10`, and the non-displayed J44 technical relations remain evaluable while absent from their displayed code sets. | Data invariant and API tests |
| **REQ-DAT-06** | Accepted forward design | The active catalogue subset shall be derived from the adopted multi-domain question bank and retained regression needs; it shall not remain restricted to the original COPD/status families. | `PATIENTPLAN-0.4`, `QSAUDIT-0.1`, `SRC-AT-DIAGLIST-2026` | Every learner-domain code exists in the active subset and the subset spans all adopted coding families. | Deterministic extraction and dataset audit |
| **REQ-DAT-07** | Accepted forward design | Multiple represented conditions across the patient set shall function as genuine coding targets rather than serving only as cosmetic background diversity. | project pedagogical decision; `PATIENTPLAN-0.4` | The question bank materially targets the physiological and mental-health conditions declared by its design rather than repeating one diagnosis family. | Patient-to-question content audit |
| **REQ-DAT-08** | Accepted forward design | COPD-related learner content shall be confined to at most one patient; any additional COPD cases needed for technical regression shall remain verification fixtures rather than learner patients. | project design decision after pedagogical review; `PATIENTPLAN-0.4`, `QSAUDIT-0.1` | Exactly one learner patient contains J44/COPD content; the remaining five learner patients contain no J44 question relation. | Dataset audit |
| **REQ-DAT-09** | Accepted forward content baseline | The present learner content baseline shall contain six synthetic patients and 25 atomic coding questions with versioned patient-to-question membership; these counts are a project content choice, not a catalogue-coverage quota or schema maximum. | `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`, `QSAUDIT-0.1` | Materialized counts and membership match the versioned baseline; later changes increment the patient/question baseline rather than silently changing these IDs. | Manifest and referential-integrity checks |
| **REQ-RUL-06** | Accepted forward design | `none_of_above` shall be an interface response kind, not an ICD catalogue record. It is `correct` exactly when the fixed displayed code set contains no `accepted_reference`; otherwise it is `incorrect`. | project interaction decision; `RULE-NOA-01` | Set-intersection logic is deterministic for all 25 learner questions; `Q-004-05` and `Q-005-05` are positive controls and all other learner questions are negative controls. | Rule unit tests plus full learner-oracle sweep |
| **REQ-RUL-07** | Accepted forward design | Every classified code response shall have an explicit question-scoped semantic relation. The evaluator shall not manufacture `incorrect` or `suboptimal` from `submitted_code != reference_code`, lexical similarity, code depth or catalogue label alone. | `QSAUDIT-0.1`, `RULEBASE-0.2` | Undefined relations are rejected by the eligibility gate; every hard generic relation links to at least one explicit question fact. | Data-contract validation and negative-boundary tests |
| **REQ-INT-02** | Accepted forward design | Patient identity, demographics, history-availability boundary and learner-visible context shall remain accessible while the learner works through that patient's questions. | project pedagogical decision; `PATIENTPLAN-0.4` | Learner can reopen the patient summary/context without losing current question state. | UI/end-to-end test |
| **REQ-INT-03** | Accepted forward design | Question order shall be randomized between playthroughs without changing question membership, option membership, facts, or expected evaluation semantics. | project pedagogical decision | Repeated starts can produce different question order while a frozen question ID always resolves to identical semantic content and option membership. | Deterministic-content and random-order UI/service tests |
| **REQ-INT-04** | Accepted forward design | Every learner question shall have a fixed, versioned option set containing a dedicated `none_of_above` option. Only presentation order may be randomized. | `RULE-NOA-01`, `MODELBASE-0.2` | All 25 questions have exactly one `none_of_above` row; option membership is stable for a frozen `QUESTIONBASE-*`. | Data invariant and UI tests |
| **REQ-VER-08** | Accepted forward design | Before the next final verification run, every learner question-domain code relation and every learner `none_of_above` response shall have an independently predefined expected output in the external verification oracle. | `REQ-VER-03`, `QSAUDIT-0.1`, `MODELBASE-0.2` | The new oracle contains 100 learner code-domain expectations plus 25 learner `none_of_above` expectations, created without copying classifier output. | Oracle completeness audit |
| **REQ-VER-09** | Accepted forward design | The 18 historical `RCBASE-0.2` expectations shall remain regression obligations when the new evaluator/data model is implemented, or any intentionally retired expectation shall be explicitly justified rather than silently removed. | configuration-control decision; historical `RCBASE-0.2` | Regression mapping accounts for all 18 historical expectations before the new software baseline is frozen. | Regression trace audit |

## 4. Immediate trace consequences

The forward dependency chain is now:

> `QSAUDIT-0.1` -> requirements revision 0.6 -> `RULEBASE-0.2` / `MODELBASE-0.2` -> `SUBSET-0.2` + `PATIENTBASE-0.1` + `QUESTIONBASE-0.1` -> SQL/API/UI revision -> new external oracle -> verification.

The materialized learner data are therefore design inputs to implementation, not test results. No `PASS`, conformance percentage, or frozen verification claim follows merely from producing these files.

## 5. Rebase note

Catalogue 0.5 predates the implementation repository's later `CASEBASE-0.2` work. When this delta is moved into that repository, the current repository requirements/traceability file must be treated as the merge base. The IDs above should be preserved where possible, but an ID collision in the repository is resolved by renumbering the **new** entry, not by silently replacing an existing requirement.
