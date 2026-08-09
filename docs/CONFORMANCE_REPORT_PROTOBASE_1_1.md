# `PROTOBASE-1.1` localization-correction conformance report

**Verification date:** 9 August 2026  
**Local run:** 20:02-20:05 CEST (`18:02-18:05Z`)  
**Scope:** complete Austrian-German (`de-AT`) / British-English (`en-GB`)
learner-surface correction and regression verification  
**Parent revision:** `912e9923924b3958bd2dd1a7948d0d7be4a71869`  
**Corrective freeze commit:** `8bb74b31596c3380c45b74643af63d744082be4e`<br>
**Corrective freeze tag:** annotated `dev-freeze-5`<br>
**Evidence:** [evidence/PROTOBASE-1.1/](evidence/PROTOBASE-1.1/)

## 1. Verdict

The localization-corrected revision conforms to the unchanged
`MODELBASE-0.2`, `RULEBASE-0.2`, `APIBASE-0.1` and byte-identical
`RCBASE-0.3` semantic contracts. The fresh, volume-free run passed all data,
persistence, frontend-build, unit, integration, Selenium and isolation
checks. The complete containerized application suite passed **258/258 tests
with 5,682 assertions**. No remaining localization defect was observed in
the inventoried surface or bilingual runtime traversal.

This is a new report, not a rewrite of [CONFORMANCE_REPORT.md](CONFORMANCE_REPORT.md).
The earlier report remains accurate in its actual scope: its predefined
tests found no deviations. The present audit later found presentation
defects those tests did not inventory; it does not retroactively support the
broader claim that the 1.0 software had no defects.

## 2. Bound identities and semantic invariants

| Artefact | Frozen identity / result |
|---|---|
| Prototype | `PROTOBASE-1.1`, status `frozen_localization_correction_baseline` |
| Data/rule/API | `MODELBASE-0.2`; `SUBSET-0.2`; `PATIENTBASE-0.1`; `QUESTIONBASE-0.1`; `RULEBASE-0.2`; `APIBASE-0.1` |
| Oracle | `RCBASE-0.3`, 143 rows, SHA-256 `21c3f02697fe9b20028ec1121d28fce3389c027705372ae08c43f894b3342540` |
| Runtime digest | `63d7ddccc11b4e4c40a33d8dc8eec65e528a9786de2e8820a5b01ca58bc07008` |
| Source catalogue | BMASGPK 2026 DIAGLIST SHA-256 `66713da5d63afcd37b0152ae7058f2188bf34d557bfa06ad4ce008825fb94a4b` |
| Materialized rows | 99 catalogue; 6 patients; 32 context items; 33 questions (25 learner + 8 verification-only); 88 facts; 118 domain relations; 182 relation/fact links; 120 options |
| Learner localization inventory | 349 entries: 94 UI, 6 summaries, 6 names, 32 contexts, 25 titles, 25 prompts, 87 displayed codes, 9 evaluator branches, 58 feedback fact combinations, 7 non-translation policy entries |

The only runtime-data edits are learner-presentation wording in three CSVs,
their re-derived hashes, and the synchronized review workbook. No row,
identifier, option membership, typed fact, relation, rule predicate,
precedence, class, criterion, or improvement semantic changed.

## 3. Environment

The run used Docker 28.5.1, Docker Compose 2.40.2, host PHP 8.4.7,
container PHP 8.4.24, Node 23.6.0/npm 11.4.2 for host checks, Python 3.11.7
with the existing project virtual environment for source-data checks, and
the seven repository-pinned third-party image digests recorded in
[environment_manifest_0_2.json](environment_manifest_0_2.json). The MySQL
image reports 26.7.0; the bootstrap image uses Python 3.12.13. Selenium used
the pinned `seleniarm/standalone-chromium` image on this arm64 host.

No GitHub-hosted CI result is claimed for this local correction. The three
project-owned GHCR tags are mutable and were rebuilt locally; exact
reproduction therefore means building from the frozen source commit, not
pulling a later `:latest`/`:dev` tag.

## 4. Executed checks

### 4.1 Clean state and image build

```bash
docker compose --profile test down -v
docker compose --profile test build bootstrap app test
```

**PASS.** The first command removed the application/database/Selenium
containers, network and database volume. The build resolved only the pinned
base images, executed the four frontend localization-contract tests (4/4),
and produced the production Vite bundle before building the runtime/dev and
bootstrap images. Full output: `01-clean-down.txt`, `02-build.txt`.

### 4.2 Deterministic data and runtime contracts

```bash
.venv/bin/python prototype_baseline/scripts/prepare_subset_0_2.py \
  archived/development_handoff/sources/core/DIAGLIST2026.xlsx --check-existing
.venv/bin/python prototype_baseline/validate_forward_verification.py
.venv/bin/python prototype_baseline/validate_materialized_design.py
cd prototype_baseline/persistence_candidate
../../.venv/bin/python -m unittest -v test_runtime_contract_0_2
```

**PASS.** Source-to-subset reproduction was byte-identical (99 records),
forward verification and materialized design contracts passed, and the
runtime contract passed 8/8. This includes the exact 1.1 canonical digest,
runtime allowlist/oracle separation, nine-table schema, patient/question
distribution and none-of-above controls. Output: `03-data-contract.txt`.

### 4.3 Complete application suite

```bash
docker compose --profile test run --rm test
```

**PASS: 258/258 tests, 5,682 assertions.** Suite composition is 83 unit,
163 live-MySQL integration, and 12 real-browser Selenium tests. The expanded
coverage includes four additional frontend tests executed during image build,
all 143 reference responses, every 58 feedback-linked fact/relation
combination, both locales across all 6 patients/32 contexts/25 learner
questions/option sets/completion views, both tutorials, all learner-reachable
rules, both `RULE-NOA-01` branches, and dark-mode persistence/presentation.
Output: `04-full-application-suite.txt`.

### 4.4 Live persistence and idempotence

```bash
docker compose run --rm bootstrap
docker run --rm --network bsc-thesis-icd10_default \
  -v "$PWD/prototype_baseline:/work:ro" -w /work/persistence_candidate \
  -e ICD_DB_HOST=db -e ICD_DB_PORT=3306 -e ICD_DB_NAME=icd_learning \
  -e ICD_DB_USER=icd_app -e ICD_DB_PASSWORD=icd_app_password \
  --entrypoint python ghcr.io/junomarx/bsc-thesis-icd10-bootstrap:latest \
  -m unittest -v test_mysql_persistence_0_2
```

**PASS.** The fresh suite bootstrap inserted the baseline; the explicit
second bootstrap returned `no_op`. The live persistence suite passed 6/6,
including schema/oracle-column exclusion, counts/identity, FK rejection,
transactional conflict rejection, visibility boundaries and none-of-above /
COPD boundaries. Output: `05-persistence.txt`.

### 4.5 Runtime smoke and oracle isolation

Health and patient APIs returned successfully; the learner roster exposed 6
patients and 25 questions, while MySQL held the expected 33 total questions
and `PROTOBASE-1.1` identity. `git diff --exit-code` confirmed the oracle was
unchanged. Source inspection found no oracle/RCBASE reference under
`app/src`; the runtime and bootstrap images contained no oracle-named file;
the dev/test image contained the single expected test fixture. Output:
`06-runtime-smoke-and-isolation.txt`.

### 4.6 Selenium visual evidence and teardown

```bash
ICD_E2E_SELENIUM_URL=http://127.0.0.1:4444 \
ICD_E2E_BROWSER_BASE_URL=http://host.docker.internal:5860 \
php app/scripts/capture_localization_screenshots.php \
  docs/evidence/PROTOBASE-1.1
docker compose --profile test down -v
```

**PASS.** Five screenshots were captured through Selenium and visually
inspected: English light roster, German dark roster, German tutorial, and the
same `E03.4` value-aware feedback in German and English dark mode. No clipped
control, mixed-language clause or raw technical identifier is visible in the
ordinary learner result. The final teardown again removed the database volume
and stack. Output: `07-screenshots.txt`, the five PNG files, and
`08-final-down.txt`.

## 5. Semantic regression proof

The live integration audit compares all semantic fields for all 143 oracle
rows. Distribution remains:

- classes: 33 correct, 20 suboptimal, 90 incorrect;
- determining rules: Correct 31, Depth 3, Evid 9, NOA 25, RelHard 53,
  RelSpec 17, Spec 3, Status 2.

For each row it also obtains both localized explanations and rejects mixed
language/raw identifier patterns. A separate SQL-driven loop sends all 58
current relation/fact/value combinations through the production
`LocalizedFactFormatter`. Consequently the systemic formatter is verified
across the materialized data rather than only against the reported `E03.4`
example. The test oracle is read only by the test harness and remains absent
from production/bootstrap paths.

## 6. Corrected deviations and root causes

The audit corrected presentation deviations: English authoring labels inside
German relation feedback, abstract labels in place of values, permissive
cross-language/raw-key fallback paths, incomplete localized failure states,
and Austrian-German/British-English wording defects. The primary root cause
was treating `question_fact.learner_label` as executable bilingual prose.
`LocalizedFactFormatter` now formats the typed semantic key/value, without
question or ICD-code branches, and fails closed for unsupported metadata.

No semantic, schema, option-membership, scoring, layout, source-domain or
oracle deviation was introduced or found by this corrective run.

## 7. Deliberate non-translations and residual limitation

ICD codes, technical `RULE-*`/criterion IDs inside the collapsed disclosure,
baseline/source IDs, patient proper names, product identity and `EN`/`DE`
abbreviations remain untranslated by policy. German catalogue designations
remain authoritative and byte-preserved.

The repository has no official English counterpart to the Austrian
catalogue. The 87 displayed English titles are reviewed interface
presentations, not authoritative replacements. Strict checks make expansion
fail visibly until a new displayed code receives an English presentation.

## 8. Freeze identity

The verified implementation and complete evidence package were committed as
`8bb74b31596c3380c45b74643af63d744082be4e` and frozen with the new annotated
tag `dev-freeze-5`. Earlier tags (`dev-freeze` through `dev-freeze-4`) remain
immutable and were neither moved nor overwritten. This exact-identity record
is a documentation-only follow-up to the tagged commit.

- implementation/evidence commit: `8bb74b31596c3380c45b74643af63d744082be4e`
- annotated tag: `dev-freeze-5`
- tag target: `8bb74b31596c3380c45b74643af63d744082be4e`
- remote/CI status: local only; not pushed; no 1.1 CI result claimed

## 9. Evidence integrity

`docs/evidence/PROTOBASE-1.1/SHA256SUMS` records every evidence file and
screenshot (excluding itself). A packaged ZIP is stored beside the directory
after the checksums are finalized. The package contains the raw command
outputs, not merely this summary.
