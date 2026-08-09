#!/usr/bin/env python3
"""Persist the validated MODELBASE-0.2 runtime dataset in MySQL.

DDL is applied separately. This module performs only runtime DML inside one
transaction. It never reads the RCBASE-0.3 verification oracle.
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path
from typing import Any, Iterable

from runtime_data_0_2 import (
    BASELINE_COLUMNS,
    CATALOGUE_COLUMNS,
    CONTEXT_COLUMNS,
    DOMAIN_COLUMNS,
    FACT_COLUMNS,
    OPTION_COLUMNS,
    PATIENT_COLUMNS,
    QUESTION_COLUMNS,
    RELATION_FACT_COLUMNS,
    RuntimeDataset,
    load_runtime_dataset,
)


def _fetch_rows(
    cursor: Any,
    table: str,
    columns: tuple[str, ...],
    where: str,
    params: tuple[Any, ...],
    order_by: str,
) -> tuple[tuple[Any, ...], ...]:
    cursor.execute(
        f"SELECT {', '.join(columns)} FROM {table} WHERE {where} ORDER BY {order_by}",
        params,
    )
    return tuple(tuple(row) for row in cursor.fetchall())


def _component_state(cursor: Any, dataset: RuntimeDataset) -> dict[str, tuple[tuple[Any, ...], ...]]:
    manifest = dataset.manifest
    prototype_id = dataset.prototype_baseline_id
    subset_id = manifest["subset_baseline_id"]
    patient_id = manifest["patient_baseline_id"]
    question_id = manifest["question_baseline_id"]

    return {
        "baseline": _fetch_rows(
            cursor, "prototype_baseline", BASELINE_COLUMNS,
            "prototype_baseline_id = %s", (prototype_id,), "prototype_baseline_id",
        ),
        "catalogue": _fetch_rows(
            cursor, "catalogue_code", CATALOGUE_COLUMNS,
            "subset_baseline_id = %s", (subset_id,), "code",
        ),
        "patients": _fetch_rows(
            cursor, "patient_definition", PATIENT_COLUMNS,
            "patient_baseline_id = %s", (patient_id,), "patient_id",
        ),
        "patient_context": _fetch_rows(
            cursor, "patient_context_item", CONTEXT_COLUMNS,
            "patient_baseline_id = %s", (patient_id,),
            "patient_id, canonical_position, context_item_id",
        ),
        "questions": _fetch_rows(
            cursor, "coding_question", QUESTION_COLUMNS,
            "question_baseline_id = %s", (question_id,), "question_id",
        ),
        "question_facts": _fetch_rows(
            cursor, "question_fact", FACT_COLUMNS,
            "question_baseline_id = %s", (question_id,), "question_id, fact_key",
        ),
        "question_code_domain": _fetch_rows(
            cursor, "question_code_domain", DOMAIN_COLUMNS,
            "question_baseline_id = %s", (question_id,), "question_id, code",
        ),
        "question_relation_facts": _fetch_rows(
            cursor, "question_relation_fact", RELATION_FACT_COLUMNS,
            "question_baseline_id = %s", (question_id,), "question_id, code, fact_key",
        ),
        "question_options": _fetch_rows(
            cursor, "question_option", OPTION_COLUMNS,
            "question_baseline_id = %s", (question_id,),
            "question_id, canonical_position, option_id",
        ),
    }


def _expected_state(dataset: RuntimeDataset) -> dict[str, tuple[tuple[Any, ...], ...]]:
    return {"baseline": (dataset.baseline_row(),), **dataset.component_rows()}


def _assert_equal_component(
    name: str,
    observed: tuple[tuple[Any, ...], ...],
    expected: tuple[tuple[Any, ...], ...],
) -> None:
    if observed != expected:
        raise RuntimeError(
            f"version conflict in {name}: existing rows do not equal the declared runtime input "
            f"(observed={len(observed)}, expected={len(expected)})"
        )


def _insert_many(
    cursor: Any,
    table: str,
    columns: tuple[str, ...],
    rows: Iterable[tuple[Any, ...]],
) -> None:
    materialized = tuple(rows)
    if not materialized:
        return
    placeholders = ", ".join(["%s"] * len(columns))
    cursor.executemany(
        f"INSERT INTO {table} ({', '.join(columns)}) VALUES ({placeholders})",
        materialized,
    )


INSERT_SPECS = (
    ("catalogue", "catalogue_code", CATALOGUE_COLUMNS),
    ("patients", "patient_definition", PATIENT_COLUMNS),
    ("patient_context", "patient_context_item", CONTEXT_COLUMNS),
    ("questions", "coding_question", QUESTION_COLUMNS),
    ("question_facts", "question_fact", FACT_COLUMNS),
    ("question_code_domain", "question_code_domain", DOMAIN_COLUMNS),
    ("question_relation_facts", "question_relation_fact", RELATION_FACT_COLUMNS),
    ("question_options", "question_option", OPTION_COLUMNS),
)


def load_dataset(connection: Any, dataset: RuntimeDataset) -> str:
    """Insert an absent immutable baseline or prove an identical no-op."""

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

        # Reusing an already-populated component ID is allowed only if its
        # persisted meaning is exactly identical to the declared component.
        for name, expected_rows in expected.items():
            if name != "baseline" and observed[name]:
                _assert_equal_component(name, observed[name], expected_rows)

        for name, table, columns in INSERT_SPECS:
            if not observed[name]:
                _insert_many(cursor, table, columns, expected[name])

        # Metadata is last so an identifier can never denote a partial import.
        _insert_many(cursor, "prototype_baseline", BASELINE_COLUMNS, expected["baseline"])

        persisted = _component_state(cursor, dataset)
        for name, expected_rows in expected.items():
            _assert_equal_component(name, persisted[name], expected_rows)

        connection.commit()
        return "inserted"
    except Exception:
        connection.rollback()
        raise
    finally:
        cursor.close()


def connect_mysql() -> Any:
    try:
        import mysql.connector  # type: ignore[import-not-found]
    except ModuleNotFoundError as exc:
        raise RuntimeError(
            "database mode requires mysql-connector-python; use --check-only for runtime preflight"
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
    default_base = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base-dir", type=Path, default=default_base)
    parser.add_argument(
        "--check-only",
        action="store_true",
        help="validate and normalize all runtime inputs without opening a MySQL connection",
    )
    args = parser.parse_args()

    try:
        dataset = load_runtime_dataset(args.base_dir)
        print("MODELBASE-0.2 runtime input validation: PASS")
        print(f"  prototype baseline candidate: {dataset.prototype_baseline_id}")
        for name, rows in dataset.component_rows().items():
            print(f"  {name}: {len(rows)}")
        print(f"  canonical runtime digest: {dataset.canonical_digest()}")

        if args.check_only:
            return

        connection = connect_mysql()
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
