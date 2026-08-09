import { readFileSync, writeFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { CODE_DESIGNATION_EN } from '../src/lib/catalogueTranslations.js'
import { CONTEXT_ITEM_DE, PATIENT_SUMMARY_DE, QUESTION_CONTENT_DE } from '../src/lib/contentTranslations.js'

const repositoryRoot = fileURLToPath(new URL('../../../', import.meta.url))
const outputPath = process.argv[2]
if (!outputPath) throw new Error('Output path is required')

function read(relativePath) {
  return readFileSync(repositoryRoot + relativePath, 'utf8')
}

function parseCsv(text) {
  const rows = []
  let row = []
  let field = ''
  let quoted = false
  for (let index = 0; index < text.length; index += 1) {
    const character = text[index]
    if (quoted) {
      if (character === '"' && text[index + 1] === '"') {
        field += '"'
        index += 1
      } else if (character === '"') {
        quoted = false
      } else {
        field += character
      }
    } else if (character === '"') {
      quoted = true
    } else if (character === ',') {
      row.push(field)
      field = ''
    } else if (character === '\n') {
      row.push(field.replace(/\r$/, ''))
      rows.push(row)
      row = []
      field = ''
    } else {
      field += character
    }
  }
  if (field || row.length) {
    row.push(field)
    rows.push(row)
  }
  const [header, ...values] = rows
  return values.filter((value) => value.length === header.length).map((value) => Object.fromEntries(
    header.map((name, index) => [name.replace(/^\uFEFF/, ''), value[index]]),
  ))
}

function csv(filename) {
  return parseCsv(read(`/prototype_baseline/data/${filename}`))
}

function extractUiStrings() {
  const source = read('/app/frontend/src/lib/i18n.jsx')
  const prefix = 'const STRINGS = '
  const start = source.indexOf(prefix)
  const end = source.indexOf('\n\nfunction detectDefaultLocale', start)
  return Function(`"use strict"; return (${source.slice(start + prefix.length, end)})`)()
}

function placeholders(...values) {
  return [...new Set(values.flatMap((value) => [...value.matchAll(/\{([^}]+)\}/g)].map((match) => match[1])))]
}

const correctedUiKeys = new Set([
  'tutorial.step1.body', 'tutorial.step4.body', 'roster.error', 'question.exitConfirm',
  'question.notEvaluatedBody', 'gateReason.none_option_not_defined', 'question.reviewPatient',
  'review.heading', 'review.playAgain', 'review.chooseAnother', 'footer.build',
])
const addedCoverageKeys = new Set([
  'app.browserTitle', 'patient.loadError', 'question.loading', 'question.loadError',
  'question.notFound', 'question.returnToRoster', 'gateReason.evaluation_failed',
  'gateReason.unknown', 'review.questionUnavailable', 'value.unknown',
])
const entries = []

function add(entry) {
  entries.push({
    interpolation: placeholders(entry.en ?? '', entry.de ?? ''),
    intentional_non_translated: false,
    correction_made: null,
    ...entry,
  })
}

const strings = extractUiStrings()
for (const key of Object.keys(strings.en).sort()) {
  let correction = null
  if (correctedUiKeys.has(key)) correction = 'Localized wording, grammar, terminology, punctuation, or raw-error leakage corrected.'
  if (addedCoverageKeys.has(key)) correction = 'Explicit localized state added to prevent a blank, raw-token, or opposite-language fallback.'
  add({
    id: `ui:${key}`,
    location: `app/frontend/src/lib/i18n.jsx#${key}`,
    en: strings.en[key],
    de: strings.de[key],
    interface_area: key.split('.')[0],
    origin: 'application translation dictionary',
    audit_result: correction ? 'pass_after_correction' : 'pass_no_change',
    correction_made: correction,
  })
}

const patients = csv('patients_0_1.csv')
for (const patient of patients) {
  add({
    id: `patient-summary:${patient.patient_id}`,
    location: `prototype_baseline/data/patients_0_1.csv#${patient.patient_id} + contentTranslations.js`,
    en: patient.general_health_summary,
    de: PATIENT_SUMMARY_DE[patient.patient_id],
    interface_area: 'patient roster and dossier summary',
    origin: 'database content plus frontend translation asset',
    audit_result: patient.patient_id === 'PATIENT-006' ? 'pass_after_correction' : 'pass_no_change',
    correction_made: patient.patient_id === 'PATIENT-006' ? 'Replaced unnatural “provide an anamnesis” with “provide a medical history”.' : null,
  })
  add({
    id: `patient-name:${patient.patient_id}`,
    location: `prototype_baseline/data/patients_0_1.csv#${patient.patient_id}.display_name`,
    en: patient.display_name,
    de: patient.display_name,
    interface_area: 'patient roster, dossier, and completion',
    origin: 'database content',
    audit_result: 'pass_intentionally_identical',
    intentional_non_translated: true,
    correction_made: 'Proper name; identical in both locales by policy.',
  })
}

const correctedContext = new Map([
  ['CTX-003-03', 'British English spelling: Generalized → Generalised.'],
  ['CTX-004-05', 'British English spelling: Localized → Localised.'],
  ['CTX-006-01', 'Replaced unnatural “provide an anamnesis” with “provide a medical history”.'],
  ['CTX-006-04', 'British English spelling: Generalized → Generalised.'],
  ['CTX-003-01', 'German article corrected: “keine Status migrainosus” → “kein Status migrainosus”.'],
])
for (const item of csv('patient_context_items_0_1.csv')) {
  const correction = correctedContext.get(item.context_item_id) ?? null
  add({
    id: `context:${item.context_item_id}`,
    location: `prototype_baseline/data/patient_context_items_0_1.csv#${item.context_item_id} + contentTranslations.js`,
    en: item.display_text,
    de: CONTEXT_ITEM_DE[item.context_item_id],
    interface_area: 'patient dossier context',
    origin: 'database content plus frontend translation asset',
    audit_result: correction ? 'pass_after_correction' : 'pass_no_change',
    correction_made: correction,
  })
}

const correctedQuestions = new Map([
  ['Q-002-02', 'German unit typography corrected from ml to mL.'],
  ['Q-003-01', 'German article corrected: “keine Status migrainosus” → “kein Status migrainosus”.'],
  ['Q-003-03', 'British English spelling: generalized → generalised.'],
  ['Q-004-05', 'British English spelling localised; German patient reference corrected to “die Patientin”.'],
  ['Q-006-03', 'British English spelling: generalized → generalised.'],
])
for (const question of csv('questions_0_1.csv').filter((row) => row.intended_use === 'learner_visible')) {
  for (const field of ['title', 'prompt']) {
    const correction = field === 'prompt' ? correctedQuestions.get(question.question_id) ?? null : null
    add({
      id: `question-${field}:${question.question_id}`,
      location: `prototype_baseline/data/questions_0_1.csv#${question.question_id}.${field} + contentTranslations.js`,
      en: question[field],
      de: QUESTION_CONTENT_DE[question.question_id][field],
      interface_area: `question and review ${field}`,
      origin: 'database content plus frontend translation asset',
      audit_result: correction ? 'pass_after_correction' : 'pass_no_change',
      correction_made: correction,
    })
  }
}

const subset = new Map(csv('subset_0_2.csv').map((row) => [row.Diagnose, row.Kurzbezeichnung]))
const displayedCodes = [...new Set(csv('question_options_0_1.csv')
  .filter((row) => row.option_kind === 'code')
  .map((row) => row.code))].sort()
const correctedCatalogueCodes = new Set(['F41.1', 'G40.0', 'G40.3', 'G40.4', 'L40.1'])
for (const code of displayedCodes) {
  add({
    id: `catalogue-option:${code}`,
    location: `catalogueTranslations.js#${code} + subset_0_2.csv#${code}`,
    en: CODE_DESIGNATION_EN[code],
    de: subset.get(code),
    interface_area: 'answer option designation',
    origin: 'English presentation asset plus authoritative Austrian catalogue data',
    audit_result: correctedCatalogueCodes.has(code) ? 'pass_after_correction' : 'pass_no_change',
    correction_made: correctedCatalogueCodes.has(code) ? 'English presentation normalised to British spelling.' : null,
  })
}

const evaluatorBranches = [
  ['RULE-CORRECT-01', '{code} is supported by the documented information as an appropriate code for this question.', '{code} wird durch die dokumentierten Angaben als passende Kodierung unterstützt.', 'Natural supported-response wording replaced the literal “declared acceptable” translation.'],
  ['RULE-NOA-01:correct', 'None of the displayed codes is supported by the documented information as an appropriate response. Therefore, “None of the above” is correct.', 'Keiner der angezeigten Codes wird durch die dokumentierten Angaben als passende Kodierung unterstützt. Daher ist „Keine der genannten“ richtig.', 'Natural supported-response wording and German quotation marks corrected.'],
  ['RULE-NOA-01:incorrect', 'The displayed codes include a response supported by the documented information. Therefore, “None of the above” is not correct here.', 'Unter den angezeigten Codes befindet sich eine durch die dokumentierten Angaben unterstützte Antwort. Daher ist „Keine der genannten“ hier nicht richtig.', 'Natural supported-response wording and German quotation marks corrected.'],
  ['RULE-STATUS-01', '{code} carries the “!” status marker and cannot be used as the {role} diagnosis in this {setting} context.', '{code} trägt die Statuskennzeichnung „!“ und darf {setting_phrase} nicht als {role}diagnose verwendet werden.', 'German quotation, article/case, and compound construction corrected.'],
  ['RULE-DEPTH-01', '{code} does not meet the mandatory {level} coding depth required for this diagnosis family.', '{code} erreicht nicht die für diese Diagnosegruppe vorgeschriebene Kodiertiefe ({level}).', null],
  ['RULE-EVID-01', '{code} conflicts with the represented stable-phase FEV1 of {value}; the source-defined suffix is {suffix}.', '{code} widerspricht der angegebenen FEV1 in der stabilen Phase von {value}; die quellendefinierte Endziffer ist {suffix}.', 'German stable-phase word order audited.'],
  ['RULE-SPEC-01', '{code} leaves the FEV1 severity unspecified; {specific_code} reflects the stable-phase value.', '{code} lässt den FEV1-Schweregrad unspezifiziert; {specific_code} bildet den Wert in der stabilen Phase ab.', 'German stable-phase word order corrected.'],
  ['RULE-REL-HARD-01', '{code} conflicts with the documented fact that {localized_value_clause}.', '{code} widerspricht der dokumentierten Angabe, dass {localized_value_clause}.', 'English learner_label interpolation removed; localized value-aware fact formatter added.'],
  ['RULE-REL-SPEC-01', '{code} is supported; however, {specific_code} more precisely reflects that {localized_value_clause}.', '{code} wird durch die Angaben unterstützt; {specific_code} bildet jedoch genauer ab, dass {localized_value_clause}.', 'English learner_label interpolation removed; value-aware wording and acceptance terminology corrected.'],
]
for (const [branch, en, de, correction] of evaluatorBranches) {
  add({
    id: `evaluation:${branch}`,
    location: `app/src/Evaluation/Evaluator.php#${branch}`,
    en,
    de,
    interface_area: 'evaluation feedback',
    origin: branch.startsWith('RULE-REL-') ? 'backend template plus LocalizedFactFormatter' : 'backend application code',
    audit_result: correction ? 'pass_after_correction' : 'pass_no_change',
    correction_made: correction,
  })
}

for (const [id, value, reason] of [
  ['technical:icd-code', 'ICD-10 codes', 'Technical identifier shared across locales.'],
  ['technical:rules', 'RULE-* identifiers', 'Shown only inside the expanded technical-details disclosure.'],
  ['technical:criteria', 'machine-readable criteria', 'Shown only inside the expanded technical-details disclosure.'],
  ['technical:language-codes', 'EN / DE', 'Standard language codes used by the compact selector.'],
  ['technical:baseline-source-api', 'baseline IDs, source IDs, and API field names', 'Machine identifiers; API fields are not rendered as learner prose.'],
  ['technical:german-catalogue', 'official Austrian DIAGLIST designation', 'Authoritative German source wording is preserved verbatim.'],
  ['technical:build-identity', 'version, build date, copyright year, and author name', 'Product identity values remain unchanged inside localized footer templates.'],
]) {
  add({
    id,
    location: 'cross-cutting localization policy',
    en: value,
    de: value,
    interface_area: 'technical or identity content',
    origin: 'application/runtime technical data',
    audit_result: 'pass_intentionally_untranslated',
    intentional_non_translated: true,
    correction_made: reason,
  })
}

const feedbackFactEntries = JSON.parse(execFileSync(
  'php',
  [repositoryRoot + 'app/scripts/generate_fact_localization_inventory.php'],
  { encoding: 'utf8' },
))
entries.push(...feedbackFactEntries)

const inventory = {
  inventory_id: 'LOCALIZATION-AUDIT-1.0',
  generated_on: '2026-08-09',
  corrected_prototype: 'PROTOBASE-1.1',
  locales: ['en-GB', 'de-AT'],
  entries,
  counts: {
    total_entries: entries.length,
    ui_translation_keys: Object.keys(strings.en).length,
    patient_summaries: patients.length,
    patient_names: patients.length,
    context_items: csv('patient_context_items_0_1.csv').length,
    question_titles: 25,
    question_prompts: 25,
    displayed_catalogue_codes: displayedCodes.length,
    evaluator_branches: evaluatorBranches.length,
    feedback_fact_relation_combinations: feedbackFactEntries.length,
    intentional_non_translated_policy_entries: 7,
  },
}

writeFileSync(outputPath, `${JSON.stringify(inventory, null, 2)}\n`)
