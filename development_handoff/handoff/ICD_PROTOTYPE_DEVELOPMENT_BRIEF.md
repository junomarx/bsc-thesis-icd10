# Austrian ICD-10 Educational Prototype

## Development commission and technical handoff brief

**Handoff status:** corrected preimplementation handoff and working specification brief  
**Snapshot date:** 7 August 2026  
**Project type:** Bachelor-thesis design and software-development artefact  
**Jurisdiction:** Austria  
**Catalogue baseline:** ICD-10 BMASGPK 2026  
**Current prototype baseline:** `PROTOBASE-0.1` proposed working identifier; not yet verification-frozen  
**Document purpose:** portable project context for a developer, contractor, or
coding agent that has no access to the preceding conversation

## 1. Commission

Build a small, traceable web application that demonstrates how selected coding
responses from the Austrian ICD-10 BMASGPK 2026 catalogue can be evaluated
against synthetic, explicitly represented coding cases using transparent rules.
For a supported case and submitted code, the prototype returns one of three
educational feedback classes:

- `correct`;
- `suboptimal`; or
- `incorrect`.

The system must explain why that result was reached. Its central value is not
the number of ICD codes implemented or sophistication of the interface. The
important deliverable is a reproducible vertical path from authoritative source
evidence through requirements, data and case models, explicit decision rules,
software behaviour, predefined reference expectations, and technical
verification.

The current working research question is:

> How can a web-based learning prototype for the Austrian ICD-10 catalogue be
> designed, implemented, and technically verified to classify selected coding
> responses as correct, suboptimal, or incorrect through explicit rule-based
> feedback?

The wording remains subject to final supervisory confirmation. Software should
therefore implement the already bounded behaviour rather than become coupled to
minor wording changes in the research question.

### 1.1 What counts as success

The commissioned prototype is successful when a learner-visible synthetic case
can be retrieved, one supported code can be selected and submitted, the backend
can evaluate that submission deterministically from the versioned case,
catalogue and rule inputs, and the UI can present the expected class and
criterion-specific explanation. The same rule engine must be executable
independently of the UI so that the predefined verification suite can exercise
it systematically.

The final thesis claim is deliberately narrower than "the application codes
patients correctly". The intended claim is technical/model conformance within a
small frozen source, rule and reference-case baseline.

## 2. Intended use and non-goals

The application is an educational demonstrator using synthetic cases. It does
not receive or infer real patient diagnoses and does not process real patient
data.

The following are explicitly outside the current commission:

- diagnosis of disease or inference of the patient's true condition;
- clinical decision support or treatment recommendations;
- official Austrian diagnosis reporting;
- LKF reimbursement, pricing or ICD-to-payment logic;
- production or medical-device use;
- comprehensive Austrian ICD-10 coverage;
- extramural-specific executable coding rules;
- historical-code activation/deactivation logic without a new source model;
- multi-code response aggregation;
- user accounts, authentication as a learner feature, learner histories,
  longitudinal analytics or attempt persistence;
- usability, acceptance, learning-effectiveness or knowledge-retention studies;
- clinical validation, real-world error reduction claims or independent proof
  of Austrian coding correctness;
- performance/load/scalability work, penetration testing, regulatory
  certification or production hardening unless the project scope is explicitly
  revised.

The user interface should be clear and functional, but visual sophistication is
secondary to understandable feedback, deterministic behaviour and
traceability.

## 3. Governing development principle

The project follows this dependency chain:

```text
authoritative Austrian sources + research evidence + project constraints
                              |
                              v
                         REQ-* catalogue
                              |
                              v
                  PAT-* / RULE-* specification
                              |
                              v
                 CASE-* / SUBSET-* / MODELBASE
                              |
                              v
             implementation + runtime persistence
                              |
                              v
        RC-* oracle + TEST-* technical verification
```

Implementation is downstream of the specification. Existing code must never be
used retrospectively to invent its own expected result.

Three kinds of authority must remain distinct:

1. Official Austrian sources establish catalogue facts and the selected coding
   criteria.
2. Research and software-engineering sources justify design, feedback,
   traceability and verification methods, but cannot establish the correct
   Austrian code for a case.
3. Internal project and supervisory decisions define scope, architecture
   qualities and educational classification semantics, but are not Austrian
   coding authority.

The Ministry's source material does not label responses `correct`, `suboptimal`
or `incorrect`. Those are artefact classifications. The underlying predicate
must still trace to an applicable source wherever it represents an Austrian
coding criterion.

If this portable brief and a versioned control artefact disagree about an
exact requirement, rule, case, data contract, or test, the control artefact is
authoritative. Do not resolve contradictions by guessing from the software or
by silently choosing whichever document is newer. Record the discrepancy and
trace its downstream impact. Section 23.3 identifies known stale statements in
older working documents so that they are not mistaken for reopened decisions.

## 4. Authoritative source baseline

### 4.1 Core sources used by executable behaviour

| Source ID | Source | Role in the project | Frozen identity |
|---|---|---|---|
| `SRC-AT-ICD-SYS-2026` | *ICD-10 BMASGPK 2026 - Systematisches Verzeichnis*, thesis key `BMASGPK2025ICD10Catalogue` | Human-readable catalogue, hierarchy, notes and Austrian status context | SHA-256 `cc46dbd161c6d4d75f4196a25139b1b200dcb2f24858f2bedacb81295604de2d` |
| `SRC-AT-DOC-2026` | *Medizinische Dokumentation: Codierhinweise bis inklusive 41. LKF-Rundschreiben*, key `BMASGPK2025MedicalDocumentation2026` | Controlling guidance for represented coding depth, FEV1 mapping, specificity warning and hospital `!` status restrictions | SHA-256 `69b37f1879acb5cda63eca30086e61a1f17b058bb26fc629d6a64bd25736653b` |
| `SRC-AT-DIAGLIST-2026` | `DIAGLIST2026.xlsx`, key `BMASGPK2026DIAGLIST` | Machine-readable catalogue and reproducible subset source | SHA-256 `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` |

Official baseline locations recorded in the source register:

- Systematic directory: <https://www.sozialministerium.gv.at/dam/jcr%3A64beeaa0-ec63-4864-a954-0ee1beb9e5c8/ICD-10%20BMASGPK%202026%2B%20-%20SYSTEMATISCHES%20VERZEICHNIS.pdf>
- Medical Documentation 2026: <https://www.sozialministerium.gv.at/dam/jcr%3A2acd92ba-9b21-45c4-a4f6-d8345943e7b1/MEDIZINISCHE%20DOKUMENTATION%202026.pdf>
- DIAGLIST 2026: <https://www.sozialministerium.gv.at/dam/jcr%3Aadcd10f6-ae8d-4e5c-9de8-e33628e739c0/DIAGLIST2026.xlsx>

A live download is not automatically the frozen source. If a source must be
retrieved again, compare its checksum with the value above before using it to
reproduce an existing baseline. A changed official file requires an explicit
source-baseline change and downstream impact analysis.

### 4.2 Contextual and conditional official sources

These sources are important for thesis context and scope but do not
automatically become code-selection oracles.

| Source ID | Source | Current permitted role | SHA-256 / status |
|---|---|---|---|
| `SRC-AT-LKF-SYS-2026` | LKF System Description 2026 | Hospital/LKF documentation and system context | `3b2645550b1c34fef7f382951430995b8e06cc6ff2599ee3b43c8a73fa741e0a` |
| `SRC-AT-LKF-AMB-2026` | LKF model 2026 for hospital outpatient care | Hospital-outpatient setting context | `fdfc884ea5bc583a9af39d02f105f295f572d290c30da0304a3f97afe4a2b724` |
| `SRC-AT-EXT-HB-2026` | Extramural medical-documentation handbook, 1 July 2026 | Extramural scope/context only unless a later baseline activates a rule | `9dce0bc14c4836c6fb966621b683879d1546e5f27cfb79361432ad944763bc80` |
| `SRC-AT-EXT-FAQ-2025` | Extramural FAQ, 19 December 2025 | Supplementary explanatory context | `b827597088def7692c106ae6a85b2ceea2ae852d6667365c6064c647d8796912` |
| `SRC-AT-ICD-EXT-XLSX-2026` | `ICD-10_Extramural.xlsx` | Setting-specific reduced catalogue aid; not a replacement for DIAGLIST | `ad305a23dbc038dd5cf136739fe90f5eb6af61f8bf07c569c358b909ca64436d` |

The extramural workbook contains 9,238 unique code records and is a subset of
the full DIAGLIST. It deliberately omits intramural five-character and special
record forms and contains only five principal catalogue fields. No
extramural-specific behaviour is executable in `DOMBASE-0.1`.

### 4.3 Research and methodological evidence

The working evidence register includes the following sources. They can justify
project design but must never be used as Austrian code-level authority.

| Evidence ID | Thesis key | Project role |
|---|---|---|
| `EVID-CLS-01` | `WHO2019ICD10Volume2` | classification structure and coding concepts |
| `EVID-CQ-01` | `OMalley2005ICDAccuracy` | coding process and sources of coding inaccuracy |
| `EVID-CQ-02` | `Campbell2001DischargeCoding` | agreement/accuracy cautions and defensible alternatives |
| `EVID-CASE-01` | `Plackett2022VirtualPatients` | controlled case-based digital-learning rationale |
| `EVID-FB-01` | `Shute2008FormativeFeedback` | formative and elaborated feedback design |
| `EVID-FB-02` | `HattieTimperley2007Feedback` | task-focused feedback principles |
| `EVID-REL-01` | `AgudeloLondono2019CODIFICO` | related ICD-learning approach and construct alignment |
| `EVID-REL-02` | `WHO2010ICD10TrainingTool` | related training/reference approach |
| `EVID-SE-01` | `Washizaki2025SWEBOK` | requirements, traceability, testing and configuration control |
| `EVID-RULE-01` | `OMG2024DMN15` | explicit decision-rule and hit-policy concepts |
| `EVID-DSR-01` | `Hevner2004DesignScience` | design-science build/evaluate framing |
| `EVID-DSR-02` | `Peffers2007DSRM` | high-level DSR process mapping |
| `EVID-EVAL-01` | `Venable2016FEDS` | artificial/naturalistic and formative/summative evaluation framing |

### 4.4 Source locator policy

For internal traceability, PDF rules retain printed page locators, not PDF
viewer page indexes. Machine-readable sources use workbook, worksheet, row,
field and checksum locators. Thesis-facing citation formatting may later omit
pinpoint pages to follow the supervisor's requested institutional convention;
that formatting decision must not erase the internal source locator.

The rule-critical printed locations are currently:

- Medical Documentation pp. 10-11 and 18: `!` main-diagnosis restrictions;
- p. 22: concrete permitted hospital-outpatient `Z01.6!` / `Z01.8!`
  examples;
- pp. 12 and 26: mandatory five-character COPD coding;
- p. 26: `Unzureichend abgeklärte Hauptdiagnose` warning, including selected
  `J44.x9` forms;
- p. 34: stable-phase FEV1 mapping to COPD fifth-character suffixes;
- Systematic directory p. 14: represented Austrian edition/status context.

## 5. Current baseline snapshot and state semantics

The present state is primarily a **specified design with candidate
implementation scaffolding**. It is not a completed implementation and it is
not the final thesis evaluation freeze.

The following status words are used deliberately throughout this handoff:

- **specified**: behaviour or structure has been defined in a control artefact;
- **candidate**: an implementation/data file has been prepared outside the
  actual application repository and is available for inspection/adoption;
- **adopted**: the candidate has been deliberately integrated into the actual
  project, but adoption alone says nothing about whether it works;
- **development-tested**: a named check has actually been executed against an
  identified project state and its observation has been recorded; and
- **frozen/verified**: the final relevant baselines, software revision and
  environment have been identified and the principal verification has been
  executed against that state.

No item in this handoff is to be treated as `development-tested` or
`frozen/verified` merely because exploratory code exists for it.

| Layer | Current identifier/version | Status |
|---|---|---|
| Source register | `0.4` | working |
| Requirements catalogue | `0.5` | working; 31 requirements/constraints |
| Domain taxonomy | `DOMBASE-0.1` | working design baseline |
| Rule catalogue | `RULEBASE-0.1` | implementation-ready working baseline |
| Case/subset plan | `CASEPLAN-0.1` | working plan |
| Catalogue subset | `SUBSET-0.1` | specified 13-record candidate; derived CSV supplied for reproduction/checking |
| Data/interaction model | `MODELBASE-0.1` | working specification; candidate physical artefacts supplied |
| Synthetic cases | `CASEBASE-0.1` | four working candidate case definitions |
| Reference responses | `RCBASE-0.1` | 14 working candidate expectations; not final-frozen |
| Technical tests | `TESTBASE-0.1` | 17 test specifications; no inherited execution verdicts |
| Prototype data baseline | `PROTOBASE-0.1` | proposed working identifier represented by candidate files; not an established verified project baseline |

`prototype_baseline_0_1/baseline_manifest.json` is a candidate manifest that
binds the intended working identifiers and DIAGLIST identity. Any digest of
derived/runtime material must be generated and recorded again after the
candidate data pipeline is adopted in the actual project. The handoff does not
carry an exploratory derived-data digest forward as verification evidence.

## 6. Compact requirements contract

The full acceptance criteria and provenance remain in
`chapter3_requirements_catalogue.md`. The implementation-facing obligations are
summarised here so a contractor can understand the commission even when the
full thesis workspace is not immediately available.

| ID | Required outcome |
|---|---|
| `REQ-SCP-01` | Web educational demonstrator; synthetic cases; bounded versioned Austrian 2026 subset. |
| `REQ-SCP-02` | No diagnosis, clinical decision support, reporting/reimbursement or production/medical-device function. |
| `REQ-SCP-03` | Claims limited to examined technical/model conformance. |
| `REQ-DAT-01` | Exact Austrian edition/source identities must be recorded and frozen. |
| `REQ-DAT-02` | DIAGLIST supplies machine-readable records; systematic/guidance sources supply semantics not present in DIAGLIST. |
| `REQ-DAT-03` | Every active subset record must have a case/rule/test inclusion rationale. |
| `REQ-DAT-04` | Import fields and transformations must be explicit and reproducible. |
| `REQ-DAT-05` | Represent the selected hospital context for the `!` rule; no extramural executable rule. |
| `REQ-MOD-01` | Rules may consume only explicitly represented case/catalogue/context facts. |
| `REQ-MOD-02` | Separate base case (`CASE-*`) from submitted reference response (`RC-*`). |
| `REQ-RUL-01` | Every executable classification rule has stable ID, inputs, condition, output, source/rationale, explanation, precedence and verification links. |
| `REQ-RUL-02` | `correct`, `suboptimal`, `incorrect` must have objective conditions; `suboptimal` is not residual uncertainty. |
| `REQ-RUL-03` | Acceptable alternatives must be explicit; unbounded subjective situations are excluded. |
| `REQ-RUL-04` | Hard invalidating rules precede graded specificity; primary hard priority is `STATUS > DEPTH > EVID`; secondary matches remain traceable. |
| `REQ-RUL-05` | Unsupported/malformed/out-of-scope input is not silently labelled `incorrect`. |
| `REQ-FBK-01` | Every classified response includes class, criterion and concise explanation; determining rule remains technically traceable. |
| `REQ-FBK-02` | `suboptimal` feedback identifies the concrete source-backed improvement. |
| `REQ-INT-01` | Learner path is case -> one code -> evaluation -> feedback; one submitted code per request. |
| `REQ-ARC-01` | Reference/case data, evaluation logic and presentation are logically separated; verification oracle is outside runtime classification. |
| `REQ-ARC-02` | Identical inputs and baselines produce deterministic evaluation semantics. |
| `REQ-IMP-01` | Working stack is React + PHP/API + MySQL + Python preparation/import unless a requirement-driven change is documented before freeze. |
| `REQ-DOC-01` | Architecture, relevant data structures, interfaces/data flow and rule responsibility must be reconstructible from documentation. |
| `REQ-TRC-01` | Preserve source/evidence -> requirement -> model/rule -> implementation -> case/test traceability. |
| `REQ-CFG-01` | Final evaluation freezes source, subset, rules, cases/oracle, tests, software revision and environment. |
| `REQ-VER-01` | Case/code count follows coverage, not an arbitrary quota. |
| `REQ-VER-02` | Coverage must include all three classes, multiple patterns, straightforward and harder objectively decidable cases, and important boundaries/interactions. |
| `REQ-VER-03` | Reference expectations are predefined independently of implementation and withheld from runtime classification. |
| `REQ-VER-04` | Use targeted unit, integration, boundary/negative, end-to-end and regression testing appropriate to the artefact. |
| `REQ-VER-05` | Principal verification uses predefined conformance categories, not post-hoc judgement. |
| `REQ-VER-06` | Deviations are classified by cause, retained, impact-analysed and followed by appropriate reruns. |
| `REQ-VER-07` | Representative cases in thesis text; full versioned reference matrix in appendix. |

## 7. DIAGLIST data contract and prototype subset

### 7.1 Frozen machine-readable input

`DIAGLIST2026.xlsx` contains one worksheet named `DIAGLIST2026`, with one header
row and 13,298 unique `Diagnose` identifiers. It contains 17 source fields, but
the prototype does not import them merely because they exist.

`SUBSET-0.1` retains only:

| DIAGLIST source field | Runtime meaning | Transformation |
|---|---|---|
| `Diagnose` | `code` | trim surrounding whitespace; preserve identifier |
| `Kennzeichen` | `marker` | trim; whitespace-only becomes null/blank representation; preserve `!` |
| `Bezeichnung` | `designation` | preserve Unicode source text |
| `Kurzbezeichnung` | `short_designation` | preserve Unicode source text |

The selected codes are externally configured in
`prototype_baseline_0_1/config/subset_definition_0_1.json`; they are not hidden
inside `prepare_subset.py`.

### 7.2 Active 13-code subset

```text
J44.0
J44.00
J44.01
J44.02
J44.03
J44.09
J44.1
J44.10
J44.11
J44.12
J44.13
J44.19
Z01.6   [Kennzeichen = !]
```

`Z01.8!` is deliberately outside the active subset and is used as a known 2026
source control for `outside_active_subset` behaviour. Absence from this small
subset must never be interpreted as proof that a code is invalid Austrian
coding.

The two J44 families are complete selectable response families for the two
learner COPD cases. `J44.8`, `J44.9` and further catalogue records are omitted
because they add no distinct rule behaviour required by the current working
coverage model.

## 8. Synthetic case baseline and response domain

### 8.1 Base cases

| Case | Facts | Intended use | Closed response domain | Accepted set |
|---|---|---|---|---|
| `CASE-001` | Inpatient, main diagnosis, documented COPD with acute lower-respiratory infection, stable-phase FEV1 55%, base `J44.0` | learner visible | six `J44.0*` records in subset | `{J44.02}` |
| `CASE-002` | Inpatient, main diagnosis, documented COPD with acute exacerbation, stable-phase FEV1 exactly 50%, base `J44.1` | learner visible | six `J44.1*` records in subset | `{J44.12}` |
| `CASE-003` | Hospital outpatient, main diagnosis, ordinary/non-inpatient-LKF-scored radiography/CT context, `inpatient_lkf_scored=false` | learner visible | `{Z01.6}` | `{Z01.6}` |
| `CASE-004` | Hospital outpatient, main diagnosis, paired `Z01.6!` status fixture with `inpatient_lkf_scored=true` | **verification only** | `{Z01.6}` | empty |

`CASE-004` is intentionally not a learner task. Its purpose is to exercise the
prohibited status branch without inventing another diagnosis as a supposed
replacement.

### 8.2 Current external reference oracle

`RCBASE-0.1` contains 14 response expectations: all six response-domain codes
for each COPD case plus one status response for each of `CASE-003/004`.

The working class distribution is:

```text
correct:     3
suboptimal:  2
incorrect:   9
total:      14
```

This distribution has no statistical or epidemiological meaning. The suite is
purposive and coverage-driven. A final report should retain per-response
verdicts rather than turn 14 purposive observations into a clinically
meaningful "accuracy percentage".

## 9. Domain classification contract

Four response patterns are in scope:

| Pattern | Meaning | Terminal class |
|---|---|---|
| `PAT-DEPTH-01` | Required Austrian coding depth not met | `incorrect` |
| `PAT-SPEC-01` | Explicit source-backed specificity available from represented information but unspecified form used | `suboptimal` |
| `PAT-EVID-01` | Submitted code encodes detail that contradicts an explicit case fact | `incorrect` |
| `PAT-STATUS-01` | `!` code used as main diagnosis in a represented prohibited hospital context | `incorrect` |

`Correct` is a separate terminal acceptance result: a response must belong to
the predefined acceptable set and must survive all applicable higher-priority
rules.

### 9.1 FEV1 mapping used by the COPD rules

For the initial inpatient COPD implementation, Medical Documentation printed
p. 34 defines:

| Stable-phase FEV1 (% predicted) | Fifth-character suffix |
|---:|---|
| `<35` | `0` |
| `>=35` and `<50` | `1` |
| `>=50` and `<70` | `2` |
| `>=70` | `3` |
| not further specified | `9` |

No unreferenced clinical plausibility range is to be invented around this
mapping.

### 9.2 Meaning of `suboptimal`

`suboptimal` is true only when all hard rules pass, the response is an active
warning-listed unspecified COPD main-diagnosis form, the case contains the
stable-phase FEV1 value needed to distinguish the more specific suffix, and the
official mapping yields a supported specific target. In the initial baseline,
`PAT-SPEC-01` / `RULE-SPEC-01` is the only source of this class.

A merely different, broader, related or arguable code is not automatically
`suboptimal`.

## 10. Executable rule contract

The eight working rules are:

| Rule | Responsibility | Effect |
|---|---|---|
| `RULE-GATE-01` | Evaluation eligibility and scope | eligible or `not_evaluated` |
| `RULE-MAP-01` | Stable-phase FEV1 -> expected COPD suffix/target | derived data, no class |
| `RULE-STATUS-01` | Prohibited `!` main-diagnosis use | hard `incorrect` |
| `RULE-DEPTH-01` | Mandatory inpatient COPD fifth-character depth | hard `incorrect` |
| `RULE-EVID-01` | FEV1/suffix contradiction | hard `incorrect` |
| `RULE-SPEC-01` | Known FEV1 left unspecified under source-backed warning condition | `suboptimal` |
| `RULE-CORRECT-01` | Declared acceptable response after prior rules clear | `correct` |
| `RULE-PREC-01` | Deterministic conflict/terminal policy | controls final result |

Normative evaluation order:

```text
gate
  |
  +-- fail ------------------------------> not_evaluated
  |
  v
derive FEV1 mapping where applicable
  |
  v
evaluate STATUS, DEPTH, EVID hard predicates
  |
  +-- any hard match --------------------> incorrect
  |       primary: STATUS > DEPTH > EVID
  |       retain every hard match in trace
  |
  v
evaluate SPEC
  |
  +-- match -----------------------------> suboptimal
  |
  v
evaluate predefined acceptance
  |
  +-- accepted --------------------------> correct
  |
  v
specification/conformance gap
```

The last path is deliberately **not** `incorrect`. If a relation declared
evaluable reaches it, the model or implementation is incomplete.

### 10.1 Required result semantics

The evaluator conceptually returns:

```text
evaluation_status: classified | not_evaluated
classification: correct | suboptimal | incorrect | null
determining_rule: RULE-* | null
criterion: stable machine-readable criterion key
explanation: learner-readable explanation
explanation_elements: structured semantic payload
matched_rules: RULE-*[]
improvement_code: ICD code | null
baseline_versions: relevant source/subset/rule/case/model identities
```

Exact JSON field names can be finalised during PHP implementation, but the
semantic information required by `TEST-RC-01` and `TEST-DET-01` must remain
recoverable.

## 11. Explicit non-rules

Without a versioned upstream change, do not implement:

- `submitted_code != expected_code -> incorrect`;
- `is_acceptable = false -> incorrect`;
- a default `else -> incorrect`;
- diagnosis inference from symptoms;
- a learner-facing `G40` hierarchy error based on a non-selectable category
  heading;
- historical inactive-code logic;
- extramural-specific coding rules;
- LKF reimbursement rules;
- inferred learner intent, fraud or upcoding;
- any rule that needs a case fact that the case does not explicitly represent.

## 12. Runtime data model and oracle boundary

The proposed runtime MySQL schema defines exactly four tables:

| Table | Responsibility |
|---|---|
| `prototype_baseline` | Identifies the active source/model/requirements/domain/rule/case/subset combination. |
| `catalogue_code` | 13 selected normalized DIAGLIST records. |
| `case_definition` | Four represented synthetic case records and explicit rule facts. |
| `case_code_domain` | Closed case-code response relations and `is_acceptable` membership. |

The verification oracle is the separate file:

```text
prototype_baseline_0_1/verification/reference_responses_0_1.csv
```

It contains expected classes, rules, criteria, improvement codes and required
explanation elements. It is test-harness data only.

The runtime database must **not** contain an expected-class, expected-rule,
expected-criterion or RC answer-key table. The PHP evaluator must work when the
verification file is physically unavailable to its process.

`case_code_domain.is_acceptable` is permitted runtime data because
`RULE-CORRECT-01` explicitly depends on a predefined accepted set. A false
value is not itself an error classification.

## 13. Data preparation and persistence

The deterministic preparation path is:

```text
DIAGLIST2026.xlsx
        +
config/subset_definition_0_1.json
        |
        v
scripts/prepare_subset.py
        |
        v
data/subset_0_1.csv
        +
cases_0_1.csv
        +
case_code_domain_0_1.csv
        |
        v
runtime_data.py validation
        |
        v
MySQL schema + load_mysql.py
```

Important implementation properties:

1. The workbook checksum and worksheet are checked before extraction.
2. The expected 13,298 unique source identifiers are checked.
3. Code selection and the four-field whitelist live in JSON configuration, not
   hidden inside Python selection logic.
4. Only declared normalization is performed.
5. MySQL DDL is applied separately because DDL is not claimed to be part of the
   runtime DML transaction.
6. Runtime DML is transactional and read back before commit.
7. Reimport of the same exact baseline returns `no_op`.
8. Divergent contents under an already meaningful baseline identifier are
   rejected, not silently updated.
9. An intentional semantic change receives a new baseline identifier.
10. Learner attempts/results are not currently persisted.

`bootstrap_mysql.py` coordinates this safely: an empty database receives the
schema, an exact known runtime table set is reused, and a partial/unexpected
schema fails rather than being "repaired" automatically.

## 14. Application architecture and technology contract

The selected working stack is:

- React for the learner frontend;
- PHP for backend/API and rule evaluation;
- MySQL for versioned runtime reference/case data;
- Python for offline source preparation and bootstrap/import tooling;
- Docker Compose as the reproducible local execution scaffold.

No particular React or PHP framework is scientifically required. If the
application repository is empty, prefer the smallest maintainable/testable
solution that satisfies the contracts rather than introducing a large
framework by default. If the repository already has a coherent framework,
preserve it unless a concrete requirement conflicts with it.

Logical responsibilities must remain separable even if they ultimately run in
one application container:

```text
React presentation
       |
       v
PHP request/API boundary
       |
       +------------> case/catalogue repository ------> MySQL
       |
       v
rule evaluator
       |
       v
feedback/result builder
       |
       v
API response -> React feedback presentation
```

The evaluator should be testable without React. Database access should not be
embedded into presentation code, and rule predicates should not be hidden in UI
conditionals.

## 15. Minimum learner workflow

The learner-visible application must support:

1. listing or navigating learner-visible cases;
2. viewing the synthetic case facts needed for the coding task;
3. selecting or searching the codes explicitly supported for that case;
4. submitting exactly one code;
5. receiving the feedback class;
6. receiving a concise criterion-specific explanation; and
7. where applicable, receiving a concrete improvement target.

The UI should normally offer only the case's defined response domain. The PHP
boundary must nevertheless enforce the gate independently because tests will
submit unsupported combinations directly.

`CASE-004` must be retrievable by the technical verification path but excluded
from learner-facing case navigation.

## 16. API semantic boundary

The existing conceptual endpoint is:

```text
POST /api/cases/{case_id}/evaluate

request:
  submitted_code: one non-empty code string
```

The final implementation may choose concrete response fields and HTTP status
codes, but must preserve these semantics:

- one code string is accepted;
- missing/blank/malformed submissions are rejected before classification;
- a list/array of several codes is rejected, not aggregated;
- outside-subset and undefined case relations produce a non-classified result;
- classified results expose the class, criterion and explanation semantics
  required by the rule and test baselines;
- technical trace fields can remain hidden from the learner while still being
  available to automated verification.

## 17. Container and source-management scaffold

`prototype_stack/` contains a candidate development scaffold with the intended
topology below. It has to be inspected, adopted and actually executed in the
project environment before it can be described as the project's working
stack:

```text
Git source configuration + ignored token
                 |
                 v
           stack.sh sync
                 |
                 v
          .runtime/app
                 |
                 v
           app Docker build
                 |
                 +-----------------------+
                 |                       |
                 v                       v
        bootstrap one-shot          app runtime
                 |                       |
                 v                       |
            MySQL 8.4.8 <----------------+
```

Long-running services are `db` and `app`. `bootstrap` is a one-shot service.
The app repository must provide the root `Dockerfile` referenced by Compose.
The intended app image may use Node only in a build stage to compile React,
then run PHP/Apache plus the static build without a permanent Node service.

Supported wrapper commands are:

```bash
./stack.sh init
./stack.sh doctor
./stack.sh sync
./stack.sh up
./stack.sh up --sync
./stack.sh verify
./stack.sh verify --frozen
./stack.sh status
./stack.sh down
```

Normal `down` preserves the MySQL named volume. No destructive reset command is
part of ordinary operation.

### 17.1 Git and credential policy

Non-secret source configuration defines repository URL, ref, ref type,
username, token-file location and generated checkout location. The token itself
is accepted from `APP_GIT_TOKEN` or the ignored `.secrets/git-token` file and
is passed through `GIT_ASKPASS`. Explicit-token mode disables configured Git
credential helpers. Credentials must not be embedded in the repository URL,
Dockerfile, build arguments, image or saved `origin`.

`sync` behaves conservatively:

- absent checkout: clone;
- clean existing branch: fetch and fast-forward only;
- exact revision mode: detached checkout of configured revision;
- dirty checkout: refuse update;
- mismatched `origin`: refuse rewrite;
- no automatic reset or deletion.

During development a branch such as `main` may be used. The principal thesis
verification must use a full 40-character application commit SHA with
`APP_GIT_REF_TYPE=revision` and `verify --frozen`.

## 18. Technical verification contract

`TESTBASE-0.1` defines 17 test specifications. Several contain multiple
vectors, so "17 tests" is not equivalent to 17 assertions and is not a quality
metric.

| Test ID | Responsibility |
|---|---|
| `TEST-DAT-01` | Frozen DIAGLIST -> subset reproducibility. |
| `TEST-DAT-02` | MySQL rows, relations, accepted sets and case-use flags. |
| `TEST-ARC-01` | Verification-oracle isolation. |
| `TEST-MAP-01` | FEV1 mapping, including exact 35/50/70 boundaries. |
| `TEST-GATE-01` | Eligibility, outside-subset, undefined-relation and missing-fact handling. |
| `TEST-STATUS-01` | Hospital `!` status predicate branches. |
| `TEST-DEPTH-01` | Mandatory COPD coding depth. |
| `TEST-EVID-01` | FEV1/code contradiction. |
| `TEST-SPEC-01` | Source-backed specificity / `suboptimal`. |
| `TEST-CORRECT-01` | Acceptance only after prior rules clear. |
| `TEST-PREC-01` | Hard/graded/acceptance precedence, rule-order independence and terminal gap. |
| `TEST-API-01` | Single-code request and negative validation semantics. |
| `TEST-RC-01` | All reference responses against the independent RC oracle. |
| `TEST-DET-01` | Repeatability under unchanged inputs/baselines. |
| `TEST-E2E-01` | React -> PHP -> MySQL/rules -> feedback for all three classes using `CASE-001`. |
| `TEST-E2E-02` | `CASE-004` hidden from learner navigation but available to verification. |
| `TEST-CFG-01` | Exact evaluated baseline/software/environment identity. |

### 18.1 Required FEV1 boundary vectors

`TEST-MAP-01` includes at least:

| FEV1 | Expected suffix for `J44.0` |
|---:|---|
| 34.99 | `0` -> `J44.00` |
| 35.00 | `1` -> `J44.01` |
| 49.99 | `1` -> `J44.01` |
| 50.00 | `2` -> `J44.02` |
| 69.99 | `2` -> `J44.02` |
| 70.00 | `3` -> `J44.03` |

### 18.2 Reference verification

`TEST-RC-01` is one parameterised harness over the current 14 `RC-*` rows. It
sends only `case_id` and `submitted_code` through the actual evaluation
boundary, then compares the observed result with the external oracle. Required
comparisons include evaluation status, class, determining rule, criterion,
improvement code when defined and required structured explanation elements.

Free-text explanation wording need not be byte-identical to a stored sentence.
Tests compare required semantics so harmless copy editing does not redefine
correctness.

## 19. Candidate implementation material and evidence boundary

The handoff includes the following exploratory infrastructure/data material.
It is supplied so the work does not need to be reconstructed from a
conversation, but it must be treated as **candidate implementation**, not as
already adopted or verified project code:

```text
prototype_baseline_0_1/
  baseline_manifest.json
  mysql_schema.sql
  config/subset_definition_0_1.json
  data/subset_0_1.csv
  data/cases_0_1.csv
  data/case_code_domain_0_1.csv
  verification/reference_responses_0_1.csv
  scripts/prepare_subset.py
  scripts/runtime_data.py
  scripts/apply_mysql_schema.py
  scripts/bootstrap_mysql.py
  scripts/load_mysql.py
  tests/test_runtime_contract.py
  tests/test_mysql_persistence.py
  validate_baseline.py

prototype_stack/
  compose.yaml
  stack.sh
  .env.example
  .gitignore
  config/git-source.conf.example
```

No prior `PASS`, test-count verdict, database insertion result, Git scaffold
check or Compose check is inherited as project evidence. Exploratory execution
claims that appeared in an earlier version of this brief were produced while
candidate scaffolding was being constructed in a temporary analysis workspace
and were incorrectly promoted to project status. They are intentionally
withdrawn here.

The authoritative source files themselves may still be identified by their
SHA-256 values because those values are properties of the supplied source
bytes. The project should nevertheless recompute and record those identities
when adopting the source files. Derived-data hashes and test observations are
to be generated from the actual adopted workflow.

## 20. Immediate work order for the contractor

### Phase 0: establish the development environment

1. Inventory the actual application repository and distinguish what genuinely
   exists there from what is only supplied in the handoff.
2. Review `prototype_baseline_0_1/` and `prototype_stack/` against the working
   control documents before adopting them. Do not overwrite a coherent
   existing project implementation merely to match the candidate scaffold.
3. Adopt the relevant data-pipeline files deliberately, recompute the official
   source identity, reproduce/check the working subset and execute applicable
   structural tests. Record the observations from this project execution.
4. Configure the actual application Git repository and local secrets without
   storing authentication tokens in versioned configuration.
5. If the application repository does not yet contain the root `Dockerfile`
   required by the candidate Compose design, complete that Phase 1 dependency
   before expecting a full stack start.
6. Once the application image is buildable and Docker is available, execute
   the actual stack preflight/Compose path and record what happened. Resolve
   infrastructure discrepancies without silently altering domain baselines.

### Phase 1: application image and PHP repository layer

1. Provide the root application `Dockerfile` expected by Compose.
2. Establish a small PHP application structure with configuration read from the
   existing `ICD_DB_*` environment contract.
3. Implement repositories/queries for learner-visible cases, catalogue/code
   records and the closed case-code domain.
4. Keep SQL/persistence access distinct from rule evaluation.
5. Add a minimal health/readiness path if useful for container verification;
   do not turn observability into a major feature.

### Phase 2: deterministic evaluator

Implement `RULE-GATE-01`, `RULE-MAP-01`, `RULE-STATUS-01`, `RULE-DEPTH-01`,
`RULE-EVID-01`, `RULE-SPEC-01`, `RULE-CORRECT-01`, and `RULE-PREC-01` against
the existing model. Prefer small pure or near-pure rule functions plus an
orchestrating evaluator so predicates and precedence can be tested without
React.

Bind the direct rule tests as this layer is built. Do not postpone all tests
until after the UI exists.

### Phase 3: PHP API and reference integration

Expose the case/evaluation boundary, finalise response field names and HTTP
semantics, and bind `TEST-API-01`, `TEST-RC-01`, `TEST-DET-01`, and the
behavioural half of `TEST-ARC-01`.

The RC harness belongs in test tooling, not the PHP runtime. Demonstrate that
the evaluator still functions if the `verification/` directory is unavailable
to it.

### Phase 4: minimal React learner interface

Implement only what `REQ-INT-01` and the feedback requirements require. Then
bind `TEST-E2E-01` and `TEST-E2E-02`.

Do not spend the page/time budget on elaborate UI features before the full
rule/reference path passes.

### Phase 5: coverage review before freeze

Do not assume that the working `CASEBASE-0.1` / `RCBASE-0.1` count is final
merely because it is implemented. Review the complete rule and integration
coverage against `REQ-VER-02` and the client concern recorded in Section 23.2
below. If additional cases are justified, create new versioned case/reference
baselines and propagate the change through affected artefacts. Do not mutate
the meaning of `CASEBASE-0.1` silently.

### Phase 6: freeze and principal verification

Once the app, tests and cases are stable:

1. resolve or conservatively close all relevant open decisions;
2. promote or otherwise identify final frozen source/requirements/rule/model/
   case/oracle/test/prototype baselines;
3. pin the application to an exact Git commit;
4. record container/runtime versions and relevant image identities;
5. run `verify --frozen` plus the complete final verification suite;
6. retain per-test/per-RC observations and any deviations;
7. correct defects only through recorded change/impact/rerun procedure; and
8. never overwrite the historical observation that originally failed.

## 21. Change-control rules

The working `0.1` identifiers are meaningful. A material semantic change must
not occur invisibly under the same identifier.

Examples of changes that require impact analysis and normally a new baseline
version include:

- changing the active source edition/file;
- changing the 13-code subset or four-field whitelist;
- adding/removing case facts needed by a rule;
- changing a case response domain or acceptable set;
- changing a source-derived FEV1 interval or status/depth predicate;
- changing the meaning/precedence of a feedback class;
- adding an executable coding pattern;
- changing a predefined RC expectation;
- allowing multiple codes per evaluation request;
- making previously verification-only data available to runtime;
- changing a test expectation to match observed software output.

Implementation-only refactoring that demonstrably preserves behaviour does not
require a new medical/domain baseline, but the final software revision still
changes and affected tests must be rerun.

## 22. Verification and deviation reporting

The principal evaluation must distinguish at least:

- classification/rule mismatch;
- criterion/explanation mismatch;
- execution or infrastructure failure;
- unexecuted/blocked check;
- data-preparation or persistence deviation;
- specification/rule issue;
- reference-expectation issue; and
- accepted limitation where appropriate.

A mismatch is not automatically an implementation bug. First determine
whether code, rule specification, reference expectation, source transformation
or execution environment is wrong. The fix must occur at the layer where the
defect originated, followed by impact-based reruns.

## 23. Open items and known documentation drift

### 23.1 Genuine open decisions

1. **Final research-question wording.** Supervisor confirmation remains
   outstanding. This should not block the bounded implementation.
2. **Independent expert review.** The advisor has not explicitly stated whether
   internal technical verification alone is sufficient. Until this is resolved,
   make technical-conformance claims only. Do not treat supervisory discussion
   as independent domain validation.
3. **Final institutional placement of observed test results.** This affects
   thesis organisation, not software behaviour.

### 23.2 Reference-suite breadth requires a pre-freeze review

The current working baseline has four `CASE-*` records and 14 `RC-*` variants.
This is substantially more verification than the single `verification_only`
case flag may suggest: `CASE-001` through `CASE-003` are learner-visible but are
also verification inputs, and all 14 RC rows belong to the parameterised
reference test.

There is nevertheless a legitimate integration-level coverage question. Both
current COPD base cases resolve to suffix `2` (`J44.02` and `J44.12`).
`TEST-MAP-01` covers suffixes 0, 1, 2 and 3 at unit level, including the exact
35/50/70 boundaries, but the current RC suite does not exercise suffixes 0, 1
and 3 through complete case-to-evaluator integration.

Before the verification freeze, explicitly decide whether this division of
unit versus RC coverage is sufficient or whether to add verification-focused
base cases, for example FEV1 values below 35%, at 35%, and at 70%, plus an
inpatient `!` status fixture. Such an expansion is **not part of the current
`CASEBASE-0.1` contract**. If adopted, it must create a versioned new case/RC
baseline and update the coverage, model, manifest and tests consistently.

### 23.3 Older copies and state/provenance drift

Earlier copies of the control documents and handoff may describe the working
subset/model as "instantiated" or may still contain stale checklist wording.
In this corrected handoff, such language means at most that candidate records
or files were materialised from the specification. It does not mean that they
were adopted and tested in the actual application repository.

The bundled working copies reconcile the most consequential stale wording.
If another copy disagrees, do not infer project state from verbs such as
"implemented", "instantiated" or "passed". Establish repository and execution
state directly and preserve the substantive source/rule/case semantics unless
a versioned change is justified.

## 24. Contractor working rules

When making a technical decision:

1. inspect the current repository before choosing a framework or file layout;
2. prefer the smallest solution that satisfies an identified requirement;
3. do not broaden the medical/coding domain to make the demo look larger;
4. do not infer domain truth from a convenient database field or current
   implementation result;
5. keep source-derived constants explicit and traceable;
6. keep runtime data and verification expectations physically/logically
   separated;
7. preserve deterministic behaviour and stable machine-readable criteria;
8. treat version identifiers and Git commit identity as part of the evidence
   chain;
9. run the tests relevant to each material increment and state exactly what was
   and was not executed;
10. document deviations instead of silently repairing the specification after
    the fact; and
11. ask for an authoritative source or project decision if a requested coding
    judgement is not actually supported by the frozen material.

## 25. Definition of done for the implementation phase

The implementation is ready to enter the final freeze procedure when all of
the following are true:

- the real Docker Compose stack builds and starts on the target host;
- MySQL bootstrap/import remains reproducible and idempotent;
- the app reads versioned runtime data and not the DIAGLIST workbook at learner
  request time;
- PHP evaluation implements every active `RULE-*` and no undocumented coding
  branch;
- the rule evaluator is testable without the React UI;
- learner-visible case retrieval excludes `CASE-004`;
- the API enforces one submitted code and the non-classification gate;
- learner feedback supplies class, criterion and required explanation semantics;
- RC expectations remain outside runtime dependencies;
- each direct rule/control test is bound to actual implementation;
- API, reference, deterministic and end-to-end tests are executable;
- the reference-case breadth review has a recorded disposition;
- every mandatory implemented requirement has a test/inspection destination or
  an explicit justified gap;
- material deviations are documented and affected tests rerun;
- the final source/rule/model/case/test/software/environment identities are
  ready to be frozen; and
- no claim is made beyond the technical/model conformance actually examined.

## 26. Canonical project artefacts to preserve

When the full development workspace is available, these are the principal
control files and should be carried forward together:

```text
chapter3_input_source_baseline_register.md
chapter3_requirements_catalogue.md
chapter3_domain_error_taxonomy_and_classification_baseline.md
chapter3_rule_catalogue.md
chapter3_reference_case_coverage_plan.md
chapter3_data_model_and_interaction_baseline.md
chapter3_test_catalogue.md

prototype_baseline_0_1/
prototype_stack/

revision_work/chapter_3_methods_and_practical_work_specification_bachelor_scope(2).md
```

The uploaded/source working set also includes `DIAGLIST2026.xlsx`,
`ICD-10_Extramural.xlsx`, the thesis bibliography, thesis proposal/materials and
Chapter 2/3 LaTeX/specification files. The machine-readable workbook bytes are
important for source reproduction; other source PDFs can be reacquired only if
their exact frozen identity is verified against the source register.

If only this brief is transferred to another environment, it is sufficient for
orientation and task planning but not a substitute for the normative CSV/JSON,
rule catalogue, test catalogue or frozen official source files. Request those
artefacts before changing any behaviour whose correctness depends on their
exact contents.
