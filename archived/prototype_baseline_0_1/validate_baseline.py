#!/usr/bin/env python3
"""Structural/source-integrity checks for PROTOBASE-0.2.

This script is deliberately read-only. It checks the derived working artefacts
against the frozen DIAGLIST source and against the declared case/oracle model.
It does not execute or reproduce the prototype's classification rules.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import re
from collections import Counter, defaultdict
from pathlib import Path

import pandas as pd


EXPECTED_SHA256 = "66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b"
EXPECTED_SUBSET_CODES = {
    "J44.0", "J44.00", "J44.01", "J44.02", "J44.03", "J44.09",
    "J44.1", "J44.10", "J44.11", "J44.12", "J44.13", "J44.19",
    "Z01.6",
}
EXPECTED_ACCEPTABLE = {
    "CASE-001": {"J44.02"},
    "CASE-002": {"J44.12"},
    "CASE-003": {"Z01.6"},
    "CASE-004": set(),
    "CASE-005": {"J44.00"},
    "CASE-006": {"J44.11"},
    "CASE-007": {"J44.03"},
    "CASE-008": set(),
}
EXPECTED_DOMAIN_SIZES = {
    "CASE-001": 6, "CASE-002": 6, "CASE-003": 1, "CASE-004": 1,
    "CASE-005": 1, "CASE-006": 1, "CASE-007": 1, "CASE-008": 1,
}
EXPECTED_CLASSES = Counter({"incorrect": 10, "correct": 6, "suboptimal": 2})
EXPECTED_RULES = {
    "RULE-DEPTH-01", "RULE-EVID-01", "RULE-SPEC-01",
    "RULE-CORRECT-01", "RULE-STATUS-01",
}
EXPECTED_PATTERNS = {"PAT-DEPTH-01", "PAT-EVID-01", "PAT-SPEC-01", "PAT-STATUS-01"}


def read_csv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as handle:
        return list(csv.DictReader(handle))


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("diaglist", type=Path, help="Path to frozen DIAGLIST2026.xlsx")
    args = parser.parse_args()

    base = Path(__file__).resolve().parent
    project = base.parent
    manifest = json.loads((base / "baseline_manifest.json").read_text(encoding="utf-8"))
    subset_definition = json.loads((base / "config" / "subset_definition_0_1.json").read_text(encoding="utf-8"))
    subset = read_csv(base / "data" / "subset_0_1.csv")
    cases = read_csv(base / "data" / "cases_0_2.csv")
    domain = read_csv(base / "data" / "case_code_domain_0_2.csv")
    oracle = read_csv(base / "verification" / "reference_responses_0_2.csv")

    require(sha256(args.diaglist) == EXPECTED_SHA256, "DIAGLIST SHA-256 mismatch")
    require(manifest["diaglist_sha256"] == EXPECTED_SHA256, "manifest checksum mismatch")
    require(subset_definition["source"]["sha256"] == EXPECTED_SHA256, "subset definition checksum mismatch")
    require(set(subset_definition["selected_codes"]) == EXPECTED_SUBSET_CODES, "subset definition code identity mismatch")
    require(subset_definition["output_fields"] == ["Diagnose", "Kennzeichen", "Bezeichnung", "Kurzbezeichnung"], "subset definition field whitelist mismatch")
    require(subset_definition["source"]["expected_unique_diagnose_count"] == 13298, "subset definition source-count mismatch")
    require(manifest["interaction_cardinality"] == "one_submitted_code_per_evaluation_request", "interaction cardinality mismatch")
    require(manifest["verification_oracle_runtime_access"] is False, "oracle must be excluded from runtime access")

    source = pd.read_excel(args.diaglist, sheet_name="DIAGLIST2026", dtype=str, keep_default_na=False)
    require(source["Diagnose"].nunique() == 13298, "unexpected unique DIAGLIST code count")

    source_by_code = source.set_index("Diagnose", drop=False)
    subset_codes = {row["Diagnose"] for row in subset}
    require(len(subset) == 13 and len(subset_codes) == 13, "SUBSET-0.1 must contain 13 unique records")
    require(subset_codes == EXPECTED_SUBSET_CODES, "SUBSET-0.1 code identity mismatch")
    require("Z01.8" not in subset_codes, "Z01.8 must remain outside SUBSET-0.1")
    require("Z01.8" in source_by_code.index and source_by_code.at["Z01.8", "Kennzeichen"].strip() == "!", "Z01.8 gate control missing from source")

    for row in subset:
        code = row["Diagnose"]
        require(code in source_by_code.index, f"subset code absent from source: {code}")
        src = source_by_code.loc[code]
        require(row["Kennzeichen"] == src["Kennzeichen"].strip(), f"marker mismatch: {code}")
        require(row["Bezeichnung"] == src["Bezeichnung"], f"designation mismatch: {code}")
        require(row["Kurzbezeichnung"] == src["Kurzbezeichnung"], f"short designation mismatch: {code}")

    case_ids = {row["case_id"] for row in cases}
    require(len(cases) == 8 and case_ids == set(EXPECTED_DOMAIN_SIZES), "CASEBASE-0.2 identity/count mismatch")
    verification_only = {row["case_id"] for row in cases if row["intended_use"] == "verification_only"}
    require(verification_only == {"CASE-004", "CASE-008"}, "CASE-004 and CASE-008 must be the only verification-only cases")

    relations = {(row["case_id"], row["code"]) for row in domain}
    require(len(domain) == 18 and len(relations) == 18, "case-code domain must contain 18 unique relations")
    by_case: dict[str, list[dict[str, str]]] = defaultdict(list)
    for row in domain:
        require(row["case_id"] in case_ids, f"orphan case-code relation: {row['case_id']}")
        require(row["code"] in subset_codes, f"domain code outside SUBSET-0.1: {row['code']}")
        require(row["is_acceptable"] in {"0", "1"}, "is_acceptable must be 0/1")
        by_case[row["case_id"]].append(row)
    require({case: len(rows) for case, rows in by_case.items()} == EXPECTED_DOMAIN_SIZES, "case response-domain sizes differ from plan")
    for case, expected in EXPECTED_ACCEPTABLE.items():
        observed = {row["code"] for row in by_case[case] if row["is_acceptable"] == "1"}
        require(observed == expected, f"acceptable set mismatch for {case}")

    rc_ids = {row["rc_id"] for row in oracle}
    require(len(oracle) == 18 and len(rc_ids) == 18, "RCBASE-0.2 must contain 18 unique RC rows")
    require(Counter(row["expected_class"] for row in oracle) == EXPECTED_CLASSES, "RC class distribution mismatch")
    require(all(row["expected_evaluation_status"] == "classified" for row in oracle), "all current RC rows must be classified")
    require(all((row["case_id"], row["submitted_code"]) in relations for row in oracle), "oracle contains undefined case-code relation")
    require({row["determining_rule"] for row in oracle} <= EXPECTED_RULES, "unexpected determining rule in oracle")
    require({row["pattern_id"] for row in oracle if row["pattern_id"]} <= EXPECTED_PATTERNS, "unexpected PAT ID in oracle")

    rule_text = (project / "chapter3_rule_catalogue.md").read_text(encoding="utf-8")
    domain_text = (project / "chapter3_domain_error_taxonomy_and_classification_baseline.md").read_text(encoding="utf-8")
    for rule in {row["determining_rule"] for row in oracle}:
        require(rule in rule_text, f"oracle rule not found in rule catalogue: {rule}")
    for pattern in {row["pattern_id"] for row in oracle if row["pattern_id"]}:
        require(pattern in domain_text, f"oracle pattern not found in domain baseline: {pattern}")
    criterion_keys = set(re.findall(r"`([a-z][a-z0-9_]+)`", rule_text))
    for criterion in {row["criterion"] for row in oracle}:
        require(criterion in criterion_keys, f"oracle criterion not found in rule catalogue: {criterion}")

    schema = (base / "mysql_schema.sql").read_text(encoding="utf-8")
    create_statements = "\n".join(line for line in schema.splitlines() if not line.lstrip().startswith("--"))
    forbidden_runtime_tokens = ("expected_class", "expected_evaluation_status", "determining_rule", "reference_response")
    require(not any(token in create_statements.lower() for token in forbidden_runtime_tokens), "verification-answer field/table leaked into runtime schema")

    print("PROTOBASE-0.2 validation: PASS")
    print("  DIAGLIST checksum and 13,298-code source identity: PASS")
    print("  SUBSET-0.1: 13 unique records, exact normalized source projection: PASS")
    print("  CASEBASE-0.2: 8 cases; response domain: 18 relations: PASS")
    print("  RCBASE-0.2: 18 expectations (6 correct / 2 suboptimal / 10 incorrect): PASS")
    print("  Oracle-to-runtime separation: PASS")


if __name__ == "__main__":
    main()
