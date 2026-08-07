"""Live MySQL integration assertions for TEST-DAT-02 / PROTOBASE-0.1.

The test reads persisted runtime state only. It never reads RCBASE-0.1 or any
verification expectation file.
"""

from __future__ import annotations

import os
import unittest
from decimal import Decimal

import mysql.connector
from mysql.connector import Error as MySQLError


EXPECTED_TABLES = {
    "prototype_baseline",
    "catalogue_code",
    "case_definition",
    "case_code_domain",
}


def connect() -> mysql.connector.MySQLConnection:
    database = os.environ.get("ICD_DB_NAME")
    user = os.environ.get("ICD_DB_USER")
    if not database or not user:
        raise RuntimeError("ICD_DB_NAME and ICD_DB_USER are required")
    return mysql.connector.connect(
        host=os.environ.get("ICD_DB_HOST", "127.0.0.1"),
        port=int(os.environ.get("ICD_DB_PORT", "3306")),
        database=database,
        user=user,
        password=os.environ.get("ICD_DB_PASSWORD", ""),
        charset="utf8mb4",
        autocommit=False,
        connection_timeout=10,
    )


class MySQLPersistenceTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.connection = connect()

    @classmethod
    def tearDownClass(cls) -> None:
        cls.connection.close()

    def tearDown(self) -> None:
        self.connection.rollback()

    def query(self, sql: str, params: tuple = ()) -> list[tuple]:
        cursor = self.connection.cursor()
        try:
            cursor.execute(sql, params)
            return [tuple(row) for row in cursor.fetchall()]
        finally:
            cursor.close()

    def test_server_and_runtime_schema(self) -> None:
        version = self.query("SELECT VERSION()")[0][0]
        self.assertTrue(str(version).startswith("8.4.8"), version)

        tables = {row[0] for row in self.query(
            "SELECT table_name FROM information_schema.tables "
            "WHERE table_schema = DATABASE()"
        )}
        self.assertEqual(tables, EXPECTED_TABLES)

        columns = {row[0].lower() for row in self.query(
            "SELECT column_name FROM information_schema.columns "
            "WHERE table_schema = DATABASE()"
        )}
        self.assertFalse(any(name.startswith("expected_") for name in columns))
        self.assertNotIn("determining_rule", columns)

    def test_persisted_counts_and_baseline_identity(self) -> None:
        self.assertEqual(self.query("SELECT COUNT(*) FROM catalogue_code")[0][0], 13)
        self.assertEqual(self.query("SELECT COUNT(*) FROM case_definition")[0][0], 4)
        self.assertEqual(self.query("SELECT COUNT(*) FROM case_code_domain")[0][0], 14)
        self.assertEqual(self.query("SELECT COUNT(*) FROM prototype_baseline")[0][0], 1)

        row = self.query(
            "SELECT prototype_baseline_id, subset_baseline_id, case_baseline_id, "
            "diaglist_sha256 FROM prototype_baseline"
        )[0]
        self.assertEqual(
            row,
            (
                "PROTOBASE-0.1",
                "SUBSET-0.1",
                "CASEBASE-0.1",
                "66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b",
            ),
        )

    def test_response_domains_and_acceptable_sets(self) -> None:
        sizes = dict(self.query(
            "SELECT case_id, COUNT(*) FROM case_code_domain GROUP BY case_id ORDER BY case_id"
        ))
        self.assertEqual(sizes, {"CASE-001": 6, "CASE-002": 6, "CASE-003": 1, "CASE-004": 1})

        accepted: dict[str, set[str]] = {case_id: set() for case_id in sizes}
        for case_id, code in self.query(
            "SELECT case_id, code FROM case_code_domain WHERE is_acceptable = 1 ORDER BY case_id, code"
        ):
            accepted[case_id].add(code)
        self.assertEqual(
            accepted,
            {
                "CASE-001": {"J44.02"},
                "CASE-002": {"J44.12"},
                "CASE-003": {"Z01.6"},
                "CASE-004": set(),
            },
        )

    def test_case_values_and_visibility_boundary(self) -> None:
        rows = self.query(
            "SELECT case_id, intended_use, fev1_stable_pct_predicted, inpatient_lkf_scored "
            "FROM case_definition ORDER BY case_id"
        )
        self.assertEqual(
            rows,
            [
                ("CASE-001", "learner_visible", Decimal("55.00"), None),
                ("CASE-002", "learner_visible", Decimal("50.00"), None),
                ("CASE-003", "learner_visible", None, 0),
                ("CASE-004", "verification_only", None, 1),
            ],
        )

    def test_no_orphans_and_foreign_key_is_enforced(self) -> None:
        orphan_count = self.query(
            "SELECT COUNT(*) FROM case_code_domain d "
            "LEFT JOIN case_definition c ON c.case_baseline_id=d.case_baseline_id "
            "AND c.case_id=d.case_id AND c.subset_baseline_id=d.subset_baseline_id "
            "LEFT JOIN catalogue_code k ON k.subset_baseline_id=d.subset_baseline_id AND k.code=d.code "
            "WHERE c.case_id IS NULL OR k.code IS NULL"
        )[0][0]
        self.assertEqual(orphan_count, 0)
        self.connection.rollback()

        cursor = self.connection.cursor()
        try:
            with self.assertRaises(MySQLError) as raised:
                cursor.execute(
                    "INSERT INTO case_code_domain "
                    "(case_baseline_id, case_id, subset_baseline_id, code, is_acceptable) "
                    "VALUES (%s, %s, %s, %s, %s)",
                    ("CASEBASE-0.1", "CASE-001", "SUBSET-0.1", "J99.99", 0),
                )
            self.assertEqual(raised.exception.errno, 1452)
        finally:
            self.connection.rollback()
            cursor.close()

        self.assertEqual(self.query("SELECT COUNT(*) FROM case_code_domain")[0][0], 14)


if __name__ == "__main__":
    unittest.main()
