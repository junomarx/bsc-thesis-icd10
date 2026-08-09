#!/usr/bin/env python3
"""Deterministically derive SUBSET-0.2 from the frozen DIAGLIST workbook.

This is catalogue-preparation tooling only. It reads the selected code list from
the versioned JSON definition; code membership is not hard-coded in Python.
It performs no coding classification and never reads a verification oracle.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import io
import json
import os
import tempfile
from pathlib import Path

import pandas as pd


def require(condition: bool, message: str) -> None:
    if not condition:
        raise ValueError(message)


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def sha256_bytes(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def load_definition(path: Path) -> dict:
    definition = json.loads(path.read_text(encoding="utf-8"))
    require(definition.get("subset_baseline_id") == "SUBSET-0.2", "unexpected subset baseline")
    require(
        definition.get("retained_fields") == ["Diagnose", "Kennzeichen", "Bezeichnung", "Kurzbezeichnung"],
        "unexpected retained-field whitelist",
    )
    selected = definition.get("selected_codes", [])
    require(selected and len(selected) == len(set(selected)) == 99, "selected_codes must contain 99 unique codes")
    return definition


def build_subset_rows(source_path: Path, definition: dict) -> list[dict[str, str]]:
    source = definition["source"]
    actual_sha = sha256_file(source_path)
    require(actual_sha == source["sha256"], f"DIAGLIST SHA-256 mismatch: {actual_sha}")

    frame = pd.read_excel(source_path, sheet_name=source["worksheet"], dtype=str, keep_default_na=False)
    required = definition["retained_fields"]
    missing_fields = [field for field in required if field not in frame.columns]
    require(not missing_fields, f"DIAGLIST is missing required fields: {missing_fields}")

    normalized_codes = frame["Diagnose"].map(lambda value: str(value).strip())
    require(not normalized_codes.duplicated().any(), "DIAGLIST contains duplicate Diagnose identifiers after trimming")
    require(
        normalized_codes.nunique() == source["expected_unique_diagnose_identifiers"],
        f"unexpected unique Diagnose count: {normalized_codes.nunique()}",
    )
    row_index = {code: index for index, code in enumerate(normalized_codes)}

    selected_codes = definition["selected_codes"]
    missing_codes = [code for code in selected_codes if code not in row_index]
    require(not missing_codes, f"selected codes missing from frozen source: {missing_codes}")

    selected_set = set(selected_codes)
    for control in definition.get("deliberately_excluded_controls", []):
        code = control["code"]
        require(code not in selected_set, f"excluded control was accidentally selected: {code}")
        require(code in row_index, f"excluded control is absent from frozen source: {code}")
        raw = frame.iloc[row_index[code]]
        require(str(raw["Kennzeichen"]).strip() == control["expected_marker"], f"unexpected marker for excluded control {code}")

    rows = []
    for code in selected_codes:
        raw = frame.iloc[row_index[code]]
        designation = str(raw["Bezeichnung"])
        short_designation = str(raw["Kurzbezeichnung"])
        require(designation != "", f"empty Bezeichnung for {code}")
        require(short_designation != "", f"empty Kurzbezeichnung for {code}")
        rows.append(
            {
                "Diagnose": str(raw["Diagnose"]).strip(),
                "Kennzeichen": str(raw["Kennzeichen"]).strip(),
                "Bezeichnung": designation,
                "Kurzbezeichnung": short_designation,
            }
        )
    return rows


def render_csv(rows: list[dict[str, str]], fields: list[str]) -> bytes:
    buffer = io.StringIO(newline="")
    writer = csv.DictWriter(buffer, fieldnames=fields, lineterminator="\n", quoting=csv.QUOTE_MINIMAL)
    writer.writeheader()
    writer.writerows(rows)
    return buffer.getvalue().encode("utf-8")


def atomic_write(path: Path, data: bytes) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temp_path: Path | None = None
    try:
        with tempfile.NamedTemporaryFile(mode="wb", dir=path.parent, prefix=f".{path.name}.", delete=False) as handle:
            handle.write(data)
            handle.flush()
            os.fsync(handle.fileno())
            temp_path = Path(handle.name)
        os.replace(temp_path, path)
    finally:
        if temp_path is not None and temp_path.exists():
            temp_path.unlink()


def main() -> None:
    baseline_dir = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("source", type=Path, help="Path to the frozen DIAGLIST2026.xlsx")
    parser.add_argument("--definition", type=Path, default=baseline_dir / "config" / "subset_definition_0_2.json")
    parser.add_argument("--output", type=Path, default=baseline_dir / "data" / "subset_0_2.csv")
    parser.add_argument("--check-existing", action="store_true", help="Fail unless the existing CSV is byte-identical")
    args = parser.parse_args()

    definition = load_definition(args.definition)
    generated = render_csv(build_subset_rows(args.source, definition), definition["retained_fields"])
    if args.check_existing:
        require(args.output.exists(), f"expected output does not exist: {args.output}")
        existing = args.output.read_bytes()
        require(
            existing == generated,
            f"existing output differs (existing={sha256_bytes(existing)}, generated={sha256_bytes(generated)})",
        )
        mode = "checked"
    else:
        atomic_write(args.output, generated)
        mode = "written"

    print("SUBSET-0.2 preparation: PASS")
    print(f"  mode: {mode}")
    print(f"  records: {len(definition['selected_codes'])}")
    print(f"  source_sha256: {definition['source']['sha256']}")
    print(f"  output_sha256: {sha256_bytes(generated)}")


if __name__ == "__main__":
    main()
