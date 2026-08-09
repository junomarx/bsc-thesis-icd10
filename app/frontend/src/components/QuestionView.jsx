import { useState } from 'react'
import PatientDossier from './PatientDossier.jsx'
import { STATUS_ICONS, STATUS_LABEL_KEYS } from '../lib/classification.js'
import { useLocale } from '../lib/i18n.jsx'
import { localizeQuestionContent } from '../lib/contentTranslations.js'
import { englishCatalogueDesignation } from '../lib/catalogueTranslations.js'

const LOCALIZED_GATE_REASONS = new Set([
  'outside_active_subset',
  'undefined_case_relation',
  'missing_required_case_fact',
  'none_option_not_defined',
  'malformed_input',
  'unsupported_response_kind',
  'evaluation_failed',
])

export default function QuestionView({
  patient,
  question,
  questionNumber,
  totalQuestions,
  result,
  submitting,
  onSubmit,
  onAdvance,
  onExit,
  isLast,
}) {
  const { t, locale } = useLocale()
  const [selectedOptionId, setSelectedOptionId] = useState(null)
  const [dossierOpen, setDossierOpen] = useState(false)

  const selectedOption = question.options.find((option) => option.option_id === selectedOptionId)
  const { title, prompt } = localizeQuestionContent(question, locale)

  function handleSubmit() {
    if (!selectedOption) return
    const response =
      selectedOption.option_kind === 'none_of_above'
        ? { type: 'none_of_above' }
        : { type: 'code', code: selectedOption.code }
    onSubmit(response)
  }

  function handleExit() {
    if (window.confirm(t('question.exitConfirm'))) {
      onExit()
    }
  }

  return (
    <section className="question-view">
      <div className="question-toolbar">
        <p className="question-progress">{t('question.progress', { number: questionNumber, total: totalQuestions })}</p>
        <button type="button" className="link-button question-exit" onClick={handleExit}>
          {t('question.exit')}
        </button>
      </div>
      <ol className="question-progress-bar" aria-hidden="true">
        {Array.from({ length: totalQuestions }, (_, index) => (
          <li key={index} className={index < questionNumber ? 'question-progress-segment-done' : ''} />
        ))}
      </ol>
      <PatientDossier patient={patient} open={dossierOpen} onToggle={() => setDossierOpen((v) => !v)} />

      <h2>{title}</h2>
      <p className="question-prompt">{prompt}</p>

      {result === null ? (
        <>
          <ul className="code-list">
            {question.options.map((option) => (
              <li key={option.option_id}>
                <label
                  className={
                    selectedOptionId === option.option_id ? 'code-option code-option-selected' : 'code-option'
                  }
                >
                  <input
                    type="radio"
                    name="response"
                    value={option.option_id}
                    checked={selectedOptionId === option.option_id}
                    onChange={() => setSelectedOptionId(option.option_id)}
                  />
                  {option.option_kind === 'none_of_above' ? (
                    <em>{t('question.noneOfAbove')}</em>
                  ) : (
                    <>
                      <strong>{option.code}</strong> —{' '}
                      {locale === 'en' ? englishCatalogueDesignation(option.code) : option.short_designation}
                    </>
                  )}
                </label>
              </li>
            ))}
          </ul>
          <div className="submit-bar">
            <button type="button" disabled={selectedOptionId === null || submitting} onClick={handleSubmit}>
              {submitting ? t('question.submitting') : t('question.submit')}
            </button>
          </div>
        </>
      ) : (
        <QuestionFeedback result={result} onAdvance={onAdvance} isLast={isLast} />
      )}
    </section>
  )
}

function QuestionFeedback({ result, onAdvance, isLast }) {
  const { t, locale } = useLocale()

  if (result.evaluation_status === 'not_evaluated') {
    const reasonText = t(LOCALIZED_GATE_REASONS.has(result.reason) ? `gateReason.${result.reason}` : 'gateReason.unknown')
    return (
      <div className="question-feedback">
        <h3>{t('question.notEvaluated')}</h3>
        <p>{t('question.notEvaluatedBody', { reason: reasonText })}</p>
        <button type="button" onClick={onAdvance}>
          {isLast ? t('question.reviewPatient') : t('question.next')}
        </button>
      </div>
    )
  }

  const StatusIcon = STATUS_ICONS[result.classification]
  const explanation = locale === 'de' ? result.explanation_de : result.explanation
  if (typeof explanation !== 'string' || explanation.trim() === '') {
    throw new Error(`Missing ${locale} evaluation explanation`)
  }

  return (
    <div className="question-feedback" aria-live="polite">
      <h3 className={`result-heading result-${result.classification}`}>
        {StatusIcon && <StatusIcon />}
        {t(STATUS_LABEL_KEYS[result.classification] ?? 'question.notEvaluated')}
      </h3>
      <p>{explanation}</p>
      {result.improvement_code && (
        <p className="improvement">{t('question.improvement', { code: result.improvement_code })}</p>
      )}
      <details className="technical-details">
        <summary>{t('technicalDetails.toggle')}</summary>
        <dl>
          <dt>{t('technicalDetails.determiningRule')}</dt>
          <dd>{result.determining_rule}</dd>
          <dt>{t('technicalDetails.criterion')}</dt>
          <dd>{result.criterion}</dd>
          {result.matched_rules && result.matched_rules.length > 0 && (
            <>
              <dt>{t('technicalDetails.matchedRules')}</dt>
              <dd>{result.matched_rules.join(', ')}</dd>
            </>
          )}
        </dl>
      </details>
      <button type="button" onClick={onAdvance}>
        {isLast ? t('question.reviewPatient') : t('question.next')}
      </button>
    </div>
  )
}
