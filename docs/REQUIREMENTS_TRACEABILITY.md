# Requirements traceability audit

**Purpose:** `REQ-TRC-01` requires that "every mandatory implemented
requirement has at least one downstream implementation/model destination and
verification reference or an explicitly declared gap." This document is
that audit: every `Accepted`/`Scope constraint` entry in
`chapter3_requirements_catalogue.md` (catalogue version 0.5), checked
against what actually exists in the repository today, not what was planned.
**Not the principal verification run:** this confirms a verification
*destination* exists and produces a genuine result; it is not itself
`REQ-VER-05`'s final conformance report, which belongs to the freeze
procedure (§3 below).
**Companion documents:** [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) §12
has an architecture-centric traceability table; this document is
requirement-centric and exhaustive across all 31 `REQ-*` entries, which
also makes it a ready draft for the appendix `REQ-VER-07` asks for.

## 1. How to read this table

- **✅ Verified** — the planned-verification technique in the catalogue was
  actually carried out against the current implementation, with a concrete,
  checkable pointer (file, test, or doc section).
- **🕓 Deferred to freeze** — correctly *not yet done*, because the
  requirement is specifically about the principal verification/freeze
  procedure (`chapter3_test_catalogue.md` §11, brief §20 Phase 6), which has
  not started. Not a gap in the implementation phase.
- **📄 Thesis-text scope** — the acceptance criterion is about how the
  thesis document itself is written (claim framing, main-text vs. appendix
  placement), not about anything in this repository. Verifiable only by
  reading the thesis, not the code.

A requirement audited here as "Verified" was previously satisfied by work
already described in [CHANGELOG.md](CHANGELOG.md) — this document does not
duplicate that reasoning, only the pointer to it.

## 2. Full audit

### 2.1 Intended use and claim boundaries (catalogue §4)

| ID | Status | Evidence |
|---|---|---|
| `REQ-SCP-01` | ✅ Verified | `prototype_baseline_0_1/baseline_manifest.json` (edition, checksum); `CASEBASE-0.2` contains only synthetic cases (`chapter3_reference_case_coverage_plan.md` §4). |
| `REQ-SCP-02` | ✅ Verified | No diagnosis/CDS/reporting/reimbursement code path exists anywhere in `app/src/`; intended-use disclaimer rendered in `CaseList` (`app/frontend/src/App.jsx`), confirmed on-screen in the Selenium/browser walkthrough. |
| `REQ-SCP-03` | 📄 Thesis-text scope | This repository's own documentation maintains the boundary (`DEVELOPMENT_DOCUMENTATION.md` §2.2); whether the thesis text itself does is not something a repository audit can check. |

### 2.2 Authoritative data and prototype subset (catalogue §5)

| ID | Status | Evidence |
|---|---|---|
| `REQ-DAT-01` | ✅ Verified | All core + contextual source checksums reproduced and matched the register exactly (`CHANGELOG.md`, 2026-08-07 "Application layer stood up" entry). |
| `REQ-DAT-02` | ✅ Verified | `prototype_baseline_0_1/scripts/prepare_subset.py` traces every record to DIAGLIST; `RuleMap`/`RuleStatus`/`RuleDepth`/`RuleSpec` docblocks cite `SRC-AT-DOC-2026` page locators for every semantic rule DIAGLIST itself doesn't encode. |
| `REQ-DAT-03` | ✅ Verified | All 13 `SUBSET-0.1` records are used: the `J44.0`/`J44.1` families by `CASE-001`/`002`/`005`/`006`/`007`; `Z01.6` by `CASE-003`/`004`/`008` (`chapter3_reference_case_coverage_plan.md` §3-§4). No unused record. |
| `REQ-DAT-04` | ✅ Verified | `TEST-DAT-01`: `prepare_subset.py --check-existing` and `validate_baseline.py` both pass against the real frozen workbook (`CHANGELOG.md`). |
| `REQ-DAT-05` | ✅ Verified | `RuleStatus::matches()` (`app/src/Rules/RuleStatus.php`) takes exactly `marker`/`role`/`setting`/`lkfScored`; `TEST-STATUS-01` (`RuleStatusTest.php`) plus `CASE-003`/`004`/`008` cover permitted, prohibited-outpatient, and prohibited-inpatient branches; no extramural code path exists. |

### 2.3 Case, rule, and feedback model (catalogue §6)

| ID | Status | Evidence |
|---|---|---|
| `REQ-MOD-01` | ✅ Verified | Every `Rules/*.php` predicate takes only `CaseFacts`/`CatalogueRecord` fields (`IMPLEMENTATION_SPECIFICATION.md` §3.2); no rule queries a database or infers an unrepresented fact. |
| `REQ-MOD-02` | ✅ Verified | `case_definition`/`case_code_domain` schema (one case, many code relations) mirrored 1:1 by `CASEBASE-0.2`/`RCBASE-0.2` CSVs. |
| `REQ-RUL-01` | ✅ Verified | Every `RULE-*` has one PHP class with a docblock citing its rule ID, and an entry in `chapter3_rule_catalogue.md` §5; `IMPLEMENTATION_SPECIFICATION.md` §3.2 is the consolidated trace. |
| `REQ-RUL-02` | ✅ Verified | `RuleSpec.php` is a positive predicate (not "whatever isn't correct/incorrect"); `TEST-SPEC-01` (`RuleSpecTest.php`) exercises it in isolation. |
| `REQ-RUL-03` | ✅ Verified (by documented absence) | No frozen case currently declares more than one accepted code; `chapter3_reference_case_coverage_plan.md` §6 explicitly records this as "deliberately excluded and documented," which is itself what the acceptance criterion asks for. |
| `REQ-RUL-04` | ✅ Verified | `Rules/Precedence.php` + `TEST-PREC-01` (`PrecedenceTest.php`, 6 vectors including all-three-hard-rules-matching in every iteration order). |
| `REQ-RUL-05` | ✅ Verified | `RuleGate.php` + `TEST-GATE-01` (`RuleGateTest.php`) + `TEST-API-01` (`EvaluationApiTest.php`): outside-subset/undefined-relation/malformed input all return a non-classified result, never `incorrect`. |
| `REQ-FBK-01` | ✅ Verified | `EvaluationResult` always carries `determiningRule`/`matchedRules` even when the UI doesn't render them; `TEST-RC-01` (18/18 rows) and `TEST-E2E-01` both confirm class+criterion+explanation reach the boundary a learner/verifier actually uses. |
| `REQ-FBK-02` | ✅ Verified | `RuleSpec`'s explanation payload always includes `expected_code` + `improvement_direction`; `RC-001-06`/`RC-002-06` assert this via `TEST-RC-01`. |

### 2.4 Interaction, architecture, and implementation (catalogue §7)

| ID | Status | Evidence |
|---|---|---|
| `REQ-INT-01` | ✅ Verified | `TEST-E2E-01` (`LearnerWorkflowTest.php`) is exactly this end-to-end path, browser-driven, no manual intervention; `TEST-API-01` confirms array-of-codes is rejected, not aggregated. |
| `REQ-ARC-01` | ✅ Verified | Layering in `DEVELOPMENT_DOCUMENTATION.md` §5.1; `TEST-ARC-01` (`ArchitectureIsolationTest.php`) asserts both the schema and the PHP source itself never reference the verification oracle. |
| `REQ-ARC-02` | ✅ Verified | `TEST-DET-01` (`DeterminismTest.php`): repeated correct/suboptimal/incorrect/gate-failure requests against an unchanged baseline return byte-identical bodies. |
| `REQ-IMP-01` | ✅ Verified | Stack matches (React/PHP/MySQL/Python); the two infrastructure deviations (MySQL version pinning, `Dockerfile` location) are recorded with rationale in `DEVELOPMENT_DOCUMENTATION.md` §10.1-§10.2, not silent. |
| `REQ-DOC-01` | ✅ Verified | `docs/USER_GUIDE.pdf` covers installation and operation; `docs/DEVELOPMENT_DOCUMENTATION.md` and `docs/IMPLEMENTATION_SPECIFICATION.md` trace one response end to end (§3 of the specification) and cover architecture, data structures, rule responsibility, and the native AMD64/ARM64 distribution contract. |

### 2.5 Traceability and configuration control (catalogue §8)

| ID | Status | Evidence |
|---|---|---|
| `REQ-TRC-01` | ✅ Verified | This document *is* the traceability-matrix audit the acceptance criterion asks for; every row below either has a destination or a declared reason it's deferred. |
| `REQ-CFG-01` | 🕓 Deferred to freeze | No git commit has been pinned and no baseline has been promoted to `1.0`; this is the actual freeze step (brief §20 Phase 6), which has not been requested. |

### 2.6 Reference-suite and verification requirements (catalogue §9)

| ID | Status | Evidence |
|---|---|---|
| `REQ-VER-01` | ✅ Verified | `chapter3_reference_case_coverage_plan.md` §1.1/§2 states the selection criteria and records the material change from the `CASEPLAN-0.1` estimate explicitly, rather than silently. |
| `REQ-VER-02` | ✅ Verified | `chapter3_reference_case_coverage_plan.md` §6 coverage matrix: no row is an unexplained gap after the pre-freeze expansion; the one intentionally-excluded dimension (`acceptable alternative`) is declared, not counted as covered. |
| `REQ-VER-03` | ✅ Verified | For `CASE-005`-`008`, the expected suffix/target was derived from `RULEBASE-0.1`'s documented FEV1 intervals *before* running the evaluator against them (not observed from output and then written down) — the CSV rows were authored, then the PHP suite was run to confirm the already-implemented `RuleMap`/`RuleCorrect` matched. `TEST-ARC-01` confirms the runtime never reads the oracle regardless. |
| `REQ-VER-04` | ✅ Verified | `IMPLEMENTATION_SPECIFICATION.md` §7 test-inventory table maps every `TEST-*` to a real file; nothing is a "justified omission" anymore now that `TEST-E2E-01`/`02` are implemented. |
| `REQ-VER-05` | 🕓 Deferred to freeze | The conformance *categories* already exist (brief §22); the *final test report* applying them is the principal verification run, not yet performed. |
| `REQ-VER-06` | ✅ Verified as ongoing practice; final log is a freeze-phase artefact | Every deviation hit during this implementation phase (MySQL 26.7.0/volume incompatibility, arm64 Selenium image, two test-authoring bugs) is logged in `CHANGELOG.md` with cause and resolution, and every affected test was rerun after the fix — the mechanism `REQ-VER-06` asks for is demonstrably in use, not just described. |
| `REQ-VER-07` | ✅ Verified (data side); 📄 Thesis-text scope (main-text side) | `RCBASE-0.2`'s 18 rows carry every mandatory field this requirement lists (case ID, description, accepted set, submitted code, class, pattern, rationale, catalogue version) — ready to become the appendix table. Which cases get worked examples in the main text is a thesis-writing decision, not a repository one. |

## 3. What this audit found

**No undeclared gaps.** Every `Accepted`/`Scope constraint` requirement
either has verified evidence or is correctly deferred to a freeze procedure
that has not been requested yet (`REQ-CFG-01`, the final-report half of
`REQ-VER-05`) or depends on the thesis document itself rather than the
repository (`REQ-SCP-03`, the main-text half of `REQ-VER-07`).

**One stale cross-reference fixed as a side effect of this audit:**
`chapter3_requirements_catalogue.md` still named `CASEPLAN-0.1`/`CASEBASE-0.1`/`RCBASE-0.1`
and "four base cases and fourteen atomic response variants" in its header
and freeze-criteria section, left over from before the pre-freeze coverage
review. Corrected to `CASEPLAN-0.2`/`CASEBASE-0.2`/`RCBASE-0.2` and
"eight/eighteen" — a pointer correction, not a change to any requirement's
substance, so the catalogue version (0.5) was not bumped for it. Logged in
[CHANGELOG.md](CHANGELOG.md).

**What remains before `REQBASE-1.0`/`CASEBASE-1.0`/`RULEBASE-1.0`/`PROTOBASE-1.0`
can be frozen** (catalogue §12, unchanged by this audit): `OPEN-RQ-01`
(final research-question wording) and `OPEN-EVAL-01` (whether independent
domain-expert review is required) remain open, and both are explicitly
supervisor-level decisions, not implementation gaps.
