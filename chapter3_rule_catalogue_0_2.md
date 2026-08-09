# Chapter 3 Rule Catalogue, Forward Revision

**Document status:** working design control artefact; not implemented or verification-frozen  
**Rule baseline:** `RULEBASE-0.2`  
**Date:** 8 August 2026  
**Interface clarification:** 9 August 2026, `APIBASE-0.1`  
**Historical baseline retained:** `RULEBASE-0.1` and its recorded `CASEBASE-0.2` / `RCBASE-0.2` executions  
**Principal new upstream input:** `QSAUDIT-0.1` (`chapter3_question_bank_source_audit.md`)  
**Companion model revision:** `MODELBASE-0.2` (`chapter3_data_model_and_interaction_baseline_0_2.md`)  

## 1. Purpose and version boundary

`RULEBASE-0.2` extends the first implemented rule model so that the redesigned six-patient, 25-question learner bank can be evaluated without writing one decision branch per question. It does not rewrite `RULEBASE-0.1` or invalidate the 18 historical `RCBASE-0.2` expectations. The old baseline remains evidence of the earlier development state; this document specifies the forward contract that a later implementation revision must satisfy and then reverify.

The revision preserves three layers of authority:

1. **Source-specific predicates**, such as the Austrian COPD FEV1 mapping and the hospital `!` restriction, remain direct representations of cited Austrian source rules.
2. **Question-scoped semantic relations** record a source-audited relationship between a frozen question and a candidate code, for example “less specific than the documented target” or “contradicts the documented subtype.” These relations are design data derived in `QSAUDIT-0.1`; they are not inferred from code spelling or catalogue labels at runtime.
3. **Artefact classification rules** map those predicates/relations to the project feedback classes `correct`, `suboptimal`, and `incorrect`.

The running classifier remains a technical model of the frozen question bank. It does not diagnose disease, infer causality from comorbidities, or determine coding truth for arbitrary Austrian ICD codes outside the explicitly modelled relations.

## 2. Atomic evaluation unit

The atomic unit remains one response to one coding target. The structural change is that the coding target is now a `coding_question`, optionally attached to a learner-visible synthetic patient, rather than a one-question `CASE-*` record.

```text
E = (
    coding_question,
    response,
    explicit question_facts,
    question_code_relation if response is a code,
    displayed_option_set,
    catalogue_record if response is a code,
    active baseline identities
)
```

The response is a tagged union:

```text
code_response(code)
none_of_above_response
```

There is still no multi-code response aggregation. A patient may contain any number of questions, but every evaluation request concerns exactly one question and one response.

The terminal result remains conceptually:

```text
evaluation_status: classified | not_evaluated
classification: correct | suboptimal | incorrect | null
determining_rule: RULE-* | null
criterion: stable machine-readable key | null
explanation_elements: structured payload
matched_rules: RULE-*[]
improvement_code: ICD code | null
baseline_versions: source / subset / rule / patient / question versions
```

## 3. Runtime semantic relation vocabulary

Every code that the question evaluator claims to judge has one explicit `question_code_domain` row. The row contains one `relation_kind`; it does **not** contain an expected output class.

| `relation_kind` | Meaning | Generic terminal handling |
|---|---|---|
| `accepted_reference` | the code is an explicitly source-supported acceptable response for this frozen question | `RULE-CORRECT-01` -> `correct` after higher-priority rules clear |
| `less_specific_supported` | the code is selectable/source-recognized but the frozen facts support a declared more specific accepted response | `RULE-REL-SPEC-01` -> `suboptimal` |
| `fact_conflict` | the code contradicts an explicit source-relevant question fact or expresses an unsupported subtype/etiology/causal relation | `RULE-REL-HARD-01` -> `incorrect` |
| `temporal_context_conflict` | the code expresses a temporal/status/context state incompatible with the frozen question facts | `RULE-REL-HARD-01` -> `incorrect` |
| `source_rule_resolved` | the relation is intentionally resolved by an existing source-specific rule such as `DEPTH`, `EVID`, `SPEC` or `STATUS` | no generic terminal class; a source-specific rule must resolve it |

`relation_kind` is deliberately semantic rather than lexical. No program may generate `less_specific_supported` merely because a code ends in `.9`, is shorter, contains words such as *nicht näher bezeichnet*, or looks like a parent of another code. `E11.9`, `F03`, and `N40` in `QSAUDIT-0.1` are explicit counterexamples to such heuristics.

### 3.1 Relation invariants

Before a question baseline is accepted, its relations must satisfy all of the following:

1. Every related code is a member of the active catalogue subset.
2. Every learner question has at least one `accepted_reference` in its evaluation domain. The present 25-question bank has exactly one per question, but the model does not make “one” a universal limit.
3. Every `less_specific_supported` relation names an `improvement_code` that is an `accepted_reference` for the same question and carries a source-audit rationale.
4. Every `fact_conflict` or `temporal_context_conflict` relation has a controlled semantic `reason_key` and at least one explicitly linked `question_fact` where the explanation depends on a represented fact. The runtime relation does not store the eventual output `criterion` expected by verification.
5. Every `source_rule_resolved` relation is proven by targeted validation to reach a terminal source-specific rule. Reaching the end of the evaluator without a terminal rule is a specification defect.
6. A code may not have two relation kinds for the same question/baseline.
7. Runtime option membership is distinct from response-domain membership. A valid domain relation may intentionally be hidden from the learner display.

These invariants are part of the data-contract validation, not learner feedback.

## 4. Rule inventory

Existing identifiers are retained where their semantic responsibility remains the same, so the historical regression expectations can still identify the same determining rules.

| Rule ID | Revision status in 0.2 | Effect |
|---|---|---|
| `RULE-GATE-01` | extended from case/code to question/tagged-response eligibility | eligible or `not_evaluated` |
| `RULE-MAP-01` | retained | derive COPD FEV1 suffix/target |
| `RULE-STATUS-01` | retained | source-specific `incorrect` |
| `RULE-DEPTH-01` | retained | source-specific `incorrect` |
| `RULE-EVID-01` | retained | source-specific `incorrect` |
| `RULE-SPEC-01` | retained | COPD-specific `suboptimal` |
| **`RULE-REL-HARD-01`** | new | explicit generic fact/temporal conflict -> `incorrect` |
| **`RULE-REL-SPEC-01`** | new | explicit source-audited lower specificity -> `suboptimal` |
| `RULE-CORRECT-01` | generalized input, same responsibility | `accepted_reference` -> `correct` |
| **`RULE-NOA-01`** | new | deterministic `none_of_above` result |
| `RULE-PREC-01` | extended | deterministic terminal/precedence policy |

The existing source locators and trigger definitions of `RULE-MAP-01`, `RULE-STATUS-01`, `RULE-DEPTH-01`, `RULE-EVID-01`, and `RULE-SPEC-01` remain those recorded in `RULEBASE-0.1`: principally `SRC-AT-DOC-2026`, printed pp. 10-12, 18, 26 and 34 as applicable. The migration changes their fact-access path, not their Austrian source meaning.

## 5. Detailed revised and new rule records

### 5.1 `RULE-GATE-01` - bounded question-response eligibility

| Field | `RULEBASE-0.2` specification |
|---|---|
| Kind | evaluation guard; not a three-class rule |
| Inputs | existing active question identity; syntactically validated response tag; active subset; code/domain relation for a code response; `question_option` membership for `none_of_above`; facts required by any selected source-specific resolver |
| Code pass condition | question belongs to the active baseline; code belongs to the active subset; the exact question-code relation is defined; any facts required by its source-specific resolver exist |
| `none_of_above` pass condition | question belongs to the active baseline; the frozen option set contains the dedicated `none_of_above` option |
| Fail effect | `evaluation_status = not_evaluated`, `classification = null`; return a scope/validation reason rather than `incorrect` |
| Required gate-negative reasons | `outside_active_subset`, `undefined_question_relation`, `missing_required_question_fact`, `none_option_not_defined` |
| Authority | artefact control; catalogue membership remains grounded in the frozen DIAGLIST |

The gate must not require a submitted code to be learner-visible. `M54.5` in `Q-004-05`, `I10` in `Q-005-05`, and non-displayed J44 technical relations are intentionally evaluable while absent from `question_option`.

`malformed_input` and `unsupported_response_kind` are retained at the HTTP/controller boundary rather than implemented as `GateResult` reasons. They return HTTP `400` and do not invoke the evaluator. An unresolved question ID on the evaluation route returns `404` / `question_not_found`. `RULE-GATE-01` therefore receives only an existing question and a syntactically valid tagged response; its failures are semantic/scope/configuration `not_evaluated` results. This boundary is fixed by `APIBASE-0.1`.

### 5.2 Existing source-specific rules

The following rules retain their `RULEBASE-0.1` predicates and classes:

- `RULE-MAP-01`: stable-phase FEV1 maps `<35 -> 0`, `>=35 and <50 -> 1`, `>=50 and <70 -> 2`, `>=70 -> 3` (`SRC-AT-DOC-2026`, printed p. 34).
- `RULE-STATUS-01`: the represented hospital `!` main-diagnosis restriction remains a hard `incorrect` predicate (`SRC-AT-DOC-2026`, printed pp. 10-11, 18).
- `RULE-DEPTH-01`: required Austrian five-character J44 depth remains a hard `incorrect` predicate (`SRC-AT-DOC-2026`, printed pp. 12, 26).
- `RULE-EVID-01`: a represented J44 severity suffix contradicting the mapped FEV1 band remains `incorrect` (`SRC-AT-DOC-2026`, printed p. 34).
- `RULE-SPEC-01`: the source-listed J44 unspecified severity form when a stable-phase FEV1 supports a more specific target remains `suboptimal` (`SRC-AT-DOC-2026`, printed pp. 26, 34).

Their input access changes from named columns on `case_definition` to typed `question_fact` keys such as `encounter_setting`, `diagnosis_role`, `inpatient_lkf_scored`, `copd_base_code`, and `fev1_stable_pct_predicted`. A rule may read only the keys it explicitly declares. Absence of an unrelated fact does not become a failure condition.

### 5.3 `RULE-REL-HARD-01` - explicit incompatible relation

| Field | Specification |
|---|---|
| Kind | generic hard classification rule |
| Trigger | eligible code response whose frozen relation kind is `fact_conflict` or `temporal_context_conflict` |
| Outcome | `incorrect` |
| Runtime semantic reason | a controlled `reason_key`, for example `wrong_subtype`, `wrong_etiology`, `unsupported_causal_inference`, or `wrong_temporal_state` |
| Output criterion | derived by the rule from `relation_kind`: `documented_fact_conflict` for `fact_conflict`, `temporal_context_conflict` for `temporal_context_conflict`; `reason_key` remains an explanation element |
| Required evidence | question-scoped relation from `QSAUDIT-0.1`; any fact named by the relation exists as an explicit `question_fact` |
| Explanation | identify the submitted code and the concrete represented fact/context it conflicts with; do not infer a diagnosis that the question does not state |
| Source boundary | the **concrete relation** carries source provenance in the audit/authoring baseline; this generic mapping rule is an artefact decision |
| Verification | at least one fact conflict, one temporal/context conflict, and a no-inference control; prove an undefined relation does not enter this rule |

This rule is not `submitted_code != reference_code`. Only relations deliberately audited and entered under one of the two hard relation kinds can trigger it.

### 5.4 `RULE-REL-SPEC-01` - explicit source-supported lower specificity

| Field | Specification |
|---|---|
| Kind | generic graded classification rule |
| Trigger | no hard rule matched and the eligible code relation is `less_specific_supported` |
| Outcome | `suboptimal` |
| Output criterion | `supported_specificity_not_used`, supplied by the rule rather than stored as an expected-output field in the relation row |
| Improvement | the relation's mandatory `improvement_code`, which must be an accepted reference for the same question |
| Source basis | `SRC-AT-DOC-2026`, printed p. 13 for the general preference for the specific four-character code where available, plus the exact question/family systematic-catalogue locator in `QSAUDIT-0.1` |
| Explanation | identify the submitted less-specific response, the source-supported detail already represented in the question, and the explicit improvement code |
| Verification | representative relations from materially different ICD families; countercontrols `E11.9`, `F03`, and `N40` must prove that lexical/code-shape heuristics do not trigger this rule |

The relation is source-audited **before** runtime. The evaluator does not try to discover semantic parent/child equivalence from labels.

### 5.5 `RULE-CORRECT-01` - declared accepted reference

The responsibility of `RULE-CORRECT-01` is unchanged: after every higher-priority hard/graded rule clears, an explicitly accepted response is `correct`. In `MODELBASE-0.2`, membership is represented by `question_code_domain.relation_kind = accepted_reference` rather than the former `case_code_domain.is_acceptable` Boolean.

| Field | Specification |
|---|---|
| Trigger | gate passes; no hard or graded rule matches; relation kind is `accepted_reference` |
| Outcome | `correct` |
| Criterion | `accepted_response` |
| Source boundary | each concrete accepted relation must be source-audited independently; the terminal acceptance mechanism itself is project control logic |
| Verification | every learner question has at least one accepted-domain control; historical accepted responses retain `RULE-CORRECT-01` as determining rule |

### 5.6 `RULE-NOA-01` - deterministic `none_of_above`

`none_of_above` is an interface response type, not a catalogue record and never appears in `catalogue_code`.

Let `D(q)` be the set of code options actually stored as displayed `question_option` rows for question `q`, and `A(q)` the set of codes whose domain relation is `accepted_reference`.

```text
if D(q) intersection A(q) is empty:
    none_of_above -> correct
else:
    none_of_above -> incorrect
```

| Field | Specification |
|---|---|
| Applicability | eligible `none_of_above` response and a frozen `none_of_above` option exists for the question |
| Correct criterion | `no_displayed_accepted_response` |
| Incorrect criterion | `displayed_accepted_response_exists` |
| Authority | artefact interaction rule fixed before question execution; no Austrian coding claim is made by the set operation itself |
| Required explanation | state whether the displayed set contains an accepted response and provide the question's unique `reference_code` after submission; on the incorrect branch it is the displayed accepted code, while on the correct branch it is the accepted but non-displayed code |
| Verification | all 25 learner questions; explicit positive controls `Q-004-05` and `Q-005-05`; at least one ordinary question where a displayed accepted code makes `none_of_above` incorrect |

The implementation must never choose random *membership* of the option set at play time. Randomization may permute order only. Otherwise the truth of `none_of_above` could change across playthroughs.

`QUESTIONBASE-0.1` currently has exactly one `accepted_reference` relation per learner question. Therefore both `RULE-NOA-01` branches return `displayed_accepted_response_exists` and `reference_code` as explanation elements, matching all 25 candidate oracle rows. A future question with multiple accepted references requires an explicit feedback/oracle contract revision rather than an arbitrary choice of one code.

### 5.7 `RULE-PREC-01` - extended terminal policy

For an eligible **code** response:

1. evaluate all applicable source-specific hard predicates;
2. evaluate `RULE-REL-HARD-01` when the relation kind permits it;
3. if any hard rule matches, return `incorrect`;
4. otherwise evaluate source-specific `RULE-SPEC-01` and generic `RULE-REL-SPEC-01`;
5. if a graded rule matches, return `suboptimal`;
6. otherwise apply `RULE-CORRECT-01` to `accepted_reference`; and
7. otherwise raise a specification gap.

Primary hard-rule priority remains `STATUS > DEPTH > EVID`; `RULE-REL-HARD-01` follows those source-specific rules if an invalid authoring configuration ever permits a multi-match. Graded priority is `RULE-SPEC-01 > RULE-REL-SPEC-01`. Data validation should normally prevent those generic/source-specific overlaps rather than relying on priority to conceal them.

For an eligible **`none_of_above`** response, `RULE-NOA-01` is terminal after the gate; catalogue-code rules do not run because there is no submitted catalogue code.

There remains no default `else -> incorrect` branch.

The output `criterion` is a semantic feedback reason, not a unique rule identifier. Source-specific `RULE-SPEC-01` and generic `RULE-REL-SPEC-01` intentionally share `supported_specificity_not_used`. Whenever rule provenance matters, downstream code and verification must use `determining_rule` and must not reverse-map rule identity from the criterion string.

## 6. Normative evaluation pseudocode

```text
evaluate(question, response, baseline):
    gate = RULE-GATE-01(question, response, baseline)
    if gate fails:
        return not_evaluated(gate.reason)

    if response.kind == none_of_above:
        return RULE-NOA-01(question.displayed_options,
                           question.accepted_relations)

    relation = question.domain_relation(response.code)
    derived = RULE-MAP-01(question.facts) if applicable

    hard_matches = []
    if RULE-STATUS-01(question.facts, response.code): hard_matches += STATUS
    if RULE-DEPTH-01(question.facts, response.code):  hard_matches += DEPTH
    if RULE-EVID-01(question.facts, response.code, derived): hard_matches += EVID
    if RULE-REL-HARD-01(relation): hard_matches += REL_HARD

    if hard_matches is not empty:
        primary = first by STATUS > DEPTH > EVID > REL_HARD
        return incorrect(primary, hard_matches)

    if RULE-SPEC-01(question.facts, response.code, derived):
        return suboptimal(RULE-SPEC-01)

    if RULE-REL-SPEC-01(relation):
        return suboptimal(RULE-REL-SPEC-01,
                          improvement_code = relation.improvement_code)

    if RULE-CORRECT-01(relation):
        return correct(RULE-CORRECT-01)

    return specification_gap
```

The last line is a development/conformance failure, not learner feedback.

## 7. Worked traces across the redesigned bank

| Question/response | Runtime relation/predicate | Determining rule | Result |
|---|---|---|---|
| `Q-001-01 / J44.02` | `accepted_reference`; no J44 hard/graded predicate | `RULE-CORRECT-01` | `correct` |
| `Q-001-01 / J44.01` | `source_rule_resolved`; 55% conflicts with suffix 1 | `RULE-EVID-01` | `incorrect` |
| `Q-001-01 / J44.09` | `source_rule_resolved`; FEV1 known | `RULE-SPEC-01` | `suboptimal` |
| `Q-002-01 / I48.9` | `less_specific_supported`, improvement `I48.0` | `RULE-REL-SPEC-01` | `suboptimal` |
| `Q-002-01 / I48.1` | `fact_conflict`, documented form is paroxysmal | `RULE-REL-HARD-01` | `incorrect` |
| `Q-004-01 / E11.3` | `fact_conflict`, no diabetic eye complication is documented | `RULE-REL-HARD-01` | `incorrect` |
| `Q-004-05 / none_of_above` | displayed set excludes accepted `M54.5` | `RULE-NOA-01` | `correct` |
| `Q-005-05 / none_of_above` | displayed set excludes accepted `I10` | `RULE-NOA-01` | `correct` |
| `Q-006-02 / I63.9` | `temporal_context_conflict`, task asks for documented sequela | `RULE-REL-HARD-01` | `incorrect` |
| `Q-006-04 / F03` | `accepted_reference`; no dementia aetiology documented | `RULE-CORRECT-01` | `correct` |
| `Q-006-06 / G40.9` | `fact_conflict`, epilepsy history is not established as the current cause | `RULE-REL-HARD-01` | `incorrect` |

The `F03` trace is intentional. A generic “unspecified code is suboptimal” heuristic would fail this case and is prohibited by this baseline.

## 8. Runtime rule data versus independent oracle

The new relation vocabulary does not eliminate the verification boundary.

Runtime data may contain:

- the question's explicit facts;
- candidate-code `relation_kind` and semantic `reason_key`;
- relation-to-fact links;
- an `improvement_code` for `less_specific_supported`; and
- the fixed displayed option membership.

Runtime data must **not** contain:

- `expected_class`;
- `expected_determining_rule`;
- expected `matched_rules`;
- expected explanation-element assertions;
- `RC-*` verdicts; or
- observed implementation output copied back into the specification.

The semantic relation is an input to the classifier because the prototype is a curated rule-based teaching system, not a natural-language clinical inference engine. The independent `RCBASE-0.3` oracle will separately assert what output the specified evaluator ought to produce for each response. This means final verification tests implementation conformance to the source-audited model; it does not constitute independent proof of real-world clinical validity.

## 9. Legacy regression compatibility

`CASEBASE-0.2` and `RCBASE-0.2` remain immutable historical artefacts. When represented in `MODELBASE-0.2`, their facts may be transformed into verification-only `coding_question` records with a `legacy_case_id`, but their semantic expectations are not rewritten.

To preserve regression value:

- legacy accepted codes become `accepted_reference` and still terminate through `RULE-CORRECT-01`;
- legacy J44 depth/evidence/specificity variants use `source_rule_resolved`, so `RULE-DEPTH-01`, `RULE-EVID-01`, and `RULE-SPEC-01` remain the determining rules;
- legacy prohibited `!` variants use `source_rule_resolved`, so `RULE-STATUS-01` remains determining; and
- the 18 historical expected rows stay outside runtime data and are rerun after migration.

The generic relation rules are not allowed to replace those source-specific legacy branches merely to make migration easier.

## 10. Verification obligations introduced by 0.2

Before `RULEBASE-0.2` can become part of a final frozen baseline, targeted tests must demonstrate at least:

1. all existing `RULEBASE-0.1` rule and 18 historical reference expectations still conform after model migration;
2. `RULE-REL-HARD-01` for fact and temporal/context conflicts;
3. `RULE-REL-SPEC-01` across several unrelated ICD families;
4. negative controls proving `E11.9`, `F03`, and `N40` are not classified by code-shape heuristics;
5. `RULE-NOA-01` with both true and false outcomes and with option-order permutations;
6. a code in the evaluation domain but absent from the displayed options remains technically evaluable;
7. an active-subset code outside a question's domain is `not_evaluated`, not `incorrect`;
8. a `source_rule_resolved` relation reaching no source-specific terminal rule fails as a specification gap; and
9. repeated evaluation against unchanged facts/relations remains deterministic regardless of displayed option order.

## 11. Requirement impact carried forward

This revision operationalizes the requirement additions already identified in `PATIENTPLAN-0.4` / `QUESTIONPLAN-0.4`, especially `REQ-MOD-03` to `REQ-MOD-05`, `REQ-RUL-06`, `REQ-INT-04`, `REQ-VER-08`, and `REQ-VER-09`. Before implementation freeze, those proposed entries must be incorporated into a versioned requirements catalogue. In addition, that catalogue should make explicit that generic response relations are source-audited, question-scoped inputs rather than inferred from code morphology or display text.

No change in this document authorizes clinical diagnosis inference, arbitrary catalogue-wide classification, multi-code answer aggregation, extramural-specific executable rules, reimbursement logic, user profiling, or learning-effect claims.
