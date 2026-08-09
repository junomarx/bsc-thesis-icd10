#!/usr/bin/env python3
"""Apply the MODELBASE-0.2 runtime schema to an empty MySQL database."""

from __future__ import annotations

import argparse
import sys
from pathlib import Path
from typing import Any

from load_mysql_0_2 import connect_mysql


EXPECTED_TABLES = {
    "prototype_baseline",
    "catalogue_code",
    "patient_definition",
    "patient_context_item",
    "coding_question",
    "question_fact",
    "question_code_domain",
    "question_relation_fact",
    "question_option",
}


def _table_names(cursor: Any) -> set[str]:
    cursor.execute("SHOW TABLES")
    return {str(row[0]) for row in cursor.fetchall()}


def _statements(schema_sql: str) -> list[str]:
    # This candidate schema intentionally contains only plain CREATE TABLE DDL,
    # no stored programs and no semicolons inside string literals.
    without_line_comments = "\n".join(
        line for line in schema_sql.splitlines() if not line.lstrip().startswith("--")
    )
    return [statement.strip() for statement in without_line_comments.split(";") if statement.strip()]


def main() -> None:
    default_schema = Path(__file__).with_name("mysql_schema_0_2.sql")
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--schema", type=Path, default=default_schema)
    args = parser.parse_args()

    try:
        schema_sql = args.schema.read_text(encoding="utf-8")
        statements = _statements(schema_sql)
        if len(statements) != len(EXPECTED_TABLES):
            raise RuntimeError(
                f"expected {len(EXPECTED_TABLES)} CREATE TABLE statements, found {len(statements)}"
            )

        connection = connect_mysql()
        try:
            cursor = connection.cursor()
            try:
                existing = _table_names(cursor)
                if existing:
                    raise RuntimeError(
                        "schema target must be empty; existing tables: " + ", ".join(sorted(existing))
                    )
                for statement in statements:
                    cursor.execute(statement)
                observed = _table_names(cursor)
                if observed != EXPECTED_TABLES:
                    raise RuntimeError(
                        f"schema table mismatch: observed={sorted(observed)}, "
                        f"expected={sorted(EXPECTED_TABLES)}"
                    )
            finally:
                cursor.close()
        finally:
            connection.close()

        print("MODELBASE-0.2 MySQL schema application: PASS")
        print(f"  runtime tables: {len(EXPECTED_TABLES)}")
        print(f"  schema: {args.schema}")
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(2) from exc


if __name__ == "__main__":
    main()
