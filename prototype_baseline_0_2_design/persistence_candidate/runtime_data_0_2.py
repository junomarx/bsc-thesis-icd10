"""Runtime-only data assembly and validation for MODELBASE-0.2.

Only the explicit file allowlist in runtime_manifest_0_2.json is read. The
RCBASE-0.3 oracle is intentionally outside this module's input boundary.
"""

from __future__ import annotations

import csv
import hashlib
import json
from collections import Counter, defaultdict
from dataclasses import dataclass
from decimal import Decimal, InvalidOperation
from pathlib import Path
from typing import Any


MANIFEST_RELATIVE = "persistence_candidate/runtime_manifest_0_2.json"

CATALOGUE_FIELDS = ["Diagnose", "Kennzeichen", "Bezeichnung", "Kurzbezeichnung"]
PATIENT_FIELDS = [
    "patient_baseline_id", "patient_id", "display_name", "age_years", "sex",
    "self_described_background", "history_availability", "difficulty_role",
    "general_health_summary", "synthetic",
]
CONTEXT_FIELDS = [
    "patient_baseline_id", "patient_id", "context_item_id", "item_type",
    "information_source", "display_text", "canonical_position",
]
QUESTION_FIELDS = [
    "question_baseline_id", "question_id", "patient_baseline_id", "patient_id",
    "title", "prompt", "intended_use", "canonical_position", "legacy_case_id",
    "source_audit_ref",
]
FACT_FIELDS = [
    "question_baseline_id", "question_id", "fact_key", "value_type",
    "value_text", "value_integer", "value_decimal", "value_boolean",
    "value_code", "value_enum", "unit", "learner_label",
    "source_context_item_id",
]
DOMAIN_FIELDS = [
    "question_baseline_id", "question_id", "subset_baseline_id", "code",
    "relation_kind", "reason_key", "improvement_code", "source_audit_ref",
]
RELATION_FACT_FIELDS = [
    "question_baseline_id", "question_id", "subset_baseline_id", "code",
    "fact_key", "relation_role",
]
OPTION_FIELDS = [
    "question_baseline_id", "question_id", "option_id", "option_kind",
    "subset_baseline_id", "code", "canonical_position",
]

BASELINE_COLUMNS = (
    "prototype_baseline_id", "model_baseline_id", "requirements_catalogue_version",
    "source_register_version", "domain_baseline_id", "rule_baseline_id",
    "subset_baseline_id", "patient_baseline_id", "question_baseline_id",
    "catalogue_edition", "diaglist_sha256",
)
CATALOGUE_COLUMNS = ("subset_baseline_id", "code", "marker", "designation", "short_designation")
PATIENT_COLUMNS = (
    "patient_baseline_id", "patient_id", "display_name", "age_years", "sex",
    "self_described_background", "history_availability", "difficulty_role",
    "general_health_summary", "synthetic",
)
CONTEXT_COLUMNS = (
    "patient_baseline_id", "patient_id", "context_item_id", "item_type",
    "information_source", "display_text", "canonical_position",
)
QUESTION_COLUMNS = (
    "question_baseline_id", "question_id", "patient_baseline_id", "patient_id",
    "title", "prompt", "intended_use", "canonical_position", "legacy_case_id",
    "source_audit_ref",
)
FACT_COLUMNS = (
    "question_baseline_id", "question_id", "fact_key", "value_type",
    "value_text", "value_integer", "value_decimal", "value_boolean",
    "value_code", "value_enum", "unit", "learner_label",
    "source_context_item_id",
)
DOMAIN_COLUMNS = (
    "question_baseline_id", "question_id", "subset_baseline_id", "code",
    "relation_kind", "reason_key", "improvement_code", "source_audit_ref",
)
RELATION_FACT_COLUMNS = (
    "question_baseline_id", "question_id", "subset_baseline_id", "code",
    "fact_key", "relation_role",
)
OPTION_COLUMNS = (
    "question_baseline_id", "question_id", "option_id", "option_kind",
    "subset_baseline_id", "code", "canonical_position",
)

RELATION_KINDS = {
    "accepted_reference", "less_specific_supported", "fact_conflict",
    "temporal_context_conflict", "source_rule_resolved",
}
RELATION_ROLES = {
    "supports_reference", "conflicts_with_response", "supports_specificity",
    "supports_temporal_context", "supports_source_rule",
}
EXPECTED_ROLE_BY_RELATION = {
    "accepted_reference": "supports_reference",
    "less_specific_supported": "supports_specificity",
    "fact_conflict": "conflicts_with_response",
    "temporal_context_conflict": "supports_temporal_context",
    "source_rule_resolved": "supports_source_rule",
}
FACT_TYPES = {"text", "integer", "decimal", "boolean", "code", "enum"}
CONTEXT_ITEM_TYPES = {
    "documented_condition", "self_reported_history", "current_exam_finding",
    "social_context", "information_boundary", "other",
}
RUNTIME_COMPONENTS = {
    "catalogue", "patients", "patient_context", "questions", "question_facts",
    "question_code_domain", "question_relation_facts", "question_options",
}


def require(condition: bool, message: str) -> None:
    if not condition:
        raise ValueError(message)


def _read_csv(path: Path, expected_fields: list[str]) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8", newline="") as handle:
        reader = csv.DictReader(handle)
        require(reader.fieldnames == expected_fields, f"unexpected CSV fields in {path}: {reader.fieldnames}")
        return list(reader)


def _read_component(base_dir: Path, relatives: list[str], expected_fields: list[str]) -> list[dict[str, str]]:
    combined: list[dict[str, str]] = []
    for relative in relatives:
        combined.extend(_read_csv(base_dir / relative, expected_fields))
    return combined


def _required_int(value: str, field: str) -> int:
    try:
        return int(value.strip())
    except ValueError as exc:
        raise ValueError(f"invalid integer for {field}: {value!r}") from exc


def _required_decimal(value: str, field: str) -> Decimal:
    try:
        return Decimal(value.strip())
    except InvalidOperation as exc:
        raise ValueError(f"invalid decimal for {field}: {value!r}") from exc


def _required_bool(value: str, field: str) -> bool:
    normalized = value.strip().lower()
    if normalized in {"true", "1"}:
        return True
    if normalized in {"false", "0"}:
        return False
    raise ValueError(f"invalid boolean for {field}: {value!r}")


def _nullable(value: str) -> str | None:
    value = value.strip()
    return value if value else None


@dataclass(frozen=True)
class RuntimeDataset:
    manifest: dict[str, Any]
    catalogue: tuple[tuple[Any, ...], ...]
    patients: tuple[tuple[Any, ...], ...]
    patient_context: tuple[tuple[Any, ...], ...]
    questions: tuple[tuple[Any, ...], ...]
    question_facts: tuple[tuple[Any, ...], ...]
    question_code_domain: tuple[tuple[Any, ...], ...]
    question_relation_facts: tuple[tuple[Any, ...], ...]
    question_options: tuple[tuple[Any, ...], ...]

    @property
    def prototype_baseline_id(self) -> str:
        return self.manifest["prototype_baseline_id"]

    def baseline_row(self) -> tuple[Any, ...]:
        m = self.manifest
        return tuple(m[name] for name in BASELINE_COLUMNS)

    def component_rows(self) -> dict[str, tuple[tuple[Any, ...], ...]]:
        return {
            "catalogue": self.catalogue,
            "patients": self.patients,
            "patient_context": self.patient_context,
            "questions": self.questions,
            "question_facts": self.question_facts,
            "question_code_domain": self.question_code_domain,
            "question_relation_facts": self.question_relation_facts,
            "question_options": self.question_options,
        }

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

        payload = {"baseline": self.baseline_row(), **self.component_rows()}
        encoded = json.dumps(
            serializable(payload), ensure_ascii=False, sort_keys=True, separators=(",", ":")
        ).encode("utf-8")
        return hashlib.sha256(encoded).hexdigest()


def load_runtime_dataset(base_dir: Path) -> RuntimeDataset:
    manifest_path = base_dir / MANIFEST_RELATIVE
    require(manifest_path.is_file(), f"runtime manifest missing: {manifest_path}")
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))

    require(manifest.get("verification_oracle_runtime_access") is False, "runtime oracle access must be false")
    require("reference_response_baseline_id" not in manifest, "runtime manifest must not identify RCBASE")
    require(
        manifest.get("interaction_cardinality") == "one_tagged_response_per_question_evaluation",
        "unexpected interaction cardinality",
    )

    runtime_files = manifest.get("runtime_files")
    require(isinstance(runtime_files, dict), "runtime_files must be an explicit object")
    require(set(runtime_files) == RUNTIME_COMPONENTS, f"unexpected runtime component keys: {set(runtime_files)}")
    runtime_hashes = manifest.get("runtime_file_sha256")
    require(isinstance(runtime_hashes, dict), "runtime_file_sha256 must be an explicit object")
    declared_paths: set[str] = set()
    for component, relatives in runtime_files.items():
        require(isinstance(relatives, list) and relatives, f"{component} must have an explicit non-empty file list")
        for relative in relatives:
            require(relative not in declared_paths, f"runtime input listed more than once: {relative}")
            declared_paths.add(relative)
            path = Path(relative)
            require(not path.is_absolute() and ".." not in path.parts, f"unsafe runtime path: {relative}")
            require(path.parts[0] == "data", f"runtime input must come from data/: {relative}")
            require(not path.name.startswith("reference_responses"), f"oracle file forbidden in runtime input: {relative}")
            absolute = base_dir / path
            require(absolute.is_file(), f"runtime input missing: {relative}")
            require(relative in runtime_hashes, f"runtime input lacks declared SHA-256: {relative}")
            observed_sha = hashlib.sha256(absolute.read_bytes()).hexdigest()
            require(observed_sha == runtime_hashes[relative], f"runtime input SHA-256 mismatch: {relative}")
    require(set(runtime_hashes) == declared_paths, "runtime_file_sha256 contains an undeclared or missing runtime path")

    subset_id = manifest["subset_baseline_id"]
    patient_baseline_id = manifest["patient_baseline_id"]
    question_baseline_id = manifest["question_baseline_id"]
    expected = manifest["expected_counts"]

    raw_catalogue = _read_component(base_dir, runtime_files["catalogue"], CATALOGUE_FIELDS)
    catalogue: list[tuple[Any, ...]] = []
    catalogue_codes: set[str] = set()
    for row in raw_catalogue:
        code = row["Diagnose"].strip()
        require(code and code not in catalogue_codes, f"duplicate/empty catalogue code: {code!r}")
        require(row["Bezeichnung"] and row["Kurzbezeichnung"], f"missing catalogue designation for {code}")
        catalogue_codes.add(code)
        catalogue.append((subset_id, code, _nullable(row["Kennzeichen"]), row["Bezeichnung"], row["Kurzbezeichnung"]))
    catalogue.sort(key=lambda item: item[1])

    raw_patients = _read_component(base_dir, runtime_files["patients"], PATIENT_FIELDS)
    patients: list[tuple[Any, ...]] = []
    patient_keys: set[tuple[str, str]] = set()
    for row in raw_patients:
        require(row["patient_baseline_id"] == patient_baseline_id, f"patient baseline mismatch: {row['patient_id']}")
        key = (row["patient_baseline_id"], row["patient_id"])
        require(row["patient_id"] and key not in patient_keys, f"duplicate/empty patient identity: {key}")
        patient_keys.add(key)
        age = _required_int(row["age_years"], "age_years")
        require(0 <= age <= 125, f"invalid patient age for {row['patient_id']}: {age}")
        synthetic = _required_bool(row["synthetic"], "synthetic")
        require(synthetic, f"non-synthetic patient forbidden in this prototype: {row['patient_id']}")
        for field in ("display_name", "sex", "self_described_background", "history_availability", "difficulty_role", "general_health_summary"):
            require(row[field].strip() != "", f"missing {field} for {row['patient_id']}")
        require(
            row["history_availability"] in {"established", "partial", "unavailable_from_patient"},
            f"invalid history_availability for {row['patient_id']}: {row['history_availability']}",
        )
        require(
            row["difficulty_role"] in {"foundational", "involved"},
            f"invalid difficulty_role for {row['patient_id']}: {row['difficulty_role']}",
        )
        patients.append((
            row["patient_baseline_id"], row["patient_id"], row["display_name"], age, row["sex"],
            row["self_described_background"], row["history_availability"], row["difficulty_role"],
            row["general_health_summary"], synthetic,
        ))
    patients.sort(key=lambda item: item[1])

    raw_context = _read_component(base_dir, runtime_files["patient_context"], CONTEXT_FIELDS)
    patient_context: list[tuple[Any, ...]] = []
    context_keys: set[tuple[str, str, str]] = set()
    context_positions: set[tuple[str, str, int]] = set()
    for row in raw_context:
        patient_key = (row["patient_baseline_id"], row["patient_id"])
        require(patient_key in patient_keys, f"orphan context item: {row['context_item_id']}")
        key = (*patient_key, row["context_item_id"])
        require(row["context_item_id"] and key not in context_keys, f"duplicate/empty context identity: {key}")
        context_keys.add(key)
        position = _required_int(row["canonical_position"], "context canonical_position")
        position_key = (*patient_key, position)
        require(position > 0 and position_key not in context_positions, f"duplicate/invalid context position: {position_key}")
        context_positions.add(position_key)
        require(row["item_type"] in CONTEXT_ITEM_TYPES, f"invalid context item_type for {key}: {row['item_type']}")
        require(row["information_source"] and row["display_text"], f"incomplete context item {key}")
        patient_context.append((*patient_key, row["context_item_id"], row["item_type"], row["information_source"], row["display_text"], position))
    patient_context.sort(key=lambda item: (item[1], item[6], item[2]))

    raw_questions = _read_component(base_dir, runtime_files["questions"], QUESTION_FIELDS)
    questions: list[tuple[Any, ...]] = []
    question_keys: set[tuple[str, str]] = set()
    question_patient: dict[str, tuple[str, str] | None] = {}
    question_use: dict[str, str] = {}
    question_positions: set[tuple[str, str, int]] = set()
    legacy_case_ids: set[str] = set()
    for row in raw_questions:
        require(row["question_baseline_id"] == question_baseline_id, f"question baseline mismatch: {row['question_id']}")
        qid = row["question_id"]
        key = (row["question_baseline_id"], qid)
        require(qid and key not in question_keys, f"duplicate/empty question identity: {key}")
        question_keys.add(key)
        intended_use = row["intended_use"]
        require(intended_use in {"learner_visible", "verification_only"}, f"invalid intended_use for {qid}")
        pb = _nullable(row["patient_baseline_id"])
        pid = _nullable(row["patient_id"])
        require((pb is None) == (pid is None), f"partial patient identity on {qid}")
        patient_key = None if pb is None else (pb, pid)  # type: ignore[arg-type]
        if intended_use == "learner_visible":
            require(patient_key in patient_keys, f"learner question lacks valid patient: {qid}")
        elif patient_key is not None:
            require(patient_key in patient_keys, f"verification question references unknown patient: {qid}")
        position = _required_int(row["canonical_position"], "question canonical_position")
        require(position > 0, f"invalid question position: {qid}")
        if patient_key is not None:
            pos_key = (*patient_key, position)
            require(pos_key not in question_positions, f"duplicate patient question position: {pos_key}")
            question_positions.add(pos_key)
        legacy = _nullable(row["legacy_case_id"])
        if legacy is not None:
            require(legacy not in legacy_case_ids, f"duplicate legacy case mapping: {legacy}")
            legacy_case_ids.add(legacy)
        require(row["title"] and row["prompt"] and row["source_audit_ref"], f"incomplete question {qid}")
        question_patient[qid] = patient_key
        question_use[qid] = intended_use
        questions.append((question_baseline_id, qid, pb, pid, row["title"], row["prompt"], intended_use, position, legacy, row["source_audit_ref"]))
    questions.sort(key=lambda item: item[1])

    raw_facts = _read_component(base_dir, runtime_files["question_facts"], FACT_FIELDS)
    facts: list[tuple[Any, ...]] = []
    fact_keys: set[tuple[str, str, str]] = set()
    value_fields = ["value_text", "value_integer", "value_decimal", "value_boolean", "value_code", "value_enum"]
    for row in raw_facts:
        qid = row["question_id"]
        require((row["question_baseline_id"], qid) in question_keys, f"orphan fact on {qid}/{row['fact_key']}")
        key = (row["question_baseline_id"], qid, row["fact_key"])
        require(row["fact_key"] and key not in fact_keys, f"duplicate/empty fact identity: {key}")
        fact_keys.add(key)
        value_type = row["value_type"]
        require(value_type in FACT_TYPES, f"invalid fact type {value_type} on {key}")
        populated = [name for name in value_fields if row[name].strip() != ""]
        required_field = f"value_{value_type}"
        require(populated == [required_field], f"typed fact payload mismatch on {key}: {populated}, expected {required_field}")
        typed: dict[str, Any] = {name: None for name in value_fields}
        raw_value = row[required_field]
        if value_type == "integer":
            typed[required_field] = _required_int(raw_value, required_field)
        elif value_type == "decimal":
            typed[required_field] = _required_decimal(raw_value, required_field)
        elif value_type == "boolean":
            typed[required_field] = _required_bool(raw_value, required_field)
        else:
            typed[required_field] = raw_value
        source_context = _nullable(row["source_context_item_id"])
        patient_key = question_patient[qid]
        if source_context is not None:
            require(patient_key is not None, f"patientless question has context source: {qid}")
            require((*patient_key, source_context) in context_keys, f"fact source context does not belong to question patient: {qid}/{source_context}")
        require(row["learner_label"], f"missing learner label for fact {key}")
        facts.append((
            question_baseline_id, qid, row["fact_key"], value_type,
            typed["value_text"], typed["value_integer"], typed["value_decimal"],
            typed["value_boolean"], typed["value_code"], typed["value_enum"],
            _nullable(row["unit"]), row["learner_label"], source_context,
        ))
    facts.sort(key=lambda item: (item[1], item[2]))

    raw_domains = _read_component(base_dir, runtime_files["question_code_domain"], DOMAIN_FIELDS)
    domains: list[tuple[Any, ...]] = []
    domain_keys: set[tuple[str, str, str, str]] = set()
    relations_by_question: Counter[str] = Counter()
    accepted_by_question: dict[str, set[str]] = defaultdict(set)
    less_specific_rows: list[tuple[str, str]] = []
    hard_relation_keys: set[tuple[str, str, str, str]] = set()
    for row in raw_domains:
        qid = row["question_id"]
        require((row["question_baseline_id"], qid) in question_keys, f"orphan question/code relation: {qid}/{row['code']}")
        require(row["subset_baseline_id"] == subset_id, f"domain subset mismatch: {qid}/{row['code']}")
        code = row["code"].strip()
        require(code in catalogue_codes, f"domain code outside active subset: {qid}/{code}")
        key = (question_baseline_id, qid, subset_id, code)
        require(key not in domain_keys, f"duplicate question/code relation: {qid}/{code}")
        domain_keys.add(key)
        kind = row["relation_kind"]
        require(kind in RELATION_KINDS, f"invalid relation kind {kind}: {qid}/{code}")
        reason = _nullable(row["reason_key"])
        improvement = _nullable(row["improvement_code"])
        if kind in {"fact_conflict", "temporal_context_conflict"}:
            require(reason is not None, f"hard generic relation lacks reason_key: {qid}/{code}")
            hard_relation_keys.add(key)
        if kind == "less_specific_supported":
            require(improvement is not None, f"less-specific relation lacks improvement_code: {qid}/{code}")
            less_specific_rows.append((qid, improvement))  # type: ignore[arg-type]
        if improvement is not None:
            require(improvement in catalogue_codes, f"improvement code outside active subset: {qid}/{improvement}")
        if kind == "accepted_reference":
            accepted_by_question[qid].add(code)
        require(row["source_audit_ref"], f"domain relation lacks source audit reference: {qid}/{code}")
        relations_by_question[qid] += 1
        domains.append((question_baseline_id, qid, subset_id, code, kind, reason, improvement, row["source_audit_ref"]))
    domains.sort(key=lambda item: (item[1], item[3]))
    require(all(relations_by_question[qid] > 0 for _, qid in question_keys), "every question requires an evaluation domain")
    for qid, improvement in less_specific_rows:
        require(improvement in accepted_by_question[qid], f"improvement is not an accepted reference: {qid}/{improvement}")

    raw_links = _read_component(base_dir, runtime_files["question_relation_facts"], RELATION_FACT_FIELDS)
    links: list[tuple[Any, ...]] = []
    link_keys: set[tuple[str, str, str, str, str]] = set()
    linked_relation_counts: Counter[tuple[str, str, str, str]] = Counter()
    for row in raw_links:
        key = (row["question_baseline_id"], row["question_id"], row["subset_baseline_id"], row["code"], row["fact_key"])
        require(key not in link_keys, f"duplicate relation/fact link: {key}")
        link_keys.add(key)
        domain_key = key[:4]
        fact_key = (row["question_baseline_id"], row["question_id"], row["fact_key"])
        require(domain_key in domain_keys, f"relation/fact link has no domain relation: {key}")
        require(fact_key in fact_keys, f"relation/fact link has no fact: {key}")
        role = row["relation_role"]
        require(role in RELATION_ROLES, f"invalid relation role {role}: {key}")
        relation_kind = next(item[4] for item in domains if item[:4] == domain_key)
        require(
            role == EXPECTED_ROLE_BY_RELATION[relation_kind],
            f"relation role does not match relation kind {relation_kind}: {key}/{role}",
        )
        linked_relation_counts[domain_key] += 1
        links.append((*domain_key, row["fact_key"], role))
    links.sort(key=lambda item: (item[1], item[3], item[4]))
    require(all(linked_relation_counts[key] > 0 for key in hard_relation_keys), "every generic hard relation requires an explicit fact link")

    raw_options = _read_component(base_dir, runtime_files["question_options"], OPTION_FIELDS)
    options: list[tuple[Any, ...]] = []
    option_keys: set[tuple[str, str, str]] = set()
    option_positions: set[tuple[str, str, int]] = set()
    code_options_by_question: dict[str, set[str]] = defaultdict(set)
    none_counts: Counter[str] = Counter()
    code_option_count = 0
    for row in raw_options:
        qid = row["question_id"]
        require((row["question_baseline_id"], qid) in question_keys, f"orphan question option: {qid}/{row['option_id']}")
        key = (question_baseline_id, qid, row["option_id"])
        require(row["option_id"] and key not in option_keys, f"duplicate/empty option identity: {key}")
        option_keys.add(key)
        position = _required_int(row["canonical_position"], "option canonical_position")
        position_key = (question_baseline_id, qid, position)
        require(position > 0 and position_key not in option_positions, f"duplicate/invalid option position: {position_key}")
        option_positions.add(position_key)
        kind = row["option_kind"]
        require(kind in {"code", "none_of_above"}, f"invalid option kind: {qid}/{kind}")
        option_subset = _nullable(row["subset_baseline_id"])
        code = _nullable(row["code"])
        if kind == "code":
            require(option_subset == subset_id and code is not None, f"code option lacks active subset/code: {qid}/{row['option_id']}")
            require(code in catalogue_codes, f"option code outside active subset: {qid}/{code}")
            require((question_baseline_id, qid, subset_id, code) in domain_keys, f"displayed code is outside evaluation domain: {qid}/{code}")
            require(code not in code_options_by_question[qid], f"duplicate displayed code option: {qid}/{code}")
            code_options_by_question[qid].add(code)
            code_option_count += 1
        else:
            require(option_subset is None and code is None, f"none_of_above must not carry catalogue identity: {qid}")
            none_counts[qid] += 1
        options.append((question_baseline_id, qid, row["option_id"], kind, option_subset, code, position))
    options.sort(key=lambda item: (item[1], item[6], item[2]))

    learner_ids = {qid for qid, use in question_use.items() if use == "learner_visible"}
    verification_ids = {qid for qid, use in question_use.items() if use == "verification_only"}
    require(len(learner_ids) == expected["learner_questions"], "unexpected learner-question count")
    require(len(verification_ids) == expected["verification_questions"], "unexpected verification-question count")
    for qid in learner_ids:
        require(question_patient[qid] is not None, f"learner question has no patient: {qid}")
        require(accepted_by_question[qid], f"learner question has no accepted reference: {qid}")
        require(code_options_by_question[qid], f"learner question has no displayed code option: {qid}")
        require(none_counts[qid] == 1, f"learner question must have exactly one none_of_above option: {qid}")
    for qid in verification_ids:
        require(not code_options_by_question[qid] and none_counts[qid] == 0, f"verification-only question must be optionless: {qid}")
        require(question_patient[qid] is None, f"current legacy verification fixture must be patientless: {qid}")

    # Baseline-specific pedagogical controls.
    expected_legacy = {f"CASE-{i:03d}" for i in range(1, 9)}
    require(legacy_case_ids == expected_legacy, f"legacy regression mapping incomplete: {legacy_case_ids}")
    patient_question_counts = Counter(question_patient[qid][1] for qid in learner_ids if question_patient[qid] is not None)
    require(patient_question_counts == Counter({"PATIENT-001":3,"PATIENT-002":3,"PATIENT-003":3,"PATIENT-004":5,"PATIENT-005":5,"PATIENT-006":6}), f"unexpected patient question distribution: {patient_question_counts}")
    for qid in learner_ids:
        patient_id = question_patient[qid][1]  # type: ignore[index]
        has_j44 = any(code.startswith("J44.") or code == "J44" for code in (d[3] for d in domains if d[1] == qid))
        if patient_id == "PATIENT-001":
            continue
        require(not has_j44, f"COPD/J44 learner content leaked beyond PATIENT-001: {qid}")
    displayed_accepted = {qid: bool(code_options_by_question[qid] & accepted_by_question[qid]) for qid in learner_ids}
    require({qid for qid, value in displayed_accepted.items() if not value} == {"Q-004-05", "Q-005-05"}, "none_of_above positive controls changed")

    observed_counts = {
        "catalogue": len(catalogue), "patients": len(patients), "patient_context": len(patient_context),
        "questions": len(questions), "learner_questions": len(learner_ids), "verification_questions": len(verification_ids),
        "question_facts": len(facts), "question_code_domain": len(domains),
        "question_relation_facts": len(links), "question_options": len(options),
        "code_options": code_option_count, "none_of_above_options": sum(none_counts.values()),
    }
    require(observed_counts == expected, f"runtime count mismatch: observed={observed_counts}, expected={expected}")

    return RuntimeDataset(
        manifest=manifest,
        catalogue=tuple(catalogue),
        patients=tuple(patients),
        patient_context=tuple(patient_context),
        questions=tuple(questions),
        question_facts=tuple(facts),
        question_code_domain=tuple(domains),
        question_relation_facts=tuple(links),
        question_options=tuple(options),
    )
