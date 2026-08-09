# Chapter 3 Patient and Question Design Plan

**Document status:** working concept and data-model input, not a frozen verification baseline  
**Working design revision:** `CASEPLAN-0.4`  
**Current planning artefacts:** `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`  
**Source-audit overlay:** `QSAUDIT-0.1`  
**Historical regression baseline retained:** `CASEBASE-0.2`, `RCBASE-0.2`  
**Date:** 8 August 2026
**Interface clarification:** 9 August 2026, `APIBASE-0.1`

## 1. Purpose

This document revises the learner-case concept after formative inspection of the implemented prototype exposed a mismatch between technical verification efficiency and pedagogical usefulness. `CASE-005`, `CASE-006`, and `CASE-007` were deliberately introduced with one-code response domains to close rule-integration coverage gaps. That was appropriate for verification fixtures, but those same records were also learner-visible. As a result, some exercises offered no meaningful decision: the single selectable code effectively revealed the expected response.

The correction is structural. A learner-facing case is henceforth a **synthetic patient context containing several independently answerable coding questions**, while the atomic evaluator still judges **one response to one coding question**. Technical regression fixtures and learner presentation are no longer required to share the same shape.

This planning revision was intentionally written before new Austrian ICD-10 records or rules were admitted. The subsequent `QSAUDIT-0.1` now supplies the source-reviewed overlay for all 25 proposed learner questions. Where the tables below still say **new source/rule audit required**, that wording records the state at `QUESTIONPLAN-0.4`; it is superseded for source status by `QSAUDIT-0.1`. Rule implementation and runtime/reference baselines remain pending.

## 2. Design invariants

The following constraints govern the redesigned data layer and question bank.

1. The learner-facing prototype contains **six synthetic patients**.
2. Each patient contains a variable number of coding questions. The working plan deliberately uses **three questions for three foundational patients and five, five, and six questions for three more involved patients**, giving 25 learner questions. These counts are content decisions, not schema or application limits.
3. Each question is an independent evaluation unit: one coding target, one selected response, one deterministic outcome.
4. Questions must remain answerable in any order from the persistent patient information plus the question-specific facts. A preceding answer must never supply information required by a later question.
5. Patient background information is contextual. It cannot silently become evaluator input. Only typed question facts explicitly consumed by a declared source-specific rule or linked to a semantic relation through `question_relation_fact` may affect classification; no unmaterialized `rule_relevant` flag is assumed.
6. The application codes documented information; it does not diagnose a disease from symptoms, infer causal relationships between comorbidities, or perform clinical decision support.
7. Every learner question contains a fixed, versioned displayed option set. Normally this should comprise three or four ICD-code candidates plus a distinct `none_of_above` option.
8. `none_of_above` is not represented as an ICD code. It is correct only when no displayed code option is an explicitly acceptable response for that question.
9. The evaluator's complete response domain and the learner's displayed option set are different concepts. A question can have a broader evaluation domain than the options shown during an exercise.
10. Every displayed option, including `none_of_above`, must have a predefined expectation in the external verification oracle before final verification.
11. Question order is randomized between playthroughs. Option order may also be randomized, but the **membership** of the option set is fixed by the question baseline so that randomization can never change which answer is correct.
12. The existing 18 `RCBASE-0.2` expectations remain regression obligations. The redesign may add expectations but must not retrospectively rewrite successful historical fixtures.
13. `CASE-004` and `CASE-008` remain technical verification fixtures. They do not need to become patients merely to make the data model uniform.
14. Neither the database schema, API nor frontend may encode a fixed number of questions per patient. The patient-to-question relation is one-to-many with no pedagogically meaningful upper bound hardcoded into the application.
15. The expanded learner bank must be **genuinely multi-domain**. Patient comorbidities are not merely narrative decoration: across each patient's question set, several of the represented conditions or contexts must become actual coding targets, and the combined catalogue subset must span materially different ICD-10 chapters and code families rather than remain dominated by `J44.*`.
16. **At most one of the six learner patients may contain a COPD-related condition.** COPD-heavy `CASEBASE-0.2` records remain valid technical regression fixtures, but technical legacy coverage does not determine the clinical composition of the learner bank.

### 2.1 Difficulty structure

The distinction between foundational and more involved patients concerns **coding complexity**, not diagnostic inference. Foundational questions should normally present a clearly documented coding target and one or two decisive facts. More involved questions may require the learner to reconcile several pieces of known information, distinguish documented from self-reported material, attend to encounter/status context, recognize when greater specificity is supported, and disregard plausible but irrelevant background conditions.

Symptoms and examination findings can therefore increase the information burden, but they do not license the prototype to infer an undocumented disease. Where a final diagnosis is absent, a symptom or finding may become a coding target only if the applicable Austrian coding guidance supports that treatment. This must be established during the source audit rather than assumed in the case narrative.

The planned distribution is:

| Patient | Difficulty role | Planned questions |
|---|---|---:|
| `PATIENT-001` | foundational | 3 |
| `PATIENT-002` | foundational | 3 |
| `PATIENT-003` | foundational/context-focused | 3 |
| `PATIENT-004` | involved, partial-history reasoning | 5 |
| `PATIENT-005` | involved, mixed treatment/history reasoning | 5 |
| `PATIENT-006` | involved, unavailable-anamnesis/acute-context reasoning | 6 |
| **Total** |  | **25** |

## 3. Conceptual separation in the revised data model

The intended logical hierarchy is:

```text
Patient
  -> Patient background / known conditions
  -> Coding question
       -> Question-specific facts
       -> Evaluation domain
       -> Displayed options

Independent verification oracle
  -> expected result for each tested question/response relation
```

The patient record establishes who the synthetic person is and which information is persistently available. The question record defines the coding task. `question_fact` supplies only the evidence relevant to that task. `question_code_domain` specifies responses the deterministic evaluator knows how to judge. `question_option` specifies what the learner is actually offered.

The independent `RC-*` oracle remains outside the runtime database and is never an input to the PHP evaluation path.

## 4. Six-patient learner plan

Names below are deliberately synthetic. Age and sex are included to make the vignettes recognizably person-centred. Ethnicity is **not populated in this working baseline**: unless it is relevant to a source-backed coding rule, adding it risks suggesting a coding or clinical significance that the prototype neither models nor needs. If retained later for representational purposes, it must be display-only and must never influence evaluation without an explicit requirement and source basis.

| Patient | Principal physiological domains | Mental/behavioural or neurocognitive component | Planned questions | COPD? |
|---|---|---|---:|---|
| `PATIENT-001` | respiratory, endocrine/metabolic, musculoskeletal, circulatory background | none required for this foundational patient | 3 | **yes** |
| `PATIENT-002` | cardiovascular, renal, gout/metabolic | panic disorder | 3 | no |
| `PATIENT-003` | neurological, endocrine, gynaecological, injury history | generalized anxiety disorder | 3 | no |
| `PATIENT-004` | endocrine/metabolic, ophthalmological, haematological, musculoskeletal | recurrent depressive disorder | 5 | no |
| `PATIENT-005` | neurological/movement, dermatological, metabolic, circulatory | chronic psychiatric illness | 5 | no |
| `PATIENT-006` | cardiovascular/cerebrovascular, neurological, genitourinary, acute symptom/finding | documented dementia | 6 | no |

### 4.1 `PATIENT-001` — established chronic multimorbidity

**Synthetic identity:** Anna Berger, 68, female  
**History availability:** established longitudinal record  
**Case type:** chronic disease with an acute documented respiratory episode  
**Difficulty role:** foundational  
**General state:** lives independently; established diagnoses and prior pulmonary-function results are available in the record.

Persistent background items:

- documented COPD;
- documented arterial hypertension;
- documented type 2 diabetes mellitus;
- documented knee osteoarthritis; and
- current documented acute lower-respiratory infection in the represented encounter.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-001-01` | COPD with documented acute lower-respiratory infection and stable-phase FEV1 = 55% predicted | **existing rule baseline**; carries forward `CASE-001` behaviour | straightforward correct/incorrect/suboptimal discrimination |
| `Q-001-02` | documented type 2 diabetes mellitus | **new source/rule audit required** | introduce an endocrine/metabolic coding family and separate it from the respiratory target |
| `Q-001-03` | documented knee osteoarthritis | **new source/rule audit required** | introduce a musculoskeletal coding family while hypertension and other history remain contextual distractors |

For `Q-001-01`, a suitable learner option pattern is already defensible from the frozen rule set: `J44.02` (correct), one neighbouring FEV1 suffix such as `J44.01` (incorrect), `J44.09` (suboptimal because more specific information is present), plus `none_of_above` (incorrect). The full six-code family can remain in the evaluator/regression domain without being displayed in full.

### 4.2 `PATIENT-002` — cardiovascular/renal multimorbidity with a mental-health history

**Synthetic identity:** Michael Novak, 73, male  
**History availability:** established record  
**Case type:** chronic multimorbidity  
**Difficulty role:** foundational  
**General state:** cardiovascular and renal diagnoses are documented in the longitudinal record. A documented panic disorder supplies a separate mental-health coding domain without being treated as the explanation for physical symptoms.

Persistent background items:

- documented atrial fibrillation;
- documented chronic kidney disease, with the precise documented stage to be fixed during case construction;
- documented arterial hypertension;
- documented gout; and
- documented panic disorder.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-002-01` | documented atrial fibrillation at the source-supported level of specificity | **new source/rule audit required** | cardiovascular code-family selection from explicit documentation |
| `Q-002-02` | documented chronic kidney disease with a represented stage | **new source/rule audit required** | renal hierarchy/specificity task, subject to Austrian catalogue rules |
| `Q-002-03` | documented panic disorder | **new source/rule audit required** | introduce a mental/behavioural ICD family within the same patient while physical comorbidity remains contextual |

The previous COPD exact-50% fixture is not transferred into this patient. It remains available in `CASEBASE-0.2`/`RCBASE-0.2` solely for technical regression of the previously implemented FEV1 rule.

### 4.3 `PATIENT-003` — neurological/endocrine outpatient history

**Synthetic identity:** Lea Horvat, 46, female  
**History availability:** established but comparatively limited outpatient record  
**Case type:** hospital-outpatient patient with neurological, endocrine and mental-health diagnoses  
**Difficulty role:** foundational  
**General state:** ambulatory and clinically stable; selected documented chronic conditions are present in the record.

Persistent background items:

- documented recurrent migraine;
- documented hypothyroidism;
- documented generalized anxiety disorder;
- documented endometriosis; and
- a prior wrist fracture recorded as historical rather than a current diagnosis.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-003-01` | documented migraine at the supported specificity level | **new source/rule audit required** | neurological code-family selection |
| `Q-003-02` | documented hypothyroidism | **new source/rule audit required** | endocrine code-family selection with unrelated neurological and gynaecological history as context |
| `Q-003-03` | documented generalized anxiety disorder | **new source/rule audit required** | mental/behavioural code-family selection and a candidate for carefully sourced decoys |

The previous `Z01.6!` outpatient status case no longer has to appear in learner navigation. `CASE-003`, together with the prohibited-status fixtures, remains part of technical regression and can still demonstrate context-sensitive rule behaviour during verification without consuming one of this patient's learner questions.

### 4.4 `PATIENT-004` — limited history / new-to-service patient

**Synthetic identity:** Sofia Marin, 64, female  
**History availability:** partial; only selected external documentation is available  
**Case type:** chronic multimorbidity with incomplete longitudinal history  
**Difficulty role:** involved  
**General state:** new to the represented service. Some diagnoses are documented in transferred records; other history is self-reported and must remain explicitly distinguishable from confirmed record information.

Persistent background items:

- documented type 2 diabetes mellitus;
- documented glaucoma;
- documented recurrent depressive disorder;
- documented iron-deficiency anaemia; and
- chronic low-back pain reported by the patient, with the exact documented musculoskeletal finding to be distinguished from the self-reported history.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-004-01` | documented type 2 diabetes mellitus, with any represented complication/specificity fixed only after source audit | **new source/rule audit required** | endocrine/metabolic coding task |
| `Q-004-02` | documented glaucoma at the supported documented level of detail | **new source/rule audit required** | ophthalmological code-family task |
| `Q-004-03` | documented recurrent depressive disorder | **new source/rule audit required** | mental/behavioural coding task in a patient whose physical conditions could distract from the explicit target |
| `Q-004-04` | documented iron-deficiency anaemia | **new source/rule audit required** | blood/haematological code-family task |
| `Q-004-05` | low-back symptom/finding versus the information actually documented in the available record, **only if Austrian guidance supports the resulting coding relation** | **new source/rule audit required** | involved documentation-sufficiency task and candidate for `none_of_above = correct` |

The former FEV1-below-35% `CASE-005` is retained only in the technical regression suite. It no longer supplies learner content for this patient.

### 4.5 `PATIENT-005` — treatment-associated mixed history

**Synthetic identity:** Daniel Weiss, 57, male  
**History availability:** established specialist and general medical documentation  
**Case type:** long-term treatment with multimorbidity and a documented treatment-associated condition  
**Difficulty role:** involved  
**General state:** chronic psychiatric and medical care are established; the record contains a clinician-documented movement disorder associated with long-term antipsychotic treatment. The application must not infer the association itself.

Persistent background items:

- documented chronic psychiatric illness under long-term antipsychotic treatment;
- clinician-documented tardive dyskinesia/treatment-associated movement disorder;
- documented psoriasis;
- documented dyslipidaemia; and
- documented arterial hypertension.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-005-01` | the documented chronic psychiatric diagnosis at an appropriate supported specificity level | **new source/rule audit required** | mental/behavioural code-family task |
| `Q-005-02` | clinician-documented tardive dyskinesia/treatment-associated condition | **new source/rule audit required; possible multi-code complexity must be checked before adoption** | neurological/movement-disorder task without asking the software to infer causality |
| `Q-005-03` | documented psoriasis | **new source/rule audit required** | dermatological code-family task amid psychiatric and metabolic history |
| `Q-005-04` | documented dyslipidaemia at the supported specificity level | **new source/rule audit required** | metabolic code-family task |
| `Q-005-05` | documented arterial hypertension or a deliberately incomplete option set around that explicit target | **new source/rule audit required** | circulatory code-family task and possible `none_of_above` exercise |

`Q-005-02` is intentionally a **research candidate**, not an adopted coding task. If authoritative Austrian guidance shows that the represented situation inherently requires multiple codes or coding conventions outside the prototype's one-response model, either the model must be explicitly extended or this question must be replaced. It must not be forced into single-code form for convenience.

The former exact-35% COPD `CASE-006` remains a technical regression vector only and is not represented in this learner patient.

### 4.6 `PATIENT-006` — unconscious acute patient

**Synthetic identity:** Peter Gruber, 76, male  
**History availability:** patient anamnesis unavailable at presentation; limited pre-existing electronic record available  
**Case type:** unconscious acute presentation with known chronic background  
**Difficulty role:** involved  
**General state:** the patient cannot provide a history. Information is therefore separated into prior documented record facts and current examination/investigation findings. The physical examination is **not** described as anamnesis.

Persistent background items:

- documented coronary artery disease;
- prior documented cerebrovascular disease;
- documented epilepsy;
- documented dementia without an assumed aetiology beyond what the record explicitly states;
- documented benign prostatic hyperplasia; and
- current loss of consciousness with examination/investigation findings to be specified without diagnostic inference.

Planned questions:

| ID | Coding opportunity | Rule/source state | Pedagogical role |
|---|---|---|---|
| `Q-006-01` | documented coronary artery disease at the level supported by the accessible record | **new source/rule audit required** | cardiovascular coding from pre-existing records while the patient cannot provide history |
| `Q-006-02` | prior cerebrovascular disease, framed precisely as current sequela/history only if that distinction is documented and source-supported | **new source/rule audit required** | temporal/status reasoning without converting a past event into a current acute diagnosis |
| `Q-006-03` | documented epilepsy | **new source/rule audit required** | neurological code-family task distinct from the cause of the current unconscious state |
| `Q-006-04` | documented dementia at the specificity actually supported by the record | **new source/rule audit required** | mental/behavioural or neurocognitive coding task without inventing an aetiology |
| `Q-006-05` | documented benign prostatic hyperplasia | **new source/rule audit required** | genitourinary code-family task and deliberate contrast with the acute presentation |
| `Q-006-06` | current loss of consciousness, examination finding, or subsequently documented acute diagnosis, defined as a self-contained task with all required findings included | **new source/rule audit required** | highest-information-burden exercise and strong candidate for a source-backed `none_of_above` relation |

The current loss of consciousness must never be converted by the prototype into a guessed underlying diagnosis. Any acute coding question for this patient must state what has actually been documented by the treating team or what finding is itself the explicit coding target.

The former exact-70% COPD `CASE-007` remains available only as a technical regression fixture and does not appear in this learner patient.

## 5. What the six-patient plan deliberately achieves

The six records create variation without requiring six unrelated rule engines.

| Dimension | Planned coverage |
|---|---|
| established longitudinal history | `PATIENT-001`, `002`, `005` |
| comparatively limited/partial history | `PATIENT-003`, `004` |
| history unavailable from patient | `PATIENT-006` |
| chronic multimorbidity | all six to varying degrees |
| chronic + acute/mixed presentation | `PATIENT-001`, `004`, `006` |
| treatment-associated documented condition | `PATIENT-005` |
| hospital-outpatient status/context | `PATIENT-003` |
| COPD learner content | **`PATIENT-001` only** |
| legacy FEV1 bands/boundaries | retained separately in technical `CASEBASE-0.2`/`RCBASE-0.2` regression fixtures |
| explicit mental/behavioural or neurocognitive condition | `PATIENT-002`, `003`, `004`, `005`, `006` |
| foundational three-question cases | `PATIENT-001`, `002`, `003` |
| involved five/six-question cases | `PATIENT-004`, `005`, `006` |
| `none_of_above` present as an option | every learner question |
| at least one `none_of_above = correct` task | to be designed after source audit, likely `Q-003-03`, `Q-004-05`, `Q-005-05`, and/or `Q-006-03` |

The background conditions are intentionally more numerous than the coding targets. Their pedagogical purpose is to make each patient realistic enough that the learner must identify the information relevant to the stated coding task. They are not an invitation to encode every diagnosis in the record, and they must not be used by the evaluator unless a question explicitly activates a fact.

### 5.1 Catalogue-diversity objective

The original 13-record `SUBSET-0.1` was appropriate for proving the first rule model but is no longer adequate for the revised learner artefact. The new subset is expected to draw from the different clinical and contextual domains already represented by the patients. At the planning level these include:

| Patient | Candidate ICD-10 domains to be represented after source audit |
|---|---|
| `PATIENT-001` | respiratory; endocrine/metabolic; musculoskeletal; circulatory context |
| `PATIENT-002` | circulatory; genitourinary/renal; musculoskeletal/metabolic as applicable to gout; mental/behavioural |
| `PATIENT-003` | neurological; endocrine/metabolic; mental/behavioural; gynaecological and injury-history context |
| `PATIENT-004` | endocrine/metabolic; eye; mental/behavioural; haematological; musculoskeletal/symptom or finding |
| `PATIENT-005` | mental/behavioural; nervous-system/movement-disorder; dermatological; endocrine/metabolic; circulatory |
| `PATIENT-006` | circulatory/cerebrovascular; neurological; mental/behavioural or neurocognitive; genitourinary; symptoms/signs or an explicitly documented acute condition |

These are **domain targets, not yet code assignments**. Exact Austrian 2026 codes, hierarchy levels, markers and permissible alternatives must be derived from the authoritative catalogue and coding guidance before entering `SUBSET-0.2` or an `RC-*` expectation.

This catalogue breadth is deliberately separated from rule-engine breadth. A diverse set of ICD codes does not require one bespoke software rule class per diagnosis. Generic mechanisms such as predefined acceptance, source-backed specificity comparison, context/status checks, option eligibility and `none_of_above` can be reused across different code families where their semantics genuinely apply. Conversely, a rule must not be generalized across families merely to reduce implementation work. Family-specific coding behaviour is introduced whenever the authoritative sources require it.

There is likewise no requirement that every code family produce all three feedback classes. `suboptimal` remains a source-dependent judgement. The question bank as a whole must exercise `correct`, `suboptimal` and `incorrect`, while each individual question exposes only classifications that can be defended for its own code family and represented facts.

## 6. Legacy coverage carried into the redesign

The old cases no longer need a one-to-one learner counterpart. Their value is as immutable regression evidence for behaviour that was already implemented and tested. Keeping them outside learner navigation is therefore methodologically cleaner than distorting the new patient population merely to preserve historical presentation.

| Historical fixture(s) | Role after redesign | Learner-bank consequence |
|---|---|---|
| `CASE-001` | technical regression of the J44.0/55% path; its source-backed scenario may also underpin `PATIENT-001 / Q-001-01` | **only learner patient retaining COPD** |
| `CASE-002`, `CASE-005`, `CASE-006`, `CASE-007` | technical regression of additional FEV1 families/bands and exact boundaries | no learner counterpart required |
| `CASE-003` | technical regression of the permitted hospital-outpatient `Z01.6!` context | no learner counterpart required |
| `CASE-004`, `CASE-008` | verification-only prohibited `!` status branches | remain excluded from learner navigation |

The resulting learner bank is therefore free to represent diverse coding domains while the old regression suite continues to prove that the previously established FEV1 and status logic has not been broken. Technical coverage is preserved; only its former accidental control over learner-case composition is removed.

## 7. Response-domain policy for learner questions

The former equality

`case response domain = selectable learner options`

must be removed.

For each new question there are three related but distinct sets:

1. **evaluation domain**: responses for which the model has a deterministic, source-backed judgement;
2. **displayed option set**: the fixed subset of candidate responses shown to the learner for that question; and
3. **acceptable set**: response(s) classified as correct for that task.

For the one retained learner FEV1 question, the evaluation domain can retain all six family records while the learner normally sees a compact four-option exercise: a correct specific code, a carefully chosen evidence-conflicting neighbour, an unspecified/suboptimal code, and `none_of_above`. The additional FEV1 variants remain technical regression data rather than additional learner questions.

No global rule requires every individual question to contain an example of all three feedback classes. Coverage of `correct`, `suboptimal`, and `incorrect` is a property of the question bank and verification matrix. When a question can support all three naturally, as the FEV1 tasks do, doing so is useful.

## 8. `none_of_above` semantics

`none_of_above` must have deterministic semantics and its own option identity.

Let `D(q)` be the displayed code options for question `q` and `A(q)` its explicitly acceptable code set. Then:

- `none_of_above` is **incorrect** if `D(q) ∩ A(q)` is non-empty;
- `none_of_above` is **correct** if `D(q) ∩ A(q)` is empty and the question is otherwise valid/evaluable.

This rule depends only on the frozen question/option baseline. Randomizing the order of options does not change it. Dynamically sampling a different subset of code decoys at runtime is therefore excluded from the first redesign because it could accidentally change the truth of `none_of_above`.

A `none_of_above = correct` question must not be created by withholding the correct code arbitrarily after implementation. The displayed set and expected result must be designed and source-checked beforehand, then placed in the independent RC oracle.

## 9. Logical entities to carry forward

No SQL should be frozen until the question/source audit is complete, but the next physical schema must be capable of representing at least the following semantics.

### `patient_case`

- `patient_id`
- patient-baseline identity
- synthetic display name
- age
- sex
- general-condition summary
- history-availability classification
- history summary
- intended use

### `patient_condition`

- `patient_id`
- stable condition/background-item ID
- display label
- status
- information source (`documented_record`, `patient_reported`, `relative_reported`, `current_encounter`, etc.)
- concise summary
- display order

### `coding_question`

- `question_id`
- `patient_id`
- prompt
- explicit coding target
- encounter setting / diagnosis role only where relevant
- intended use
- option-set version

There is no patient-level question-count column and no maximum-cardinality constraint. Question count is obtained by the number of `coding_question` rows related to the patient. The same application code must therefore render three, five, six or another valid number of questions without structural changes.

### `question_fact`

- `question_id`
- `fact_key`
- typed value
- unit where relevant
- human-readable feedback/trace label
- optional link to the patient-context item from which the fact was materialized

The earlier planning idea of a Boolean `rule_relevant` flag is not part of `MODELBASE-0.2` and is superseded. Source-specific rules explicitly declare which fact keys they consume; generic semantic relations identify their supporting/conflicting facts through `question_relation_fact`. Raw facts are evaluator-internal before submission under `APIBASE-0.1`, and the evaluator may not mine prose from the patient's history to construct additional facts.

### `question_code_domain`

- question identity
- catalogue/subset identity
- code
- relation metadata needed by the runtime rule model

This table defines **evaluability**, not presentation and not the verification answer key.

### `question_option`

- question identity
- stable option ID
- `option_type = code | none_of_above`
- code, nullable for `none_of_above`
- option-set identity

No expected feedback class, determining rule, expected explanation, or oracle answer belongs in this runtime table.

## 10. API/interaction consequence

The former request shape `POST /api/cases/{case_id}/evaluate` with a raw `submitted_code` is too closely tied to the old case model. The question endpoint remains:

`POST /api/questions/{question_id}/evaluate`

The earlier planning proposal used a stable learner option identity:

```json
{"option_id": "Q-001-01-O03"}
```

That request form is now **superseded by `MODELBASE-0.2` §7.2 and `APIBASE-0.1`**. `option_id` remains useful for React rendering/local selection state, but evaluation uses one authoritative tagged-response body:

```json
{"response":{"type":"code","code":"I48.9"}}
```

or:

```json
{"response":{"type":"none_of_above"}}
```

Using the same tagged representation for learner and verification requests preserves the evaluator's ability to address non-displayed but explicitly modelled domain codes. Code-option payloads already expose the displayed ICD code/designation, so the learner frontend can construct this request without gaining access to relation truth or the external oracle.

The patient overview and patient detail endpoints should expose the static patient context and question list, but only learner-visible information. Raw `question_fact` rows remain evaluator-internal before submission; required learner information is carried by context and the question prompt. The RC oracle remains unavailable to these endpoints. `GET /api/questions/{question_id}` returns `404` for `verification_only` questions, while their IDs remain accepted by the common POST evaluation route for regression testing.

Question lists must be handled as collections of arbitrary valid length. Neither an API response contract such as `question_1`/`question_2`/`question_3` nor frontend components with three fixed question slots are permissible. Randomization operates over the returned collection rather than over a hardcoded index range.

## 11. Versioning proposal

The historical 0.2 artefacts must remain immutable because they record the state actually exercised during the earlier development/test cycle. The conceptual redesign should move forward rather than rewrite history.

| Artefact | Current | Proposed next state | Reason |
|---|---|---|---|
| requirements catalogue | `0.5` | forward revision `0.7` | patient/question requirements plus immediate feedback, completion review and bounded UX/gameful presentation |
| source register | `0.4` | `0.5` only when new authoritative evidence is admitted | new coding domains may require new locators/sources |
| `MODELBASE` | `0.1` historical/implemented | `0.2` now specified as the forward design; not yet implemented | patient/question separation, typed question facts and option/domain semantics |
| `CASEPLAN` | `0.2` | `0.4` | learner cases decoupled from COPD-heavy technical fixtures; `0.3` planning concept superseded before freeze |
| patient plan/base | `PATIENTPLAN-0.4` | later `PATIENTBASE-0.1` | six synthetic patients, only one with COPD, explicit difficulty distribution and multi-domain content requirement |
| question plan/base | `QUESTIONPLAN-0.4` | later `QUESTIONBASE-0.1` | variable one-to-many question cardinality and approximately 25 multi-domain atomic learner tasks |
| `CASEBASE` | `0.2` | retain historically; supersede for learner runtime | old cases remain regression fixtures |
| `RCBASE` | `0.2` | `0.3` after questions/options are source-checked | new option-level expectations while retaining old 18 rows |
| `TESTBASE` | `0.1` | next forward revision | patient, question, option, randomization, feedback/review, UX interaction and regression tests |
| `RULEBASE` | `0.1` historical/implemented | `0.2` now specified as the forward design; not yet implemented | generic source-audited response relations and `none_of_above` while retaining old source-specific rules |
| `SUBSET` | `0.1` | `0.2` required | the 13-record, predominantly COPD subset cannot represent the revised multi-domain question bank |
| `PROTOBASE` | `0.2` | `0.3` after implementation | binds the revised data/model/rule/application revisions |

`PATIENTBASE`, `QUESTIONBASE`, `RCBASE-0.3`, and `PROTOBASE-0.3` must not be called frozen until the new source audit, implementation, and verification prerequisites are actually satisfied.

## 12. Requirements implied by this revision

The next requirements-catalogue revision should add or refine at least the following obligations:

- `REQ-MOD-03`: a learner case represents a patient and contains multiple separately identified coding questions.
- `REQ-MOD-04`: only explicitly declared question facts may affect evaluation; background context cannot become implicit rule input.
- `REQ-MOD-05`: question cardinality is data-driven; no schema, API or UI rule may assume exactly three questions per patient.
- `REQ-DAT-06`: the active catalogue subset is derived from the adopted multi-domain question bank and must include the source-verified Austrian 2026 records needed for its coding targets and decoys; it must not remain artificially restricted to the original COPD/status families.
- `REQ-DAT-07`: multiple represented background conditions across every patient must function as genuine coding targets across that patient's question set; background diversity must not be purely cosmetic.
- `REQ-DAT-08`: COPD-related learner content is confined to at most one patient. Additional COPD/FEV1 cases required for regression remain technical fixtures and are not counted as learner cases.
- `REQ-INT-02`: patient information remains accessible while answering that patient's questions.
- `REQ-INT-03`: learner-question order is randomized without changing question content or expected results.
- `REQ-INT-04`: every learner question has a fixed versioned option set including `none_of_above`.
- `REQ-FBK-01`: a successfully evaluated learner response is locked and receives immediate task-focused elaborated feedback before progression.
- `REQ-FBK-03`: completion of a patient produces a read-only review and raw `correct`/`suboptimal`/`incorrect` counts without an invented weighted score.
- `REQ-INT-05`: playthrough state is transient; replay starts a fresh presentation state without creating a requirement for longitudinal learner-history persistence.
- `REQ-UI-01`--`03`: the stretch iteration supplies orientation, patient/progress presentation and accessibility-informed responsive interaction without claiming formal usability or WCAG conformance.
- `REQ-GAM-01`: gameful mechanics are limited to progress, completion and replay cues and cannot alter evaluator truth, option membership or reference expectations.
- `REQ-RUL-06`: `none_of_above` has deterministic set-based semantics and is not encoded as an ICD code.
- `REQ-VER-08`: every displayed option has an independently predefined expected result before the verification run.
- `REQ-VER-09`: all `RCBASE-0.2` expectations remain regression obligations after the learner-model redesign.

`TEST-CARD-01` should accompany `REQ-MOD-05`: load at least one three-question patient and one six-question patient through the database, API and rendered learner workflow, and confirm that both complete sets are available and evaluable without configuration or code changes. The test should assert the baseline-defined counts, not establish three or six as universal limits.

The larger question bank also has a verification cost. With 25 questions and four to five displayed choices per question including `none_of_above`, the learner-facing oracle would contain roughly 100 to 125 option-level expectations before the 18 historical regression rows are counted. This is technically manageable, but it makes source discipline more important. **Catalogue breadth is nevertheless a requirement of the redesign.** What should be reused are general evaluation mechanisms where justified, not a narrow repertoire of ICD families. The complete matrix belongs in machine-readable verification data and, if useful, the appendix; Chapter 3 itself should report coverage and representative traces rather than reproduce every row.

## 13. Completed dependencies and next materialization step

The source-audit step described by the original planning revision has now been completed as `QSAUDIT-0.1` against the frozen ICD-10 BMASGPK 2026 systematic catalogue, *Medizinische Dokumentation* guidance, and DIAGLIST workbook.

The audit retains all 25 learner questions but constrains several vignette facts so that their expected coding relations are deterministic. It fixes the proposed displayed option sets, makes `Q-004-05` and `Q-005-05` deliberate `none_of_above = correct` exercises, resolves the former multi-code concern around `Q-005-02` by bounding it to the diagnosis code for explicitly documented tardive dyskinesia, and identifies a 99-record candidate union when the learner domains and historical regression subset are combined. All 99 candidate records were found in the frozen DIAGLIST. These are design/source-audit results, not an implemented or frozen `SUBSET-0.2`.

The rule/data-model dependency has subsequently been specified as working `RULEBASE-0.2` and `MODELBASE-0.2`. The rule revision retains the historical source-specific COPD/status rules and adds the generic, question-scoped relations `accepted_reference`, `less_specific_supported`, `fact_conflict`, `temporal_context_conflict`, and `source_rule_resolved`, together with deterministic `none_of_above`. The model separates learner-visible patient context from typed evaluator facts and separates the closed evaluator response domain from the fixed displayed option set. Neither document is an implementation or test-pass claim.

The learner-data materialization step is now complete as a **forward design baseline**, not an application freeze. `chapter3_requirements_forward_revision_0_7.md` is the current forward requirements delta; `prototype_baseline_0_2_design/` contains `SUBSET-0.2`, six `PATIENTBASE-0.1` records, 25 `QUESTIONBASE-0.1` learner questions, 60 typed question facts, 100 question-code-domain relations, 142 relation-to-fact links, and 120 displayed options (95 code options plus 25 `none_of_above`). The 99 subset records are projected from the frozen DIAGLIST four-field source data. Requirements revision 0.7 adds presentation/interaction obligations without changing those clinical/data cardinalities.

The materialized data contract has been checked for its stated design invariants, including question counts `3,3,3,5,5,6`, exactly one accepted-reference relation per current learner question, exactly one `none_of_above` option per learner question, the two intended `none_of_above = correct` controls (`Q-004-05`, `Q-005-05`), and confinement of J44 learner content to `PATIENT-001`. This is **data-contract validation only**. It is not evidence that the MySQL schema, PHP evaluator, API or React UI has been migrated or that a new verification baseline passes.

The eight historical `CASEBASE-0.2` fixtures have now been bridged into verification-only `coding_question` records and the candidate external `RCBASE-0.3` contains all 143 planned expectations. Four additive historical rows remain explicitly marked as provisional reconstructions from the implementation documentation and require a one-time comparison with the original `0.2` CSVs before freeze. This is a reconciliation gate rather than a development blocker. The immediate application dependency is therefore the `MODELBASE-0.2` SQL/loader migration, followed by the PHP evaluator/API migration and then the learner-facing React workflow.

## 14. Chapter 3 methodological interpretation

This redesign itself is methodologically relevant. It records a genuine formative design-science iteration: implementation and inspection revealed that a structure optimized for branch coverage was inadequate as a learner-facing interaction. The response is not cosmetic UI refinement; the artefact model is revised so that technical verification fixtures and pedagogical cases serve distinct purposes while retaining traceability to the same rule and source baselines.

In Chapter 3 this can later be reported as an iteration from `CASEBASE-0.2` to the patient/question design: the original cases successfully demonstrated rule conformance, but the pre-freeze inspection showed that several learner-visible cases had degenerate single-option domains. The revised model preserves those fixtures for regression and introduces patient-level context, multiple independent coding tasks, fixed non-trivial option sets, and explicit `none_of_above` semantics. This is an evidence-producing refinement step, not a post-hoc rewrite of the earlier development history.

## 15. Learner interaction and bounded UX/gameful extension

`UXBASE-0.1` supplements this content design without changing question truth or the patient/question data model. The core learner interaction is now fixed as:

`orientation -> patient selection -> patient dossier/question -> submit -> locked immediate feedback -> next -> patient review -> replay/another patient`.

Immediate feedback and the patient-completion review are **core interaction requirements**, not optional visual polish. After successful evaluation, the submitted answer is read-only for that playthrough. Feedback reports `correct`, `suboptimal`, or `incorrect`, gives a concise task-focused explanation and, where defined, the better-supported/reference code or corrective direction. Completion reports raw counts of the three existing classes plus a per-question review. No weighted point total or mastery percentage is derived from those nominal/ordinal-looking categories.

The pre-freeze UX/UI effort is deliberately a **stretch iteration**. It may introduce a clearer landing/orientation view, patient case-file cards, continuously accessible dossier information, question/patient progress, restrained completion acknowledgement, replay affordances, responsive layouts and accessibility-informed controls. It must not introduce leaderboards, competitive ranking, lives, countdown pressure, weighted scores, daily streaks or content-changing rewards. The purpose is to make the demonstrator self-explanatory and intentionally designed without allowing game mechanics to redefine coding semantics.

Question order remains a fresh permutation on replay. Presentation may also permute code options, while option membership and every evaluator relationship remain frozen. The current 25-question bank has also been checked for direct same-patient answer leakage at the code-option level: an accepted code disclosed by immediate feedback for one question does not appear as a selectable option in a different question for that patient. This safeguard must be rechecked if question membership or options change.

No new MySQL learner-history tables are implied by this addition. Transient playthrough state belongs to the frontend unless a later explicit requirement changes that boundary. Any UX refinement actually implemented before freeze is reported in Chapter 3 as a bounded formative design iteration and tested for interaction conformance; it is **not** evidence of improved usability, engagement, learning effectiveness or formal accessibility conformance.
