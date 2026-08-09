import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { fileURLToPath } from 'node:url'
import { CODE_DESIGNATION_EN } from '../src/lib/catalogueTranslations.js'
import { CONTEXT_ITEM_DE, PATIENT_SUMMARY_DE, QUESTION_CONTENT_DE } from '../src/lib/contentTranslations.js'

const frontendRoot = fileURLToPath(new URL('../', import.meta.url))
const repositoryRoot = fileURLToPath(new URL('../../../', import.meta.url))

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
  if (field !== '' || row.length > 0) {
    row.push(field)
    rows.push(row)
  }

  const [header, ...values] = rows
  return values.filter((value) => value.length === header.length).map((value) => Object.fromEntries(
    header.map((name, index) => [name.replace(/^\uFEFF/, ''), value[index]]),
  ))
}

function readCsv(filename) {
  return parseCsv(read(`/prototype_baseline/data/${filename}`))
}

function extractUiStrings() {
  const source = readFileSync(frontendRoot + 'src/lib/i18n.jsx', 'utf8')
  const prefix = 'const STRINGS = '
  const start = source.indexOf(prefix)
  const end = source.indexOf('\n\nfunction detectDefaultLocale', start)
  assert.notEqual(start, -1)
  assert.notEqual(end, -1)
  // The selected range is a reviewed data-only object literal. Evaluating it
  // keeps this check dependency-free while the production module remains JSX.
  return Function(`"use strict"; return (${source.slice(start + prefix.length, end)})`)()
}

function placeholders(value) {
  return [...value.matchAll(/\{([a-zA-Z][a-zA-Z0-9]*)\}/g)].map((match) => match[1]).sort()
}

test('English and German UI dictionaries have exact key and interpolation parity', () => {
  const strings = extractUiStrings()
  const enKeys = Object.keys(strings.en).sort()
  const deKeys = Object.keys(strings.de).sort()

  assert.deepEqual(deKeys, enKeys)
  assert.ok(enKeys.length >= 90)
  for (const key of enKeys) {
    assert.ok(strings.en[key].trim(), `empty English translation: ${key}`)
    assert.ok(strings.de[key].trim(), `empty German translation: ${key}`)
    assert.deepEqual(placeholders(strings.de[key]), placeholders(strings.en[key]), `placeholder mismatch: ${key}`)
  }

  const source = readFileSync(frontendRoot + 'src/lib/i18n.jsx', 'utf8')
  assert.doesNotMatch(source, /STRINGS\.en\[key\]/)
  assert.doesNotMatch(source, /\?\?\s*key/)
  assert.match(source, /Missing \$\{locale\} translation/)
})

test('all six patients, 32 context items, and 25 questions have complete localized content', () => {
  const patients = readCsv('patients_0_1.csv')
  const contextItems = readCsv('patient_context_items_0_1.csv')
  const questions = readCsv('questions_0_1.csv').filter((question) => question.intended_use === 'learner_visible')

  assert.equal(patients.length, 6)
  assert.equal(contextItems.length, 32)
  assert.equal(questions.length, 25)
  assert.deepEqual(Object.keys(PATIENT_SUMMARY_DE).sort(), patients.map((row) => row.patient_id).sort())
  assert.deepEqual(Object.keys(CONTEXT_ITEM_DE).sort(), contextItems.map((row) => row.context_item_id).sort())
  assert.deepEqual(Object.keys(QUESTION_CONTENT_DE).sort(), questions.map((row) => row.question_id).sort())

  for (const question of questions) {
    assert.ok(question.title.trim())
    assert.ok(question.prompt.trim())
    assert.ok(QUESTION_CONTENT_DE[question.question_id].title.trim())
    assert.ok(QUESTION_CONTENT_DE[question.question_id].prompt.trim())
  }

  const englishContent = [
    ...patients.map((row) => row.general_health_summary),
    ...contextItems.map((row) => row.display_text),
    ...questions.flatMap((row) => [row.title, row.prompt]),
  ].join('\n')
  assert.doesNotMatch(englishContent, /\b(?:generalized|localized|localization)\b/i)
  assert.doesNotMatch(englishContent, /provide an anamnesis/i)

  const germanContent = [
    ...Object.values(PATIENT_SUMMARY_DE),
    ...Object.values(CONTEXT_ITEM_DE),
    ...Object.values(QUESTION_CONTENT_DE).flatMap(({ title, prompt }) => [title, prompt]),
  ].join('\n')
  assert.doesNotMatch(germanContent, /\b(?:Documented|Current consciousness|Recorded GFR|Time since prior event)\b/i)
  assert.doesNotMatch(germanContent, /keine Status migrainosus/)
  assert.doesNotMatch(germanContent, /45 ml\/min/)
})

test('every displayed code has an explicit British-English catalogue presentation', () => {
  const displayedCodes = [...new Set(
    readCsv('question_options_0_1.csv')
      .filter((option) => option.option_kind === 'code')
      .map((option) => option.code),
  )].sort()

  assert.equal(displayedCodes.length, 87)
  assert.deepEqual(Object.keys(CODE_DESIGNATION_EN).sort(), displayedCodes)
  for (const [code, designation] of Object.entries(CODE_DESIGNATION_EN)) {
    assert.ok(designation.trim(), `empty catalogue presentation for ${code}`)
    assert.doesNotMatch(designation, /\b(?:generalized|localized|localization)\b/i, code)
  }

  const source = readFileSync(frontendRoot + 'src/components/QuestionView.jsx', 'utf8')
  assert.doesNotMatch(source, /CODE_DESIGNATION_EN\[option\.code\]\s*\?\?/)
  assert.match(source, /englishCatalogueDesignation\(option\.code\)/)
})

test('learner components contain no opposite-language or raw-identifier fallback paths', () => {
  const files = [
    'src/components/PatientCard.jsx',
    'src/components/PatientDossier.jsx',
    'src/components/PatientReview.jsx',
    'src/components/QuestionView.jsx',
  ].map((path) => readFileSync(frontendRoot + path, 'utf8')).join('\n')

  assert.doesNotMatch(files, /\?\?\s*(?:patient\.(?:sex|difficulty_role)|item\.item_type|questionId)/)
  assert.doesNotMatch(files, /explanation_de\s*\?\?\s*result\.explanation/)
  assert.doesNotMatch(files, /localizedContent\?\.(?:title|prompt)\s*\?\?/)
})
