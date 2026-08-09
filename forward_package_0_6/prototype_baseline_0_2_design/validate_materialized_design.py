from __future__ import annotations

import csv
import hashlib
import json
from collections import Counter, defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parent
DATA = ROOT / "data"
CONFIG = ROOT / "config"
EXPECTED_DIAG_SHA = "66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b"


def rows(name: str) -> list[dict[str, str]]:
    with (DATA / name).open(encoding="utf-8", newline="") as handle:
        return list(csv.DictReader(handle))


subset = rows("subset_0_2.csv")
patients = rows("patients_0_1.csv")
context = rows("patient_context_items_0_1.csv")
questions = rows("questions_0_1.csv")
facts = rows("question_facts_0_1.csv")
domain = rows("question_code_domain_0_1.csv")
relation_facts = rows("question_relation_facts_0_1.csv")
options = rows("question_options_0_1.csv")
definition = json.loads((CONFIG / "subset_definition_0_2.json").read_text(encoding="utf-8"))

forbidden_runtime_fields = {"expected_class", "expected_rule", "expected_determining_rule", "expected_criterion", "observed_class", "verdict"}
for name in ["patients_0_1.csv", "patient_context_items_0_1.csv", "questions_0_1.csv", "question_facts_0_1.csv", "question_code_domain_0_1.csv", "question_relation_facts_0_1.csv", "question_options_0_1.csv"]:
    with (DATA / name).open(encoding="utf-8", newline="") as handle:
        header = set(next(csv.reader(handle)))
    assert not (header & forbidden_runtime_fields), (name, header & forbidden_runtime_fields)

assert definition["source"]["sha256"] == EXPECTED_DIAG_SHA
assert len(subset) == 99 == len({r["Diagnose"] for r in subset})
assert set(definition["selected_codes"]) == {r["Diagnose"] for r in subset}
assert "Z01.8" not in {r["Diagnose"] for r in subset}

assert len(patients) == 6 == len({r["patient_id"] for r in patients})
expected_patient_fields = {
    "patient_baseline_id",
    "patient_id",
    "display_name",
    "age_years",
    "sex",
    "self_described_background",
    "history_availability",
    "difficulty_role",
    "general_health_summary",
    "synthetic",
}
assert set(patients[0]) == expected_patient_fields
assert all(r["patient_baseline_id"] == "PATIENTBASE-0.1" for r in patients)
assert all(r["history_availability"] in {"established", "partial", "unavailable_from_patient"} for r in patients)
assert all(r["difficulty_role"] in {"foundational", "involved"} for r in patients)
assert all(r["synthetic"].lower() == "true" for r in patients)
assert all(r["display_name"].strip() and r["general_health_summary"].strip() for r in patients)
assert all(r["age_years"].isdigit() and 0 < int(r["age_years"]) < 120 for r in patients)
patient_ids = {r["patient_id"] for r in patients}
assert all(r["patient_id"] in patient_ids for r in context)
assert len(context) == 32
context_keys = {(r["patient_id"], r["context_item_id"]) for r in context}
assert len(context_keys) == len(context)
context_item_types = {r["item_type"] for r in context}
assert context_item_types <= {
    "documented_condition", "self_reported_history", "current_exam_finding",
    "social_context", "information_boundary", "other",
}
assert any(
    r["patient_id"] == "PATIENT-006"
    and r["context_item_id"] == "CTX-006-01"
    and r["item_type"] == "information_boundary"
    for r in context
)

assert len(questions) == 25 == len({r["question_id"] for r in questions})
question_ids = {r["question_id"] for r in questions}
assert all(r["patient_id"] in patient_ids and r["intended_use"] == "learner_visible" for r in questions)
qcounts = Counter(r["patient_id"] for r in questions)
assert [qcounts[f"PATIENT-{n:03d}"] for n in range(1, 7)] == [3, 3, 3, 5, 5, 6]
for patient_id in patient_ids:
    positions = sorted(int(r["canonical_position"]) for r in questions if r["patient_id"] == patient_id)
    assert positions == list(range(1, len(positions) + 1))
question_patient = {r["question_id"]: r["patient_id"] for r in questions}

fact_keys = {(r["question_id"], r["fact_key"]) for r in facts}
assert len(facts) == 60 == len(fact_keys)
value_columns = ["value_text", "value_integer", "value_decimal", "value_boolean", "value_code", "value_enum"]
type_col = {"text":"value_text", "integer":"value_integer", "decimal":"value_decimal", "boolean":"value_boolean", "code":"value_code", "enum":"value_enum"}
for r in facts:
    assert r["question_id"] in question_ids
    assert r["value_type"] in type_col
    present = [c for c in value_columns if r[c] != ""]
    assert present == [type_col[r["value_type"]]], (r["question_id"], r["fact_key"], present)
    if r["source_context_item_id"]:
        assert (question_patient[r["question_id"]], r["source_context_item_id"]) in context_keys

subset_codes = {r["Diagnose"] for r in subset}
domain_keys = {(r["question_id"], r["code"]) for r in domain}
assert len(domain) == 100 == len(domain_keys)
assert all(r["question_id"] in question_ids and r["code"] in subset_codes for r in domain)
learner_domain_codes = {r["code"] for r in domain}
assert len(learner_domain_codes) == definition["selection_groups"]["learner_domain_unique_code_count"] == 92
legacy_codes = set(definition["selection_groups"]["historical_subset_codes"])
assert len(legacy_codes) == definition["selection_groups"]["historical_subset_code_count"] == 13
assert learner_domain_codes | legacy_codes == subset_codes
valid_relations = {"accepted_reference", "less_specific_supported", "fact_conflict", "temporal_context_conflict", "source_rule_resolved"}
assert {r["relation_kind"] for r in domain} <= valid_relations

by_question = defaultdict(list)
for r in domain:
    by_question[r["question_id"]].append(r)
for qid in question_ids:
    accepted = [r for r in by_question[qid] if r["relation_kind"] == "accepted_reference"]
    assert len(accepted) == 1, (qid, accepted)
    accepted_codes = {r["code"] for r in accepted}
    for r in by_question[qid]:
        if r["relation_kind"] == "less_specific_supported":
            assert r["improvement_code"] in accepted_codes
        if r["relation_kind"] in {"fact_conflict", "temporal_context_conflict"}:
            assert r["reason_key"]

rel_link_keys = {(r["question_id"], r["code"], r["fact_key"]) for r in relation_facts}
assert len(relation_facts) == 142 == len(rel_link_keys)
for r in relation_facts:
    assert (r["question_id"], r["code"]) in domain_keys
    assert (r["question_id"], r["fact_key"]) in fact_keys
    relation = next(d for d in by_question[r["question_id"]] if d["code"] == r["code"])
    expected_role = {
        "accepted_reference": "supports_reference",
        "less_specific_supported": "supports_specificity",
        "fact_conflict": "conflicts_with_response",
        "temporal_context_conflict": "supports_temporal_context",
        "source_rule_resolved": "supports_source_rule",
    }[relation["relation_kind"]]
    assert r["relation_role"] == expected_role
for r in domain:
    if r["relation_kind"] in {"fact_conflict", "temporal_context_conflict", "less_specific_supported", "source_rule_resolved"}:
        assert any(k[0] == r["question_id"] and k[1] == r["code"] for k in rel_link_keys)

assert len(options) == 120 == len({(r["question_id"], r["option_id"]) for r in options})
assert sum(r["option_kind"] == "code" for r in options) == 95
assert sum(r["option_kind"] == "none_of_above" for r in options) == 25
options_by_q = defaultdict(list)
for r in options:
    assert r["question_id"] in question_ids
    options_by_q[r["question_id"]].append(r)
    if r["option_kind"] == "code":
        assert (r["question_id"], r["code"]) in domain_keys
    else:
        assert r["option_kind"] == "none_of_above" and not r["code"] and not r["subset_baseline_id"]

noa_correct = []
for qid in sorted(question_ids):
    qopts = options_by_q[qid]
    assert sum(r["option_kind"] == "none_of_above" for r in qopts) == 1
    positions = sorted(int(r["canonical_position"]) for r in qopts)
    assert positions == list(range(1, len(positions) + 1))
    code_opts = [r["code"] for r in qopts if r["option_kind"] == "code"]
    assert len(code_opts) == len(set(code_opts))
    displayed_codes = {r["code"] for r in qopts if r["option_kind"] == "code"}
    accepted = {r["code"] for r in by_question[qid] if r["relation_kind"] == "accepted_reference"}
    if displayed_codes.isdisjoint(accepted):
        noa_correct.append(qid)
assert noa_correct == ["Q-004-05", "Q-005-05"]

# Immediate feedback must not directly disclose a code that becomes a selectable
# answer to another question for the same patient. This is a presentation-level
# leakage safeguard introduced with UXBASE-0.1; it does not change evaluator truth.
cross_question_answer_leaks = []
for source_qid in sorted(question_ids):
    patient_id = question_patient[source_qid]
    accepted_codes = {
        r["code"]
        for r in by_question[source_qid]
        if r["relation_kind"] == "accepted_reference"
    }
    for target_qid in sorted(question_ids):
        if target_qid == source_qid or question_patient[target_qid] != patient_id:
            continue
        displayed_codes = {
            r["code"]
            for r in options_by_q[target_qid]
            if r["option_kind"] == "code"
        }
        overlap = accepted_codes & displayed_codes
        if overlap:
            cross_question_answer_leaks.append((patient_id, source_qid, target_qid, sorted(overlap)))
assert not cross_question_answer_leaks, cross_question_answer_leaks

j44_questions = {r["question_id"] for r in domain if r["code"].startswith("J44")}
assert j44_questions == {"Q-001-01"}
q_to_patient = {r["question_id"]: r["patient_id"] for r in questions}
assert {q_to_patient[q] for q in j44_questions} == {"PATIENT-001"}
assert {r["code"] for r in by_question["Q-001-01"]} == {"J44.0", "J44.00", "J44.01", "J44.02", "J44.03", "J44.09"}

relation_counts = Counter(r["relation_kind"] for r in domain)
displayed_rel_counts = Counter()
for r in options:
    if r["option_kind"] == "code":
        rel = next(d for d in by_question[r["question_id"]] if d["code"] == r["code"])
        displayed_rel_counts[rel["relation_kind"]] += 1

print("Materialized forward-design validation: PASS")
print(f"  subset/patients/questions: {len(subset)}/{len(patients)}/{len(questions)}")
print(f"  facts/domain/options: {len(facts)}/{len(domain)}/{len(options)}")
print(f"  learner-domain / retained-historical unique codes: {len(learner_domain_codes)}/{len(legacy_codes)} -> union {len(subset_codes)}")
print(f"  question counts: {[qcounts[f'PATIENT-{n:03d}'] for n in range(1,7)]}")
print(f"  context item types: {sorted(context_item_types)}")
print(f"  none_of_above correct controls: {noa_correct}")
print("  immediate-feedback cross-question code leakage: none")
print(f"  all J44 learner relations belong to: {sorted(j44_questions)}")
print(f"  domain relation counts: {dict(sorted(relation_counts.items()))}")
print(f"  displayed relation counts: {dict(sorted(displayed_rel_counts.items()))}")
print("  NOTE: PASS means data-contract validation only; it is not application or thesis evaluation evidence.")
