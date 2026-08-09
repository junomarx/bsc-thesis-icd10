# Austrian ICD-10 Educational Prototype

A bachelor-thesis Design Science Research artefact: a small web application
for practising a bounded, versioned subset of Austrian ICD-10 (BMASGPK
2026) coding against six synthetic patients and 25 atomic coding questions,
evaluated by explicit, traceable, deterministic rules — not a real
diagnostic or clinical-decision-support tool.

> **Educational use only.** The patients and questions are synthetic. This
> tool does not diagnose patients, does not provide clinical decision
> support, and is not used for official coding, reporting, or
> reimbursement. Do not enter real patient data.

## What it does

A learner chooses one of six synthetic patients, works through that
patient's independent coding questions one at a time, and submits either a
supported ICD-10 code or **None of the above**. Each answer is evaluated
immediately against a deterministic rule engine and returned as
**Correct**, **Suboptimal**, or **Incorrect**, together with an
explanation and, where applicable, a suggested improvement code. A
patient-completion review shows raw per-class counts — never a weighted
score. The interface is available in English and German.

## Quick start

```bash
git clone https://github.com/junomarx/bsc-thesis-icd10.git
cd bsc-thesis-icd10
docker compose pull
docker compose up -d --wait app
```

Open <http://localhost:5860>, or check the API directly:

```bash
curl http://127.0.0.1:5860/api/health     # {"status":"ok"}
curl http://127.0.0.1:5860/api/patients   # the 6 synthetic patients
```

Full install/troubleshooting guide: [docs/USER_GUIDE.pdf](docs/USER_GUIDE.pdf).
Building locally instead (`docker compose build bootstrap app`) also works
and always matches whatever is in your checkout, including uncommitted
changes.

## Stack

React 19 + Vite (frontend) → PHP 8.4, no framework (backend) → MySQL via
Docker (persistence). Python handles offline data preparation. See
[docs/IMPLEMENTATION_SPECIFICATION.md](docs/IMPLEMENTATION_SPECIFICATION.md)
for exact versions, schema, and API contracts.

## Repository layout

```text
app/                              PHP backend (src/), React frontend (frontend/), tests (tests/)
prototype_baseline/               active Python data-preparation pipeline + MySQL persistence loader
prototype_stack/                  stack.sh-managed Docker Compose deployment scaffold
docs/                             living documentation — see docs/README.md for what's what
chapter3_*.md                     upstream methodological specification (rule/data-model authority)
archived/                         superseded pipeline, pre-implementation planning docs, a one-time delivery drop — kept for reference, nothing here is live
Dockerfile, docker-compose.yml    self-contained, publishable Docker bundle (repo root, see below)
```

`Dockerfile`/`docker-compose.yml` live at the repository root rather than
under `app/` so that `prototype_stack/stack.sh sync` can pull this whole
repository as the application source — see
[docs/DEVELOPMENT_DOCUMENTATION.md](docs/DEVELOPMENT_DOCUMENTATION.md) §10.2.

## Documentation

Start with **[HANDOFF.md](HANDOFF.md)** — the current, dated snapshot of
what exists, what's verified, and what's next. It also gives the reading
order for everything else. In short:

| Document | Answers |
|---|---|
| [HANDOFF.md](HANDOFF.md) | What's the current state, and what's next? |
| [docs/USER_GUIDE.pdf](docs/USER_GUIDE.pdf) | How do I install, use, and troubleshoot it? |
| [docs/IMPLEMENTATION_SPECIFICATION.md](docs/IMPLEMENTATION_SPECIFICATION.md) | What exactly was built — schema, API, build/deploy contract? |
| [docs/DEVELOPMENT_DOCUMENTATION.md](docs/DEVELOPMENT_DOCUMENTATION.md) | Why does it look the way it does? |
| [docs/REQUIREMENTS_TRACEABILITY.md](docs/REQUIREMENTS_TRACEABILITY.md) | Does every requirement have real verification evidence? |
| [docs/CHANGELOG.md](docs/CHANGELOG.md) | What changed, and when? |
| `chapter3_*.md` (repository root) | The upstream methodological specification this implementation realizes |

## Testing

```bash
docker compose --profile test up -d --wait selenium
docker compose --profile test run --rm test
```

Browser-driven tests use [Selenium](https://www.selenium.dev/) via
`php-webdriver/webdriver`. The suite targets the current patient/question
model (77 unit / 160 integration / 7 e2e tests, all passing as of the last
verified run); see [HANDOFF.md](HANDOFF.md) for exact counts and how they
were verified.

## Status

Not frozen. Currently mid-way through a structural forward redesign (see
[HANDOFF.md](HANDOFF.md) §0 for the full 10-step implementation order and
exactly where it stands) — the learner-facing application is functional,
polished, covered by a genuinely passing automated test suite, and its
verification oracle is human-oracle-audited against source with zero
discrepancies. A formal freeze + principal verification run (step 10) is
the remaining piece of work before a `1.0` baseline can be considered.

## License

Bachelor-thesis academic artefact. No open-source license is granted; see
the repository owner for reuse terms.
