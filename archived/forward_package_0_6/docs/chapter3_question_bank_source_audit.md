# Chapter 3 Question-Bank and Source Audit

**Working audit identifier:** `QSAUDIT-0.1`  
**Date:** 2026-08-08  
**Input design:** `CASEPLAN-0.4`, `PATIENTPLAN-0.4`, `QUESTIONPLAN-0.4`  
**Status:** source-reviewed design candidate; not a frozen implementation or evaluation baseline

## 1. Purpose and decision boundary

This document turns the six-patient concept into a source-bounded learner-question specification. It does four things before the database schema, rule catalogue or implementation is revised:

1. fixes the minimum documented facts needed to make each proposed question deterministic;
2. identifies the Austrian ICD-10 BMASGPK 2026 records needed for the intended answers and decoys;
3. states the proposed `correct`, `suboptimal`, `incorrect`, and `none_of_above` relations before they enter application code; and
4. rejects clinical or coding inferences that cannot be substantiated by the authoritative sources within the prototype's bounded one-response model.

The audit is deliberately a design artefact, not evidence that the revised software exists. `SUBSET-0.2`, `RULEBASE-0.2`, the patient/question database and `RCBASE-0.3` are downstream products.

The cases remain synthetic. The catalogue is used to code **explicitly documented diagnoses, conditions, sequelae or findings**. The prototype does not diagnose a patient from signs or symptoms and the question bank is not a clinical decision-support system.

## 2. Frozen authority used for this audit

| Source ID | Frozen source | SHA-256 | What it is allowed to establish |
|---|---|---|---|
| `SRC-AT-ICD-SYS-2026` | *ICD-10 BMASGPK 2026 – Systematisches Verzeichnis* | `cc46dbd161c6d4d75f4196a25139b1b200dcb2f24858f2bedacb81295604de2d` | hierarchy, official category/subcategory meaning, inclusions, exclusions, coding notes |
| `SRC-AT-DOC-2026` | *Medizinische Dokumentation: Codierhinweise bis inklusive 41. LKF-Rundschreiben* | `69b37f1879acb5cda63eca30086e61a1f17b058bb26fc629d6a64bd25736653b` | Austrian coding depth, specificity, represented setting rules and special instructions |
| `SRC-AT-DIAGLIST-2026` | `DIAGLIST2026.xlsx`, worksheet `DIAGLIST2026` | `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` | machine-readable record membership, code, marker and designation used by the application subset |

Printed source locators below always refer to the page numbers printed on the source documents. DIAGLIST is non-paginated; its worksheet row and code are therefore used as native locators.

Three general rules materially constrain the question bank:

- Austrian diagnosis coding normally uses four-character subcategories where the catalogue provides them. Three-character coding is retained when the category has no four-character subdivision. The guidance also identifies a small set of categories, including `J44.0-J44.9`, for which five-character coding is mandatory in Austrian hospitals (`SRC-AT-DOC-2026`, printed pp. 11-12).
- The specific four-character code should generally be used when the documented information supports it, and the systematic directory must be consulted for inclusions, exclusions and coding notes (`SRC-AT-DOC-2026`, printed p. 13).
- Codes from `R00-R99` are generally appropriate only where diagnostic clarification could not be completed and, as a main diagnosis, when no codable disease cause is found (`SRC-AT-DOC-2026`, printed p. 66).

The prototype's label `suboptimal` is a **project feedback class**, not an official ICD-10 category. For this question bank it is used only for a source-recognized, selectable but less specific alternative when the question explicitly supplies the information required by the more specific response. The official specificity rule on printed p. 13 supplies the coding basis; the three-class pedagogical interpretation is the artefact's operationalization.

It follows that a code ending in `.9` is not automatically `suboptimal`. Three counterexamples are intentionally retained in the learner bank:

- `E11.9` means type 2 diabetes **without complications**, not unspecified diabetes (`SRC-AT-ICD-SYS-2026`, printed pp. 178-179);
- `F03` is the catalogue category for dementia whose aetiology is not further specified (`SRC-AT-ICD-SYS-2026`, printed p. 201); and
- `N40` is a valid three-character record because this category has no four-character subdivision (`SRC-AT-DOC-2026`, printed pp. 11-13; `SRC-AT-ICD-SYS-2026`, printed p. 448).

## 3. Outcome notation and option policy

The question tables use:

- `C` = `correct`;
- `S` = `suboptimal`;
- `I` = `incorrect`;
- `NOA:C` = `none_of_above` is the correct displayed response; and
- `NOA:I` = `none_of_above` is incorrect because a displayed code is acceptable.

`Incorrect` is assigned only to an explicitly modelled contradiction with the documented target, subtype, temporal state or other question fact. An arbitrary code outside the question domain is not manufactured into an `incorrect` judgement; it remains unsupported/not evaluated under the existing gate principle.

Each adopted question has exactly one correct **displayed** choice. In two questions the source-supported reference code is deliberately present in the evaluator domain but absent from the displayed code set, making `none_of_above` correct by construction. Option membership is versioned; only its order may be randomized.

## 4. Source-reviewed learner questions

### 4.1 `PATIENT-001` — Anna Berger

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-001-01` | COPD with acute lower-respiratory infection; stable-phase FEV1 = 55% predicted | `J44.02 C`; `J44.01 I`; `J44.09 S` | `NOA:I` | `SRC-AT-DOC-2026`, pp. 12, 26, 34; DIAGLIST rows 3884-3889 | **Adopt.** Retains the existing source-backed COPD rule and remains the only COPD learner task. |
| `Q-001-02` | Record explicitly states type 2 diabetes **without documented diabetic complications** | `E11.9 C`; `E11.3 I`; `E11.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 178-179; DIAGLIST rows 1925, 1926, 1932 | **Adopt.** `E11.9` is positively defined as “without complications”; it must not be treated as an unspecified/suboptimal response. |
| `Q-001-03` | Record explicitly states **primary bilateral** knee osteoarthritis/gonarthrosis; no post-traumatic or secondary aetiology is documented | `M17.0 C`; `M17.9 S`; `M17.2 I`; `M17.4 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 410; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 5743, 5745, 5747, 5749 | **Adopt.** The facts support the specific bilateral primary subcategory. |

`Q-001-01` retains the complete six-record `J44.0` evaluation family (`J44.0`, `J44.00`, `J44.01`, `J44.02`, `J44.03`, `J44.09`) so the old depth/evidence branches remain testable even though only three code options are displayed.

### 4.2 `PATIENT-002` — Michael Novak

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-002-01` | Longitudinal record explicitly states **paroxysmal atrial fibrillation** | `I48.0 C`; `I48.9 S`; `I48.1 I`; `I48.3 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 311-312; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 3536, 3537, 3539, 3541 | **Adopt.** Distinguishes paroxysmal, persistent and flutter forms without asking the learner to diagnose the rhythm. |
| `Q-002-02` | Record explicitly states CKD stage 3; if a numeric fact is shown, use a GFR within the catalogue's stage-3 interval | `N18.3 C`; `N18.9 S`; `N18.2 I`; `N18.4 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 442-443; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 9016-9018, 9020 | **Adopt.** Stage is documentation, not inferred by the evaluator. |
| `Q-002-03` | Record explicitly states **panic disorder**; no current depressive disorder is represented as the basis of the panic diagnosis | `F41.0 C`; `F41.9 S`; `F41.1 I`; `F41.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 217; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2437-2439, 2442 | **Adopt.** The task is classification of an established diagnosis, not psychiatric diagnosis from symptoms. |

### 4.3 `PATIENT-003` — Lea Horvat

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-003-01` | Record explicitly states recurrent **migraine with aura**; current episode is not status migrainosus | `G43.1 C`; `G43.9 S`; `G43.0 I`; `G43.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 256-257; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2758-2760, 2763 | **Adopt.** Aura status must be explicit. |
| `Q-003-02` | Record explicitly states **postinfectious hypothyroidism** | `E03.3 C`; `E03.9 S`; `E03.2 I`; `E03.4 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 176; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 1883-1885, 1888 | **Adopt with vignette revision.** Generic “hypothyroidism” is insufficient to justify `E03.3`; the postinfectious aetiology must be visible in the question/record. |
| `Q-003-03` | Record explicitly states **generalized anxiety disorder** | `F41.1 C`; `F41.9 S`; `F41.0 I`; `F41.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 217; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2437-2439, 2442 | **Adopt.** Creates a deliberate contrast with the panic-disorder task without linking either diagnosis to unrelated physical symptoms. |

### 4.4 `PATIENT-004` — Sofia Marin

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-004-01` | Type 2 diabetes is documented **without diabetic complications**; primary open-angle glaucoma is documented separately and **no diabetic eye complication is documented** | `E11.9 C`; `E11.3 I`; `E11.2 I`; `E11.8 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 178-179; DIAGLIST rows 1925, 1926, 1931, 1932 | **Adopt with explicit no-causality wording.** This is an involved context task: coexisting glaucoma must not be converted into diabetic eye disease. |
| `Q-004-02` | Transferred record explicitly states **primary open-angle glaucoma** | `H40.1 C`; `H40.9 S`; `H40.0 I`; `H40.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 282; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 3142-3144, 3150 | **Adopt.** |
| `Q-004-03` | Record explicitly states **recurrent depressive disorder, current moderate episode**; no manic episode is documented | `F33.1 C`; `F33.9 S`; `F32.1 I`; `F33.0 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 213-214; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2412, 2417, 2418, 2423 | **Adopt.** Distinguishes recurrent from single-episode and current severity. |
| `Q-004-04` | Record explicitly states **iron-deficiency anaemia due to chronic blood loss** | `D50.0 C`; `D50.9 S`; `D50.8 I`; `D64.9 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 161; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 1699, 1701, 1702, 1769 | **Adopt.** Chronic blood loss must be part of the documented diagnosis/facts. |
| `Q-004-05` | Current documentation states localized low-back/lumbar pain **without leg radiation and without an established underlying diagnosis** | `M54.2 I`; `M54.3 I`; `M54.6 I` | `NOA:C` | `SRC-AT-ICD-SYS-2026`, p. 421; DIAGLIST rows 6937, 6938, 6940, 6941 | **Adopt as first `none_of_above` control.** `M54.5` is the source-supported reference relation and remains in the evaluation domain, but it is intentionally not displayed. |

`M54.5` must not be subjected to the COPD five-character depth rule. The Austrian guidance says the WHO fifth-character musculoskeletal subdivisions are optional, whereas the mandatory Austrian five-character list is limited to specified categories including `J44.0-J44.9` (`SRC-AT-DOC-2026`, printed pp. 11-12).

### 4.5 `PATIENT-005` — Daniel Weiss

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-005-01` | Specialist record explicitly states **paranoid schizophrenia** | `F20.0 C`; `F20.9 S`; `F20.1 I`; `F20.2 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 207-208; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2369-2371, 2377 | **Adopt.** The learner codes the documented subtype; the application does not infer it from psychiatric features. |
| `Q-005-02` | Clinician explicitly documents **tardive dyskinesia associated with long-term neuroleptic treatment**; the task asks for the diagnosis code, not identification of the causative substance | `G24.0 C`; `G24.9 S`; `G25.4 I`; `G25.1 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 252-253; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2701, 2708, 2710, 2713 | **Adopt with bounded task wording.** The systematic catalogue explicitly includes *Dyskinesia tarda* under `G24.0`; an additional Chapter-XX code is needed only if the substance is to be specified. The question therefore stays within the one-response model. |
| `Q-005-03` | Record explicitly states **psoriasis vulgaris**, with no pustular/guttate form documented | `L40.0 C`; `L40.9 S`; `L40.4 I`; `L40.1 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 391; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 4571, 4572, 4575, 4578 | **Adopt.** The more complex `L40.5†` arthropathy convention is deliberately excluded from this teaching task. |
| `Q-005-04` | Record explicitly states **mixed hyperlipidaemia** | `E78.2 C`; `E78.5 S`; `E78.0 I`; `E78.1 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 194-195; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2162-2164, 2167 | **Adopt.** |
| `Q-005-05` | Record explicitly states **essential (primary) hypertension** and does not document hypertensive heart disease or a secondary cause | `I11.9 I`; `I15.9 I`; `I95.9 I` | `NOA:C` | `SRC-AT-ICD-SYS-2026`, p. 301 (`I10-I15`) and p. 324 (`I95`); DIAGLIST rows 3388, 3390, 3401, 3736 | **Adopt as second `none_of_above` control.** `I10` is the accepted reference relation in the evaluation domain but is intentionally omitted from display. |

### 4.6 `PATIENT-006` — Peter Gruber

| Question | Minimum frozen question facts | Fixed displayed code options | `none_of_above` | Source basis | Decision |
|---|---|---|---|---|---|
| `Q-006-01` | Accessible prior record explicitly states **atherosclerotic heart disease**; it does not state an old MI or ischaemic cardiomyopathy as the coding target | `I25.1 C`; `I25.9 S`; `I25.2 I`; `I25.5 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 304-305; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 3429, 3430, 3433, 3436 | **Adopt.** |
| `Q-006-02` | Prior **cerebral infarction** is explicitly documented; the represented current deficit is explicitly documented as a sequela/late effect and satisfies the catalogue's sequela framing | `I69.3 C`; `I69.4 S`; `I63.9 I`; `I69.1 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 317; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 3607, 3637, 3639, 3640 | **Adopt with temporal wording.** The task must ask for the code representing the documented sequela relationship, not invite an acute-stroke diagnosis. |
| `Q-006-03` | Prior record explicitly states **generalized idiopathic epilepsy**; no status epilepticus is represented | `G40.3 C`; `G40.9 S`; `G40.0 I`; `G40.4 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, pp. 255-256; `SRC-AT-DOC-2026`, p. 13; DIAGLIST rows 2743, 2746, 2747, 2752 | **Adopt.** The chronic epilepsy diagnosis must not be treated as proof of the cause of the current loss of consciousness. |
| `Q-006-04` | Prior record states **dementia**, but no dementia aetiology is documented; prior cerebrovascular disease exists separately and is not documented as causal | `F03 C`; `F01.9 I`; `F05.1 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 201; DIAGLIST rows 2240, 2247, 2250 | **Adopt.** This is an intentional counterexample to “unspecified = suboptimal”: greater aetiological specificity would be an unsupported inference. |
| `Q-006-05` | Prior record explicitly states **benign prostatic hyperplasia/prostatahyperplasia** | `N40 C`; `N41.0 I`; `N42.9 I`; `C61 I` | `NOA:I` | `SRC-AT-ICD-SYS-2026`, p. 448; `SRC-AT-DOC-2026`, pp. 11-13; DIAGLIST rows 1207, 9092, 9093, 9104 | **Adopt.** `N40` is a valid three-character diagnosis record; the application must not apply a blanket four-character-depth error to categories without subdivisions. |
| `Q-006-06` | At the represented end of the encounter the patient remains unconscious; diagnostic clarification is incomplete and **no codable disease cause has been established** | `R40.2 C`; `R40.1 I`; `R55 I`; `G40.9 I` | `NOA:I` | `SRC-AT-DOC-2026`, p. 66; `SRC-AT-ICD-SYS-2026`, p. 555 (`R40.2`) and p. 559 (`R55`); DIAGLIST rows 10845, 10846, 10901, 2752 | **Adopt with explicit work-up boundary.** `R40.2` includes unspecified unconsciousness; the documented epilepsy history is a distractor, not evidence that the acute episode is epileptic. |

## 5. Audit result

All 25 proposed learner questions can be retained within the bounded single-response architecture **provided the minimum facts above become part of the versioned patient/question data**. None of the adopted questions requires the evaluator to diagnose a disease from raw symptoms.

The source audit resolves the earlier uncertainty around `Q-005-02`: tardive dyskinesia is explicitly included under `G24.0` in the Austrian systematic catalogue, and the note on an additional external-cause code is conditional on specifying the substance. A diagnosis-code-only question is therefore representable without extending the learner response to a code pair (`SRC-AT-ICD-SYS-2026`, printed p. 252).

The question bank deliberately contains several different reasoning modes rather than 25 copies of the same specificity exercise:

- exact subtype selection from explicit documentation;
- less-specific but selectable alternatives (`suboptimal`);
- contradictory subtype/severity/temporal alternatives (`incorrect`);
- resistance to unsupported causal inference (`Q-004-01`, `Q-006-04`, `Q-006-06`);
- a legitimate unspecified diagnosis (`F03`);
- a legitimate three-character diagnosis (`N40`);
- a source-bounded symptom/finding response when work-up is incomplete (`R40.2`); and
- two predesigned `none_of_above = correct` questions (`Q-004-05`, `Q-005-05`).

Across the fixed learner displays there are **120 option-level outcomes**: 95 ICD-code options plus 25 `none_of_above` options. The planned distribution is 23 correct code choices, 18 suboptimal code choices, 54 incorrect code choices, 23 incorrect `none_of_above` choices and 2 correct `none_of_above` choices. Every question therefore has exactly one correct displayed response, while `suboptimal` appears only where the sources and represented facts justify it.

## 6. Candidate `SUBSET-0.2` implication

The question audit determines catalogue breadth; catalogue breadth does not determine the questions.

The 25 learner question domains require **92 unique DIAGLIST records**. Six of those (`J44.0`, `J44.00`, `J44.01`, `J44.02`, `J44.03`, `J44.09`) already occur in the 13-record legacy subset. Preserving the complete historical regression subset therefore produces `SUBSET-0.2` with **99 unique records**: the 92 learner-domain records plus the remaining 7 legacy-only records. The earlier design-stage count of 89 learner-domain records omitted three non-displayed J44 technical relations; materialization exposed and corrected that arithmetic without changing subset membership.

An exhaustive membership check against the frozen `DIAGLIST2026.xlsx` found all 99 candidate records. This is a source-audit result only; it does not yet create `SUBSET-0.2`.

Candidate union:

```text
C61 D50.0 D50.8 D50.9 D64.9 E03.2 E03.3 E03.4 E03.9 E11.2 E11.3 E11.8 E11.9
E78.0 E78.1 E78.2 E78.5 F01.9 F03 F05.1 F20.0 F20.1 F20.2 F20.9 F32.1 F33.0 F33.1
F33.9 F41.0 F41.1 F41.2 F41.9 G24.0 G24.9 G25.1 G25.4 G40.0 G40.3 G40.4 G40.9 G43.0
G43.1 G43.2 G43.9 H40.0 H40.1 H40.2 H40.9 I10 I11.9 I15.9 I25.1 I25.2 I25.5 I25.9 I48.0
I48.1 I48.3 I48.9 I63.9 I69.1 I69.3 I69.4 I95.9 J44.0 J44.00 J44.01 J44.02 J44.03 J44.09
J44.1 J44.10 J44.11 J44.12 J44.13 J44.19 L40.0 L40.1 L40.4 L40.9 M17.0 M17.2 M17.4 M17.9
M54.2 M54.3 M54.5 M54.6 N18.2 N18.3 N18.4 N18.9 N40 N41.0 N42.9 R40.1 R40.2 R55 Z01.6
```

DIAGLIST code `Z01.8` with `Kennzeichen = !` remains deliberately outside the active subset as an outside-subset gate/status control unless a later test redesign explicitly changes that decision. The marker is source metadata and is not concatenated into the `Diagnose` identifier.

For the learner layer, the expected data cardinalities at this design revision are:

- 6 patient records;
- 25 learner questions;
- 95 displayed ICD-code relations;
- 25 displayed `none_of_above` relations;
- 5 additional non-displayed code relations required by evaluation (`M54.5`, `I10`, plus three non-displayed members of the six-code `J44.0` family); and therefore
- 100 learner question-to-code-domain relations before any separate technical fixtures are counted.

If `RCBASE-0.3` verifies every learner question-domain response and `none_of_above` relation rather than only the visible choices, it will need 125 new learner-question expectations. Retaining the existing 18 historical regression rows would yield 143 total oracle rows. These are design-derived counts, not executed-test results.

## 7. Consequences for `RULEBASE-0.2`

The new catalogue breadth must **not** be implemented as 25 bespoke `if question_id == ...` branches. The next rule revision should preserve the existing source-specific COPD/status rules and introduce a small generic response-relation vocabulary that can be populated from the source-reviewed question definitions.

At minimum the model must distinguish:

- `accepted_reference` -> `correct`;
- `less_specific_supported` -> `suboptimal`, with an explicit preferred replacement and source rationale;
- `fact_conflict` -> `incorrect` for a code contradicting an explicit question fact;
- `temporal_or_context_conflict` -> `incorrect` where the selected code expresses the wrong documented temporal/context state;
- existing source-specific rule branches such as COPD depth/FEV1 and hospital status restrictions; and
- `none_of_above`, derived from the frozen displayed option membership and accepted set rather than stored as an arbitrary answer key.

The relation type is runtime rule data, not the independent verification oracle. The oracle must separately state the expected class, determining rule/relation, expected explanation elements and source justification so that implementation data and expected test results remain distinct.

Most importantly, `less_specific_supported` must be **question-scoped and source-reviewed**. The evaluator may not infer it from a `.9` suffix, code length or label text.

## 8. Next dependency

The patient/source design is now sufficiently constrained to revise the data layer without guessing at its contents. The next increment should therefore be:

1. update the patient/question design document from `QUESTIONPLAN-0.4` to reflect the adopted facts and option sets from `QSAUDIT-0.1`;
2. revise the requirements/domain/rule artefacts to the planned next working revisions, especially the generalized response-relation model and the explicit no-inference constraints;
3. derive a machine-readable `SUBSET-0.2` definition from the 99-code candidate union and regenerate the four-field DIAGLIST projection deterministically;
4. specify the normalized patient/question schema and migration from the old one-question-per-case model; and only then
5. construct `PATIENTBASE`, `QUESTIONBASE`, and the independent `RCBASE-0.3` oracle before changing the application evaluator/UI.

This preserves the intended dependency order:

`authoritative sources -> requirements/rules -> patients/questions -> catalogue subset -> data model -> implementation -> independent expectations -> verification`.
