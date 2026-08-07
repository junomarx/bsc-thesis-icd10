# Chapter 3 Input and Source-Baseline Register

**Document status:** Working methodological control document  
**Register version:** 0.4  
**Date:** 6 August 2026  
**Applies to:** Chapter 3, *Methods and practical work*, and the prototype/evaluation baseline  

## 1. Purpose

This register fixes the provenance and permitted evidential role of inputs used to derive the prototype requirements, data subset, rule model, reference cases, and verification criteria. It is a project control document rather than thesis prose. Chapter 2 establishes why the respective sources are relevant; Chapter 3 should report which exact inputs were actually used and how they were transformed into project work products.

The register deliberately separates three input classes:

1. **Authoritative Austrian domain sources**, which can establish catalogue facts and official coding/documentation constraints within their stated scope.
2. **Research and methodological evidence**, which can justify design, modelling, traceability, feedback, and evaluation choices but cannot establish the correctness of an Austrian code for a particular case.
3. **Internal research and project constraints**, which define the intended use and feasible bachelor-level implementation but are not external domain evidence.

The downstream control chain is therefore:

> authoritative domain sources + research evidence + project constraints  
> → requirements  
> → prototype subset and data model  
> → feedback and rule model  
> → implementation  
> → reference cases and predefined expectations  
> → verification

No downstream requirement, rule, or expected reference-case result should acquire greater evidential authority than the input from which it was derived.

## 2. Authority classes and use policy

| Class | Meaning | Permitted role | Prohibited inference |
|---|---|---|---|
| **A1: Official catalogue/data** | Austrian ministry publication defining the applicable ICD-10 edition or machine-readable catalogue data | Code existence, hierarchy, labels, status properties and other fields actually contained in the source | Does not by itself establish clinical truth or all case-specific coding rules |
| **A2: Official coding/documentation guidance** | Austrian ministry guidance specifying coding depth, status use, encounter-dependent documentation or related constraints | Source for explicit coding criteria where the prototype represents those criteria | Does not make the prototype legally comprehensive or clinically validating |
| **A3: Official system/context documentation** | LKF and setting documentation needed to delimit the environment | Context, care-setting attributes, scope boundaries; rule input only when an explicitly represented criterion depends on the setting | Must not be converted into an ICD-to-price rule or treated as a general code-selection oracle |
| **A4: Official supplementary/explanatory material** | FAQ, publication pages and similar ministry material | Clarification, source discovery and contextual support | Must not silently override a later or more directly applicable official source |
| **E: Research/methodological evidence** | Peer-reviewed research, standards and methodological literature | Requirements rationale, design principles, evaluation procedure and claim boundaries | Cannot determine Austrian catalogue validity or a case-specific expected code |
| **I: Internal research/project input** | Research question, declared scope, resource and technology constraints | Intended-use, scope and implementation requirements | Must not be described as an official or literature-derived domain requirement |

### 2.1 Conflict and precedence rule

There is no blanket rule that a source with a higher table row or later date automatically overrides every other source. Applicability must first be resolved by subject, jurisdiction, care setting and version. The systematic catalogue, machine-readable data and coding guidance are complementary rather than interchangeable.

Where two applicable sources appear inconsistent, the discrepancy must be recorded. A rule or reference case must not resolve the contradiction by convenience. The appropriate action is to identify a more direct controlling source, obtain clarification, represent multiple acceptable outcomes where the evidence permits this, or exclude the disputed situation from the frozen verification suite. If the prototype were to implement a legal obligation rather than merely describe its context, the corresponding primary legal text would have to be added to the active baseline.

## 3. Authoritative Austrian source baseline

### 3.1 Active core sources

These sources are permitted to feed executable catalogue data, coding criteria, or predefined reference-case expectations within their documented scope.

| ID | Source and thesis key | Authority / role | Permitted downstream use | Explicit limitation | Baseline state |
|---|---|---|---|---|---|
| **SRC-AT-ICD-SYS-2026** | *ICD-10 BMASGPK 2026 – Systematisches Verzeichnis*, BMASGPK-Version 2026+ (`BMASGPK2025ICD10Catalogue`) | A1. Human-readable Austrian catalogue baseline | Verify code/category existence, hierarchy, labels, inclusions/exclusions, notes and Austrian status markings; substantiate code-level reference expectations | A catalogue entry does not by itself show that a diagnosis is clinically true or that the code is appropriate for every documented context | **Core baseline; exact PDF copy identified** |
| **SRC-AT-DOC-2026** | *Medizinische Dokumentation: Codierhinweise bis inklusive 41. LKF-Rundschreiben* (`BMASGPK2025MedicalDocumentation2026`) | A2. Official coding/documentation guidance for the intramural/LKF environment | Derive represented rules for coding depth, specificity, status restrictions and setting-dependent documentation where applicable | Applies only within the scope stated by the document; does not establish comprehensive legal compliance or clinical correctness | **Core baseline; exact PDF copy identified** |
| **SRC-AT-DIAGLIST-2026** | `DIAGLIST 2026` machine-readable Excel catalogue (`BMASGPK2026DIAGLIST`; publication context also `BMASGPK2026AmbulatoryDocumentation`) | A1. Technical import source | Reproducible import and subset extraction for the catalogue fields actually present in the workbook | The ministry states that the machine-readable form excludes *Exklusiva* and *Inklusiva*. The workbook also contains intramural/special code forms in addition to conventional ICD-10 identifiers. It therefore cannot be treated as a semantically complete ICD rule source | **Core baseline; exact workbook identified, inspected and fingerprinted** |

The intended technical source model is deliberately layered:

> **DIAGLIST 2026** supplies machine-readable catalogue records  
> + **Systematisches Verzeichnis 2026+** supplies the human-readable semantic catalogue context omitted from the spreadsheet  
> + **Medizinische Dokumentation 2026** supplies represented Austrian coding/documentation constraints.

This prevents a convenient import format from being treated as a complete coding specification.

### 3.2 Conditional and contextual official sources

These sources remain available to establish setting or scope but are not part of the case-level oracle unless the final prototype subset explicitly represents a criterion that depends on them.

| ID | Source and thesis key | Current role | May support | Must not support by default |
|---|---|---|---|---|
| **SRC-AT-LKF-SYS-2026** | *LKF-Systembeschreibung 2026 BMASGPK-Version 2026+* (`BMASGPK2026LKFSystembeschreibung`) | A3, context | Hospital documentation structure, LKF setting, grouping/plausibility context, scope delimitation | Standalone code correctness, ICD-to-price mapping, clinical validity |
| **SRC-AT-LKF-AMB-2026** | *LKF-Modell 2026 für den spitalsambulanten Bereich* (`BMASGPK2026OutpatientCare`) | A3, context/conditional | Hospital-outpatient reporting and setting distinctions where a selected rule requires them | General ICD code appropriateness outside the represented setting |
| **SRC-AT-EXT-HB-2026** | *Handbuch Medizinische Dokumentation für den extramuralen ambulanten Bereich*, 1 July 2026 (`BMASGPK2026Handbuch`) | A2/A3, conditional | Extramural coding/reporting criteria only if extramural cases are deliberately brought into the prototype subset; otherwise scope delimitation | Hospital LKF rules or generalisation from extramural guidance to every setting |
| **SRC-AT-EXT-FAQ-2025** | *Fragen und Antworten zur Diagnosen- und Leistungscodierung im extramuralen ambulanten Bereich*, 19 December 2025 (`BMASGPK2025FragenUndAntwortenExtramural`) | A4, supplementary | Explanatory context and reporting-route clarification | Controlling oracle where it conflicts with the later 1 July 2026 handbook or a more direct source |
| **SRC-AT-CAT-PAGE-2026** | Ministry *Kataloge 2026* publication page (`BMASGPK2025Kataloge2026`) | A4, provenance/discovery | Establish publication package and official download provenance | Record-level catalogue truth or coding criteria |
| **SRC-AT-ICD-ALPHA-2026** | *ICD-10 BMASGPK 2026 – Alphabetisches Verzeichnis* | A1, lookup aid; not currently an implementation input | Candidate-code discovery if later needed | Final code validity or case outcome without verification against the systematic catalogue/guidance |
| **SRC-AT-ICD-EXT-XLSX-2026** | `ICD-10_Extramural.xlsx` (`BMASGPK2026ICD10Extramural`) | A1, setting-specific machine-readable aid; conditional | Extramural subset construction if extramural coverage is explicitly selected | General Austrian catalogue replacement or a substitute for coding guidance; its five-field ICD table does not carry the full DIAGLIST status/plausibility metadata |

### 3.3 Official URLs and exact-copy fingerprints

The links below are provenance records, not substitutes for version freezing. Web pages can change after the thesis baseline has been defined.

| Source ID | Official location | Exact-copy SHA-256 / status |
|---|---|---|
| SRC-AT-ICD-SYS-2026 | [Systematic directory PDF](https://www.sozialministerium.gv.at/dam/jcr%3A64beeaa0-ec63-4864-a954-0ee1beb9e5c8/ICD-10%20BMASGPK%202026%2B%20-%20SYSTEMATISCHES%20VERZEICHNIS.pdf) | `cc46dbd161c6d4d75f4196a25139b1b200dcb2f24858f2bedacb81295604de2d` |
| SRC-AT-DOC-2026 | [Medical Documentation 2026 PDF](https://www.sozialministerium.gv.at/dam/jcr%3A2acd92ba-9b21-45c4-a4f6-d8345943e7b1/MEDIZINISCHE%20DOKUMENTATION%202026.pdf) | `69b37f1879acb5cda63eca30086e61a1f17b058bb26fc629d6a64bd25736653b` |
| SRC-AT-DIAGLIST-2026 | [DIAGLIST 2026 XLSX](https://www.sozialministerium.gv.at/dam/jcr%3Aadcd10f6-ae8d-4e5c-9de8-e33628e739c0/DIAGLIST2026.xlsx) | `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` |
| SRC-AT-LKF-SYS-2026 | [LKF System Description 2026 PDF](https://www.sozialministerium.gv.at/dam/jcr%3A7e7222c1-83d0-4752-b3c5-72e11e4532cb/SYSTEMBESCHREIBUNG%202026.pdf) | `3b2645550b1c34fef7f382951430995b8e06cc6ff2599ee3b43c8a73fa741e0a` |
| SRC-AT-LKF-AMB-2026 | [Hospital-outpatient LKF model 2026 PDF](https://www.sozialministerium.gv.at/dam/jcr%3A12f2a0b3-ff9a-4eb0-adc0-a31113a78664/LKF-MODELL%202026%20spitalsambulant.pdf) | `fdfc884ea5bc583a9af39d02f105f295f572d290c30da0304a3f97afe4a2b724` |
| SRC-AT-EXT-HB-2026 | [Extramural handbook, 1 July 2026 PDF](https://www.sozialministerium.gv.at/dam/jcr%3A033b5a81-4248-485e-9d77-0a1adc75cbcd/Handbuch%20Medizinische%20Dokumentation%20f%C3%BCr%20den%20extramuralen%20ambulanten%20Bereich%20Stand%2001-07-2026.pdf) | `9dce0bc14c4836c6fb966621b683879d1546e5f27cfb79361432ad944763bc80` |
| SRC-AT-EXT-FAQ-2025 | [Extramural FAQ, 19 December 2025 PDF](https://www.sozialministerium.gv.at/dam/jcr%3Af08c84c6-b666-4c75-8053-e11cbcfeae0d/Fragen%20und%20Antworten%20Diagnosen-%20und%20Leistungscodierung%20extramural%20ambulant%2020251219.pdf) | `b827597088def7692c106ae6a85b2ceea2ae852d6667365c6064c647d8796912` |
| SRC-AT-CAT-PAGE-2026 | [Kataloge 2026](https://www.sozialministerium.gv.at/Themen/Gesundheit/Gesundheitssystem/Krankenanstalten/LKF-Modell-2026/Kataloge-2026.html) | Live publication page; not byte-frozen |
| SRC-AT-ICD-ALPHA-2026 | [Kataloge 2026 download page](https://www.sozialministerium.gv.at/Themen/Gesundheit/Gesundheitssystem/Krankenanstalten/LKF-Modell-2026/Kataloge-2026.html) | Optional; exact workbook not archived |
| SRC-AT-ICD-EXT-XLSX-2026 | [ICD-10_Extramural.xlsx](https://www.sozialministerium.gv.at/dam/jcr%3A8b83e950-f9d3-4fba-b58a-94c70193a57a/ICD-10_Extramural.xlsx) | `ad305a23dbc038dd5cf136739fe90f5eb6af61f8bf07c569c358b909ca64436d` |

The ministry's current ambulatory documentation page explicitly identifies DIAGLIST 2026 as a machine-readable Excel version of ICD-10 BMASGPK 2026 and states that *Exklusiva* and *Inklusiva* are omitted. It also identifies `ICD-10_Extramural.xlsx` as a shortened table for extramural providers. See [Ambulante Leistungs- und Diagnosendokumentation](https://www.sozialministerium.gv.at/Themen/Gesundheit/Gesundheitssystem-und-Qualitaetssicherung/Dokumentation/Ambulante-Leistungs--und-Diagnosendokumentation.html).

### 3.4 Verified machine-readable source structure

The uploaded workbooks were inspected directly. Their structure is now part of the reproducibility record rather than being inferred from the ministry website.

#### DIAGLIST 2026

`DIAGLIST2026.xlsx` contains one worksheet, `DIAGLIST2026`, with the used range `A1:Q13299`: one header row and **13,298 unique diagnosis/code records**. Its 17 fields are:

`Diagnose`, `Kennzeichen`, `Gruppe`, `HD_Gruppe`, `ZD_Gruppe`, `Geschlecht`, `Mindestalter`, `Höchstalter`, `Unwahrscheinlich`, `HD_ambulant`, `HD_>0_Tage`, `Bezeichnung`, `Meldepflicht`, `Kapitel`, `Unterkapitel`, `Kurzbezeichnung`, and `Stationär_erworben`.

The file is not a flat list of conventional ICD-10 identifiers only. Of its 13,298 records, 13,167 match the ordinary alphanumeric ICD-like pattern used for the structural audit, 102 use numeric special forms such as `101.0`, and 29 use forms such as `C19.x1`. Within the alphanumeric group, 3,929 records use the fifth-character form represented in the Austrian material by codes such as `M00.00`. The `Kennzeichen` field contains the Austrian markers `*`, `!`, `#`, and `+` where applicable. The presence of LKF grouping, demographic, plausibility and reporting fields does **not** make those fields prototype requirements; they should be imported only if a later requirement or represented rule needs them.

The practical consequence is that subset extraction must be explicit. “Import DIAGLIST” is not equivalent to “import the ICD subset used by the artefact.” The import procedure must state which record forms and fields it retains and why.

#### ICD-10_Extramural

`ICD-10_Extramural.xlsx` contains four worksheets:

| Worksheet | Used range | Function |
|---|---:|---|
| `Legende` | `A1:C24` | Source/date information, excluded intramural categories, and field descriptions |
| `ICD-10` | `A1:E9239` | One header row plus **9,238 unique code records** |
| `Kapitel` | `A1:B22` | Chapter identifiers and descriptions |
| `Unterkapitel` | `A1:B229` | Subchapter identifiers and descriptions |

The `ICD-10` sheet contains only `Diagnose`, `Bezeichnung`, `Kurzbezeichnung`, `Kapitel`, and `Unterkapitel`. All 9,238 of its codes also occur in DIAGLIST 2026. Relative to DIAGLIST, 4,060 records are absent. The difference consists of the 3,929 fifth-character records, 102 numeric special records, and 29 `x`-form records identified above. The workbook's own `Legende` states that the table was extracted from the 2026 LKF system data, version 10 December 2025, and excludes intramural-only five-character codes as well as the listed special ranges for endoprosthesis revision reasons, stroke/carotid documentation, gestational age and exogenous noxae.

This makes the extramural workbook a genuine setting-specific subset rather than a second independent catalogue. It is useful if extramural coverage is selected, but because it omits `Kennzeichen` and the other DIAGLIST control fields, status-dependent prototype logic still requires the full DIAGLIST/systematic-directory/guidance baseline.

**Bibliography metadata caution.** The revised `BMASGPK2026DIAGLIST` entry now points directly to the correct official workbook. Its descriptive title, however, calls DIAGLIST solely a list of Austrian ICD-10 codes. Because the workbook demonstrably also contains numeric and other special record forms, the neutral official dataset title `DIAGLIST 2026` would be more precise unless the longer wording can be attributed directly to the publisher. No change has been made to the bibliography in this register update.

### 3.5 Printed-page locator map already verified in Chapter 2

This is a convenience index to source locations already audited during Chapter 2. It is not a substitute for recording the exact locator used by each future requirement or rule.

| Source ID | Printed pages already verified | Supported topic |
|---|---:|---|
| SRC-AT-ICD-SYS-2026 | 14–15 | Austrian edition/version and catalogue status context |
| SRC-AT-ICD-SYS-2026 | 255–256 | G40.3/G40.9 catalogue example used in Chapter 2 |
| SRC-AT-DOC-2026 | 10–13 | ICD hierarchy, status markings, coding depth and specificity |
| SRC-AT-DOC-2026 | 18–20 | Hospital-outpatient diagnosis documentation and selected setting restrictions |
| SRC-AT-DOC-2026 | 23–25 | Inpatient principal/additional diagnosis documentation |
| SRC-AT-LKF-SYS-2026 | 9–16 | MBDS, documentation, grouping, plausibility and LKF context |
| SRC-AT-LKF-AMB-2026 | 9, 12–13, 43–45 | Hospital-outpatient model and diagnosis-reporting context |
| SRC-AT-EXT-HB-2026 | 5–10 | Extramural coding/reporting framework and commencement |
| SRC-AT-EXT-FAQ-2025 | 6–10 | Extramural reporting routes and explanatory context |

### 3.6 Active executable domain criteria after `DOMBASE-0.1`

The domain/classification baseline now fixes the first executable coding-response patterns. This activates only the source fragments needed by those patterns; it does not promote every contextual source in Section 3.2 into a rule oracle.

| Active pattern/use | Controlling source and internal locator | Machine-readable support | Status |
|---|---|---|---|
| Required coding depth for the initial COPD family | `SRC-AT-DOC-2026:pp.12,26` | `SRC-AT-DIAGLIST-2026:DIAGLIST2026/J44.0-J44.09`, including rows 3884-3889 | Active |
| Insufficient-specificity warning and deterministic FEV1 improvement target | `SRC-AT-DOC-2026:pp.26,34` | J44 records/labels from frozen DIAGLIST; FEV1 is a synthetic-case fact, not a DIAGLIST field | Active |
| Code/detail conflict for the source-defined FEV1 bands | `SRC-AT-DOC-2026:p.34` plus the applicable catalogue entry | J44 five-character records from frozen DIAGLIST | Active |
| Hospital `!` main-diagnosis restriction | `SRC-AT-DOC-2026:pp.10-11,18`; concrete permitted outpatient examples on p.22 | `Kennzeichen = !` in frozen DIAGLIST, including `Z00.0`, `Z01.6`, `Z01.8` | Active |
| Austrian edition/version membership | `SRC-AT-ICD-SYS-2026:p.14` | Frozen DIAGLIST membership | Active validation/source boundary |
| Extramural-specific coding behaviour | none | `ICD-10_Extramural.xlsx` remains available for scope/source context | **Not executable in `DOMBASE-0.1`** |

`SRC-AT-LKF-AMB-2026` remains contextual rather than an active code-selection oracle. For the selected `!` rule, the synthetic case can state whether inpatient-LKF scoring applies and `SRC-AT-DOC-2026:p.18` supplies the corresponding coding criterion. The prototype is not required to derive the scoring status of arbitrary hospital services.

This source activation resolves the former setting decision without widening the prototype into comprehensive hospital/LKF or extramural coding support.

## 4. Locator policy

Source locators must reflect the native structure of the source rather than forcing every source into a PDF-page model.

| Source type | Internal trace locator | Thesis citation practice |
|---|---|---|
| Paginated PDF | `SRC-ID:p.13` or `SRC-ID:pp.11-13` | Cite the **printed page number(s)** shown on the document page |
| DIAGLIST 2026 | `SRC-AT-DIAGLIST-2026:DIAGLIST2026/<code>/<field>` | Cite the dataset as a whole where appropriate; identify code and field in the methodological record |
| Extramural catalogue | `SRC-AT-ICD-EXT-XLSX-2026:ICD-10/<code>/<field>` | Cite the dataset as a whole where appropriate; identify code and field in the methodological record |
| Web publication page | `SRC-ID:retrieved-YYYY-MM-DD` | Use only for publication/provenance statements that genuinely depend on the page |
| Internal scope decision | `INT-ID` | No external citation; identify it explicitly as a project/research decision |

Electronic PDF page indices must not be substituted for printed pagination. For catalogue records, a code and field are a more reproducible locator than an invented page number.

## 5. Research and methodological evidence register

These sources appear in Chapters 1 and 2 and can legitimately feed requirement rationales or methodological choices. They are deliberately excluded from the Austrian code-level oracle.

| ID | Thesis key | Permitted downstream role | Not permitted to establish |
|---|---|---|---|
| **EVID-CLS-01** | `WHO2019ICD10Volume2` | Classification structure, relationship between categories/subcategories, conceptual treatment of residual or unspecified classes | Austrian code availability, Austrian status restrictions, case-specific expected code |
| **EVID-CQ-01** | `OMalley2005ICDAccuracy` | Coding-process boundary, documentation trail, sources of coding inaccuracy, distinction between stages of error | Austrian administrative procedure or an Austrian reference-case answer |
| **EVID-CQ-02** | `Campbell2001DischargeCoding` | Measurement cautions, agreement/accuracy distinction, possibility of defensible alternatives | Austrian code ground truth |
| **EVID-CASE-01** | `Plackett2022VirtualPatients` | Rationale for a controlled case context as a digital learning design principle | Evidence that the present prototype improves learning |
| **EVID-FB-01** | `Shute2008FormativeFeedback` | Formative, verification and elaboration feedback principles; task-focused feedback design | Empirical effectiveness of the implemented feedback |
| **EVID-FB-02** | `HattieTimperley2007Feedback` | Feedback alignment with task/goal discrepancy and avoidance of unsupported person-level judgement | Empirical effectiveness of the implemented feedback |
| **EVID-REL-01** | `AgudeloLondono2019CODIFICO` | Related-work comparison; requirement to keep coding objective, interaction and evaluation construct aligned | Effectiveness or correctness of the present prototype |
| **EVID-REL-02** | `WHO2010ICD10TrainingTool` | Comparator showing exercises alongside visible classification/reference material and coding rules | Austrian coding rules or effectiveness of the present prototype |
| **EVID-SE-01** | `Washizaki2025SWEBOK` | Requirement records, traceability, prototyping, test structure, verification terminology and configuration control | Domain/coding correctness |
| **EVID-RULE-01** | `OMG2024DMN15` | Vocabulary for explicit rule conditions, outputs and multiple-match/hit-policy handling | DMN conformance of the prototype, domain correctness, or clinical validity |
| **EVID-DSR-01** | `Hevner2004DesignScience` | Design-science build/evaluate framing and artefact/instantiation concepts | Utility or validity merely from artefact construction |
| **EVID-DSR-02** | `Peffers2007DSRM` | High-level DSR process mapping | A software life cycle or domain rule |
| **EVID-EVAL-01** | `Venable2016FEDS` | Formative/summative and artificial/naturalistic evaluation framing | Real-world usefulness or learning effectiveness from an artificial technical evaluation |

## 6. Internal research and project inputs

These are genuine requirement inputs but must retain internal provenance rather than being presented as external facts.

| ID | Input | Status and downstream role |
|---|---|---|
| **INT-RQ-01** | Main research question: design and implement an Austrian ICD-10 learning tool using explicit rule-based feedback with `correct`, `suboptimal`, and `incorrect` outcomes | Research driver. Requirements and models must collectively permit the question to be answered |
| **INT-RQ-02** | Subquestion on representable recurring coding-error patterns and their translation into decision criteria | Drives error-pattern selection, feedback classifications and rule model |
| **INT-RQ-03** | Subquestion on examining classification consistency using fixed reference cases and targeted software tests | Drives reference-case design, predefined expectations and verification |
| **INT-SCOPE-01** | Austrian baseline fixed to ICD-10 BMASGPK 2026; only a selected subset will be implemented | Frozen scope boundary; prevents catalogue-wide claims |
| **INT-SCOPE-02** | Synthetic learning cases only; no real patient data | Frozen data and ethical/use boundary |
| **INT-SCOPE-03** | Educational demonstrator only; no diagnosis, clinical decision support, official reporting, reimbursement decision or production use | Frozen intended-use boundary; must propagate to requirements, UI wording and claims |
| **INT-EVAL-01** | Technical/model-conformance evaluation through predefined reference cases and targeted software tests; no learner, usability, acceptance or clinical validation study | Working project evaluation boundary. The latest supervisory reply requires systematic testability but does not explicitly answer whether technical verification alone is sufficient; any additional external domain review remains an open supervisory item |
| **INT-TRACE-01** | Requirements, rules, reference cases and tests use stable identifiers and retain backward/forward links | Methodological control derived from EVID-SE-01; required to preserve the evidence chain |
| **INT-TECH-01** | Web prototype with the selected implementation stack | Implementation constraint only. Exact as-built technologies and versions are to be frozen in Section 3.1.4; they are not scientific contributions or domain requirements |
| **INT-SUP-01** | Supervisory decision: no fixed minimum number of cases/codes and no mandatory medical domain; case/subset size is to follow justified coverage of all three feedback classes, multiple error patterns, straightforward cases, and at least some more difficult or ambiguous cases | Project/methodology constraint. Supports the selection and coverage procedure, not code-level truth |
| **INT-SUP-02** | Supervisory decision: representative cases may be explained in the main text; the complete versioned reference-case set is to be documented tabularly in the appendix | Thesis-presentation and reproducibility constraint |
| **INT-SUP-03** | Supervisory decision: technology choice is free but must be requirements-derived; reference data, evaluation logic and UI responsibilities should be separated, with explicit version control, reproducibility, testability, understandable feedback, and appropriate technical documentation | Architecture/development constraint. Does not prescribe a particular framework or deployment topology |
| **INT-SUP-04** | Supervisory decision: `suboptimal` requires explicit, technically assessable and professionally defensible rules rather than intuitive assignment | Drives operationalisation and verification coverage. The actual coding criterion for any rule must still come from an applicable authoritative source rather than the supervisory statement itself |

## 7. Downstream permission matrix

`P` means the source class may provide primary support for that work product. `S` means supporting/contextual use only. `N` means it must not be used for that purpose without an explicit scope decision and justification.

| Input | Requirement rationale | Data/subset | Coding/feedback rule | Reference expected outcome | Method/test design | Context only |
|---|---:|---:|---:|---:|---:|---:|
| SRC-AT-ICD-SYS-2026 | P | P | P | P | N | S |
| SRC-AT-DOC-2026 | P | S | P | P | N | S |
| SRC-AT-DIAGLIST-2026 | P | P | P, limited to represented fields | P, limited to represented fields | N | S |
| SRC-AT-LKF-SYS-2026 | S | S | N by default | N by default | N | P |
| SRC-AT-LKF-AMB-2026 | S | S | Conditional | Conditional | N | P |
| SRC-AT-EXT-HB-2026 | Conditional | Conditional | Conditional | Conditional | N | P |
| SRC-AT-ICD-EXT-XLSX-2026 | Conditional | P if extramural scope is activated | Limited to the five represented fields | Limited to code presence/label/hierarchy represented by the file | N | P |
| SRC-AT-EXT-FAQ-2025 | S | N | N by default | N by default | N | P |
| Research evidence (`EVID-*`) | P | S where directly relevant | Design rationale only | N for case-specific code truth | P where applicable | P |
| Internal inputs (`INT-*`) | P | P | Scope/design constraints | N for external/domain truth | P | P |

## 8. Rule and reference-case source requirements

Every rule that can affect `correct`, `suboptimal`, or `incorrect` should eventually record:

```text
RULE-ID
implements: REQ-ID(s)
conditions: ...
outcome / effect: ...
source_basis:
  - SRC-ID + exact printed-page or dataset locator
precedence / conflict relation: ...
explanation payload: ...
verified_by: RC-ID(s), TEST-ID(s)
```

Every reference case used as a verification oracle should record:

```text
RC-ID
synthetic_case_facts: ...
submitted_code: ...
expected_classification: ...
expected_criterion / rule: ...
expected_explanation_elements: ...
source_basis:
  - applicable catalogue entry/field
  - applicable coding criterion with exact locator
alternative_acceptable_outputs: ...
```

A synthetic case is therefore **test input**, not authority. Its expected result must be derived independently from the frozen source/rule baseline before the final software execution. If an expected result is copied from the implementation output, the test oracle becomes circular.

## 9. Source-change control

1. Once a source version is used to derive requirements, rules or reference cases, its exact edition/file is frozen for the corresponding baseline.
2. A later web revision is not silently substituted for the frozen source.
3. If a source must change, the change receives a new baseline version and triggers impact analysis through all linked requirements, rules, cases and tests.
4. The old observation is retained. A changed expectation does not retrospectively convert an earlier failed execution into a pass.
5. The final verification baseline should identify the exact source-set version alongside the rule, reference-case, test and software versions.

## 10. Baseline freeze checklist and open decisions

### Already established

- [x] Jurisdiction fixed to Austria.
- [x] Catalogue edition fixed to ICD-10 BMASGPK 2026.
- [x] Exact systematic-directory PDF identified and fingerprinted.
- [x] Exact Medical Documentation 2026 PDF identified and fingerprinted.
- [x] Exact `DIAGLIST2026.xlsx` identified, fingerprinted and structurally inspected.
- [x] Exact `ICD-10_Extramural.xlsx` identified, fingerprinted and structurally inspected.
- [x] DIAGLIST and extramural workbook schemas and their subset relation recorded.
- [x] LKF and extramural sources explicitly separated from the core code-level oracle.
- [x] Research evidence separated from authoritative Austrian coding sources.
- [x] Synthetic cases explicitly excluded from the source-of-truth layer.
- [x] Printed-page locator policy established for PDFs.
- [x] Supervisory selection principle fixed to justified coverage rather than a case/code quota or mandatory medical domain.
- [x] Reference-case presentation fixed to representative main-text examples plus the complete versioned appendix matrix.
- [x] Architecture/design constraints fixed to requirements-derived technology choice, logical separation, explicit version control, reproducibility, explainable feedback, systematic testability and appropriate documentation.
- [x] `Suboptimal` identified as an operational category requiring explicit source-backed criteria rather than subjective judgement.
- [x] `DOMBASE-0.1` fixes four response patterns and an explicit `suboptimal` predicate; the classification label is kept distinct from the authority of the underlying Austrian rule.
- [x] Hospital-sector `!` status behaviour is the only selected setting-dependent executable rule in the current domain baseline.
- [x] Extramural-specific executable coding behaviour is excluded from the current domain baseline; extramural sources remain context/provenance material.
- [x] `REQ-DAT-04` and the working `SUBSET-0.1`/`MODELBASE-0.1` specification fix the candidate import whitelist to `Diagnose`, `Kennzeichen`, `Bezeichnung`, and `Kurzbezeichnung` with explicit normalization.
- [x] `CASEPLAN-0.1` defines a working coverage matrix and derives the candidate 13-record subset, four base cases, and 14 response variants. These are working design outputs, not a final verification freeze.

### Required before the data/subset baseline can be declared frozen

- [ ] Adopt and recheck the working four-field whitelist, source projection, and selected records in the actual implementation environment; any as-built change requires explicit justification and versioning.
- [ ] Perform the pre-freeze coverage review required by `REQ-VER-02`, including the documented question of whether additional integration/reference cases are needed beyond the working four-case/14-response design.
- [ ] Record the final as-built case-linked subset and derived-data identities after reproduction from the frozen source; do not inherit exploratory derived-data hashes as project evidence.
- [ ] Assign a frozen source-set version (for example `SRCBASE-1.0`) before predefined reference-case expectations are locked for final verification.

### Open supervisory item outside the data/source freeze

- [ ] Obtain or conservatively proceed without an explicit answer on whether the planned internal technical verification is sufficient without additional independent domain-expert review. Until resolved, make only technical-conformance claims and do not treat supervision as validation evidence.

## 11. How this register enters Chapter 3

The complete control document should not be reproduced in the thesis. Section 3.1.1 can briefly explain the three provenance classes and the traceability convention. Section 3.1.2 should contain a compact table naming the **active** source baseline, its exact versions, its use in subset construction, and the distinction between machine-readable data and semantic/coding guidance. Conditional/contextual sources should be mentioned only if they materially affect the implemented subset.

The remaining detail belongs in this register and, if useful for reproducibility, selected parts may later be moved to an appendix. The register itself should evolve only when a source or its role actually changes; requirements, rules and reference cases should reference its stable IDs rather than repeatedly restating source descriptions.
