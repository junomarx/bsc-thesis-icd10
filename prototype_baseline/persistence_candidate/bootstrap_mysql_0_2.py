#!/usr/bin/env python3
"""Safely bootstrap the MODELBASE-0.2 runtime schema and versioned data.

Mirrors archived/prototype_baseline_0_1/scripts/bootstrap_mysql.py: the schema is
applied only when the target database contains no tables, an exact runtime
table set is reused as-is, and any partial/unexpected schema is treated as
an error instead of being repaired implicitly. Data loading then delegates
to the immutable/idempotent MODELBASE-0.2 loader.
"""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

from apply_mysql_schema_0_2 import EXPECTED_TABLES, _table_names
from load_mysql_0_2 import connect_mysql


def _observed_tables() -> set[str]:
    connection = connect_mysql()
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
    apply_schema = scripts_dir / "apply_mysql_schema_0_2.py"
    load_data = scripts_dir / "load_mysql_0_2.py"

    try:
        observed = _observed_tables()
        if not observed:
            print("MySQL bootstrap: empty database; applying MODELBASE-0.2 runtime schema")
            subprocess.run([sys.executable, str(apply_schema)], check=True)
        elif observed == EXPECTED_TABLES:
            print("MySQL bootstrap: exact MODELBASE-0.2 runtime table set already present; schema unchanged")
        else:
            missing = sorted(EXPECTED_TABLES - observed)
            unexpected = sorted(observed - EXPECTED_TABLES)
            raise RuntimeError(
                "database is neither empty nor the expected MODELBASE-0.2 runtime schema; "
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
