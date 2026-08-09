"""Database-independent MODELBASE-0.2 runtime-boundary tests.

These tests are implementation checks for the input contract only. They do
not substitute for a live MySQL persistence execution or RCBASE evaluation.
"""

from __future__ import annotations

import json
import unittest
from collections import Counter
from pathlib import Path

from apply_mysql_schema_0_2 import EXPECTED_TABLES, _statements
from runtime_data_0_2 import load_runtime_dataset


BASE_DIR = Path(__file__).resolve().parent.parent


class RuntimeContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.dataset = load_runtime_dataset(BASE_DIR)
        cls.manifest = cls.dataset.manifest

    def test_runtime_allowlist_is_exact_and_oracle_free(self) -> None:
        paths = {
            path
            for relatives in self.manifest["runtime_files"].values()
            for path in relatives
        }
        self.assertEqual(paths, set(self.manifest["runtime_file_sha256"]))
        self.assertTrue(all(path.startswith("data/") for path in paths))
        self.assertTrue(all("reference_responses" not in path for path in paths))
        self.assertFalse(self.manifest["verification_oracle_runtime_access"])
        self.assertNotIn("reference_response_baseline_id", self.manifest)

    def test_normalized_component_counts(self) -> None:
        expected = self.manifest["expected_counts"]
        self.assertEqual(len(self.dataset.catalogue), expected["catalogue"])
        self.assertEqual(len(self.dataset.patients), expected["patients"])
        self.assertEqual(len(self.dataset.patient_context), expected["patient_context"])
        self.assertEqual(len(self.dataset.questions), expected["questions"])
        self.assertEqual(len(self.dataset.question_facts), expected["question_facts"])
        self.assertEqual(len(self.dataset.question_code_domain), expected["question_code_domain"])
        self.assertEqual(len(self.dataset.question_relation_facts), expected["question_relation_facts"])
        self.assertEqual(len(self.dataset.question_options), expected["question_options"])

        context_types = {row[3] for row in self.dataset.patient_context}
        self.assertIn("information_boundary", context_types)
        self.assertLessEqual(
            context_types,
            {
                "documented_condition", "self_reported_history", "current_exam_finding",
                "social_context", "information_boundary", "other",
            },
        )

        accepted_by_question: dict[str, set[str]] = {}
        for row in self.dataset.question_code_domain:
            if row[4] == "accepted_reference":
                accepted_by_question.setdefault(row[1], set()).add(row[3])
        for row in self.dataset.question_code_domain:
            if row[4] == "less_specific_supported":
                self.assertIn(row[6], accepted_by_question[row[1]])

    def test_patient_difficulty_and_question_distribution(self) -> None:
        difficulty = {row[1]: row[7] for row in self.dataset.patients}
        self.assertEqual(
            difficulty,
            {
                "PATIENT-001": "foundational",
                "PATIENT-002": "foundational",
                "PATIENT-003": "foundational",
                "PATIENT-004": "involved",
                "PATIENT-005": "involved",
                "PATIENT-006": "involved",
            },
        )
        learner = [row for row in self.dataset.questions if row[6] == "learner_visible"]
        counts = Counter(row[3] for row in learner)
        self.assertEqual(
            counts,
            Counter({
                "PATIENT-001": 3,
                "PATIENT-002": 3,
                "PATIENT-003": 3,
                "PATIENT-004": 5,
                "PATIENT-005": 5,
                "PATIENT-006": 6,
            }),
        )
        self.assertGreater(max(counts.values()), 3)

    def test_learner_options_and_none_of_above_controls(self) -> None:
        options_by_question: dict[str, list[tuple]] = {}
        for row in self.dataset.question_options:
            options_by_question.setdefault(row[1], []).append(row)
        self.assertEqual(len(options_by_question), 25)
        self.assertTrue(
            all(sum(row[3] == "none_of_above" for row in rows) == 1 for rows in options_by_question.values())
        )

        accepted = {
            (row[1], row[3])
            for row in self.dataset.question_code_domain
            if row[4] == "accepted_reference"
        }
        displayed = {
            (row[1], row[5])
            for row in self.dataset.question_options
            if row[3] == "code"
        }
        no_displayed_reference = {
            question_id
            for question_id in options_by_question
            if not any(qid == question_id and (qid, code) in displayed for qid, code in accepted)
        }
        self.assertEqual(no_displayed_reference, {"Q-004-05", "Q-005-05"})

    def test_copd_learner_content_is_confined_to_one_patient(self) -> None:
        patient_by_question = {row[1]: row[3] for row in self.dataset.questions if row[6] == "learner_visible"}
        j44_questions = {
            row[1]
            for row in self.dataset.question_code_domain
            if row[1].startswith("Q-") and str(row[3]).startswith("J44")
        }
        self.assertEqual(j44_questions, {"Q-001-01"})
        self.assertEqual({patient_by_question[qid] for qid in j44_questions}, {"PATIENT-001"})

    def test_legacy_regression_fixture_shape_is_preserved(self) -> None:
        verification = [row for row in self.dataset.questions if row[6] == "verification_only"]
        self.assertEqual(len(verification), 8)
        self.assertEqual({row[8] for row in verification}, {f"CASE-{i:03d}" for i in range(1, 9)})
        legacy_relations = [row for row in self.dataset.question_code_domain if row[1].startswith("VQ-")]
        self.assertEqual(len(legacy_relations), 18)
        self.assertFalse(any(row[1].startswith("VQ-") for row in self.dataset.question_options))

    def test_canonical_runtime_digest_is_stable_for_candidate(self) -> None:
        self.assertEqual(
            self.dataset.canonical_digest(),
            "2b20a3f336ed3106749eb34020a52499c22800a72e456d992288ae073f0d1f51",
        )

    def test_schema_has_nine_runtime_tables_and_no_oracle_columns(self) -> None:
        schema = (Path(__file__).with_name("mysql_schema_0_2.sql")).read_text(encoding="utf-8")
        statements = _statements(schema)
        self.assertEqual(len(statements), len(EXPECTED_TABLES))
        self.assertEqual(sum("CREATE TABLE" in statement.upper() for statement in statements), 9)
        executable_sql = "\n".join(statements).lower()
        self.assertIn("information_boundary", executable_sql)
        for forbidden in (
            "expected_class", "expected_rule", "expected_criterion",
            "reference_response_baseline_id", "required_explanation_elements",
        ):
            self.assertNotIn(forbidden, executable_sql)


if __name__ == "__main__":
    unittest.main()
