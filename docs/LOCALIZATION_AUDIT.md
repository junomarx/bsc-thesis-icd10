# Localization audit — `PROTOBASE-1.1`

**Audit date:** 9 August 2026  
**Locales:** Austrian German (`de-AT`) and British English (`en-GB`)  
**Scope:** learner-facing localization only; evaluation meaning, rule precedence, classifications, criteria, option membership, scoring, layout and source-domain scope are unchanged.  
**Previous frozen revision:** `PROTOBASE-1.0`, preserved by the immutable `dev-freeze` through `dev-freeze-4` tags and [CONFORMANCE_REPORT.md](CONFORMANCE_REPORT.md). Its predefined conformance run found no deviations; this later audit found localization defects that those tests did not cover.  
**Corrected revision:** `PROTOBASE-1.1`; final freeze evidence is recorded separately in `CONFORMANCE_REPORT_PROTOBASE_1_1.md`.

## 1. Complete machine-readable inventory

The complete, per-entry inventory is [localization_inventory.json](localization_inventory.json). It contains **349 entries**, each with the required location/key, English value, German value, interface area, interpolation fields, origin, audit result, correction, and intentional-non-translation status.

| Inventory group | Entries | Policy / coverage |
|---|---:|---|
| UI translation keys | 94 | Exact EN/DE key and placeholder parity; no opposite-language fallback |
| Patient summaries | 6 | English database text + ID-keyed German presentation |
| Patient names | 6 | Proper names intentionally identical |
| Context records | 32 | Every learner-visible context row in both locales |
| Question titles | 25 | Every learner-visible question |
| Question prompts | 25 | Every learner-visible question |
| Displayed catalogue codes | 87 | Explicit British-English presentation; authoritative German DIAGLIST short designation unchanged |
| Evaluation branches | 9 | All terminal explanation templates, including both `RULE-NOA-01` outcomes |
| Feedback fact/relation combinations | 58 | Every distinct hard/spec/temporal relation fact and represented value |
| Intentional non-translations | 7 policy entries | Codes, rule/criterion IDs, technical IDs, official German catalogue text and build identity |

The inventory is reproducible with:

```bash
node app/frontend/scripts/generate-localization-inventory.js docs/localization_inventory.json
```

The 58 fact entries are generated through the production PHP formatter, not copied into a separate audit-only translation list.

## 2. String-source inventory and ownership

| Source | Learner-visible content | Origin / localization boundary |
|---|---|---|
| `app/frontend/src/lib/i18n.jsx` | Header, disclaimer, controls, tutorial, states, status labels, completion, accessibility text, footer | Application dictionary; strict locale lookup |
| `contentTranslations.js` + patient/question/context CSVs | 6 summaries, 32 context records, 25 titles, 25 prompts | English runtime content plus ID-keyed German presentation asset |
| `catalogueTranslations.js` + `subset_0_2.csv` | 87 displayed code designations | British-English presentation asset; German remains authoritative catalogue data |
| `Evaluator.php` | Correct, NOA, status, depth, evidence, specificity, relation feedback | Backend-generated bilingual result contract |
| `LocalizedFactFormatter.php` + typed question facts | Value-aware hard/temporal/specificity clauses | General fact-key/value mechanism; no question/code branches and no `learner_label` consumption |
| API/gate reason keys | Malformed/unsupported/gate/evaluation failure states | Machine reason remains in API; frontend maps an allowlisted reason to localized prose and uses a localized generic for unknown values |
| React components | Page composition, accessible attributes, fallback policy | No learner prose or internal identifier fallback remains in component code |
| `index.html`, footer and technical-details disclosure | Initial document title, version/build identity, technical IDs | React updates title and `<html lang>`; technical IDs remain visible only in the disclosure |

No additional learner-visible literal was found in icon paths, CSS, theme storage, playthrough state, or API field names. `EN`, `DE`, the separator, patient proper names and product/build identity are deliberately identical where noted in the inventory.

## 3. Defects found and corrected

### 3.1 Systemic mixed-language and abstract feedback

| Defect | Root cause | Correction |
|---|---|---|
| German `RULE-REL-HARD-01` output interpolated English, e.g. `E03.4 widerspricht dem dokumentierten Documented aetiology …` | `question_fact.learner_label` is English authoring metadata and `Evaluator` reused it for both languages | Added `LocalizedFactFormatter`, keyed only by semantic fact key and typed value; 58 current relation combinations have EN/DE clauses |
| Relation feedback named an abstract field (`Current consciousness state`) rather than the represented state | The evaluator selected the label but discarded the fact value | Feedback now states the value, e.g. “the patient remains unconscious” / “der Patient weiterhin bewusstlos ist” |
| `RULE-REL-SPEC-01` used generic acceptance prose and kept the English label in `supported_detail` | Same label-first explanation construction | Both prose variants use the value-aware clause; the structured element contains the typed fact value |
| Missing fact/link metadata could degrade to a humanised raw `fact_key` or `reason_key` | Permissive fallback in evaluator | Missing formatter/link metadata now raises a specification gap; automated coverage prevents it in the frozen data |

Representative correction:

| Before | After |
|---|---|
| `E03.4 widerspricht dem dokumentierten Documented aetiology für diese Frage.` | `E03.4 widerspricht der dokumentierten Angabe, dass eine postinfektiöse Ätiologie vorliegt.` |
| `R55 … Current consciousness state …` | `R55 widerspricht der dokumentierten Angabe, dass der Patient weiterhin bewusstlos ist.` |

### 3.2 Acceptance and none-of-above wording

- Replaced English “declared acceptable response” and German “erklärte akzeptable Antwort” with support from the documented information.
- Rewrote both `RULE-NOA-01` branches as two natural sentences and corrected German quotation marks.
- Classification, criteria, set-membership condition, improvement semantics and precedence were not changed.

### 3.3 Frontend fallback and identifier leakage

- `t()` previously resolved a missing German key through English and then through the raw key. It is now strict; EN/DE parity and interpolation parity are checked automatically.
- German patient/question content previously fell back to English. It now rejects a missing ID; completeness checks prove all 6/32/25 entries exist.
- English catalogue display previously fell back to the official German designation. All 87 displayed codes are now required to have explicit English presentation text.
- German result text previously fell back to `explanation` when `explanation_de` was absent. Both are now required by runtime tests and rendering.
- Unknown gate reasons, enum values, question IDs and backend error tokens could appear as prose. They now resolve to localized generic states or stay within the explicitly expanded technical-details area.
- The roster no longer interpolates raw backend/network messages. Patient, question, evaluation, loading, failure and not-found states have localized learner copy.
- `<html lang>` and the browser title now follow the selected locale (`de-AT` / `en-GB`).

### 3.4 Austrian-German grammar, terminology and punctuation

- `Diesen Patient:in …` → `Möchten Sie die Bearbeitung für diese:n Patient:in …`.
- `Andere Patient:innen wählen` → `Andere:n Patient:in auswählen`.
- `Patient:in auswerten` → `Patient:in im Überblick`; completion/replay wording was aligned with a coding exercise rather than a game or evaluation of a person.
- Tutorial wording was corrected from a person “containing” questions and from a completed “case” to a person’s coding workflow.
- `keine Status migrainosus` → `kein Status migrainosus` in context and prompt.
- Sophie Mayer’s prompt now refers to `die Patientin`, not `der Patient`.
- `45 ml/min/1,73 m²` → `45 mL/min/1,73 m²`.
- `„!"`, `„Keine der genannten"` → `„!“`, `„Keine der genannten“`.
- Status and stable-phase FEV1 sentences were reordered to avoid invalid compounds such as `stationär-Kontext` and unnatural `stabile FEV1` phrasing.

### 3.5 British-English consistency and natural wording

- Learner-visible `Generalized`, `Localized` and `Localization` were normalised to `Generalised`, `Localised` and `Localisation` in runtime content and five catalogue presentations.
- “cannot provide an anamnesis” was replaced with “cannot provide a medical history” in the patient summary and context record.
- Machine enum values such as `generalized_idiopathic` remain unchanged because they are technical data, not learner prose.

### 3.6 Accessibility and compact controls

- Language and theme controls retain localized `aria-label`, action title, hidden text and state.
- Tutorial close/progress labels remain localized for all four steps.
- The selected locale now sets the document language, improving pronunciation and language selection for assistive technology.
- No layout or visual-style change was required; both localized variants fit the existing responsive controls in Selenium.

## 4. Localized feedback architecture

`LocalizedFactFormatter::clauses(QuestionFact)` returns an English and German subordinate clause for a supported semantic fact key/value. `Evaluator` wraps those clauses in one hard-conflict or specificity sentence. The mechanism:

- never reads `learner_label`;
- has no patient, question or ICD-code condition;
- preserves the typed value used by rule evaluation;
- fails closed for unsupported localization metadata;
- covers every current feedback-linked relation through a database-driven integration test;
- leaves evaluation predicates and precedence untouched.

The runtime data contract did not need a schema revision: existing fact keys and typed values are sufficient. `learner_label` remains English authoring metadata but is no longer a learner-prose source.

## 5. Automated and runtime coverage

| Check | Coverage |
|---|---|
| Frontend Node audit | 94-key parity/placeholders; strict fallback policy; 6 patients; 32 contexts; 25 questions; 87 catalogue codes; British-English checks |
| PHP unit localization tests | Fact-clause pairs, unsupported-fact failure, natural correct feedback, both NOA branches |
| PHP integration localization tests | All 143 reference responses; exact class/rule/criterion conformance; all 58 relation fact/value combinations; mixed-language/raw-ID guards |
| Selenium bilingual workflow | All 6 patients, 32 context records, 25 questions and their option lists in each locale; 50 submitted question views; all completion screens; both tutorials |
| Selenium branch audit | Every learner-reachable determining rule and both NOA outcomes in EN and DE; `RULE-DEPTH-01`/`RULE-STATUS-01` are verification-only/non-displayed and covered through the 143-row integration audit |
| Architecture isolation | Oracle remains test-harness-only and absent from runtime/bootstrap/production paths |

Exact final commands, counts, environment and results are in [CONFORMANCE_REPORT_PROTOBASE_1_1.md](CONFORMANCE_REPORT_PROTOBASE_1_1.md) and the freeze evidence package.

## 6. Deliberately untranslated content

- ICD-10 codes.
- `RULE-*` identifiers, matched-rule lists and machine-readable criteria, visible only after opening Technical details.
- Baseline/source identifiers and API field names when used as technical data; they are not rendered as learner prose.
- Patient proper names and author/product identity values.
- `EN` / `DE` language abbreviations.
- Official Austrian DIAGLIST German designations, preserved byte-for-byte in `subset_0_2.csv`; English descriptions are presentation translations only.

## 7. Semantic safeguard and residual limitation

`RCBASE-0.3` remains byte-identical at SHA-256 `21c3f02697fe9b20028ec1121d28fce3389c027705372ae08c43f894b3342540`. All 143 expected classes, determining rules, criteria, improvement semantics and precedence remain unchanged; the class distribution is still 33 correct / 20 suboptimal / 90 incorrect. No oracle field or file entered the runtime data path.

Residual limitation: the Austrian source catalogue has no official English counterpart in this repository. English option titles are explicitly reviewed interface presentations for the 87 displayed codes, not authoritative replacements for the German records. Expanding the displayed option set therefore requires adding and reviewing an English presentation before the strict frontend check will pass.
