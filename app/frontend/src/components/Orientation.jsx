import { useState } from 'react'
import { useLocale } from '../lib/i18n.jsx'
import { STATUS_ICONS } from '../lib/classification.js'

// REQ-UI-01: the entry view must be self-explanatory without an oral
// briefing - purpose, workflow, the three-class legend, and the
// non-clinical-use boundary (already covered separately by the header's
// permanent disclaimer). Shown on the roster/home view; collapsible so a
// returning learner is not forced to re-read it every time.
export default function Orientation() {
  const { t } = useLocale()
  const [open, setOpen] = useState(true)

  const CorrectIcon = STATUS_ICONS.correct
  const SuboptimalIcon = STATUS_ICONS.suboptimal
  const IncorrectIcon = STATUS_ICONS.incorrect

  return (
    <div className="orientation">
      <button type="button" className="orientation-toggle" onClick={() => setOpen((v) => !v)}>
        {open ? t('orientation.toggleHide') : t('orientation.toggleShow')}
      </button>
      {open && (
        <div className="orientation-panel">
          <p>{t('orientation.purpose')}</p>
          <p>{t('orientation.workflow')}</p>
          <h3>{t('orientation.legendHeading')}</h3>
          <ul className="orientation-legend">
            <li>
              <div className="result-heading result-correct">
                <CorrectIcon /> {t('status.correct')}
              </div>
              <p className="orientation-legend-text">{t('orientation.legend.correct')}</p>
            </li>
            <li>
              <div className="result-heading result-suboptimal">
                <SuboptimalIcon /> {t('status.suboptimal')}
              </div>
              <p className="orientation-legend-text">{t('orientation.legend.suboptimal')}</p>
            </li>
            <li>
              <div className="result-heading result-incorrect">
                <IncorrectIcon /> {t('status.incorrect')}
              </div>
              <p className="orientation-legend-text">{t('orientation.legend.incorrect')}</p>
            </li>
          </ul>
        </div>
      )}
    </div>
  )
}
