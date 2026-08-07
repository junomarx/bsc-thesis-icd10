"""Database-independent contract checks for the PROTOBASE-0.1 runtime inputs.

These tests intentionally do not stand in for TEST-DAT-02. They verify the
loader's input boundary and normalized model before a real MySQL integration
run is available.
"""

from __future__ import annotations

import sys
import unittest
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent.parent
SCRIPTS_DIR = BASE_DIR / "scripts"
sys.path.insert(0, str(SCRIPTS_DIR))

from runtime_data import RUNTIME_FILES, load_runtime_dataset  # noqa: E402


class RuntimeContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.dataset = load_runtime_dataset(BASE_DIR)

    def test_runtime_input_allowlist_excludes_verification_oracle(self) -> None:
        self.assertEqual(
            set(RUNTIME_FILES.values()),
            {
                "baseline_manifest.json",
                "data/subset_0_1.csv",
                "data/cases_0_1.csv",
                "data/case_code_domain_0_1.csv",
            },
        )
        self.assertTrue(all("verification" not in path for path in RUNTIME_FILES.values()))

    def test_normalized_runtime_counts(self) -> None:
        self.assertEqual(len(self.dataset.catalogue), 13)
        self.assertEqual(len(self.dataset.cases), 4)
        self.assertEqual(len(self.dataset.case_code_domain), 14)

    def test_acceptable_sets_are_exact(self) -> None:
        accepted: dict[str, set[str]] = {}
        for _, case_id, _, code, is_acceptable in self.dataset.case_code_domain:
            if is_acceptable:
                accepted.setdefault(case_id, set()).add(code)

        self.assertEqual(accepted.get("CASE-001", set()), {"J44.02"})
        self.assertEqual(accepted.get("CASE-002", set()), {"J44.12"})
        self.assertEqual(accepted.get("CASE-003", set()), {"Z01.6"})
        self.assertEqual(accepted.get("CASE-004", set()), set())

    def test_case_004_is_only_verification_only_case(self) -> None:
        verification_only = {row[1] for row in self.dataset.cases if row[9] == "verification_only"}
        self.assertEqual(verification_only, {"CASE-004"})

    def test_runtime_digest_is_stable_for_current_baseline(self) -> None:
        self.assertEqual(
            self.dataset.canonical_digest(),
            "ca24056250f1a327c278613280178216bb7a69cf502bec65cada77fa09d894aa",
        )


if __name__ == "__main__":
    unittest.main()
