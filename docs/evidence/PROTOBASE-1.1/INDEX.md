# PROTOBASE-1.1 localization-correction evidence

This directory is the direct evidence package for
`docs/CONFORMANCE_REPORT_PROTOBASE_1_1.md`. The run starts from
`docker compose --profile test down -v`, rebuilds the relevant images from
the working source against the repository's pinned third-party digests, and
uses only Selenium for browser automation.

## Logs

- `00-environment.txt`: host/tool versions, repository state and pinned image
  references before the run.
- `01-clean-down.txt`: clean Compose teardown including the database volume.
- `02-build.txt`: bootstrap/application/test image build. The frontend-build
  stage includes four localization-contract tests and the production Vite
  build.
- `03-data-contract.txt`: deterministic subset, forward-verification,
  materialized-dataset and runtime-manifest checks.
- `04-full-application-suite.txt`: complete containerized PHPUnit run (unit,
  integration and Selenium E2E).
- `05-persistence.txt`: live MySQL persistence checks and idempotent bootstrap
  evidence.
- `06-runtime-smoke-and-isolation.txt`: health/patient API checks, component
  counts, oracle checksum/isolation and production-image inspection.
- `07-screenshots.txt`: Selenium screenshot command and emitted paths.
- `08-final-down.txt`: final Compose teardown.
- `SHA256SUMS`: digests for the evidence files, excluding itself.

## Screenshots

- `01-roster-en-light.png`: complete British-English roster in light mode.
- `02-roster-de-dark.png`: complete Austrian-German roster in persisted dark
  mode.
- `03-tutorial-de-dark.png`: German tutorial step 4 and feedback legend.
- `04-feedback-de-e03-4-dark.png`: corrected value-aware German `E03.4`
  feedback.
- `05-feedback-en-e03-4-dark.png`: the same submitted result after switching
  to British English.

The screenshot script is committed at
`app/scripts/capture_localization_screenshots.php`; it drives the real app
through the same Selenium service used by the E2E suite.
