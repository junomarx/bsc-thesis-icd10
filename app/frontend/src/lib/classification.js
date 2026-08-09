import { IconCheck, IconCircleDash, IconCross, IconWarning } from '../components/icons.jsx'

// Shared vocabulary for classification (correct/suboptimal/incorrect) plus
// not_attempted, used by the per-question feedback panel and the patient
// review's per-question list. Values are i18n keys (see lib/i18n.jsx), not
// display strings - callers resolve them via useLocale().t().
export const STATUS_LABEL_KEYS = {
  not_attempted: 'status.not_attempted',
  correct: 'status.correct',
  suboptimal: 'status.suboptimal',
  incorrect: 'status.incorrect',
}

export const STATUS_ICONS = {
  not_attempted: IconCircleDash,
  correct: IconCheck,
  suboptimal: IconWarning,
  incorrect: IconCross,
}
