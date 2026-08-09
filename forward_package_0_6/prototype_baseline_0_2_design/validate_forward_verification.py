#!/usr/bin/env python3
"""Validate the forward authoring/verification contract without executing the application.

This validator checks only design-data structure, legacy-regression accounting, and
runtime/oracle separation. A successful result is not software verification.
"""

from __future__ import annotations

import csv
from collections import Counter, defaultdict
from pathlib import Path


ROOT = Path(__file__).resolve().parent
DATA = ROOT / "data"
VER = ROOT / "verification"
MIG = ROOT / "migration"


def rows(path: Path) -> list[dict[str, str]]:
    with path.open(newline="", encoding="utf-8") as handle:
        return list(csv.DictReader(handle))


learner_questions = rows(DATA / "questions_0_1.csv")
learner_domains = rows(DATA / "question_code_domain_0_1.csv")
learner_options = rows(DATA / "question_options_0_1.csv")
subset = rows(DATA / "subset_0_2.csv")

legacy_questions = rows(DATA / "verification_questions_legacy_0_1.csv")
legacy_facts = rows(DATA / "verification_question_facts_legacy_0_1.csv")
legacy_domains = rows(DATA / "verification_question_code_domain_legacy_0_1.csv")
legacy_links = rows(DATA / "verification_question_relation_facts_legacy_0_1.csv")

oracle = rows(VER / "reference_responses_0_3_candidate.csv")
reconstructed_additions = rows(MIG / "rcbase_0_2_additions_provisional.csv")

assert len(learner_questions) == 25
assert len(learner_domains) == 100
assert len(learner_options) == 120
assert len(subset) == 99
assert len(legacy_questions) == 8
assert len(legacy_facts) == 28
assert len(legacy_domains) == 18
assert len(legacy_links) == 40
assert len(reconstructed_additions) == 4

assert {q["intended_use"] for q in legacy_questions} == {"verification_only"}
assert all(not q["patient_id"] and not q["patient_baseline_id"] for q in legacy_questions)
assert {q["legacy_case_id"] for q in legacy_questions} == {f"CASE-{i:03d}" for i in range(1, 9)}

domain_keys = {(r["question_id"], r["code"]) for r in learner_domains + legacy_domains}
assert len(domain_keys) == len(learner_domains) + len(legacy_domains)
subset_codes = {r["Diagnose"] for r in subset}
assert {r["code"] for r in learner_domains + legacy_domains} <= subset_codes

assert len(oracle) == 143
assert len({r["rc_id"] for r in oracle}) == 143
assert Counter(r["expected_class"] for r in oracle) == Counter(
    {"correct": 33, "suboptimal": 20, "incorrect": 90}
)

learner_oracle = [r for r in oracle if not r["legacy_case_id"]]
legacy_oracle = [r for r in oracle if r["legacy_case_id"]]
assert len(learner_oracle) == 125
assert len(legacy_oracle) == 18
assert Counter(r["response_kind"] for r in learner_oracle) == Counter(
    {"code": 100, "none_of_above": 25}
)
assert {r["question_id"] for r in legacy_oracle} == {f"VQ-{i:03d}" for i in range(1, 9)}

none_rows = [r for r in learner_oracle if r["response_kind"] == "none_of_above"]
assert {r["question_id"] for r in none_rows if r["expected_class"] == "correct"} == {
    "Q-004-05",
    "Q-005-05",
}
assert all(r["expected_determining_rule"] == "RULE-NOA-01" for r in none_rows)
assert all(r["required_explanation_elements"] == "displayed_accepted_response_exists|reference_code" for r in none_rows)

provenance = Counter(r["provenance_status"] for r in legacy_oracle)
assert provenance == Counter(
    {
        "exact_semantic_carry_forward_from_rcbase_0_1": 14,
        "reconstructed_from_implementation_documentation": 4,
    }
)
assert {r["legacy_rc_id"] for r in legacy_oracle if r["provenance_status"] == "reconstructed_from_implementation_documentation"} == {
    "RC-005-01",
    "RC-006-01",
    "RC-007-01",
    "RC-008-01",
}

legacy_by_qcode = {(r["question_id"], r["submitted_code"]): r for r in legacy_oracle}
assert set(legacy_by_qcode) == {(r["question_id"], r["code"]) for r in legacy_domains}

displayed_codes: dict[str, set[str]] = defaultdict(set)
for option in learner_options:
    if option["option_kind"] == "code":
        displayed_codes[option["question_id"]].add(option["code"])
accepted: dict[str, set[str]] = defaultdict(set)
for relation in learner_domains:
    if relation["relation_kind"] == "accepted_reference":
        accepted[relation["question_id"]].add(relation["code"])
for result in none_rows:
    assert len(accepted[result["question_id"]]) == 1
    has_displayed_accepted = bool(displayed_codes[result["question_id"]] & accepted[result["question_id"]])
    assert (result["expected_class"] == "incorrect") == has_displayed_accepted

# Runtime authoring files must not contain verification-oracle answer fields.
for runtime_file in list(DATA.glob("*.csv")):
    with runtime_file.open(newline="", encoding="utf-8") as handle:
        header = next(csv.reader(handle))
    forbidden = {
        "expected_class",
        "expected_determining_rule",
        "expected_pattern_id",
        "expected_criterion",
        "required_explanation_elements",
        "reference_response_baseline_id",
    }
    overlap = forbidden & set(header)
    assert not overlap, f"oracle field(s) leaked into runtime file {runtime_file.name}: {sorted(overlap)}"

print("Forward verification-design contract: PASS")
print("  learner expectations: 125 (100 code + 25 none_of_above)")
print("  legacy expectations: 18 (14 exact semantic carry-forward + 4 documented reconstruction)")
print("  candidate oracle: 143 rows; classes 33 correct / 20 suboptimal / 90 incorrect")
print("  all 25 none_of_above expectations require displayed-accepted flag + reference code")
print("  runtime/oracle field separation: PASS")
print("  NOTE: this is design-data validation, not application verification")
