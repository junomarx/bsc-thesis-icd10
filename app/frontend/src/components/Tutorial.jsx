import { useEffect, useRef, useState } from 'react'
import { STATUS_ICONS } from '../lib/classification.js'
import { useLocale } from '../lib/i18n.jsx'
import { IconClose } from './icons.jsx'

const STEPS = [
  { title: 'tutorial.step1.title', body: 'tutorial.step1.body', cue: 'tutorial.step1.cue' },
  { title: 'tutorial.step2.title', body: 'tutorial.step2.body', cue: 'tutorial.step2.cue' },
  { title: 'tutorial.step3.title', body: 'tutorial.step3.body', cue: 'tutorial.step3.cue' },
  { title: 'tutorial.step4.title', body: 'tutorial.step4.body', cue: 'tutorial.step4.cue', legend: true },
]

const FOCUSABLE_SELECTOR = [
  'button:not([disabled])',
  '[href]',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[tabindex]:not([tabindex="-1"])',
].join(',')

// REQ-UI-01: a compact, guided introduction explains the learner flow and
// feedback vocabulary. It is a real modal walkthrough, shown automatically
// only on the first browser visit and always available again from Header.
export default function Tutorial({ onClose }) {
  const { t } = useLocale()
  const [stepIndex, setStepIndex] = useState(0)
  const dialogRef = useRef(null)
  const previouslyFocusedRef = useRef(null)
  const step = STEPS[stepIndex]
  const isLastStep = stepIndex === STEPS.length - 1

  useEffect(() => {
    previouslyFocusedRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    dialogRef.current?.focus()

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        event.preventDefault()
        onClose()
        return
      }

      if (event.key !== 'Tab' || !dialogRef.current) return

      const focusable = [...dialogRef.current.querySelectorAll(FOCUSABLE_SELECTOR)]
      if (focusable.length === 0) {
        event.preventDefault()
        dialogRef.current.focus()
        return
      }

      const first = focusable[0]
      const last = focusable[focusable.length - 1]
      const active = document.activeElement

      if (event.shiftKey && (active === first || active === dialogRef.current)) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && active === last) {
        event.preventDefault()
        first.focus()
      }
    }

    document.addEventListener('keydown', handleKeyDown)
    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = previousOverflow
      const previous = previouslyFocusedRef.current
      if (previous && previous !== document.body && previous.isConnected) previous.focus()
    }
  }, [onClose])

  const CorrectIcon = STATUS_ICONS.correct
  const SuboptimalIcon = STATUS_ICONS.suboptimal
  const IncorrectIcon = STATUS_ICONS.incorrect

  function handleBackdropClick(event) {
    if (event.target === event.currentTarget) onClose()
  }

  return (
    <div className="tutorial-backdrop" onClick={handleBackdropClick}>
      <div
        ref={dialogRef}
        className="tutorial-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tutorial-title"
        aria-describedby="tutorial-step-body"
        tabIndex="-1"
      >
        <div className="tutorial-header">
          <div>
            <p className="tutorial-eyebrow">{t('tutorial.eyebrow')}</p>
            <h2 id="tutorial-title">{t('tutorial.title')}</h2>
          </div>
          <button type="button" className="tutorial-close" onClick={onClose} aria-label={t('tutorial.close')}>
            <IconClose />
          </button>
        </div>

        <p className="tutorial-step-count">
          {t('tutorial.stepCount', { current: stepIndex + 1, total: STEPS.length })}
        </p>
        <ol className="tutorial-progress" aria-label={t('tutorial.progressLabel')}>
          {STEPS.map((item, index) => (
            <li key={item.title} className={index <= stepIndex ? 'tutorial-progress-reached' : ''} aria-current={index === stepIndex ? 'step' : undefined}>
              <span className="visually-hidden">{t(item.title)}</span>
            </li>
          ))}
        </ol>

        <div className="tutorial-step" aria-live="polite">
          <div className="tutorial-step-number" aria-hidden="true">{stepIndex + 1}</div>
          <div>
            <h3>{t(step.title)}</h3>
            <p id="tutorial-step-body">{t(step.body)}</p>
            <p className="tutorial-cue">{t(step.cue)}</p>
          </div>
        </div>

        {step.legend && (
          <ul className="tutorial-legend">
            <li className="result-correct">
              <CorrectIcon />
              <span><strong>{t('status.correct')}</strong> — {t('tutorial.legend.correct')}</span>
            </li>
            <li className="result-suboptimal">
              <SuboptimalIcon />
              <span><strong>{t('status.suboptimal')}</strong> — {t('tutorial.legend.suboptimal')}</span>
            </li>
            <li className="result-incorrect">
              <IncorrectIcon />
              <span><strong>{t('status.incorrect')}</strong> — {t('tutorial.legend.incorrect')}</span>
            </li>
          </ul>
        )}

        <div className="tutorial-actions">
          <button type="button" className="link-button tutorial-skip" onClick={onClose} data-testid="tutorial-skip">
            {t('tutorial.skip')}
          </button>
          <div className="tutorial-navigation">
            {stepIndex > 0 && (
              <button type="button" className="tutorial-secondary" onClick={() => setStepIndex((index) => index - 1)} data-testid="tutorial-back">
                {t('tutorial.back')}
              </button>
            )}
            <button
              type="button"
              className="tutorial-primary"
              onClick={isLastStep ? onClose : () => setStepIndex((index) => index + 1)}
              data-testid={isLastStep ? 'tutorial-finish' : 'tutorial-next'}
            >
              {isLastStep ? t('tutorial.finish') : t('tutorial.next')}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
