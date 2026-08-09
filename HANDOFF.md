# Handoff

**Snapshot date:** 9 August 2026 (revised same day — steps 1-7 of the forward implementation order are now complete; see the revision note directly below)
**Audience:** a developer, contractor, or coding agent with no access to the conversation that produced this state — read this first, before touching anything.
**Supersedes:** everything below §0 in this document's own prior form (dated 8 August 2026, describing the case-centric `CASEBASE-0.2` implementation), and the status/progress sections of `development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` and `CODEX_VSCODE_CONTINUATION_INSTRUCTION.md` (dated 7 August 2026, in `development_handoff/`, an intentionally-preserved historical archive, not the live project state).
**Same-day revision note:** the version of this file written earlier 9 August 2026 (preserved in git history, not here) covered steps 1-6 plus the persistence/deployment-path fix in §0.1. Since then: step 7 (`UXBASE-0.1` visual/gameful polish) landed and was verified; three project-owner-reported issues found by actually using the running app were fixed (an option-list CSS truncation bug, the raw `none_of_above` token leaking into feedback text, and English text appearing in German mode); a footer with version/build-date/author was added; and — the point of this specific revision — `docs/IMPLEMENTATION_SPECIFICATION.md`, `docs/REQUIREMENTS_TRACEABILITY.md`, `docs/DEVELOPMENT_DOCUMENTATION.md`, and `docs/USER_GUIDE.tex`/`.pdf` were all brought current for the forward model, closing the "stale by construction" flags the earlier version of this file pointed readers past. Read §0 anyway — most of it still applies.

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
| 8 | Extend test coverage, rerun all 18 historical regressions | **Not started — the committed suite is currently broken, see §0.1 (still true; step 7 did not touch `app/tests/`)** |
| 9 | Oracle/source audit reconciliation (125 new expectations + 4 provisional `VQ-005..008`) | Not started |
| 10 | Freeze + principal verification run | Not started |

### 0.1 A real gap this snapshot exists to record: "verified" ≠ "deployed"

Steps 2-3 and 6 were each verified against **throwaway infrastructure** — a scratch `docker run` MySQL container on a non-default port, the Vite dev server — that was torn down after each check. Nobody had repointed the repository's actual `docker compose up` path at the new schema/data. Concretely: `docker-compose.yml`'s `bootstrap` service still built from `prototype_baseline_0_1/Dockerfile.bootstrap`, which applies the historical `mysql_schema.sql` and loads `cases_0_2.csv` (`CASEBASE-0.2`, 8 COPD-only cases). The already-published GHCR `app` image was also from before the migration. Net effect: the PHP/React source was genuinely migrated and correct on disk, but **anyone running `docker compose up` and opening a browser saw the old case-centric app** — old schema, old data, old routes. The project owner caught this by inspecting the actual running app, not by reading a report.

**Fixed this same day** — see `docs/CHANGELOG.md`'s "steps 2-3 completed for real" entry for the full verification trail:

- New `prototype_baseline_0_2_design/Dockerfile.bootstrap` + `persistence_candidate/bootstrap_mysql_0_2.py`, wired to `apply_mysql_schema_0_2.py`/`load_mysql_0_2.py` (the previously-standalone-only MODELBASE-0.2 persistence candidate).
- `docker-compose.yml` and `prototype_stack/compose.yaml`: `bootstrap` service `build.context` repointed at `prototype_baseline_0_2_design/`.
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
| Reference responses (candidate) | `RCBASE-0.3` | 143 rows (125 new + 18 historical) — **not yet oracle-audited, step 9** |
| API/feedback contract | `APIBASE-0.1` | Working — tagged-response contract, resolved 9 implementation-detail ambiguities |
| SQL/loader migration contract | `DATAMIG-0.2` | Implemented (§0.1) |
| UX/gamification concept | `UXBASE-0.1` | Applied to the frontend, step 7 — see §0.3 |
| Prototype identity (candidate) | `PROTOBASE-0.3` | `working_forward_implementation_candidate_not_frozen` |
| **`1.0`/frozen anything** | **none** | **Not frozen — steps 7-10 remain, plus the pre-existing supervisor decisions in §5 of the pre-redesign era, still open** |

The old table (`CASEBASE-0.2`/`RCBASE-0.2`/`MODELBASE-0.1`/`PROTOBASE-0.2`, etc.) describes a superseded model. Do not resume work against it.

### 0.3 Step 7 (`UXBASE-0.1`) and the fixes that followed from actually using the app

Step 7 added: an orientation/three-class-legend panel on the roster (`REQ-UI-01`), equal-height patient cards, a session-local per-patient completion badge plus an aggregate "N of 6" line and a reset-progress control, a visual question-progress bar, a collapsible technical-details disclosure (determining rule/criterion), and a restrained completion acknowledgment on the review screen. An EN/DE language switch (`components/LanguageSwitch.jsx`) was added alongside it, translating UI chrome, patient/question *content*, and — after the project owner found German mode still showing English sentences — the evaluator's own explanation text (`EvaluationResult::$explanationDe`, additive, every rule call site updated) and (after English mode was found showing German ICD-10 code names, since the runtime catalogue is German-only data) English titles for the 87 displayed codes. A footer (`v0.7.0 · build 2026-08-09 · © 2026 Juno Anna Marx`) was added.

Two real defects surfaced this same day by the project owner actually using the running app, not by a planned test, both fixed and re-verified against the real container: the question option list had a fixed `max-height`/internal scroll that clipped any question with more than ~4 options; and `RULE-NOA-01`'s explanation literally embedded the machine token `none_of_above` in learner-facing prose (the same defect class was also found and fixed one layer deeper — a fact-key/reason-key fallback in `RULE-REL-HARD-01`'s explanation, and a raw gate-`reason` enum in the frontend's `not_evaluated` panel). Also applied: an explicit project-owner naming override — three of six patient names (`Michael Novak`→`Michael Bauer`, `Lea Horvat`→`Lea Wagner`, `Sofia Marin`→`Sophie Mayer`) changed to read as unambiguously common-Austrian for demo purposes; the other three already qualified and were left alone.

Full detail, and the honest record of what step 7 deliberately did *not* implement (code-option display-order permutation, a separate literal "Home" screen — both explicit `Should`/optional items, not `Must`): `docs/CHANGELOG.md`'s five 2026-08-09 entries after the persistence fix, and `docs/DEVELOPMENT_DOCUMENTATION.md` §13-14.

## 1. What this project is

A bachelor-thesis Design Science Research artefact: a small web application that evaluates a learner-submitted Austrian ICD-10 BMASGPK 2026 code against a synthetic patient/question and returns one of `correct`/`suboptimal`/`incorrect`/`none_of_above`-aware feedback with an explanation, via explicit, traceable, deterministic rules — not a real diagnostic or clinical tool. Full commission/scope/non-goals: `development_handoff/handoff/ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md` §1-2 (still accurate for *scope*; status sections are superseded, and its case-centric implementation detail is now historical).

**Read in this order if you're starting cold:**

1. This document.
2. `CLAUDE.md` (repo root) — operational rules, especially the documentation-upkeep table. Read this before changing anything.
3. `chapter3_forward_implementation_instruction_0_5.md` and `chapter3_api_and_feedback_contract_0_1.md` at repo root — the forward redesign's own authority on required order and API/feedback shape.
4. `docs/README.md` — what the live documentation files are each for.
5. `docs/DEVELOPMENT_DOCUMENTATION.md` and `docs/IMPLEMENTATION_SPECIFICATION.md` — **both fully rewritten 9 August 2026 for the forward model** (the earlier "stale by construction" flag is gone; `IMPLEMENTATION_SPECIFICATION.md` is now current through step 7, including the language switch/footer/bilingual explanations, and honestly documents the still-broken test suite rather than glossing over it).
6. `docs/REQUIREMENTS_TRACEABILITY.md` — likewise fully re-audited 9 August 2026 against every `REQ-*` in the current catalogue; six rows are marked ⚠ ("verified by inspection, automated test currently broken") rather than a plain ✅, and several `REQ-VER-*` rows are honestly deferred to steps 9/10 — read its §1/§1a before trusting any row at a glance.
7. `docs/USER_GUIDE.tex`/`.pdf` — also rewritten 9 August 2026 for the patient/question learner workflow, language switch, and a warning that the published GHCR images still predate this migration (§0.1) — use the native local build until CI republishes.
8. `docs/CHANGELOG.md` — the full dated history; the most authoritative source for "what actually happened, in what order."
9. The other `chapter3_*.md` files at repo root — the upstream methodological specification. A few (`chapter3_test_catalogue.md` among them) still read in pre-implementation tense in places from before the *original* PHP app existed; the forward-redesign siblings (`_0_2`/`_0_5`/`_0_6`/`_0_7` suffixes) are the current authority where they overlap with an older file.

## 2. What exists and is verified working

- **Persistence** (`prototype_baseline_0_2_design/`, Python + MySQL): `MODELBASE-0.2`'s 9-table schema (`prototype_baseline`, `catalogue_code`, `patient_definition`, `patient_context_item`, `coding_question`, `question_fact`, `question_code_domain`, `question_relation_fact`, `question_option`), applied and loaded transactionally/idempotently, now wired into the actual `docker compose` bootstrap path (§0.1).
- **PHP backend** (`app/src/`): rule engine migrated to `RULEBASE-0.2` (`RULE-GATE/MAP/STATUS/DEPTH/EVID/SPEC/CORRECT/REL-HARD/REL-SPEC/NOA/PREC-01`), new `PatientRepository`/`QuestionRepository` over raw PDO, HTTP API `GET /api/patients`, `GET /api/patients/{id}`, `GET /api/questions/{id}`, `POST /api/questions/{id}/evaluate` — tagged-response contract only (`{"response":{"type":"code","code":"..."}}` or `{"type":"none_of_above"}`), per `APIBASE-0.1`. Raw `question_fact` rows are never exposed pre-submission (a real correction made mid-migration, not a design given from the start).
- **React frontend** (`app/frontend/`): patient roster (orientation panel, equal-height cards, completion badges, reset-progress) → collapsible patient dossier (reopenable without losing question state) → question (progress bar, code options + `none_of_above`, exit-to-roster control) → submit → locked inline feedback with a technical-details disclosure → next → patient review (raw counts, completion badge, no score) → replay/choose another patient. EN/DE throughout, including evaluator explanation text. `UXBASE-0.1`'s `Must`-priority mechanics and accessibility requirements are applied (§0.3); its optional `Should`/`Could` items (code-option order permutation, a literal separate "Home" screen) were deliberately not built.
- **Docker**: `docker-compose.yml` (repo root) — `db` → `bootstrap` (`MODELBASE-0.2`) → `app`, one command. `prototype_stack/compose.yaml` mirrors the same bootstrap repoint for the `stack.sh`-managed deployment path. **The published GHCR images have not been rebuilt since the migration** — `docker compose pull` still fetches the pre-migration case-centric image; use `docker compose build bootstrap app` until CI republishes (§5).

## 3. What's NOT done, and why it matters

- **Step 8 (test suite rewrite) — the committed suite is currently broken, not just incomplete.** `php vendor/bin/phpunit --testsuite unit` currently reports **47 of 49 tests erroring** with `Class "Icd10Prototype\Model\CaseFacts" not found` (`app/tests/Support/Fixtures.php` and most of `app/tests/Unit/*` still construct the deleted case-centric fixtures). Integration and E2E suites are in the same state. Don't trust any pre-redesign test count in an older document as still applying. All 18 historical regression expectations still need to be re-proven once the suite is rewritten against the new model. **Immediate next step — see §4.**
- **Step 9 (oracle/source audit)** — the 125 new `RCBASE-0.3` expectations and the 4 provisional `VQ-005..008` reconstructions are not yet reconciled against source; the oracle's own `provenance_status` column still reads `forward_specification_derived_pending_human_oracle_audit` for every row. Explicitly flagged as pre-freeze work, not a current blocker.
- **Step 10 (freeze + principal verification run)** — depends on 8-9.
- CI (`.github/workflows/ci.yml`) has not been re-run since the migration; its `publish-images` job is why the GHCR images are stale (§2).
- Pre-redesign open items (`OPEN-RQ-01` thesis research-question wording, `OPEN-EVAL-01` whether independent domain-expert review is required) remain the supervisor's to decide and are unaffected by any of the above.

## 4. Immediate next step

**Step 8**: rewrite `app/tests/Unit`/`Integration`/`E2E` for the patient/question model and re-prove all 18 historical regression expectations. This is not optional busywork — the suite is currently red, not just stale, and every verification performed during steps 4-7 used ad hoc `curl`/Selenium checks against the real running stack instead of a committed, rerunnable suite (`docs/DEVELOPMENT_DOCUMENTATION.md` §13.4 explains why that gap was left for its own dedicated pass rather than patched opportunistically alongside feature work). After step 8, step 9 (oracle audit) is next.

## 5. How to resume the environment

```bash
docker compose build bootstrap app   # local source — do this before `up` until a fresh GHCR tag is published for the forward model
docker compose down -v               # drops any stale pre-forward-redesign volume; safe, nothing but versioned baseline data lives in it
docker compose up -d --wait app
curl http://127.0.0.1:5860/api/patients   # sanity check: expect 6 patients with display_name, not CASE-* ids
```

The `stack.sh`-managed path (`prototype_stack/`) follows the same three-command sequence documented in `docs/IMPLEMENTATION_SPECIFICATION.md` §6.4/§6.5, with the same `bootstrap` repoint already applied to `prototype_stack/compose.yaml`.

Selenium (project standard — **not Playwright**, see `CLAUDE.md`): `app/tests/E2E/docker-compose.yml`, per `app/tests/E2E/README.md`. The committed PHPUnit e2e suite itself will not run correctly yet (§3) — for ad hoc visual verification against a live stack, write a throwaway script against the same `php-webdriver/webdriver` dependency instead of reaching for anything else.
