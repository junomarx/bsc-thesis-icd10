"""Live MySQL assertions for the MODELBASE-0.2 persistence candidate.

The suite skips cleanly when mysql-connector-python or database configuration
is unavailable. A skipped run is not evidence that MySQL persistence passed.
"""

from __future__ import annotations

import os
import unittest
from dataclasses import replace
from pathlib import Path

try:
    import mysql.connector  # type: ignore[import-not-found]
    from mysql.connector import Error as MySQLError  # type: ignore[import-not-found]
except ModuleNotFoundError:  # pragma: no cover - environment-dependent boundary
    mysql = None  # type: ignore[assignment]
    MySQLError = Exception  # type: ignore[assignment,misc]

from load_mysql_0_2 import load_dataset
from runtime_data_0_2 import load_runtime_dataset


BASE_DIR = Path(__file__).resolve().parent.parent
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


def _configured() -> bool:
    return mysql is not None and bool(os.environ.get("ICD_DB_NAME")) and bool(os.environ.get("ICD_DB_USER"))


@unittest.skipUnless(_configured(), "live MySQL connector/database configuration not available")
class MySQLPersistenceTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        assert mysql is not None
        cls.connection = mysql.connector.connect(
            host=os.environ.get("ICD_DB_HOST", "127.0.0.1"),
            port=int(os.environ.get("ICD_DB_PORT", "3306")),
            database=os.environ["ICD_DB_NAME"],
            user=os.environ["ICD_DB_USER"],
            password=os.environ.get("ICD_DB_PASSWORD", ""),
            charset="utf8mb4",
            autocommit=False,
            connection_timeout=10,
        )
        cls.dataset = load_runtime_dataset(BASE_DIR)

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

    def test_schema_contains_runtime_tables_only(self) -> None:
        tables = {
            row[0]
            for row in self.query(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()"
            )
        }
        self.assertEqual(tables, EXPECTED_TABLES)
        columns = {
            str(row[0]).lower()
            for row in self.query(
                "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE()"
            )
        }
        self.assertFalse(any(name.startswith("expected_") for name in columns))
        self.assertNotIn("reference_response_baseline_id", columns)
        self.assertNotIn("determining_rule", columns)

    def test_persisted_counts_and_candidate_identity(self) -> None:
        expected = {
            "catalogue_code": 99,
            "patient_definition": 6,
            "patient_context_item": 32,
            "coding_question": 33,
            "question_fact": 88,
            "question_code_domain": 118,
            "question_relation_fact": 182,
            "question_option": 120,
            "prototype_baseline": 1,
        }
        for table, count in expected.items():
            self.assertEqual(self.query(f"SELECT COUNT(*) FROM {table}")[0][0], count)
        identity = self.query(
            "SELECT prototype_baseline_id, model_baseline_id, subset_baseline_id, "
            "patient_baseline_id, question_baseline_id, diaglist_sha256 FROM prototype_baseline"
        )[0]
        self.assertEqual(
            identity,
            (
                "PROTOBASE-1.0", "MODELBASE-0.2", "SUBSET-0.2", "PATIENTBASE-0.1",
                "QUESTIONBASE-0.1",
                "66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b",
            ),
        )

    def test_question_distribution_and_visibility_boundary(self) -> None:
        visibility = dict(self.query(
            "SELECT intended_use, COUNT(*) FROM coding_question GROUP BY intended_use"
        ))
        self.assertEqual(visibility, {"learner_visible": 25, "verification_only": 8})
        counts = dict(self.query(
            "SELECT patient_id, COUNT(*) FROM coding_question "
            "WHERE intended_use='learner_visible' GROUP BY patient_id ORDER BY patient_id"
        ))
        self.assertEqual(
            counts,
            {
                "PATIENT-001": 3, "PATIENT-002": 3, "PATIENT-003": 3,
                "PATIENT-004": 5, "PATIENT-005": 5, "PATIENT-006": 6,
            },
        )
        verification_options = self.query(
            "SELECT COUNT(*) FROM question_option o JOIN coding_question q "
            "ON q.question_baseline_id=o.question_baseline_id AND q.question_id=o.question_id "
            "WHERE q.intended_use='verification_only'"
        )[0][0]
        self.assertEqual(verification_options, 0)

    def test_none_of_above_and_copd_boundaries(self) -> None:
        none_count = self.query(
            "SELECT COUNT(*) FROM question_option WHERE option_kind='none_of_above'"
        )[0][0]
        self.assertEqual(none_count, 25)
        j44_patients = {
            row[0]
            for row in self.query(
                "SELECT DISTINCT q.patient_id FROM question_code_domain d "
                "JOIN coding_question q ON q.question_baseline_id=d.question_baseline_id "
                "AND q.question_id=d.question_id "
                "WHERE q.intended_use='learner_visible' AND d.code LIKE 'J44%'"
            )
        }
        self.assertEqual(j44_patients, {"PATIENT-001"})

    def test_loader_identical_reimport_is_no_op_and_conflict_is_rejected(self) -> None:
        self.assertEqual(load_dataset(self.connection, self.dataset), "no_op")
        changed_patients = list(self.dataset.patients)
        first = list(changed_patients[0])
        first[2] = "Conflicting Name"
        changed_patients[0] = tuple(first)
        conflicting = replace(self.dataset, patients=tuple(changed_patients))
        with self.assertRaisesRegex(RuntimeError, "version conflict in patients"):
            load_dataset(self.connection, conflicting)
        self.assertEqual(self.query("SELECT COUNT(*) FROM patient_definition")[0][0], 6)

    def test_foreign_key_rejects_outside_domain_option_without_mutation(self) -> None:
        before = self.query("SELECT COUNT(*) FROM question_option")[0][0]
        cursor = self.connection.cursor()
        try:
            with self.assertRaises(MySQLError):
                cursor.execute(
                    "INSERT INTO question_option "
                    "(question_baseline_id, question_id, option_id, option_kind, "
                    "subset_baseline_id, code, canonical_position) "
                    "VALUES (%s, %s, %s, %s, %s, %s, %s)",
                    (
                        "QUESTIONBASE-0.1", "Q-001-01", "INVALID-OUTSIDE-DOMAIN", "code",
                        "SUBSET-0.2", "I10", 99,
                    ),
                )
        finally:
            self.connection.rollback()
            cursor.close()
        self.assertEqual(self.query("SELECT COUNT(*) FROM question_option")[0][0], before)


if __name__ == "__main__":
    unittest.main()
