# Chapter 3 Requirements Catalogue

**Document status:** Working methodological control document  
**Catalogue version:** 0.5  
**Date:** 6 August 2026  
**Applies to:** Austrian ICD-10 educational prototype, Chapter 3 development work, and later technical verification  
**Upstream baseline:** `chapter3_input_source_baseline_register.md`, register version 0.4  
**Domain/classification baseline:** `chapter3_domain_error_taxonomy_and_classification_baseline.md`, `DOMBASE-0.1`
**Rule baseline:** `chapter3_rule_catalogue.md`, `RULEBASE-0.1`
**Case/subset planning baseline:** `chapter3_reference_case_coverage_plan.md`, `CASEPLAN-0.1` / `SUBSET-0.1`
**Data/interaction baseline:** `chapter3_data_model_and_interaction_baseline.md`, `MODELBASE-0.1` / `CASEBASE-0.1` / `RCBASE-0.1`
**Technical test baseline:** `chapter3_test_catalogue.md`, `TESTBASE-0.1`

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
| `CASE-*` | Synthetic base case | Working IDs and first estimate in `CASEPLAN-0.1`; not yet verification-frozen |
| `RC-*` | Submitted-code/reference-response variant belonging to a base case | Working expectations in `CASEPLAN-0.1`; not yet verification-frozen |
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
| **REQ-DAT-03** | Accepted | The prototype subset shall be purposive and case/rule linked. A retained record must serve an expected/acceptable response, an included error-pattern comparison, a required hierarchy relation, or an intentional boundary/negative test. | `INT-SCOPE-01`, `INT-SUP-01` | Every active subset record has at least one recorded inclusion rationale and downstream link; unrelated catalogue records are absent from the frozen active subset. | Subset-manifest/traceability audit |
| **REQ-DAT-04** | Accepted, working whitelist fixed | Imported fields and transformations shall be explicitly whitelisted and reproducible; source fields shall not become prototype requirements merely because DIAGLIST contains them. `SUBSET-0.1` retains `Diagnose`, `Kennzeichen`, `Bezeichnung` and `Kurzbezeichnung`, with catalogue/subset identity and checksum held as dataset-level metadata. Deterministic preprocessing checks shall cover the fields/relations actually relied upon. | `SRC-AT-DIAGLIST-2026`, `EVID-SE-01` | Import specification names retained fields, transformations and checks; repeated import from the frozen source produces the same active records and values. | Import unit/integration tests and manifest comparison |
| **REQ-DAT-05** | Accepted, hospital rule activated | The active rule baseline shall represent the hospital-sector context needed by `PAT-STATUS-01`. Cases exercising that pattern shall explicitly encode the applicable encounter setting, diagnosis role and, where required, whether a hospital-outpatient visit is scored by the inpatient LKF model. No extramural-specific executable coding rule is included in `DOMBASE-0.1`; `ICD-10_Extramural.xlsx` remains a reduced setting-specific source/context aid rather than a replacement for the full Austrian catalogue baseline. | `SRC-AT-DOC-2026`, `SRC-AT-ICD-EXT-XLSX-2026`, `INT-SCOPE-01` | Every status-rule case contains the setting inputs on which the rule actually depends and traces to the applicable printed Austrian criterion; no executable branch applies an extramural rule. | Rule/data inspection plus hospital-setting boundary tests |

## 6. Case, rule, and feedback model

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-MOD-01** | Accepted | Rule evaluation shall use only case facts explicitly represented in the synthetic case model and shall not invent missing clinical information. | `INT-SCOPE-02`, `INT-SCOPE-03`, `SRC-AT-DOC-2026` | Every rule input is mapped to an explicit case/catalogue/context field; no rule condition depends on an unrepresented clinical inference. | Rule-model inspection and boundary tests |
| **REQ-MOD-02** | Accepted | Synthetic base cases and evaluated coding-response variants shall be distinctly versioned entities: `CASE-*` identifies the vignette/facts and one coding target, while `RC-*` identifies one submitted-code variant and its predefined expectation. | `INT-SUP-01`, `INT-TRACE-01`, `INT-MOD-01` | Each `RC-*` links to exactly one parent `CASE-*` and one submitted code; one `CASE-*` may support multiple independent attempts/response variants without duplicating the vignette. | Schema/reference-matrix inspection |
| **REQ-RUL-01** | Accepted | Every implemented classification rule shall have a stable `RULE-*` identifier and record its required inputs/condition, effect/output, rationale/source basis, explanation payload, precedence/conflict relation where relevant, and verification links. | `INT-TRACE-01`, `EVID-RULE-01`, `EVID-SE-01` | No executable classification branch lacks a corresponding inspectable rule record and source/rationale link. | Rule-catalogue/implementation trace audit |
| **REQ-RUL-02** | Accepted | The rule model shall define objectively assessable conditions for the three feedback outcomes `correct`, `suboptimal`, and `incorrect`; `suboptimal` shall not be used as a residual category for uncertainty or disagreement. `DOMBASE-0.1` fixes `PAT-SPEC-01` as the sole initial `suboptimal` pattern. | `INT-RQ-01`, `INT-SUP-04`, `EVID-CQ-01`, `EVID-CQ-02`, `SRC-AT-DOC-2026` | The decision table implements the `DOMBASE-0.1` predicates. Every `suboptimal` reference variant passes all hard constraints and identifies both the source-backed insufficient-specificity condition and the case-supported improvement target. | Decision-table inspection plus rule/reference tests |
| **REQ-RUL-03** | Accepted | Acceptable alternatives shall be modelled explicitly where the source/case specification permits them. A situation whose three-class outcome cannot be determined without hidden expert judgement shall not be forced into the deterministic reference suite. | `EVID-CQ-02`, `INT-SUP-04` | Each included alternative has an explicit expected treatment; no frozen `RC-*` expectation depends on an undocumented subjective choice. | Reference-case/source audit |
| **REQ-RUL-04** | Accepted | Where multiple rules can match, hard invalidating conditions shall precede graded specificity, so an `incorrect` condition cannot be downgraded to `suboptimal`. All matched reasons shall remain traceable; where one primary hard-error criterion is required, the current stable priority is `STATUS > DEPTH > EVID`. | `EVID-RULE-01`, `INT-TRACE-01`, `INT-SUP-04` | Multi-match behaviour implements `DOMBASE-0.1` independently of incidental rule-storage/order effects and retains secondary matches in the technical trace. | Unit tests for precedence/multi-match cases |
| **REQ-RUL-05** | Accepted | The three feedback classes shall apply only to evaluated responses for which the frozen version/subset and case model define an in-scope relation. Malformed input, an identifier absent from the frozen Austrian version, or a valid Austrian code outside the supported prototype subset shall be prevented or reported as validation/out-of-scope rather than being silently labelled `incorrect`. | `INT-SCOPE-01`, `INT-SCOPE-03`, `SRC-AT-ICD-SYS-2026`, `SRC-AT-DIAGLIST-2026` | UI/API tests distinguish unsupported input from a modelled `incorrect` coding response; a deliberately bounded subset is never used as proof that an omitted Austrian code is invalid. | Input-validation, API and negative-boundary tests |
| **REQ-FBK-01** | Accepted | Every evaluated in-scope response shall return the feedback class together with the determining criterion and a concise explanation; the determining `RULE-*` identifier shall remain available in the technical trace even if it is not shown to the learner. | `INT-RQ-01`, `EVID-FB-01`, `EVID-FB-02`, `INT-SUP-03` | UI response contains class, criterion and explanation for every frozen `RC-*`; the corresponding technical result can be traced to its determining rule identifier. | Integration/end-to-end reference tests |
| **REQ-FBK-02** | Accepted | A `suboptimal` result shall identify the source-backed respect in which the response can be improved, rather than displaying the middle-category label alone. | `INT-SUP-04`, `EVID-FB-01` | Every `suboptimal` reference expectation specifies the required improvement/explanation element and the observed output can be compared against it. | `suboptimal` reference-case tests |

## 7. Interaction, architecture, and implementation

| ID | Status | Requirement | Basis | Acceptance criterion | Planned verification |
|---|---|---|---|---|---|
| **REQ-INT-01** | Accepted | The learner-facing workflow shall permit presentation of a synthetic case, selection/search of an ICD code from the supported prototype data, submission, evaluation, and display of feedback. The initial prototype shall accept exactly one submitted ICD code for the case-defined coding target per evaluation request; later attempts are separate requests and no multi-code result aggregation is performed. | `INT-RQ-01`, `EVID-CASE-01`, `INT-SUP-03`, `INT-MOD-01` | At least one end-to-end path performs all stated stages without manual intervention in the evaluation result; API/UI inspection confirms one-code request cardinality and absence of implicit multi-code aggregation. | End-to-end/API test and representative screenshot/trace |
| **REQ-ARC-01** | Accepted | Reference data/cases, evaluation/feedback logic, and presentation/UI responsibilities shall be logically separated so that classification behaviour is not embedded solely in the interface. Predefined verification expectations shall remain outside the runtime classification-data path. | `INT-SUP-03`, `EVID-SE-01`, `INT-MOD-01` | Architecture/component documentation allocates the three responsibilities distinctly; evaluation can be exercised independently of UI state, and the runtime database/evaluation endpoint does not consume `RC-*` expected classes/rules as classification inputs. | Architecture/schema inspection and integration test |
| **REQ-ARC-02** | Accepted | For identical case facts, submitted code, rules, catalogue/reference data, and version baseline, evaluation shall be deterministic and reproducible. | `INT-SUP-03`, `EVID-RULE-01` | Repeated executions against an unchanged baseline yield identical class, determining rule/criterion, and required explanation elements. | Repeatability/regression tests |
| **REQ-IMP-01** | Accepted project constraint | The working implementation shall use the selected web stack (React frontend, PHP backend/API, MySQL persistence, and Python preparation/import tooling) unless a documented requirement-driven change is made before implementation freeze. | `INT-TECH-01`, `INT-SUP-03` | As-built architecture records actual technologies and versions; any departure is recorded with its rationale and affected requirements. | Architecture/build manifest inspection |
| **REQ-DOC-01** | Accepted | The thesis/project artefacts shall document the logical architecture, relevant data structures, principal interfaces/data flow, and rule-evaluation responsibility sufficiently to relate implementation to the conceptual model. | `INT-SUP-03`, `EVID-SE-01` | Chapter 3 and/or appendix contains the agreed architecture/model exhibits and enough interface/data description to trace one response end to end. | Documentation inspection |

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
| 3.1.4 Architecture and Implementation | `REQ-INT-01`, `REQ-ARC-*`, `REQ-IMP-01`, `REQ-DOC-01` |
| 3.2.1 Reference Cases and Test Design | `REQ-VER-01` through `REQ-VER-04`, `REQ-VER-07` |
| 3.2.2 Verification Procedure and Conformance Criteria | `REQ-CFG-01`, `REQ-VER-03` through `REQ-VER-06`, claim boundary from `REQ-SCP-03` |

## 12. Freeze criteria for `REQBASE-1.0`

The requirement baseline is ready to freeze when:

- every Accepted requirement has a concrete acceptance criterion and a downstream model/implementation destination;
- the final included error-pattern taxonomy and source-backed `suboptimal` criteria remain fixed to a versioned domain baseline (`DOMBASE-0.1` currently satisfies this condition);
- the `SUBSET-0.1` four-field DIAGLIST whitelist and 13-record selection are reproducibly regenerated from the frozen source, or any later change is explicitly versioned; hospital-setting rule activation is already fixed by `DOMBASE-0.1`;
- the single-code interaction cardinality fixed in `MODELBASE-0.1` remains reflected by the API/UI and reference-response schema;
- the `CASEPLAN-0.1` estimate of four base cases and fourteen atomic response variants is adopted for the frozen suite or any material change is justified against the declared coverage matrix;
- the targeted technical coverage in `TESTBASE-0.1`, or a justified later revision, is bound to the as-built implementation before the principal run;
- every mandatory requirement has a planned verification path or an explicitly documented gap;
- the unresolved external-expert-review question has either been answered or is conservatively handled without expanding the evaluation claim; and
- any material change to these records increments the catalogue version and preserves the previous decision history.

At that point the catalogue should be assigned `REQBASE-1.0`. Working `RULEBASE-0.1` already cites the relevant `REQ-*` identifiers rather than restating their rationales from scratch; any later rule-baseline revision must preserve that linkage.
