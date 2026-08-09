# Project documentation set

This folder documents the **implemented prototype and how to use it**, as
distinct from the **specification** it implements. The control artefacts at
the repository root remain the upstream authority for *what the software is
supposed to do and why*; since the 8-9 August 2026 forward redesign
(patient/question model, `MODELBASE-0.2`/`RULEBASE-0.2`), the current
authority is the `_0_2`/`_0_5`/`_0_6`/`_0_7`-suffixed siblings of the
original `chapter3_*.md` files (e.g. `chapter3_rule_catalogue_0_2.md`,
`chapter3_requirements_catalogue.md` now at `0.6`) — see `HANDOFF.md` §1 for
the exact current reading order. Nothing in this folder overrides them;
where the two disagree, the control artefacts win and the discrepancy should
be logged in [CHANGELOG.md](CHANGELOG.md).

| File | Answers | Audience |
|---|---|---|
| [USER_GUIDE.pdf](USER_GUIDE.pdf) ([LaTeX source](USER_GUIDE.tex)) | *How do I install and use the prototype?* Quick start, learner (patient/question) workflow, language and appearance settings, lifecycle commands, optional test run, troubleshooting, and scope cautions. | Prototype users, demonstrators, evaluators, and new project contributors. |
| [DEVELOPMENT_DOCUMENTATION.md](DEVELOPMENT_DOCUMENTATION.md) | *Why does the implementation look the way it does?* Technology choices, architectural decisions, UI/backend design principles, the forward redesign's own rationale (§13-14), how the build fits the project's Design Science Research framing. | Thesis chapter drafting; anyone who needs the rationale, not just the result. |
| [IMPLEMENTATION_SPECIFICATION.md](IMPLEMENTATION_SPECIFICATION.md) | *What exactly was built?* Concrete data model, rule-engine contract, API contract, frontend structure, build/deploy contract, and test inventory, as it exists right now. | Anyone extending or auditing the code; the appendix-style reference. |
| [REQUIREMENTS_TRACEABILITY.md](REQUIREMENTS_TRACEABILITY.md) | *Does every requirement actually have a verification destination?* Every `REQ-*` entry in the current catalogue audited against real evidence, not planned intent, with an explicit status for the few genuinely deferred to a still-open supervisor decision or to thesis-writing. | `REQ-TRC-01` compliance; a ready draft for the thesis appendix. |
| [CHANGELOG.md](CHANGELOG.md) | *What changed, and when?* Dated, chronological log of implementation increments. | Tracing how the current state was reached; thesis "development process" narrative. |

## The frozen evidence package (not part of the living set above)

`CLAUDE.md`'s "five living documentation sources" are the table above —
each expected to keep changing as the implementation does. Two further
files exist alongside them but are deliberately **not** living documents:
they are the frozen result of the `PROTOBASE-1.0` development freeze
(implementation-order step 10), dated and not meant to be silently
rewritten as development continues on `develop`.

| File | Answers | Audience |
|---|---|---|
| [CONFORMANCE_REPORT.md](CONFORMANCE_REPORT.md) | *Did the frozen baseline actually pass its checks?* The `REQ-VER-05` formal conformance report: baseline/version table, execution-environment record, every executed check and its result, and the exact scope of what is/isn't frozen. | The principal verification record for Chapter 3/the final evaluation chapter; not to be confused with the ongoing `CHANGELOG.md`. |
| [environment_manifest_0_1.json](environment_manifest_0_1.json) | *Exactly which container image, by digest, does each pinned service resolve to?* Per-image resolved version and manifest-list digest, `status: "frozen_at_protobase_1_0"`. | Reproducing the frozen execution environment exactly; the source data `CONFORMANCE_REPORT.md` §2 summarizes. |

If a further freeze ever happens (a `1.1`, etc.), it should get its own
dated report rather than overwriting this one — see
`docs/DEVELOPMENT_DOCUMENTATION.md` §19 for why `master`/`develop` are
split the way they are.

## Keeping this set current

This documentation is meant to track the implementation continuously, not to
be a one-time snapshot. The repository's `CLAUDE.md` records the concrete
rule for when each file must be touched. In short: **every material
implementation change gets a CHANGELOG entry; every change to a contract
(schema, rule, API, build) updates the specification; every new design
decision gets a rationale note in the development documentation; and every
user-visible setup, workflow, or troubleshooting change updates the LaTeX
user guide and its compiled PDF.**

### Rebuilding the user guide

From the repository root, regenerate the PDF with:

```bash
latexmk -pdf -interaction=nonstopmode -halt-on-error -outdir=docs docs/USER_GUIDE.tex
latexmk -c -outdir=docs docs/USER_GUIDE.tex
```

The cleanup command removes only LaTeX intermediate files; it retains
`USER_GUIDE.pdf`. Commit the updated `.tex` and `.pdf` together.
