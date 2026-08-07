#!/usr/bin/env python3
"""Safely bootstrap the PROTOBASE runtime schema and versioned data.

The coordinator creates the schema only when the target database contains no
tables. An exact runtime schema is reused. Any partial or unexpected schema is
treated as an error instead of being repaired implicitly. Runtime data loading
then delegates to the immutable/idempotent PROTOBASE loader.
"""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

from apply_mysql_schema import EXPECTED_TABLES, _connect_mysql, _table_names


def _observed_tables() -> set[str]:
    connection = _connect_mysql()
    try:
        cursor = connection.cursor()
        try:
            return _table_names(cursor)
        finally:
            cursor.close()
    finally:
        connection.close()


def main() -> None:
    scripts_dir = Path(__file__).resolve().parent
    apply_schema = scripts_dir / "apply_mysql_schema.py"
    load_data = scripts_dir / "load_mysql.py"

    try:
        observed = _observed_tables()
        if not observed:
            print("MySQL bootstrap: empty database; applying runtime schema")
            subprocess.run([sys.executable, str(apply_schema)], check=True)
        elif observed == EXPECTED_TABLES:
            print("MySQL bootstrap: exact runtime table set already present; schema unchanged")
        else:
            missing = sorted(EXPECTED_TABLES - observed)
            unexpected = sorted(observed - EXPECTED_TABLES)
            raise RuntimeError(
                "database is neither empty nor the expected runtime schema; "
                f"missing={missing}, unexpected={unexpected}. Refusing automatic repair."
            )

        subprocess.run([sys.executable, str(load_data)], check=True)
        print("MySQL bootstrap: PASS")
    except subprocess.CalledProcessError as exc:
        print(f"ERROR: bootstrap child process failed with exit code {exc.returncode}", file=sys.stderr)
        raise SystemExit(exc.returncode) from exc
    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        raise SystemExit(2) from exc


if __name__ == "__main__":
    main()
