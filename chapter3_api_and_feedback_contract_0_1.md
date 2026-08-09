# Chapter 3 API and Feedback Contract

**Contract ID:** `APIBASE-0.1`  
**Status:** accepted forward interface clarification; implementation pending  
**Date:** 9 August 2026  
**Upstream:** `MODELBASE-0.2`, `RULEBASE-0.2`, requirements forward revision `0.7`, `UXBASE-0.1`  
**Purpose:** resolve pre-implementation ambiguities without changing `SUBSET-0.2`, `PATIENTBASE-0.1`, `QUESTIONBASE-0.1`, rule truth, or candidate `RCBASE-0.3`

## 1. Authority and supersession

This contract fixes the API/feedback decisions that were still ambiguous across the forward documents. For these interface questions it is the controlling interpretation of `MODELBASE-0.2` and `RULEBASE-0.2`.

In particular, the earlier `{"option_id": ...}` evaluation-request proposal in `chapter3_patient_and_question_design_plan.md` §10 is superseded. A stable `option_id` remains useful for React rendering and local selection state, but it is **not** the evaluation request body. The authoritative evaluation request is the tagged-response representation already defined by `MODELBASE-0.2` §7.2 and `REQ-INT-01`.

## 2. Learner and verification endpoint boundary

The conceptual runtime endpoints are:

| Endpoint | Learner-visible question | `verification_only` question | Unknown question |
|---|---|---|---|
| `GET /api/questions/{question_id}` | `200` | `404` | `404` |
| `POST /api/questions/{question_id}/evaluate` | permitted | permitted | `404` |

Patient discovery/detail endpoints enumerate learner-visible content only. A `verification_only` question is never made readable merely because its identifier is known. The asymmetry is deliberate: the regression harness needs to submit a predefined response to the common evaluator path, but it does not need a public read API for the hidden fixture's prompt or facts. Tests that need to inspect the fixture itself use versioned test/runtime data or repository access, not an undocumented unauthenticated GET route.

The old `/api/cases/{case_id}/evaluate` route may exist temporarily as a migration compatibility alias, but if retained it delegates to the same evaluator. It must not become a second implementation of rule truth.

## 3. Authoritative evaluation request

For a code response:

```json
{
  "response": {
    "type": "code",
    "code": "I48.9"
  }
}
```

For the dedicated non-code response:

```json
{
  "response": {
    "type": "none_of_above"
  }
}
```

The same tagged shape is used by learner and verification calls. This matters because the technical evaluator must be able to address a defined evaluation-domain code that is not a displayed learner option. The React option payload may therefore contain a stable `option_id` for presentation plus, for a code option, the code/designation required to construct the tagged request. `none_of_above` carries no catalogue code.

The evaluation endpoint does not accept `option_id` as an alternative request contract. Supporting both forms would create two request semantics without adding a requirement.

## 4. Transport validation versus evaluation eligibility

Syntactic/API validation and domain eligibility are intentionally distinct.

### 4.1 HTTP/controller boundary

The evaluator is not invoked when the request cannot be represented as a valid tagged response.

| Condition | HTTP result |
|---|---|
| malformed JSON, missing/non-object `response`, missing `type`, malformed code response, code supplied in an invalid shape | `400`, reason `malformed_input` |
| unrecognized response `type` | `400`, reason `unsupported_response_kind` |
| question identifier does not resolve on the evaluation route | `404`, reason `question_not_found` |

These are request/transport failures. They do not produce `evaluation_status = not_evaluated` and do not originate from `RULE-GATE-01`.

### 4.2 `RULE-GATE-01`

The gate receives only a syntactically valid tagged response and an existing question. Its negative semantic/configuration reasons are:

- `outside_active_subset`;
- `undefined_question_relation`;
- `missing_required_question_fact`; and
- `none_option_not_defined`.

A gate failure returns the normal evaluation response with HTTP `200`, `evaluation_status = not_evaluated`, and `classification = null`. It is not relabelled as `incorrect`.

This removes `malformed_input` and `unsupported_response_kind` from the gate's required reason vocabulary while preserving both at the HTTP boundary.

## 5. Pre-submission fact visibility

Structured `question_fact` rows are **internal evaluator data in the present baseline**. `GET /api/questions/{question_id}` does not return the raw fact collection. Information required for the learner's decision is already represented in the question prompt and/or learner-visible `patient_context_item` records.

The existing `question_fact.learner_label` field is a human-readable label that may be used to formulate post-submission feedback or technical trace output. It is **not a visibility flag**. No fact-key allowlist is used to infer visibility.

If a later interface requires structured fact chips before submission, the model must gain an explicit visibility field and receive a versioned data/model revision. Visibility must not be inferred from a label or rule family.

## 6. Context-item vocabulary

For the current model the controlled `patient_context_item.item_type` vocabulary is:

```text
documented_condition
self_reported_history
current_exam_finding
social_context
information_boundary
other
```

`information_boundary` is intentional and covers statements such as `PATIENT-006/CTX-006-01`, where the patient cannot provide an anamnesis. The loader and SQL schema should accept this value explicitly rather than relying on an undocumented free-text exception.

## 7. `none_of_above` feedback payload

For the present `QUESTIONBASE-0.1`, every learner question has exactly one `accepted_reference`. Consequently every classified `RULE-NOA-01` result must provide both:

- `displayed_accepted_response_exists`; and
- `reference_code`.

On the **incorrect** branch, `displayed_accepted_response_exists = true` and `reference_code` is the accepted code that was displayed. On the **correct** branch (`Q-004-05`, `Q-005-05`), the Boolean is false and `reference_code` is the accepted but deliberately non-displayed code. These are post-submission explanation elements, so they do not leak answer truth before the learner commits.

This makes the current 25 `RCBASE-0.3` NOA expectations literal rather than loosely interpreted. If a future question permits multiple accepted references, the feedback contract and oracle must be revised explicitly, for example to a collection, instead of choosing one reference arbitrarily.

## 8. Specificity improvement integrity

The SQL foreign key on `question_code_domain.improvement_code` proves only that the referenced code exists in `catalogue_code`; it cannot prove the cross-row semantic requirement that the code is an `accepted_reference` for the same question.

That invariant is therefore mandatory at the loader/application-validation boundary. The current `runtime_data_0_2.py` candidate already enforces it by collecting `accepted_reference` codes per question and rejecting a `less_specific_supported` relation whose `improvement_code` is absent from that same-question set. The implementation migration must preserve this check even if the physical DDL is refactored.

## 9. Criterion and determining-rule identity

`RULE-SPEC-01` and `RULE-REL-SPEC-01` intentionally emit the same criterion string, `supported_specificity_not_used`. A criterion describes the feedback reason; it is not a globally unique rule identifier.

`determining_rule` is therefore the authoritative field whenever downstream code or a test must identify which rule fired. No test, UI component, logging/analytics code, or later verification comparison may infer a rule identity by reverse-mapping `criterion`.

## 10. Legacy fixture reconciliation

`VQ-005` through `VQ-008` and their four historical expectations remain marked as provisional reconstructions from implementation documentation. The original `CASEBASE-0.2`/`RCBASE-0.2` CSVs are not present in the current handoff workspace or its available repository history, so the required byte/semantic comparison cannot be completed here.

This remains a mandatory pre-freeze reconciliation gate, not a blocker for generic evaluator/API implementation. No PHP rule branch may be hardcoded specifically to the provisional VQ rows. If the later diff changes their facts or expected values, the versioned fixture/oracle data are corrected and regression tests rerun before freeze.
