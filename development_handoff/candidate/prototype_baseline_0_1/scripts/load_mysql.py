#!/usr/bin/env python3
"""Load the validated PROTOBASE-0.1 runtime dataset into MySQL.

The schema is applied separately. This loader performs only data DML inside one
transaction. It never imports reference-response expectations.
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path
from typing import Any, Iterable

from runtime_data import RuntimeDataset, load_runtime_dataset


BASELINE_COLUMNS = (
    "prototype_baseline_id",
    "model_baseline_id",
    "requirements_catalogue_version",
    "source_register_version",
    "domain_baseline_id",
    "rule_baseline_id",
    "case_baseline_id",
    "subset_baseline_id",
    "catalogue_edition",
    "diaglist_sha256",
)

CATALOGUE_COLUMNS = ("subset_baseline_id", "code", "marker", "designation", "short_designation")
CASE_COLUMNS = (
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
)
DOMAIN_COLUMNS = ("case_baseline_id", "case_id", "subset_baseline_id", "code", "is_acceptable")


def _fetch_rows(cursor: Any, table: str, columns: tuple[str, ...], where: str, params: tuple[Any, ...], order_by: str) -> tuple[tuple[Any, ...], ...]:
    column_list = ", ".join(columns)
    cursor.execute(f"SELECT {column_list} FROM {table} WHERE {where} ORDER BY {order_by}", params)
    return tuple(tuple(row) for row in cursor.fetchall())


def _component_state(cursor: Any, dataset: RuntimeDataset) -> dict[str, tuple[tuple[Any, ...], ...]]:
    baseline_rows = _fetch_rows(
        cursor,
        "prototype_baseline",
        BASELINE_COLUMNS,
        "prototype_baseline_id = %s",
        (dataset.prototype_baseline_id,),
        "prototype_baseline_id",
    )
    catalogue_rows = _fetch_rows(
        cursor,
        "catalogue_code",
        CATALOGUE_COLUMNS,
        "subset_baseline_id = %s",
        (dataset.subset_baseline_id,),
        "code",
    )
    case_rows = _fetch_rows(
        cursor,
        "case_definition",
        CASE_COLUMNS,
        "case_baseline_id = %s",
        (dataset.case_baseline_id,),
        "case_id",
    )
    domain_rows = _fetch_rows(
        cursor,
        "case_code_domain",
        DOMAIN_COLUMNS,
        "case_baseline_id = %s",
        (dataset.case_baseline_id,),
        "case_id, code",
    )
    return {
        "baseline": baseline_rows,
        "catalogue": catalogue_rows,
        "cases": case_rows,
        "case_code_domain": domain_rows,
    }


def _expected_state(dataset: RuntimeDataset) -> dict[str, tuple[tuple[Any, ...], ...]]:
    return {
        "baseline": (dataset.baseline_row(),),
        "catalogue": dataset.catalogue,
        "cases": dataset.cases,
        "case_code_domain": dataset.case_code_domain,
    }


def _assert_equal_component(name: str, observed: tuple[tuple[Any, ...], ...], expected: tuple[tuple[Any, ...], ...]) -> None:
    if observed != expected:
        raise RuntimeError(
            f"baseline conflict in {name}: existing rows do not equal the versioned runtime input "
            f"(observed={len(observed)}, expected={len(expected)})"
        )


def _insert_many(cursor: Any, table: str, columns: tuple[str, ...], rows: Iterable[tuple[Any, ...]]) -> None:
    rows = tuple(rows)
    if not rows:
        return
    placeholders = ", ".join(["%s"] * len(columns))
    cursor.executemany(
        f"INSERT INTO {table} ({', '.join(columns)}) VALUES ({placeholders})",
        rows,
    )


def load_dataset(connection: Any, dataset: RuntimeDataset) -> str:
    """Import or verify one immutable baseline. Return 'inserted' or 'no_op'."""

    connection.start_transaction()
    cursor = connection.cursor()
    expected = _expected_state(dataset)

    try:
        observed = _component_state(cursor, dataset)

        if observed["baseline"]:
            for name, expected_rows in expected.items():
                _assert_equal_component(name, observed[name], expected_rows)
            connection.rollback()
            return "no_op"

        for name in ("catalogue", "cases", "case_code_domain"):
            if observed[name]:
                _assert_equal_component(name, observed[name], expected[name])

        if not observed["catalogue"]:
            _insert_many(cursor, "catalogue_code", CATALOGUE_COLUMNS, expected["catalogue"])
        if not observed["cases"]:
            _insert_many(cursor, "case_definition", CASE_COLUMNS, expected["cases"])
        if not observed["case_code_domain"]:
            _insert_many(cursor, "case_code_domain", DOMAIN_COLUMNS, expected["case_code_domain"])

        _insert_many(cursor, "prototype_baseline", BASELINE_COLUMNS, expected["baseline"])

        final_state = _component_state(cursor, dataset)
        for name, expected_rows in expected.items():
            _assert_equal_component(name, final_state[name], expected_rows)

        connection.commit()
        return "inserted"
    except Exception:
        connection.rollback()
        raise
    finally:
        cursor.close()


def _connect_mysql() -> Any:
    try:
        import mysql.connector  # type: ignore[import-not-found]
    except ModuleNotFoundError as exc:
        raise RuntimeError(
            "database mode requires the mysql-connector-python package; "
            "use --check-only to validate runtime inputs without a MySQL driver"
        ) from exc

    database = os.environ.get("ICD_DB_NAME")
    user = os.environ.get("ICD_DB_USER")
    if not database or not user:
        raise RuntimeError("ICD_DB_NAME and ICD_DB_USER must be set for database mode")

    try:
        port = int(os.environ.get("ICD_DB_PORT", "3306"))
    except ValueError as exc:
        raise RuntimeError("ICD_DB_PORT must be an integer") from exc

    return mysql.connector.connect(
        host=os.environ.get("ICD_DB_HOST", "127.0.0.1"),
        port=port,
        database=database,
        user=user,
        password=os.environ.get("ICD_DB_PASSWORD", ""),
        charset="utf8mb4",
        autocommit=False,
        connection_timeout=10,
    )


def main() -> None:
    baseline_dir = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-dir", type=Path, default=baseline_dir, help="Prototype baseline directory")
    parser.add_argument("--check-only", action="store_true", help="Validate runtime inputs without connecting to MySQL")
    args = parser.parse_args()

    try:
        dataset = load_runtime_dataset(args.base_dir)
        print("Runtime input validation: PASS")
        print(f"  prototype_baseline: {dataset.prototype_baseline_id}")
        print(f"  catalogue_rows: {len(dataset.catalogue)}")
        print(f"  case_rows: {len(dataset.cases)}")
        print(f"  case_code_relations: {len(dataset.case_code_domain)}")
        print(f"  canonical_digest: {dataset.canonical_digest()}")

        if args.check_only:
            return

        connection = _connect_mysql()
        try:
            status = load_dataset(connection, dataset)
        finally:
            connection.close()
        print(f"MySQL baseline load: {status}")
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(2) from exc


if __name__ == "__main__":
    main()
