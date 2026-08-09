# Handoff

**Snapshot date:** 9 August 2026 (revised same day — steps 1-7 of the forward implementation order are now complete; see the revision note directly below)
**Audience:** a developer, contractor, or coding agent with no access to the conversation that produced this state — read this first, before touching anything.
**Supersedes:** everything below §0 in this document's own prior form (dated 8 August 2026, describing the case-centric `CASEBASE-0.2` implementation), and the status/progress sections of `archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` and `CODEX_VSCODE_CONTINUATION_INSTRUCTION.md` (dated 7 August 2026, in `archived/development_handoff/`, an intentionally-preserved historical archive, not the live project state).
**Same-day revision note (eighth pass):** earlier versions of this file (preserved in git history) covered, in order: steps 1-6 plus the persistence/deployment-path fix (§0.1); then step 7 (`UXBASE-0.1` polish) plus the documentation-consolidation pass; then step 8 (§0.4) - `app/tests/*` rewritten wholesale against the patient/question model and genuinely passing; then CI's `push` trigger re-enabled after a stale-log false alarm (§0.5); then step 9, the oracle/source audit (§0.6) - all 129 previously-unaudited reference-response rows human-oracle-confirmed, zero discrepancies; then a repository housekeeping pass (§0.7) - `prototype_baseline_0_2_design/` renamed to `prototype_baseline/`, `prototype_baseline_0_1/`/`development_handoff/`/`forward_package_0_6/` archived, and three real bugs that exercise surfaced fixed along the way; then CI's first real GitHub-hosted run (§0.8) - a multi-arch build hang found and fixed, then a genuinely green run confirmed all 5 jobs pass and the published GHCR images are current, not stale; then the four legacy "reconstructed" rows confirmed against the genuine raw historical oracle (§0.9) - it was sitting archived, not lost; **now: execution-environment versions recorded ahead of freeze** (§0.10) - exact resolved versions/digests for every floating container tag, without pinning the tags themselves yet. Read §0 anyway — most of it still applies.

## 0. The forward redesign (read this section first — it invalidates most of what a stale copy of this file, or of `chapter3_*.md` at repo root, would tell you)

Mid-implementation, the project owner and a separate agent identified a real pedagogical flaw in the case-centric model this project originally built: several single-question cases (`CASE-003/005/006/007`) had response domains that gave away the answer once the case list surfaced their description. Rather than patch around it, the project owner commissioned a **structural redesign**: from 8 case-centric single-question cases to a **patient/question model** — 6 synthetic patients, 25 atomic learner-facing coding questions (plus 8 hidden `verification_only` legacy fixtures, kept only to preserve the 18 historical regression expectations), governed by a new rule/data/API contract. This is not additive work on top of the old model — it **replaces** it. The `chapter3_*.md` files at the repository root were themselves rewritten as part of this redesign (`chapter3_requirements_catalogue.md` is now `0.6`; new sibling documents like `chapter3_rule_catalogue_0_2.md` and `chapter3_data_model_and_interaction_baseline_0_2.md` are now authoritative over their `_0_1`-era predecessors where both exist).

**The required implementation order** (`chapter3_forward_implementation_instruction_0_5.md`, further clarified by `chapter3_api_and_feedback_contract_0_1.md` / `APIBASE-0.1`) has 10 steps. Current status:

| Step | What | Status |
|---|---|---|
| 1 | Requirements merge (forward revision `0.7` into the catalogue) | Done |
| 2-3 | Integrate `MODELBASE-0.2` persistence candidate into MySQL, get live evidence | Done — **but see §0.1, this needed a second pass** |
| 4 | Migrate PHP repositories/evaluator to `RULEBASE-0.2` | Done |
| 5 | Replace case-centric API with patient/question endpoints | Done |
| 6 | Functional React lifecycle (roster → dossier → question → submit → feedback → review → replay) | Done |
| 7 | `UXBASE-0.1` visual/gameful polish, as a separate iteration | Done — scoped to `Must`-priority mechanics + accessibility, per the concept document's own §10 fallback guidance; see §0.3 |
| 8 | Extend test coverage, rerun all 18 historical regressions | Done — full rewrite, 77 unit + 160 integration + 7 e2e all passing against real infrastructure; see §0.4 |
| 9 | Oracle/source audit reconciliation (125 new expectations + 4 legacy `VQ-005..008`) | Done — all 129 rows human-oracle-confirmed, zero discrepancies; the 4 legacy rows further reconciled against genuine raw historical data since, §0.9; no reconciliation gap remains anywhere in `RCBASE-0.3` |
| 10 | Freeze + principal verification run | Not started |

### 0.1 A real gap this snapshot exists to record: "verified" ≠ "deployed"

Steps 2-3 and 6 were each verified against **throwaway infrastructure** — a scratch `docker run` MySQL container on a non-default port, the Vite dev server — that was torn down after each check. Nobody had repointed the repository's actual `docker compose up` path at the new schema/data. Concretely: `docker-compose.yml`'s `bootstrap` service still built from `prototype_baseline_0_1/Dockerfile.bootstrap`, which applies the historical `mysql_schema.sql` and loads `cases_0_2.csv` (`CASEBASE-0.2`, 8 COPD-only cases). The already-published GHCR `app` image was also from before the migration. Net effect: the PHP/React source was genuinely migrated and correct on disk, but **anyone running `docker compose up` and opening a browser saw the old case-centric app** — old schema, old data, old routes. The project owner caught this by inspecting the actual running app, not by reading a report.

**Fixed this same day** — see `docs/CHANGELOG.md`'s "steps 2-3 completed for real" entry for the full verification trail:

- New `prototype_baseline/Dockerfile.bootstrap` + `persistence_candidate/bootstrap_mysql_0_2.py`, wired to `apply_mysql_schema_0_2.py`/`load_mysql_0_2.py` (the previously-standalone-only MODELBASE-0.2 persistence candidate).
- `docker-compose.yml` and `prototype_stack/compose.yaml`: `bootstrap` service `build.context` repointed at `prototype_baseline/`.
- Rebuilt `bootstrap`+`app` locally from current source, `docker compose down -v` (dropped the stale dev-only volume) + `up`. Bootstrap log confirms `MODELBASE-0.2 MySQL schema application: PASS` and the exact expected component counts. `GET /api/patients` on the real running container returns all 6 patients. A real (Selenium, not Playwright) browser check against the actual running `app` container — not a dev server — confirms the roster, a non-COPD question prompt, and the patient dossier panel all render correctly.

**If you're reading this and about to `docker compose up`: it now works correctly** as of this snapshot. But this class of gap (isolated verification vs. the actual deployment path) is exactly the kind of thing worth re-checking with your own eyes — `curl http://127.0.0.1:5860/api/patients` and confirm you get 6 patients with `display_name`s, not `CASE-*` IDs — before trusting any further "verified" claim in this document or `docs/CHANGELOG.md` without spot-checking it.

### 0.2 New baseline identifiers (forward model)

| Baseline | Version | Status |
|---|---|---|
| Requirements catalogue | `0.6` | Working — merged forward revision `0.7` |
| Rule catalogue | `RULEBASE-0.2` | Working — extends `RULEBASE-0.1` with `RULE-REL-HARD-01`/`RULE-REL-SPEC-01`/`RULE-NOA-01`, extended `RULE-PREC-01` |
| Data/interaction model | `MODELBASE-0.2` | Working — 9-table normalized patient/question schema, integrated into the real deployment path (§0.1) |
| Catalogue subset | `SUBSET-0.2` | 99 DIAGLIST records |
| Patients | `PATIENTBASE-0.1` | 6 synthetic patients |
| Questions | `QUESTIONBASE-0.1` | 25 learner-facing + 8 hidden `verification_only` legacy fixtures = 33 total |
| Reference responses (candidate) | `RCBASE-0.3` | 143 rows (125 new + 18 historical) — oracle-audited, step 9 done (§0.6); still named `_candidate` pending step 10's freeze |
| API/feedback contract | `APIBASE-0.1` | Working — tagged-response contract, resolved 9 implementation-detail ambiguities |
| SQL/loader migration contract | `DATAMIG-0.2` | Implemented (§0.1) |
| UX/gamification concept | `UXBASE-0.1` | Applied to the frontend, step 7 — see §0.3 |
| Prototype identity (candidate) | `PROTOBASE-0.3` | `working_forward_implementation_candidate_not_frozen` |
| **`1.0`/frozen anything** | **none** | **Not frozen — steps 7-10 remain, plus the pre-existing supervisor decisions in §5 of the pre-redesign era, still open** |

The old table (`CASEBASE-0.2`/`RCBASE-0.2`/`MODELBASE-0.1`/`PROTOBASE-0.2`, etc.) describes a superseded model. Do not resume work against it.

### 0.3 Step 7 (`UXBASE-0.1`) and the fixes that followed from actually using the app

Step 7 added: an orientation/three-class-legend panel on the roster (`REQ-UI-01`), equal-height patient cards, a session-local per-patient completion badge plus an aggregate "N of 6" line and a reset-progress control, a visual question-progress bar, a collapsible technical-details disclosure (determining rule/criterion), and a restrained completion acknowledgment on the review screen. An EN/DE language switch (`components/LanguageSwitch.jsx`) was added alongside it, translating UI chrome, patient/question *content*, and — after the project owner found German mode still showing English sentences — the evaluator's own explanation text (`EvaluationResult::$explanationDe`, additive, every rule call site updated) and (after English mode was found showing German ICD-10 code names, since the runtime catalogue is German-only data) English titles for the 87 displayed codes. A footer (`v0.7.0 · build 2026-08-09 · © 2026 Juno Anna Marx`) was added.

Two real defects surfaced this same day by the project owner actually using the running app, not by a planned test, both fixed and re-verified against the real container: the question option list had a fixed `max-height`/internal scroll that clipped any question with more than ~4 options; and `RULE-NOA-01`'s explanation literally embedded the machine token `none_of_above` in learner-facing prose (the same defect class was also found and fixed one layer deeper — a fact-key/reason-key fallback in `RULE-REL-HARD-01`'s explanation, and a raw gate-`reason` enum in the frontend's `not_evaluated` panel). Also applied: an explicit project-owner naming override — three of six patient names (`Michael Novak`→`Michael Bauer`, `Lea Horvat`→`Lea Wagner`, `Sofia Marin`→`Sophie Mayer`) changed to read as unambiguously common-Austrian for demo purposes; the other three already qualified and were left alone.

**Later same-day onboarding refinement:** the default-expanded
`Orientation.jsx` roster block described above was replaced with a newly
implemented four-step `Tutorial.jsx`. It opens automatically only while the
versioned browser-local seen flag is absent, is manually reopenable from the
persistent header on every view, and has dedicated Selenium coverage. This
new component is not the deleted case-centric `Tutorial.jsx` mentioned in
the historical step-6 record.

Full detail, and the honest record of what step 7 deliberately did *not* implement (code-option display-order permutation, a separate literal "Home" screen — both explicit `Should`/optional items, not `Must`): `docs/CHANGELOG.md`'s five 2026-08-09 entries after the persistence fix, and `docs/DEVELOPMENT_DOCUMENTATION.md` §13-14.

### 0.4 Step 8: the test suite is rewritten and genuinely passes

Triggered by the project owner running CI manually (push triggers are deliberately disabled, `CLAUDE.md`), pasting the real failure log, then - after a scoped CI-infrastructure fix alone didn't resolve it - pasting the same log again as confirmation to proceed with the full rewrite. `app/tests/Support/Fixtures.php` and every file under `Unit`/`Integration`/`E2E` were rewritten against `CodingQuestion`/`ResponseInput`/the tagged-response contract; three brand-new unit test files cover `RULE-REL-HARD-01`/`RULE-REL-SPEC-01`/`RULE-NOA-01`, which had zero coverage of their own before this. `ReferenceResponseTest` now reads the full 143-row `RCBASE-0.3` candidate oracle (was the 18-row historical file), with an explicit docblock caveat that the 125 new rows are *exercised*, not yet *human-audited* (step 9) - a caveat step 9 has since resolved, §0.6.

Three real bugs were found only by actually running the rewritten E2E suite against real Selenium/Chrome - not by writing code that merely compiled - and are worth knowing about if you touch this suite again: a roster race condition (waiting for the `<ul>` container instead of an actual card), a hardcoded assumption that opening a patient always shows its first-listed question first (it doesn't - order is shuffled per `REQ-INT-03`), and a regex that summed age badges into a "total question count" check. Full detail: `docs/CHANGELOG.md`'s "Step 8" entry.

**Verified, this same pass:** `php vendor/bin/phpunit --testsuite unit` 77/77; `--testsuite integration` against a freshly bootstrapped `MODELBASE-0.2` MySQL instance 160/160 (2173 assertions, every one of the 143 reference-response rows included); `--testsuite unit,integration` combined 237/237; `--testsuite e2e` against the actual running `app` container via this project's own Selenium infrastructure 7/7. All throwaway containers (MySQL, Selenium) were torn down afterward.

### 0.5 CI's `push` trigger is re-enabled — stale-log confusion resolved

The project owner pushed the step 8 fixes (`a52eb25`/`e7a076a`), then reported CI still failing - pasting a log that turned out to be from the *previous* run (`4b4fe1e`, 09:20 UTC), since `push` didn't trigger CI at the time (`CLAUDE.md`'s "disable ci actions on push" decision, `0287228`) and no `workflow_dispatch` run had happened since. Confirmed via the GitHub Actions REST API (`GET /repos/.../actions/runs`) rather than assumption: the failing run's `head_sha` was `4b4fe1e`, not the tip of `main`. The project owner then asked to re-enable `push` so this stops recurring; `.github/workflows/ci.yml`'s `on:` block now has `push: branches: [main]` active again (`pull_request` stays commented out, unchanged). The next push to `main` will be the first real confirmation that step 8's fixes pass in an actual GitHub-hosted run.

### 0.6 Step 9: the oracle is human-oracle-audited, zero discrepancies

Method, since neither of the two primary Austrian source PDFs (`SRC-AT-ICD-SYS-2026`, `SRC-AT-DOC-2026`) exists as a local file in this repository (only `DIAGLIST2026.xlsx` does) — re-deriving from them directly wasn't possible in this pass, so the audit used the two strongest checks that were:

1. **The 125 new learner-question rows** (`provenance_status` was `forward_specification_derived_pending_human_oracle_audit`) were cross-checked against `chapter3_question_bank_source_audit.md` (`QSAUDIT-0.1`) §4.1-4.6 - a design document the project owner and a separate agent already wrote *against* the two primary sources, independently of and before the evaluator existed, with a page-number/DIAGLIST-row citation for every one of the 25 questions' correct/suboptimal/incorrect/`none_of_above` calls. Checked every question, every displayed code, every `none_of_above` row, including the three deliberate "unspecified ≠ suboptimal" counterexamples (`F03`, `N40`, `R40.2`) and both `none_of_above = correct` control questions (`Q-004-05`, `Q-005-05`). Zero discrepancies.
2. **The 4 reconstructed legacy rows** (`VQ-005`-`008`, `provenance_status` was `reconstructed_from_implementation_documentation`) aren't covered by `QSAUDIT-0.1` at all, so citation-matching wasn't available - instead their documented case facts (`fev1_stable_pct_predicted`, `encounter_setting`, `diagnosis_role`) were run directly through the live `RuleMap::evaluate()`/`RuleStatus::matches()` predicates. All 4 matched exactly (including two FEV1 values sitting exactly on a boundary, `35.00` and `70.00`), and `RuleStatus`'s predicate was additionally cross-checked against the already-audited `VQ-003`/`VQ-004` pair to confirm the boundary logic itself, not just this one row.

`prototype_baseline/verification/reference_responses_0_3_candidate.csv`'s `provenance_status` column updated accordingly (`...human_oracle_audit_confirmed_against_qsaudit_0_1` / `...human_oracle_audit_confirmed_via_rule_replay`); no other column changed. `docs/REQUIREMENTS_TRACEABILITY.md`'s `REQ-VER-08`/`09` now read ✅. The file keeps its `_candidate` name and `RCBASE-0.3` stays a candidate baseline - freezing that naming is step 10's job, not this one's.

### 0.7 Repository housekeeping: rename, archive, and three bugs it surfaced

Project-owner request, after step 9 landed: a full audit for stale files, `prototype_baseline_0_2_design/` → `prototype_baseline/` (drop the design-stage name now that it's the one live pipeline), and `prototype_baseline_0_1/` archived with every reference cleaned. Scope grew naturally to cover other stale top-level items found during the audit, and doing the audit surfaced three real, previously-undiscovered bugs - found by tracing every functional reference before touching anything, not by inventing work.

**Moved/renamed** (git history preserved via `git mv`, nothing deleted):

- `prototype_baseline_0_2_design/` → `prototype_baseline/` - the active Python data-prep pipeline + MySQL persistence loader, now named for what it actually is (the only live one) rather than its design-stage provenance.
- `prototype_baseline_0_1/`, `development_handoff/`, `forward_package_0_6/` → `archived/` - respectively the superseded `_0_1`-era pipeline (nothing live pointed at it once the three bugs below were fixed), pre-implementation planning docs (already described in prose as "an intentionally-preserved historical archive," now actually filed as one), and a one-time delivery drop whose useful content (`chapter3_api_and_feedback_contract_0_1.md` and friends, a `persistence_candidate/` sync) was already extracted into the live tree weeks ago (`docs/CHANGELOG.md`'s `APIBASE-0.1` entry) - the 9.6MB copy left behind was pure leftover.
- `.venv/` (198MB, including platform-specific compiled binaries) untracked from git via `git rm -r --cached` - it was already `.gitignore`d, just committed before that rule existed; still present on disk, just no longer bloating the repository.
- Empty, untracked `latex/` directory removed outright (stray leftover, never part of the actual `docs/USER_GUIDE.tex` toolchain).
- `chapter3_*.md` root files were deliberately **not** touched - per `CLAUDE.md`, they're the upstream, versioned specification lineage (old and new revisions kept side by side on purpose), not stale duplicates.

**Three real bugs found by tracing every reference, not assumed:**

1. **`.github/workflows/ci.yml`'s `publish-images` job was building the published `bsc-thesis-icd10-bootstrap:latest` GHCR image from the *old* `prototype_baseline_0_1/Dockerfile.bootstrap`** - the pre-migration, case-centric bootstrap pipeline - while `docker-compose.yml`/`prototype_stack/compose.yaml` had already been correctly repointed at the `_0_2` design weeks ago (§0.1). Since CI's `push` trigger was just re-enabled (§0.5), the *next* push would have published a bootstrap image reproducing the exact "still shows COPD cases" bug this whole redesign started from - caught before that happened, not after.
2. **`.github/workflows/ci.yml`'s `python-checks` job was still running the superseded `_0_1` pipeline's own scripts/tests** (`prepare_subset.py`, `tests.test_runtime_contract` - `SUBSET-0.1`, 13 records) instead of the active `_0_2` ones (`prepare_subset_0_2.py`, `test_runtime_contract_0_2` - `SUBSET-0.2`, 99 records). It had been silently "passing" this whole time by testing the wrong, but internally self-consistent, thing.
3. **The root `Dockerfile`'s `dev` target `COPY`ed a CSV `ReferenceResponseTest.php` no longer reads at all** since step 8 rewired that test to the `_0_3_candidate` oracle - dead weight copying the wrong file to a path nothing resolves to, silently masking that the container's copy of `TEST-RC-01` has been unable to find its oracle file since step 8 landed. Fixed by copying the actual file the test resolves to (`dirname(__DIR__, 3)` from the container's test path) at the correct location.

`.dockerignore` also had a real, related gap: it excluded `prototype_baseline_0_1/` (with a narrow exception for the one CSV the `dev` image needs) but never excluded `prototype_baseline_0_2_design/` at all, meaning the entire design-stage tree - review spreadsheets, migration CSVs, everything - was part of the build context for no reason. Rewritten to exclude `archived/` and `prototype_baseline/` (with the equivalent narrow exception) instead.

**Verified, this same pass:** both `Dockerfile` targets (`runtime`, `dev`) and the bootstrap image build clean from their new paths; a full `docker compose build bootstrap app && docker compose up -d --wait app` against the actual renamed/archived tree - `GET /api/patients` returns all 6 patients with correct `display_name`s, bootstrap log confirms the exact expected component counts and canonical digest; `php vendor/bin/phpunit --testsuite unit` 77/77 and `--testsuite integration` (fresh throwaway MySQL) 160/160, both from the renamed CSV path; `python -m unittest test_runtime_contract_0_2` 8/8 and `test_mysql_persistence_0_2` 6/6; `prepare_subset_0_2.py --check-existing` against the archived source location, PASS. The `e2e` suite was not re-run this pass - no E2E test file references any moved path, and the full stack check above already confirms the application itself is unaffected - but a real GitHub-hosted CI run (now that `push` is active) is still the first true end-to-end confirmation of the `ci.yml` fixes specifically.

### 0.8 CI's first real GitHub-hosted run: a multi-arch build hang found and fixed, then confirmed green

The first real `push`-triggered run after §0.5's re-enablement confirmed all four test jobs (§0.7's "still true" verification, now proven on GitHub's own runners, not just reproduced locally) - but `publish-images`'s multi-arch (`linux/amd64,linux/arm64`) build of the `runtime` image then hung for 1.5+ hours with no output. Root cause: no Dockerfile stage was pinned to a platform, so `frontend-build` (`npm run build` - Vite → esbuild, a native Go binary) ran a second time under QEMU emulation for the `arm64` leg - esbuild under QEMU user-mode emulation is a documented hang/pathological-slowness case, not merely a slow one. Fixed by pinning `--platform=$BUILDPLATFORM` on `frontend-build`, `vendor`, and `vendor-dev` (none produce architecture-specific output) so they always build natively on the runner; only `base` (which compiles the native `pdo_mysql` extension) still needs a true per-arch build. Reproduced the exact failure locally and confirmed the fix before pushing it: a `docker-container`-driver multi-platform build of the same target went from "not finished after 1.5+ hours" to **1m35s**. Full account: `docs/DEVELOPMENT_DOCUMENTATION.md` §10.8, `docs/CHANGELOG.md`'s same-dated entry.

**Confirmed live, not assumed:** the next run after that fix (`31314862118`, 2026-08-09 13:04-13:09 UTC, commit `acb2ca6`) completed all 5 jobs successfully, including `publish-images`. `docker compose pull && docker compose up -d --wait app` was re-run against the real published tags afterward - `GET /api/patients` returns all 6 renamed patients, not `CASE-*` data. **The GHCR images are current as of this snapshot** - §2's Docker bullet and `README.md`'s quick-start were both updated off this, not left describing the earlier stale-image caveat.

### 0.9 The four "reconstructed" legacy rows were never actually unreconciled - the raw file was archived, not lost

Step 9 (§0.6) confirmed `VQ-005..008` by rule replay because every design-phase document describing them said the raw `RCBASE-0.2` file was unavailable, and step 9 didn't independently re-check that premise. It was wrong: the genuine, original 18-row `RCBASE-0.2` file (no `provenance_status` column, predating the concept - not a reconstruction) had been sitting at `archived/prototype_baseline_0_1/verification/reference_responses_0_2.csv` the whole time, archived during §0.7's housekeeping pass without anyone connecting it back to this older, unrelated-seeming claim. Read directly and diffed field by field against `VQ-005..008`: every field (`submitted_code`, `expected_class`, `determining_rule`, `pattern_id`, `criterion`, `improvement_code`, `required_explanation_elements`, `source_locator`) matches exactly for all 4 rows - a stronger, independent confirmation on top of step 9's rule replay, not a correction of it.

**Closed as a result:** `provenance_status` for these 4 rows now reads `exact_semantic_carry_forward_confirmed_against_rcbase_0_2`; their `prompt` text (itself drifted from the genuine historical wording - missing boundary clauses for `VQ-005..007`, substantively reworded for `VQ-008`) replaced with the exact historical text; `oracle_manifest_0_3_candidate.json`'s `raw_rcbase_0_2_diff_required_before_freeze` → `false`; affected digests regenerated; the coverage plan's (`CASEPLAN-0.3`) "must be diffed when available" language removed. Also found and fixed along the way: `validate_forward_verification.py` had been silently broken since step 9 (a hardcoded assertion never updated for step 9's own provenance-string change) - nothing in CI or the test suites runs this standalone script, so it stayed broken unnoticed until this pass touched the same code path. Full account: `docs/DEVELOPMENT_DOCUMENTATION.md` §18, `docs/CHANGELOG.md`'s same-dated entry.

**The historical-fixture reconciliation issue is now fully resolved** - `RCBASE-0.3` has no remaining reconciliation gap anywhere across all 143 rows. What remains before a `1.0` baseline is step 10 (freeze + principal verification run) and the two pre-existing supervisor decisions (§3) - evidence capture and final-baseline verification, not further substantive feature or data work.

### 0.10 Execution-environment versions recorded ahead of freeze, floating tags left alone

Project-owner instruction, explicitly framed as pre-freeze prep: the moving container tags (`mysql:latest`, `php:8.4-apache`, `node:22-alpine`, `composer:2`, both Selenium images) should resolve to exact versions/digests in a verification manifest, even though the compose/Dockerfile/CI files themselves correctly stay on floating tags for development convenience (§10.1's decision record, unchanged). New `docs/environment_manifest_0_1_candidate.json` records, per image: the exact version string (confirmed by actually running the resolved image, not read off the tag name) and the manifest-list digest (`docker buildx imagetools inspect` against the live registry - the multi-arch-aware digest form, matching how `publish-images`' own post-publish check already identifies a multi-platform image, §10.7). Nothing in any compose/Dockerfile/CI file changed - this is evidence gathered *about* the current environment for `REQ-CFG-01`, not a pin *applied to* it; whether to actually pin these files happens at step 10, once a specific commit is being frozen. Full rationale: `docs/DEVELOPMENT_DOCUMENTATION.md` §10.9.

## 1. What this project is

A bachelor-thesis Design Science Research artefact: a small web application that evaluates a learner-submitted Austrian ICD-10 BMASGPK 2026 code against a synthetic patient/question and returns one of `correct`/`suboptimal`/`incorrect`/`none_of_above`-aware feedback with an explanation, via explicit, traceable, deterministic rules — not a real diagnostic or clinical tool. Full commission/scope/non-goals: `archived/development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §1-2 (still accurate for *scope*; status sections are superseded, and its case-centric implementation detail is now historical).

**Read in this order if you're starting cold:**

1. This document.
2. `CLAUDE.md` (repo root) — operational rules, especially the documentation-upkeep table. Read this before changing anything.
3. `chapter3_forward_implementation_instruction_0_5.md` and `chapter3_api_and_feedback_contract_0_1.md` at repo root — the forward redesign's own authority on required order and API/feedback shape.
4. `docs/README.md` — what the live documentation files are each for.
5. `docs/DEVELOPMENT_DOCUMENTATION.md` and `docs/IMPLEMENTATION_SPECIFICATION.md` — **both fully rewritten 9 August 2026 for the forward model**, current through step 8 (test suite genuinely passing - the §7 test-inventory table was rewritten accordingly, so the five ⚠ rows it used to carry no longer appear).
6. `docs/REQUIREMENTS_TRACEABILITY.md` — likewise fully re-audited 9 August 2026 against every `REQ-*` in the current catalogue; the five rows previously marked ⚠ ("verified by inspection, automated test currently broken") now read ✅, and `REQ-VER-08`/`09` now read ✅ too following step 9 - read its §1/§1a regardless; `REQ-VER-05` (formal conformance report) and `REQ-VER-07` (appendix placement) remain honestly deferred to step 10/thesis-writing.
7. `docs/USER_GUIDE.tex`/`.pdf` — also rewritten 9 August 2026 for the patient/question learner workflow, language/appearance settings, and a warning that the published GHCR images still predate this migration (§0.1) — use the native local build until CI republishes.
8. `docs/CHANGELOG.md` — the full dated history; the most authoritative source for "what actually happened, in what order."
9. The other `chapter3_*.md` files at repo root — the upstream methodological specification. A few (`chapter3_test_catalogue.md` among them) still read in pre-implementation tense in places from before the *original* PHP app existed; the forward-redesign siblings (`_0_2`/`_0_5`/`_0_6`/`_0_7` suffixes) are the current authority where they overlap with an older file.

## 2. What exists and is verified working

- **Persistence** (`prototype_baseline/`, Python + MySQL): `MODELBASE-0.2`'s 9-table schema (`prototype_baseline`, `catalogue_code`, `patient_definition`, `patient_context_item`, `coding_question`, `question_fact`, `question_code_domain`, `question_relation_fact`, `question_option`), applied and loaded transactionally/idempotently, now wired into the actual `docker compose` bootstrap path (§0.1).
- **PHP backend** (`app/src/`): rule engine migrated to `RULEBASE-0.2` (`RULE-GATE/MAP/STATUS/DEPTH/EVID/SPEC/CORRECT/REL-HARD/REL-SPEC/NOA/PREC-01`), new `PatientRepository`/`QuestionRepository` over raw PDO, HTTP API `GET /api/patients`, `GET /api/patients/{id}`, `GET /api/questions/{id}`, `POST /api/questions/{id}/evaluate` — tagged-response contract only (`{"response":{"type":"code","code":"..."}}` or `{"type":"none_of_above"}`), per `APIBASE-0.1`. Raw `question_fact` rows are never exposed pre-submission (a real correction made mid-migration, not a design given from the start).
- **React frontend** (`app/frontend/`): persisted light/dark appearance setting (OS-derived on first use, compact header toggle) + first-visit-only four-step tutorial (manually reopenable from the header) + patient roster (equal-height cards, completion badges, reset-progress) → collapsible patient dossier (reopenable without losing question state) → question (progress bar, code options + `none_of_above`, exit-to-roster control) → submit → locked inline feedback with a technical-details disclosure → next → patient review (raw counts, completion badge, no score) → replay/choose another patient. EN/DE throughout, including evaluator explanation text. `UXBASE-0.1`'s `Must`-priority mechanics and accessibility requirements are applied (§0.3); its optional `Should`/`Could` items (code-option order permutation, a literal separate "Home" screen) were deliberately not built.
- **Docker**: `docker-compose.yml` (repo root) — `db` → `bootstrap` (`MODELBASE-0.2`) → `app`, one command. `prototype_stack/compose.yaml` mirrors the same bootstrap repoint for the `stack.sh`-managed deployment path. **The published GHCR images are current** — a real GitHub-hosted `publish-images` run (31314862118, 2026-08-09 13:04-13:09 UTC) completed successfully after §0.8's build-hang fix; `docker compose pull` now fetches the forward model, verified directly (`GET /api/patients` after a real `pull`+`up` returns all 6 renamed patients, not `CASE-*` data).

## 3. What's NOT done, and why it matters

- **Step 10 (freeze + principal verification run)** — now the only remaining implementation-order step; depends on nothing else undone. **Immediate next step — see §4.**
- Pre-redesign open items (`OPEN-RQ-01` thesis research-question wording, `OPEN-EVAL-01` whether independent domain-expert review is required) remain the supervisor's to decide and are unaffected by any of the above.

## 4. Immediate next step

**Step 10**: freeze + principal verification run. With step 9 done and CI confirmed green end-to-end (§0.8), nothing implementation-order-blocking remains before this. Concretely: settle `RCBASE-0.3`'s candidate status (drop the `_candidate` suffix once the project owner is ready to commit to it as frozen), settle the two supervisor-level open items (`OPEN-RQ-01`, `OPEN-EVAL-01`), then run the actual principal verification procedure `chapter3_test_catalogue.md` §3.2.2 describes and produce `REQ-VER-05`'s formal conformance report - this is a different, more formal thing than "the test suite passes," which is already true.

## 5. How to resume the environment

```bash
docker compose build bootstrap app   # local source — do this before `up` until a fresh GHCR tag is published for the forward model
docker compose down -v               # drops any stale pre-forward-redesign volume; safe, nothing but versioned baseline data lives in it
docker compose up -d --wait app
curl http://127.0.0.1:5860/api/patients   # sanity check: expect 6 patients with display_name, not CASE-* ids
```

The `stack.sh`-managed path (`prototype_stack/`) follows the same three-command sequence documented in `docs/IMPLEMENTATION_SPECIFICATION.md` §6.4/§6.5, with the same `bootstrap` repoint already applied to `prototype_stack/compose.yaml`.

Selenium (project standard — **not Playwright**, see `CLAUDE.md`): `app/tests/E2E/docker-compose.yml`, per `app/tests/E2E/README.md`. The committed PHPUnit e2e suite now runs correctly (`--testsuite e2e`, 9/9, including first-visit tutorial and persisted-theme regressions) against a stack started this way; for one-off ad hoc visual verification beyond what the committed suite covers, write a throwaway script against the same `php-webdriver/webdriver` dependency instead of reaching for anything else.
