"""Runtime-only data loading and structural validation for PROTOBASE-0.1.

The module deliberately enumerates runtime inputs. It does not discover files
by glob and has no dependency on the verification oracle.
"""

from __future__ import annotations

import csv
import hashlib
import json
from dataclasses import dataclass
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any


RUNTIME_FILES = {
    "manifest": "baseline_manifest.json",
    "catalogue": "data/subset_0_1.csv",
    "cases": "data/cases_0_2.csv",
    "case_code_domain": "data/case_code_domain_0_2.csv",
}

CATALOGUE_FIELDS = ["Diagnose", "Kennzeichen", "Bezeichnung", "Kurzbezeichnung"]
CASE_FIELDS = [
    "case_baseline_id",
    "case_id",
    "subset_baseline_id",
    "short_description",
    "encounter_setting",
    "diagnosis_role",
    "inpatient_lkf_scored",
    "copd_base_code",
    "fev1_stable_pct_predicted",
    "intended_use",
    "source_locator",
]
DOMAIN_FIELDS = ["case_baseline_id", "case_id", "subset_baseline_id", "code", "is_acceptable"]


def require(condition: bool, message: str) -> None:
    if not condition:
        raise ValueError(message)


def _read_csv(path: Path, expected_fields: list[str]) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as handle:
        reader = csv.DictReader(handle)
        require(reader.fieldnames == expected_fields, f"unexpected CSV fields in {path.name}: {reader.fieldnames}")
        return list(reader)


def _nullable_bool(value: str, field: str) -> bool | None:
    normalized = value.strip().lower()
    if normalized == "":
        return None
    if normalized == "true":
        return True
    if normalized == "false":
        return False
    raise ValueError(f"invalid nullable boolean for {field}: {value!r}")


def _required_bool01(value: str, field: str) -> bool:
    normalized = value.strip()
    if normalized == "1":
        return True
    if normalized == "0":
        return False
    raise ValueError(f"invalid 0/1 boolean for {field}: {value!r}")


def _nullable_decimal(value: str, field: str) -> Decimal | None:
    normalized = value.strip()
    if normalized == "":
        return None
    try:
        return Decimal(normalized)
    except InvalidOperation as exc:
        raise ValueError(f"invalid decimal for {field}: {value!r}") from exc


@dataclass(frozen=True)
class RuntimeDataset:
    manifest: dict[str, Any]
    catalogue: tuple[tuple[Any, ...], ...]
    cases: tuple[tuple[Any, ...], ...]
    case_code_domain: tuple[tuple[Any, ...], ...]

    @property
    def prototype_baseline_id(self) -> str:
        return self.manifest["prototype_baseline_id"]

    @property
    def subset_baseline_id(self) -> str:
        return self.manifest["subset_baseline_id"]

    @property
    def case_baseline_id(self) -> str:
        return self.manifest["case_baseline_id"]

    def baseline_row(self) -> tuple[Any, ...]:
        m = self.manifest
        return (
            m["prototype_baseline_id"],
            m["model_baseline_id"],
            m["requirements_catalogue_version"],
            m["source_register_version"],
            m["domain_baseline_id"],
            m["rule_baseline_id"],
            m["case_baseline_id"],
            m["subset_baseline_id"],
            m["catalogue_edition"],
            m["diaglist_sha256"],
        )

    def canonical_digest(self) -> str:
        def serializable(value: Any) -> Any:
            if isinstance(value, Decimal):
                return format(value, "f")
            if isinstance(value, dict):
                return {key: serializable(item) for key, item in value.items()}
            if isinstance(value, tuple):
                return [serializable(item) for item in value]
            if isinstance(value, list):
                return [serializable(item) for item in value]
            return value

        payload = {
            "baseline": self.baseline_row(),
            "catalogue": self.catalogue,
            "cases": self.cases,
            "case_code_domain": self.case_code_domain,
        }
        encoded = json.dumps(serializable(payload), ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode("utf-8")
        return hashlib.sha256(encoded).hexdigest()


def load_runtime_dataset(base_dir: Path) -> RuntimeDataset:
    paths = {name: base_dir / relative for name, relative in RUNTIME_FILES.items()}
    require(all(path.is_file() for path in paths.values()), "one or more runtime input files are missing")

    manifest = json.loads(paths["manifest"].read_text(encoding="utf-8"))
    require(manifest.get("verification_oracle_runtime_access") is False, "verification oracle must remain unavailable to runtime")
    require(manifest.get("interaction_cardinality") == "one_submitted_code_per_evaluation_request", "unexpected interaction cardinality")

    subset_id = manifest["subset_baseline_id"]
    case_baseline_id = manifest["case_baseline_id"]

    raw_catalogue = _read_csv(paths["catalogue"], CATALOGUE_FIELDS)
    catalogue: list[tuple[Any, ...]] = []
    catalogue_codes: set[str] = set()
    for row in raw_catalogue:
        code = row["Diagnose"].strip()
        require(code and code not in catalogue_codes, f"duplicate/empty catalogue code: {code!r}")
        require(row["Bezeichnung"] != "" and row["Kurzbezeichnung"] != "", f"missing catalogue label for {code}")
        catalogue_codes.add(code)
        marker = row["Kennzeichen"].strip() or None
        catalogue.append((subset_id, code, marker, row["Bezeichnung"], row["Kurzbezeichnung"]))
    catalogue.sort(key=lambda item: item[1])

    raw_cases = _read_csv(paths["cases"], CASE_FIELDS)
    cases: list[tuple[Any, ...]] = []
    case_keys: set[tuple[str, str, str]] = set()
    case_use: dict[str, str] = {}
    for row in raw_cases:
        require(row["case_baseline_id"] == case_baseline_id, f"case baseline mismatch for {row['case_id']}")
        require(row["subset_baseline_id"] == subset_id, f"subset baseline mismatch for {row['case_id']}")
        key = (row["case_baseline_id"], row["case_id"], row["subset_baseline_id"])
        require(row["case_id"] and key not in case_keys, f"duplicate/empty case identity: {key}")
        case_keys.add(key)

        encounter = row["encounter_setting"]
        role = row["diagnosis_role"]
        intended_use = row["intended_use"]
        require(encounter in {"inpatient", "hospital_outpatient"}, f"invalid encounter setting for {row['case_id']}")
        require(role in {"main", "additional"}, f"invalid diagnosis role for {row['case_id']}")
        require(intended_use in {"learner_visible", "verification_only"}, f"invalid intended use for {row['case_id']}")

        lkf_scored = _nullable_bool(row["inpatient_lkf_scored"], "inpatient_lkf_scored")
        if encounter == "inpatient":
            require(lkf_scored is None, f"inpatient case must not carry outpatient LKF flag: {row['case_id']}")
        else:
            require(lkf_scored is not None, f"hospital-outpatient case requires LKF-scoring flag: {row['case_id']}")

        copd_base = row["copd_base_code"].strip() or None
        if copd_base is not None:
            require(copd_base in catalogue_codes, f"COPD base code is outside active subset: {copd_base}")
        fev1 = _nullable_decimal(row["fev1_stable_pct_predicted"], "fev1_stable_pct_predicted")
        require(row["short_description"] != "" and row["source_locator"] != "", f"missing case description/source for {row['case_id']}")

        case_use[row["case_id"]] = intended_use
        cases.append(
            (
                row["case_baseline_id"],
                row["case_id"],
                row["subset_baseline_id"],
                row["short_description"],
                encounter,
                role,
                lkf_scored,
                copd_base,
                fev1,
                intended_use,
                row["source_locator"],
            )
        )
    cases.sort(key=lambda item: item[1])

    raw_domain = _read_csv(paths["case_code_domain"], DOMAIN_FIELDS)
    domain: list[tuple[Any, ...]] = []
    domain_keys: set[tuple[str, str, str, str]] = set()
    accepted_by_case: dict[str, int] = {case_id: 0 for _, case_id, _ in case_keys}
    relations_by_case: dict[str, int] = {case_id: 0 for _, case_id, _ in case_keys}
    for row in raw_domain:
        require(row["case_baseline_id"] == case_baseline_id, f"domain case baseline mismatch for {row['case_id']}")
        require(row["subset_baseline_id"] == subset_id, f"domain subset baseline mismatch for {row['case_id']}")
        case_key = (row["case_baseline_id"], row["case_id"], row["subset_baseline_id"])
        require(case_key in case_keys, f"orphan case-code relation: {row['case_id']} / {row['code']}")
        code = row["code"].strip()
        require(code in catalogue_codes, f"case-code relation references code outside active subset: {code}")
        key = (*case_key, code)
        require(key not in domain_keys, f"duplicate case-code relation: {row['case_id']} / {code}")
        domain_keys.add(key)
        acceptable = _required_bool01(row["is_acceptable"], "is_acceptable")
        relations_by_case[row["case_id"]] += 1
        accepted_by_case[row["case_id"]] += int(acceptable)
        domain.append((*case_key, code, acceptable))
    domain.sort(key=lambda item: (item[1], item[3]))

    require(all(count > 0 for count in relations_by_case.values()), "every case must have at least one defined response relation")
    for case_id, intended_use in case_use.items():
        if intended_use == "learner_visible":
            require(accepted_by_case[case_id] > 0, f"learner-visible case has no acceptable response: {case_id}")

    return RuntimeDataset(
        manifest=manifest,
        catalogue=tuple(catalogue),
        cases=tuple(cases),
        case_code_domain=tuple(domain),
    )
