# PROTOBASE-0.2 Data Import and Persistence Scaffold

**Status:** adopted and executed. This directory's pipeline has been adopted
into the actual application repository, executed against the frozen source
and a real MySQL instance, and its observations are recorded in
`docs/CHANGELOG.md` (2026-08-07) — it is no longer exploratory/candidate
material, and the checks below are not a hypothetical "after adoption" step.
The baseline was originally materialised as `PROTOBASE-0.1` (four cases,
14 reference responses) and superseded on 7 August 2026 by `PROTOBASE-0.2`
(eight cases, 18 reference responses) via the pre-freeze coverage review
required by `REQ-VER-02` — see `chapter3_reference_case_coverage_plan.md`
§1.1. `data/subset_0_1.csv` and its `_0_1`-suffixed siblings from the
original baseline remain on disk as retained history (this project's
immutable-baseline design never deletes a superseded version); the current
runtime loader and test harness read the `_0_2` case/domain/reference files
described below.

This directory implements a working, reproducible data baseline for the Austrian ICD-10 BMASGPK 2026 educational prototype. The data pipeline has one strict direction:

```text
frozen DIAGLIST2026.xlsx
        +
config/subset_definition_0_1.json
        |
        v
scripts/prepare_subset.py
        |
        v
data/subset_0_1.csv
        +
data/cases_0_2.csv
        +
data/case_code_domain_0_2.csv
        |
        v
scripts/load_mysql.py
        |
        v
MySQL runtime tables
```

`verification/reference_responses_0_2.csv` is outside this path. It is an independent verification oracle and is never imported into the runtime database.

## 1. Preparation step

`config/subset_definition_0_1.json` is the machine-readable extraction specification. It fixes the source checksum/worksheet, four retained fields, 13 selected codes, normalization policy, required rule targets, and the deliberate `Z01.8!` outside-subset control. The Python code implements this file rather than deciding which ICD records belong to the thesis subset itself.

To verify that the checked-in CSV is exactly reproducible from a DIAGLIST workbook without rewriting it:

```text
python scripts/prepare_subset.py /path/to/DIAGLIST2026.xlsx --check-existing
```

To regenerate the derived CSV intentionally:

```text
python scripts/prepare_subset.py /path/to/DIAGLIST2026.xlsx
```

The script first verifies the SHA-256 checksum. It then reads the named worksheet, checks source-code uniqueness/count, resolves every selected/control code, preserves the two label fields, normalizes only the code/status whitespace specified by the model, and writes deterministic UTF-8/LF CSV bytes in the order fixed by `selected_codes`.

The official workbook is never modified.

## 2. Independent baseline validation

The broader baseline audit remains:

```text
python validate_baseline.py /path/to/DIAGLIST2026.xlsx
```

Unlike the runtime loader, that audit is allowed to inspect the independent `RCBASE-0.2` fixture because its purpose is to verify the specification package itself. It must not be imported by application code.

## 3. Runtime-input preflight

Before using a database, the loader can validate only the inputs it is actually allowed to persist:

```text
python scripts/load_mysql.py --check-only
```

This reads exactly four files: `baseline_manifest.json`, `data/subset_0_1.csv`, `data/cases_0_2.csv`, and `data/case_code_domain_0_2.csv`. There is no filesystem discovery/glob and no verification-oracle path.

The preflight checks identifiers, allowed field names, uniqueness, foreign relations, case context flags, acceptable-response representation, and runtime baseline consistency. It also emits a canonical digest of the normalized runtime dataset for configuration/audit use.

## 4. Schema and MySQL data load

Apply `mysql_schema.sql` to a fresh MySQL database before running the data loader. Schema DDL is deliberately separate from data DML because MySQL DDL has implicit-commit behaviour; claiming that schema creation and data insertion form one atomic transaction would be incorrect. `scripts/apply_mysql_schema.py` provides the reproducible project-side DDL path and deliberately refuses a non-empty target database.

Database mode uses these environment variables:

```text
ICD_DB_HOST       default: 127.0.0.1
ICD_DB_PORT       default: 3306
ICD_DB_NAME       required
ICD_DB_USER       required
ICD_DB_PASSWORD   optional for local configurations that permit an empty password
```

The loader expects a Python MySQL driver compatible with `mysql.connector` (`mysql-connector-python`). Credentials are not stored in the baseline files.

With a fresh, already-created database and connection settings present:

```text
python scripts/apply_mysql_schema.py
```

Once the schema exists:

```text
python scripts/load_mysql.py
```

The data transaction proceeds in dependency order: catalogue records, case definitions, case-code-domain relations, then the `prototype_baseline` row. The final baseline row is inserted only after all runtime components are present. Before commit, the loader re-reads all four component sets and requires exact equality with the versioned inputs.

On any exception or mismatch, all DML performed by that run is rolled back.

## 5. Re-import semantics

A baseline identifier is treated as immutable:

- if the same `PROTOBASE-*` already exists and every persisted row is identical, the run returns `no_op`;
- if the same baseline/component identifier exists with different rows, the run fails rather than updating it;
- if an existing exact subset/case component is reused by another compatible prototype baseline, it may be reused without duplication;
- an intentional content change requires a new baseline identifier/version.

There is deliberately no `INSERT ... ON DUPLICATE KEY UPDATE` path.

## 6. Runtime persistence boundary

The runtime database contains only:

- baseline/version metadata;
- the 13 selected catalogue records;
- the eight synthetic case definitions;
- the 18 defined case-code relations and their explicit `is_acceptable` membership.

It does not contain expected feedback classes, expected determining rules, expected criterion keys, expected explanation elements, or the `RC-*` answer key. Learner-attempt history is also outside the present scope.

This separation allows the later reference-test harness to compare the PHP evaluator against `RCBASE-*` without giving the evaluator the expected result it is being tested against.

## 7. Database-independent contract checks

The small `tests/test_runtime_contract.py` suite checks the runtime input allowlist, normalized record counts, acceptable sets, the `CASE-004` visibility boundary, and the canonical runtime-data digest without requiring a database:

```text
python -m unittest -v tests/test_runtime_contract.py
```

These checks are preparatory controls only. They do not replace `TEST-DAT-02`, because persistence conformance still has to be executed against the actual MySQL version used for the frozen prototype. In particular, a successful `--check-only` run proves that the runtime input contract is internally consistent; it does not prove that the schema was successfully created or that the transaction was successfully committed by MySQL.

## 8. Live MySQL persistence test

After schema application and the first runtime load, the persistence-specific integration suite is:

```text
python -m unittest -v tests/test_mysql_persistence.py
```

It queries the live database independently of the loader and verifies the runtime table boundary, baseline identity, row/domain counts, acceptable sets, rule-relevant case values, orphan absence, and active foreign-key enforcement. It does not read the `RCBASE-*` oracle.

The schema application, first import, identical re-import, and persistence suite have all been executed against a real MySQL instance in the actual project environment, with observations retained as development evidence in `docs/CHANGELOG.md` (2026-08-07). The principal frozen verification must still execute the applicable checks again against the identified final baseline once that freeze happens.

## 9. Compose bootstrap coordinator

`scripts/bootstrap_mysql.py` is the non-destructive coordinator used by the
Docker stack. It first inspects the database. An empty database receives the
runtime DDL; a database containing exactly the four expected runtime tables
reuses its schema; a partial or unexpected table set fails rather than being
silently repaired. The coordinator then invokes the same versioned loader
described above, so an identical existing baseline remains a `no_op` and a
conflicting baseline remains an error.

`Dockerfile.bootstrap` packages only the runtime inputs, database scripts, and
data/persistence tests. The `verification/` directory and RCBASE answer key are
deliberately not copied into this image. The surrounding Compose/wrapper files
are documented in `../prototype_stack/README.md`.
