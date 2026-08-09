# Browser-driven end-to-end tests

Selenium-based (project decision, see `docs/CHANGELOG.md` 2026-08-07 —
Playwright is not used). Drives a real Chrome browser against a real
running application stack; nothing here is mocked.

The suite contains the formal `TEST-E2E-01` learner workflow and
`TEST-E2E-02` verification-only boundary plus frontend-only regressions for
patient completion and the first-visit tutorial. A fresh WebDriver profile
has no tutorial-seen flag, so ordinary workflow helpers dismiss the real
modal before continuing; `TutorialTest` separately verifies the complete
first-visit/manual-replay lifecycle. There is no test-only switch that
suppresses production onboarding behavior.

## 1. Start the application stack

Either the full Compose stack:

```bash
cd prototype_stack
APP_SOURCE_DIR=.. docker compose --env-file .env -f compose.yaml up -d --wait db
docker compose --env-file .env -f compose.yaml run --rm --no-deps bootstrap
docker compose --env-file .env -f compose.yaml up -d --wait app
```

or the PHP built-in dev server (`app/router.php`, see
`docs/IMPLEMENTATION_SPECIFICATION.md` §6.4) with a reachable MySQL baseline
already loaded. Either way, note the port the app is published/listening on
(`5860` for Compose by default).

## 2. Start Selenium

```bash
cd app/tests/E2E
docker compose up -d
```

This starts a standalone Selenium+browser container on `http://127.0.0.1:4444`
(WebDriver) and `http://127.0.0.1:7900` (noVNC — open it in a browser with
password `secret` to watch the tests drive the browser live, if useful). The
image is `seleniarm/standalone-chromium` by default (arm64 hosts, e.g. Apple
Silicon — `selenium/standalone-chrome` has no official arm64 Linux build);
switch the image in `docker-compose.yml` to `selenium/standalone-chrome:latest`
on amd64 hosts if you prefer actual Chrome over Chromium.

## 3. Run the suite

From the repository root:

```bash
php app/vendor/bin/phpunit -c app/phpunit.xml --testsuite e2e
```

Override these if the defaults don't match your setup:

| Variable | Default | Meaning |
|---|---|---|
| `ICD_E2E_SELENIUM_URL` | `http://127.0.0.1:4444` | Where the PHP test process talks to the Selenium/WebDriver server |
| `ICD_E2E_BROWSER_BASE_URL` | `http://host.docker.internal:5860` | Where the *browser* (running inside the Selenium container) navigates to reach the app |
| `ICD_E2E_BASE_URL` | `http://127.0.0.1:5860` | Where the *PHP test process* sends direct HTTP calls for the hidden `VQ-*` reachability checks |

`host.docker.internal` works out of the box on Docker Desktop (macOS/Windows);
on Linux the `docker-compose.yml` here adds the `host-gateway` mapping so it
resolves the same way (Docker Engine >= 20.10).

## 4. Stop Selenium

```bash
cd app/tests/E2E && docker compose down
```

This suite is intentionally excluded from `php vendor/bin/phpunit`'s default
run when no `--testsuite` is given only in the sense that it will fail fast
and clearly (connection refused) if Selenium/the app aren't running - it is
still listed as a real testsuite in `phpunit.xml`, not silently skipped.
