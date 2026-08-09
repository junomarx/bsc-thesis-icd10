import { STATUS_ICONS, STATUS_LABEL_KEYS } from '../lib/classification.js'
import { summarizeResults } from '../lib/playthrough.js'
import { useLocale } from '../lib/i18n.jsx'
import { QUESTION_CONTENT_DE } from '../lib/contentTranslations.js'
import { IconCheck } from './icons.jsx'

// REQ-FBK-03: raw class counts plus a per-question read-only list - no
// weighted score, no percentage grade. The completion badge is a restrained
// cosmetic acknowledgment (UXBASE-0.1 §4, "Could" priority) - respects
// prefers-reduced-motion via the shared --motion-duration token and never
// implies a mastery/competency claim.
export default function PatientReview({ patient, orderedQuestionIds, questionsById, results, onReplay, onChooseAnother }) {
  const { t, locale } = useLocale()
  const counts = summarizeResults(results, orderedQuestionIds)

  return (
    <section>
      <h2 className="review-heading">
        <span className="review-completion-badge">
          <IconCheck />
        </span>
        {t('review.heading', { name: patient.display_name })}
      </h2>
      <p className="progress-summary">
        {t('review.counts', { correct: counts.correct, suboptimal: counts.suboptimal, incorrect: counts.incorrect })}
      </p>

      <ul className="review-list">
        {orderedQuestionIds.map((questionId) => {
          const question = questionsById[questionId]
          const result = results[questionId]
          const StatusIcon = result?.classification ? STATUS_ICONS[result.classification] : null
          const title = locale === 'de' ? QUESTION_CONTENT_DE[questionId]?.title ?? question?.title : question?.title

          return (
            <li key={questionId} className="review-item">
              <span className="review-item-title">{title ?? questionId}</span>
              <span className={`result-heading result-${result?.classification ?? 'not_attempted'}`}>
                {StatusIcon && <StatusIcon />}
                {t(STATUS_LABEL_KEYS[result?.classification] ?? 'status.not_attempted')}
              </span>
            </li>
          )
        })}
      </ul>

      <div className="result-actions">
        <button type="button" onClick={onReplay}>
          {t('review.playAgain')}
        </button>
        <button type="button" className="link-button" onClick={onChooseAnother}>
          {t('review.chooseAnother')}
        </button>
      </div>
    </section>
  )
}
