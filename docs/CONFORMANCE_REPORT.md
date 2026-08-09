# Conformance report — `PROTOBASE-1.0` principal verification run

**Date:** 9 August 2026
**Status:** Executed. This is the completed step-10 principal verification run (`chapter3_forward_implementation_instruction_0_5.md`'s implementation order) and the formal conformance report `REQ-VER-05` requires.
**Procedure followed:** `docs/chapter3/chapter_3_methods_and_practical_work_specification.md` §3.2.2 ("Verification Procedure and Conformance Criteria"). That section's own structure — baseline/version table, execution-environment record, conformance/deviation classification, change-and-rerun log, results-summary schema — is followed exactly below, but this document combines the procedure definition with the actual executed results in one place, since it is repository-level evidence, not the thesis manuscript itself. Later transcription into Chapter 3 (procedure) versus Results (outcomes) should draw on the correspondingly labelled sections below.
**Scope boundary (§3.2.2's own required closing statement, stated up front):** passing the checks below demonstrates conformance of the implemented behaviour to the predefined model within the selected subset. It does not independently validate the clinical truth of the model, learner benefit, usability, or broader catalogue generalisability.

## 1. Baseline/version table

The evaluated software/data state, unambiguously bound per `TEST-CFG-01`:

| Identifier | Version | Note |
|---|---|---|
| Git commit (repository revision) | `7147b307caa6282766b2bed5a2d55c019d568937` | `origin/main`, titled "= actual final commit ahead of formal development freeze: no floating tags =" |
| Requirements catalogue | `0.6` | Forward revision `0.7` merged |
| Rule catalogue | `RULEBASE-0.2` | `RULE-GATE/MAP/STATUS/DEPTH/EVID/SPEC/CORRECT/REL-HARD/REL-SPEC/NOA-01`, extended `RULE-PREC-01` |
| Data/interaction model | `MODELBASE-0.2` | 9-table normalized patient/question schema |
| Catalogue subset | `SUBSET-0.2` | 99 DIAGLIST records, reproduced fresh below |
| Patients | `PATIENTBASE-0.1` | 6 synthetic patients |
| Questions | `QUESTIONBASE-0.1` | 25 learner-facing + 8 hidden `verification_only` legacy fixtures = 33 |
| Reference responses | `RCBASE-0.3` (frozen this run — §5) | 143 rows: 125 new + 18 historical |
| API/feedback contract | `APIBASE-0.1` | Tagged-response contract |
| SQL/loader migration contract | `DATAMIG-0.2` | — |
| UX/gamification concept | `UXBASE-0.1` | Applied, step 7 |
| Test specification | `TESTBASE-0.1` (as-built, extended de facto by step 8; not re-versioned on paper — §6 deviation note) | 77 unit + 160 integration + 9 E2E, bound to the actual implementing files (§4) |
| Prototype identity | `PROTOBASE-0.3` → **`PROTOBASE-1.0`, frozen this run** | `runtime_manifest_0_2.json` |
| Source catalogue checksum | `diaglist_sha256 = 66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` | `SRC-AT-DIAGLIST-2026`, `DIAGLIST2026.xlsx`, unchanged from prior sessions, reconfirmed below |
| Database state | Freshly bootstrapped, empty → loaded, this run | No pre-existing data reused |

## 2. Execution-environment record

**Host:** Darwin 24.6.0, arm64 (Apple Silicon). Docker 28.5.1 (build `e180ab8`), Docker Compose v2.40.2. PHP 8.4.7 (host CLI, used only to run `phpunit` for the non-containerized checks below). Python 3.11.7 (host CLI, for the Python data-contract/persistence checks).

**Container images — pinned to manifest-list digests, not floating tags, as of this freeze** (full detail: `docs/environment_manifest_0_1.json`; the `develop` branch's copy of every file below intentionally keeps floating tags for ongoing development):

| Image | Resolved version | Manifest-list digest |
|---|---|---|
| `mysql` | 26.7.0 | `sha256:66aec17cd21a956029b83f083b813073859e8355dc1a00e55df6ba02f0e32345` |
| `php:8.4-apache` | PHP 8.4.24 | `sha256:5f8050825b2f3de4efb0d81149c86643a9ee9c0a74ed4595ca2ad69ebfeb35fb` |
| `node:22-alpine` | Node 22.23.2 | `sha256:c610fcdfb1d5b4740dd70c284ed3cb16bb857e0f7166196e36a5501df7a3aa32` |
| `composer:2` | Composer 2.10.2 | `sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040` |
| `selenium/standalone-chrome` (CI/amd64) | image release `24.04` | `sha256:9569014786466376d3e5cf8a7758562368cd9637f783dcd3abdb2eaf3a0d5cd7` |
| `seleniarm/standalone-chromium` (local/arm64) | unlabeled by the image | `sha256:d644a5f679e83e63344cee11c08fc2c7bf4acf43217434a8621a2bc85f7473a5` |

Project-owned images (`ghcr.io/junomarx/bsc-thesis-icd10:latest`/`:dev`, `bsc-thesis-icd10-bootstrap:latest`) are built directly from the pinned commit above during this run, not pulled from a registry — their identity is the commit, not a version tag.

**Repeatability:** every check below was run against freshly created containers/volumes (`docker compose down -v` immediately beforehand; no reused state), sufficient for another party to rerun by checking out the pinned commit and executing the commands in §4.

## 3. Conformance and deviation classification (§3.2.2's required categories)

**Conformance categories** (per row/check):
- **Exact conformance** — observed result matches the frozen expectation exactly.
- **Explanation/criterion mismatch** — classification correct, explanation elements incomplete/wrong.
- **Unexpected rule/classification mismatch** — wrong `classification` and/or `determining_rule`.
- **Execution failure** — the check could not complete (crash, timeout, infrastructure fault).
- **Untested/blocked** — a defined check that did not run this pass.

**Deviation-cause categories** (applied only where a non-conformance is found — none this run, §5):
implementation defect · specification/rule defect · reference-case expectation defect · data-preparation defect · infrastructure/execution defect · accepted limitation.

## 4. Executed checks — commands, and results

All commands below were actually run this session, in this order, against the pinned commit, with output captured in the working conversation transcript this document summarizes.

### 4.1 Data-contract checks (no database)

| Check | Command | Result |
|---|---|---|
| Source-to-subset reproduction (`TEST-DAT-01`) | `python3 scripts/prepare_subset_0_2.py ../archived/development_handoff/sources/core/DIAGLIST2026.xlsx --check-existing` (from `prototype_baseline/`) | **PASS** — 99 records, `source_sha256`/`output_sha256` match the pinned values |
| Runtime contract (`TEST-runtime-contract`) | `python3 -m unittest -v test_runtime_contract_0_2` (from `persistence_candidate/`) | **PASS**, 8/8 |
| Forward verification-design contract | `python3 validate_forward_verification.py` | **PASS** — 125 learner + 18 legacy = 143 rows; classes 33/20/90 |
| Materialized design contract | `python3 validate_materialized_design.py` | **PASS** — 99/6/25 subset/patients/questions; zero cross-question code leakage |

### 4.2 Persistence checks (live MySQL, pinned digest)

Fresh throwaway container from `mysql@sha256:66aec17cd21a956029b83f083b813073859e8355dc1a00e55df6ba02f0e32345`.

| Check | Command | Result |
|---|---|---|
| Schema application (`TEST-DAT-02` precondition) | `python3 apply_mysql_schema_0_2.py` | **PASS** — 9 runtime tables |
| Baseline load | `python3 load_mysql_0_2.py` | **PASS** — `inserted`; canonical runtime digest `338cc21b533bfd2162f750c6d9608041962a4d49f4244853a9387e068c414331` (matches the pinned test value in `test_runtime_contract_0_2.py`; this is the value after `prototype_baseline_id` was promoted `PROTOBASE-0.3` → `PROTOBASE-1.0` in §7 below, re-confirmed by rerunning this exact check after that promotion, not merely asserted) |
| Persistence tests (`TEST-DAT-02`) | `python3 -m unittest -v test_mysql_persistence_0_2` | **PASS**, 6/6 (FK rejection, idempotent reimport, `none_of_above`/COPD boundaries, persisted counts, visibility boundary, schema table set) |

### 4.3 Application test suites — fully containerized, clean environment

Run via `docker compose --profile test run --rm test` (the self-contained bundle's own containerized entrypoint: `phpunit --testsuite unit,integration,e2e`, zero host PHP/Python/Node dependency), against freshly built images from the pinned Dockerfile and a freshly bootstrapped database via the ordered `db → bootstrap → app` startup.

| Suite | Result |
|---|---|
| Unit (`TEST-MAP/GATE/STATUS/DEPTH/EVID/SPEC/CORRECT/PREC-01`) | **77/77 passing** |
| Integration (`TEST-API-01`, `TEST-RC-01`, `TEST-DET-01`, `TEST-ARC-01`) | **160/160 passing, includes all 143/143 `RCBASE-0.3` rows** (33 correct / 20 suboptimal / 90 incorrect, confirmed by direct row count against the CSV) |
| E2E (`TEST-E2E-01`, `TEST-E2E-02`, theme, tutorial, progress-badge) | **9/9 passing**, real Selenium/Chrome against the actual running app container |
| **Combined** | **246/246 tests, 2349 assertions, 0 failures, 0 errors** |

**Re-run in full a second time**, after §7/§8's candidate-designation promotions (file renames, `PROTOBASE-1.0` identity change) were applied on top of this same commit, to confirm the promotions themselves introduced no regression: identical result, 246/246, same digest reasoning (§4.2's note). The counts and verdict above are the final, post-promotion state, not superseded by anything that follows in this document.

### 4.4 Container-startup sequence

| Check | Result |
|---|---|
| `docker compose build bootstrap app` (pinned Dockerfile) | **PASS** — both images build cleanly |
| `docker compose up -d --wait app` | **PASS** — ordered `db` (healthy) → `bootstrap` (exited 0) → `app` (healthy); no manual sequencing needed |
| Bootstrap log | **PASS** — `MODELBASE-0.2 runtime input validation: PASS`; 99/6/32/33/88/118/182/120 component counts; canonical digest matches §4.2 |
| `GET /api/health` | **PASS** — `{"status":"ok"}` |
| `GET /api/patients` | **PASS** — 6 patients, correct `display_name`s (`Anna Berger`, `Michael Bauer`, `Lea Wagner`, `Sophie Mayer`, `Daniel Weiss`, `Peter Gruber`), not `CASE-*` placeholders |

## 5. Results summary and conformance verdict

| Category | Count |
|---|---|
| Checks executed | 4.1 (4 checks) + 4.2 (3 checks) + 4.3 (246 tests) + 4.4 (5 checks) = **258 discrete checks** |
| Exact conformance | **258 / 258** |
| Explanation/criterion mismatch | 0 |
| Unexpected rule/classification mismatch | 0 |
| Execution failure | 0 |
| Untested/blocked | 0 (E2E suite was previously skipped in the prior housekeeping pass for unrelated reasons; run in full here) |

**Verdict: full conformance. Zero defects found.** Per this document's own governing instruction ("no further feature development shall be introduced unless the freeze run reveals an actual defect"), no corrective work was performed or is warranted — §6 below records only naming/versioning promotions, not behavioural changes.

## 6. Change-and-rerun log

Empty. No defect was discovered during this run, so no correction, no expectation change, and no regression rerun was triggered. This section exists (per §3.2.2's template) to record such an event and remains empty because none occurred — not because the section was omitted.

**Deviations from a literal reading of `TEST-CFG-01`'s printed identifier list and `chapter3_test_catalogue.md` §11's own freeze conditions, both noted honestly rather than silently reconciled:**

- `TEST-CFG-01`'s own text in `chapter3_test_catalogue.md` still prints the pre-forward-redesign identifiers (`SUBSET-0.1`, `RULEBASE-0.1`, `CASEBASE-0.2`, `RCBASE-0.2`, `PROTOBASE-0.2`) rather than the current ones bound in §1 above. This is a known, previously-documented staleness in that upstream chapter3 control document (not edited here, consistent with this project's standing practice of treating `chapter3_*.md` as upstream-authored control artefacts); the *current* identifiers are what was actually bound and verified.
- `TESTBASE-0.1`'s own version number was not incremented on paper for the forward-model test-suite rewrite (step 8), even though its actual coverage was substantially extended (`RULE-REL-HARD-01`/`REL-SPEC-01`/`NOA-01` unit tests, the full patient/question integration/E2E rewrite). The as-built binding (§4 above, `docs/IMPLEMENTATION_SPECIFICATION.md` §7) is current and verified; the catalogue-side version label is a documentation gap for the thesis author to close, not a technical gap this run found.
- Two supervisor-level open items remain explicitly unresolved by this run, as they have been throughout the project: **`OPEN-RQ-01`** (final research-question wording) and **`OPEN-EVAL-01`** (whether independent domain-expert review is required). Neither is an implementation gap; both are the project owner's/academic supervisor's decision, unaffected by anything in this report.

## 7. What this freezes, and what it does not

**Frozen by this report:**
- The git commit, container execution environment, source catalogue checksum, and every baseline identifier in §1.
- `RCBASE-0.3`'s 143 reference-response rows (`_candidate` designation dropped — §8 below).
- `PROTOBASE-0.3` → `PROTOBASE-1.0`.

**Not frozen, and explicitly out of this report's scope:**
- `REQBASE-1.0` (the requirements catalogue's own freeze designation) — gated on `OPEN-EVAL-01` per `chapter3_requirements_catalogue.md` §12, a supervisor decision this report does not make.
- `TESTBASE-1.0` — the version-number gap noted in §6; a documentation action, not a verification finding.
- Any claim of external validity, clinical correctness, or learner benefit — explicitly out of scope per this document's opening claim-boundary statement and `REQ-SCP-03`.

## 8. Downstream actions taken as a direct result of this report

Per this run's zero-defect verdict, `docs/CHANGELOG.md`'s same-dated freeze entry records the mechanical promotions applied immediately after this report was written (candidate designations dropped, status fields flipped, `HANDOFF.md`/`README.md`/`REQUIREMENTS_TRACEABILITY.md`/`IMPLEMENTATION_SPECIFICATION.md`/`DEVELOPMENT_DOCUMENTATION.md` updated) — see that entry for the exact file-by-file list, so it is not duplicated here.

## 9. Addendum (later the same day): a pinning gap found by project-owner review, corrected

**Everything above this section describes the `dev-freeze` tag (commit `7147b307caa6282766b2bed5a2d55c019d568937`) exactly as it was executed and is preserved unedited**, per this project's standing practice of correcting forward with a dated addendum rather than rewriting an already-tagged, already-cited historical record (the same practice used for the VQ-005..008 reconciliation, `docs/CHANGELOG.md`).

**The gap:** `prototype_baseline/Dockerfile.bootstrap` — the base of the published `bsc-thesis-icd10-bootstrap` image, used by the `bootstrap` service in both `docker-compose.yml` and `prototype_stack/compose.yaml` — was never brought into scope of the pinning pass described in §2 above. Its `FROM python:3.12-slim-bookworm` remained a floating tag on `master` even after this report's own §2 stated "container images — pinned to manifest-list digests, not floating tags, as of this freeze." That statement was true of the six images it listed and false of a seventh the pinning pass never looked for, because it is one Dockerfile-reference-hop away from `docker-compose.yml`/the root `Dockerfile`/`.github/workflows/ci.yml` rather than named directly in any of them. Found by project-owner review after `dev-freeze` was already tagged and pushed.

**The fix, executed the same day:**
1. Resolved `python:3.12-slim-bookworm`'s manifest-list digest (`sha256:4766d8b510c428e595d74b9cc5bbb2fae8e26316fffb4adc89908d79aacd58a2`, covering both `linux/amd64` and `linux/arm64/v8`) and exact version (Python 3.12.13), the same `docker buildx imagetools inspect`-plus-run-and-check methodology as §2's original six.
2. Pinned `prototype_baseline/Dockerfile.bootstrap`'s `FROM` line to that digest on `master`; `develop`'s copy intentionally stays on the floating tag, consistent with the existing split (§2, `docs/DEVELOPMENT_DOCUMENTATION.md` §19.2).
3. Added it as a seventh entry to `docs/environment_manifest_0_1.json`.
4. Re-ran the full check battery from §4 against the corrected commit — see `freeze_evidence.zip` (regenerated) for the raw logs.
5. Cut a **new** immutable git tag once the re-run confirmed zero defects, rather than moving `dev-freeze` — moving an already-pushed tag would silently rewrite what it had been cited as pointing to; a new tag makes the correction itself part of the visible history instead of erasing the gap it corrects.

Two other, smaller stale references from the earlier candidate-designation promotion (§7/§8 above) were found and fixed in the same pass: `.dockerignore`'s narrow re-inclusion exception still named the pre-rename `reference_responses_0_3_candidate.csv` (harmless in practice — the broader `!prototype_baseline/verification/` rule already re-included the renamed file, confirmed by an actual `docker build --no-cache --target dev`, not assumed), and a cosmetic CI step name (`Load PROTOBASE-0.3 baseline`) in `.github/workflows/ci.yml`.

**Reproducibility clarification, per project-owner instruction:** this report's claim of a reproducible frozen result holds only for **building from source at the frozen/tagged commit** (`docker compose build`, using the pinned `Dockerfile`/`Dockerfile.bootstrap` above) — it does **not** hold for `docker compose pull` against `docker-compose.yml`'s own defaults (`ghcr.io/junomarx/bsc-thesis-icd10:latest`, `:dev`, `bsc-thesis-icd10-bootstrap:latest`). Those three project-owned tags are mutable: `.github/workflows/ci.yml`'s `publish-images` job overwrites `:latest`/`:dev` on every subsequent push to `master`, so a `pull` performed after any later push fetches that later commit's build, not this frozen one. This is now stated explicitly in `docs/environment_manifest_0_1.json`'s `deliberately_not_recorded` note. Nothing about this changes §1-§7 above — the six-plus-one base images were, and remain, the correct unit of "third-party dependency pinning"; the project's own published convenience tags were never claimed to be pinned, only (incompletely, until now) documented as intentionally not pinned.
