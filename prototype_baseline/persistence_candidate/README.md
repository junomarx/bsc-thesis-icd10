# MODELBASE-0.2 persistence implementation

**Status:** frozen runtime implementation, current localization-correction
identity `PROTOBASE-1.1`. The normalized schema and all semantic identifiers
remain the `MODELBASE-0.2` design; live MySQL and application verification are
recorded in `docs/CONFORMANCE_REPORT_PROTOBASE_1_1.md`.

Requirements forward revision `0.7` and `UXBASE-0.1` postdate the original persistence-candidate assembly. They add immediate-feedback/review and presentation requirements but deliberately do not add database entities: playthrough progress and completion state remain transient frontend concerns. The runtime manifest records `forward-0.7`; all physical schema/data cardinalities below remain unchanged.

The 9 August `APIBASE-0.1` clarification likewise leaves row cardinalities unchanged. It formalizes `information_boundary` as a valid patient-context type, so the candidate DDL and runtime preflight now enforce the complete context-type vocabulary. The preflight also retains the cross-row specificity invariant: every `less_specific_supported.improvement_code` must be an `accepted_reference` for the same question, not merely an existing catalogue code.

This directory implements `DATAMIG-0.2`. It remains separate from the
historical application baseline, but it is now the loader used by the real
PHP/React application. The `PROTOBASE-1.1` increment changes localized
presentation strings and hashes only; it does not revise the schema,
evaluation model, row cardinalities, or `RCBASE-0.3` oracle.

## Files

- `mysql_schema_0_2.sql`: nine runtime tables for catalogue, patient, context, question, fact, relation, option and baseline data.
- `runtime_manifest_0_2.json`: frozen `PROTOBASE-1.1` identity, explicit runtime-file allowlist, per-file SHA-256 values and expected counts. It contains no `RCBASE-*` identifier.
- `runtime_data_0_2.py`: reads only the allowlisted runtime authoring files, verifies their hashes, normalizes typed values and rejects structural/semantic violations before a database connection is opened.
- `apply_mysql_schema_0_2.py`: applies DDL only to an empty database. DDL is separate because MySQL schema operations can commit implicitly.
- `load_mysql_0_2.py`: immutable, transactional DML loader. It inserts in dependency order, reads all persisted components back before commit, treats an identical re-import as `no_op`, and rejects conflicting content under an existing version ID.
- `test_runtime_contract_0_2.py`: database-independent boundary checks.
- `test_mysql_persistence_0_2.py`: live-MySQL integration checks. It skips if the MySQL connector/database configuration is unavailable; a skipped run is not a pass.

The scripts expect the sibling `data/` directory and this directory's runtime manifest under the forward-design root. When integrating into the real repository, preserve those relative relationships or adjust `MANIFEST_RELATIVE` and the base-directory wiring explicitly. Do not overwrite the historical `PROTOBASE-0.2` data in place.

## Low-level import sequence

1. `runtime_data_0_2.py` verifies the manifest, each allowlisted file SHA-256, headers, baseline identifiers, foreign-key-like relations, typed values, semantic relation vocabulary, option membership and the current `99/6/32/33/88/118/182/120` component counts.
2. `apply_mysql_schema_0_2.py` refuses a non-empty target and creates the nine runtime tables.
3. `load_mysql_0_2.py` performs a read-before-write conflict check.
4. Missing components are inserted inside one DML transaction in the order catalogue → patients → context → questions → facts → question/code relations → relation/fact links → options → prototype metadata.
5. The loader re-queries every component in canonical order and compares it exactly with the normalized input. Only then does it commit.
6. An identical second run returns `no_op`; differing content under the same versioned component/baseline identifier raises a conflict and rolls back.

The external `verification/reference_responses_0_3.csv` file is not in the
manifest, is never opened by this code, and has no database table.

## Commands for the real repository environment

Run the pure checks first:

```bash
python persistence_candidate/load_mysql_0_2.py --check-only
python -m unittest -v persistence_candidate/test_runtime_contract_0_2.py
```

Then provide a clean MySQL database and `mysql-connector-python`:

```bash
export ICD_DB_HOST=127.0.0.1
export ICD_DB_PORT=3306
export ICD_DB_NAME=icd_prototype
export ICD_DB_USER=icd_app
export ICD_DB_PASSWORD='...'

python persistence_candidate/apply_mysql_schema_0_2.py
python persistence_candidate/load_mysql_0_2.py     # must report: inserted
python persistence_candidate/load_mysql_0_2.py     # must report: no_op
python -m unittest -v persistence_candidate/test_mysql_persistence_0_2.py
```

Record the actual MySQL version, application commit/revision, commands, first/second import statuses, and test output. Do not carry the old `PROTOBASE-0.2` persistence result forward as evidence for this schema.

## Current downstream boundary

The PHP repository/evaluator and React learner flow consume this model. Any
future data change must repeat the manifest/digest derivation, persistence
checks, 143-row semantic regression and bilingual localization audit. The
oracle remains an external test input and must not enter this runtime path.
