# Codex / VSCode continuation instruction

You are continuing implementation of a bachelor-thesis software artefact: a
small web-based educational prototype that evaluates selected Austrian ICD-10
BMASGPK 2026 coding responses and returns explicit rule-based feedback as
`correct`, `suboptimal`, or `incorrect`.

Read `ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` and `README_FIRST.md` before changing
the project. Then inspect the actual repository. The repository, not this
handoff package and not a previous conversation, determines what software has
really been implemented and tested.

## Critical state distinction

The project is methodologically advanced but the application implementation is
not. The handoff contains four different kinds of material and they must not be
conflated:

1. **Authoritative source copies**: identified Austrian source files used to
   substantiate catalogue facts and selected coding criteria.
2. **Working specifications**: the `REQ-*`, `PAT-*`, `RULE-*`, `CASE-*`,
   `RC-*`, `MODELBASE-*` and `TEST-*` control documents. These define intended
   behaviour and verification obligations but do not prove that software
   exists or passes them.
3. **Candidate implementation scaffolding**: Python, SQL, CSV, Docker Compose
   and shell material in `prototype_baseline_0_1/` and `prototype_stack/`.
   These files were prepared during exploratory work outside the actual
   application repository. Inspect, adopt and test them before treating them as
   project implementation.
4. **Verification evidence**: none is inherited from this handoff. No `PASS`,
   `5/5`, `inserted`, `no_op`, or similar historical claim is to be carried
   forward as evidence. Results become project evidence only when the relevant
   command/test is actually executed against the adopted repository and its
   environment and the observation is recorded.

`PROTOBASE-0.1`, `SUBSET-0.1`, `CASEBASE-0.1` and `RCBASE-0.1` are therefore
working candidate identifiers. They are not final verification-frozen
baselines merely because files bearing those identifiers are supplied.

## First task: establish repository truth

Before implementing anything:

1. Inventory the actual repository: language/framework files, Docker files,
   database/schema material, tests, frontend/backend code and existing
   documentation.
2. Compare that inventory with the handoff package. Do not overwrite an
   existing coherent implementation merely because candidate scaffold files
   are supplied here.
3. Classify each relevant item as `specified`, `candidate`, `adopted but not
   tested`, `development-tested`, or `frozen/verified`.
4. Report discrepancies before resolving any that would alter domain
   behaviour, rule semantics, the catalogue subset, case expectations or
   verification scope.
5. Establish where the handoff control documents will live in the repository
   so their stable identifiers remain traceable to code and tests.

## Immediate implementation sequence

Unless the repository inventory shows that a step already genuinely exists,
continue in this order.

### 1. Adopt and verify the candidate data pipeline

Review `prototype_baseline_0_1/` against `MODELBASE-0.1`, `SUBSET-0.1` and the
requirements catalogue before integrating it. In particular:

- verify the bundled `DIAGLIST2026.xlsx` source identity;
- inspect `config/subset_definition_0_1.json` and confirm that code selection
  is configuration-driven rather than hidden in Python;
- inspect the four-field projection and declared normalization;
- regenerate/check `subset_0_1.csv` from the authoritative workbook;
- inspect case and case-code relation files against the working case plan;
- verify that the independent `RCBASE-*` answer key is outside the runtime
  import path;
- inspect the proposed MySQL schema, loader and transaction semantics; and
- execute applicable structural/unit checks locally, recording the actual
  observed result rather than inheriting a result from the handoff.

Do not silently repair a source, expected outcome, or baseline merely to make a
candidate script pass.

### 2. Establish the reproducible stack

Review `prototype_stack/` as candidate infrastructure. Preserve its intended
security and reproducibility properties if it is adopted: secrets stay outside
Git and Docker layers, ordinary shutdown does not destroy persistent data, Git
updates are non-destructive, and the final evaluated application revision is
an exact commit.

The application repository must ultimately provide the root `Dockerfile`
expected by Compose. Until that application image exists, a full stack start
may legitimately be unavailable. Once the necessary application skeleton is
present, execute the actual Docker/Compose path and record what occurred.

### 3. Implement PHP persistence access and the evaluator

Keep SQL/data access separate from classification logic. Implement the rule
evaluator as a UI-independent service against `RULEBASE-0.1`. Preserve the
specified evaluation semantics:

```text
scope gate
    -> applicable FEV1 mapping
    -> hard rules
       STATUS > DEPTH > EVID as primary hard-rule priority
    -> specificity rule
    -> predefined acceptance
    -> specification gap / not evaluated where no defined judgement exists
```

There must be no generic `submitted_code != expected_code -> incorrect` rule
and no residual `else -> incorrect` branch.

### 4. Bind rule-level tests

As rule functions are implemented, bind the corresponding `TEST-MAP-*`,
`TEST-GATE-*`, `TEST-STATUS-*`, `TEST-DEPTH-*`, `TEST-EVID-*`, `TEST-SPEC-*`,
`TEST-CORRECT-*` and `TEST-PREC-*` specifications to executable tests. Include
the predefined FEV1 boundary values rather than testing only the represented
55% and 50% case values.

### 5. Implement the PHP API

Expose only the bounded learner workflow and evaluation contract required by
the model. One evaluation request contains one case-defined coding target and
one submitted code. Bind API, reference-response, determinism and architectural
separation tests after the endpoint exists.

The test harness may read `verification/reference_responses_0_1.csv`. The
running application may not.

### 6. Implement the minimal React learner interface

Implement only the required vertical path: present/select a learner-visible
synthetic case, select/search an in-scope response, submit one code, and show
the returned class and criterion-specific explanation. Keep
verification-only fixtures out of learner navigation.

### 7. Complete integration and end-to-end verification work

Exercise the complete React -> PHP -> data/rules -> feedback path, repeatability
and configuration identity. Treat failed observations as deviations to analyse,
not as reasons to edit the oracle after seeing application output.

### 8. Review reference-suite sufficiency before freezing

The current working design contains four base cases and 14 response variants.
That count is not a final sufficiency claim. Review coverage against
`REQ-VER-02`, especially whether unit-level FEV1 boundary coverage is enough or
whether additional integration/reference cases are warranted. If case scope is
changed, version every affected case/model/oracle artefact instead of mutating
`0.1` silently.

### 9. Freeze and execute the principal verification

Only after implementation and the coverage review are complete should the
project freeze the active source, subset, requirements, rules, cases, oracle,
tests, application commit and relevant execution environment. Execute the
principal verification against that identified state and retain observed
results and deviations separately from the test specification.

## Non-negotiable behavioural boundaries

- The prototype is an educational demonstrator using synthetic cases.
- It does not diagnose disease, infer the patient's true diagnosis, provide
  clinical decision support, calculate reimbursement, perform official
  reporting, or claim comprehensive Austrian ICD-10 coverage.
- `suboptimal` is positively defined by `RULE-SPEC-01`; it is not a residual
  middle category.
- Hard coding criteria produce `incorrect` only when an explicit rule is
  satisfied.
- `correct` requires an explicitly predefined acceptable response after
  higher-priority criteria clear.
- Malformed, outside-subset, undefined or insufficiently modelled relations do
  not acquire a fabricated three-class judgement.
- Rules may consume only case/context/catalogue facts actually represented by
  the working model.
- Extramural-specific executable rules, LKF reimbursement logic, multi-code
  aggregation, learner accounts/history, analytics and production hardening
  remain outside the current scope unless explicitly versioned into it.
- Preserve traceability among `SRC-*`, `REQ-*`, `PAT-*`, `RULE-*`, `CASE-*`,
  `RC-*`, `MODELBASE-*` and `TEST-*` identifiers.

## Development and reporting discipline

After every material increment, state:

- what repository files/components changed;
- which requirements/rules/tests were implemented or affected;
- which commands/tests were actually executed;
- the observed result, including failures and blocked checks;
- assumptions or discrepancies discovered;
- whether a versioned specification/baseline change is required; and
- the next dependency.

Never use successful implementation output as the source of its own expected
result. Never report a test as passed unless it was actually executed against
the state being described.
