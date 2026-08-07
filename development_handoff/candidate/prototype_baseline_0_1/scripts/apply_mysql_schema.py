#!/usr/bin/env python3
"""Apply the PROTOBASE-0.1 runtime DDL to an empty MySQL database.

Schema application is deliberately separate from runtime data loading because
MySQL DDL can commit implicitly. The target database itself must already exist.
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path
from typing import Any


EXPECTED_TABLES = {
    "prototype_baseline",
    "catalogue_code",
    "case_definition",
    "case_code_domain",
}


def _connect_mysql() -> Any:
    try:
        import mysql.connector  # type: ignore[import-not-found]
    except ModuleNotFoundError as exc:
        raise RuntimeError("schema application requires mysql-connector-python") from exc

    database = os.environ.get("ICD_DB_NAME")
    user = os.environ.get("ICD_DB_USER")
    if not database or not user:
        raise RuntimeError("ICD_DB_NAME and ICD_DB_USER must be set")

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


def _table_names(cursor: Any) -> set[str]:
    cursor.execute("SHOW TABLES")
    return {str(row[0]) for row in cursor.fetchall()}


def main() -> None:
    baseline_dir = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--schema",
        type=Path,
        default=baseline_dir / "mysql_schema.sql",
        help="Runtime DDL file",
    )
    args = parser.parse_args()

    try:
        schema_sql = args.schema.read_text(encoding="utf-8")
        if not schema_sql.strip():
            raise RuntimeError("schema file is empty")

        connection = _connect_mysql()
        try:
            cursor = connection.cursor()
            try:
                existing = _table_names(cursor)
                if existing:
                    raise RuntimeError(
                        "schema target must be empty; existing tables: " + ", ".join(sorted(existing))
                    )

                cursor.execute(schema_sql, map_results=True)
                while cursor.nextset():
                    pass

                observed = _table_names(cursor)
                if observed != EXPECTED_TABLES:
                    raise RuntimeError(
                        f"schema table mismatch: observed={sorted(observed)}, expected={sorted(EXPECTED_TABLES)}"
                    )
            finally:
                cursor.close()
        finally:
            connection.close()

        print("MySQL runtime schema application: PASS")
        print(f"  tables: {len(EXPECTED_TABLES)}")
        print(f"  schema: {args.schema}")
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(2) from exc


if __name__ == "__main__":
    main()
