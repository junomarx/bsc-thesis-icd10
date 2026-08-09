# Legacy regression migration bridge

The files in this directory do not claim to be the missing canonical `CASEBASE-0.2`/`RCBASE-0.2` CSVs.

The available implementation documentation states that `CASE-001` through `CASE-004` and their 14 reference-response rows were unchanged when the baseline moved from `0.1` to `0.2`, apart from baseline identifiers. It separately records four additions made during the 7 August 2026 pre-freeze coverage review: `CASE-005` through `CASE-008` and `RC-005-01` through `RC-008-01`.

The three `*_additions_provisional.csv` files reconstruct only those four documented additions. Every reconstructed row carries an explicit provenance status. They exist so the forward migration can preserve all 18 regression obligations while the original repository files are temporarily unavailable.

Before the final software/data/oracle freeze, compare these rows with the canonical `0.2` files. If the semantic fields agree, replace the provisional provenance with the canonical historical source. If any field differs, the canonical record takes precedence and the forward mapping must be corrected before verification.
