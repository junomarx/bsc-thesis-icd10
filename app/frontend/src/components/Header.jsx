import LanguageSwitch from './LanguageSwitch.jsx'
import { useLocale } from '../lib/i18n.jsx'
import { IconHelp } from './icons.jsx'

export default function Header({ onOpenTutorial, tutorialOpen }) {
  const { t } = useLocale()

  return (
    <header className="app-header">
      <div className="app-header-row">
        <h1 className="app-title">{t('app.title')}</h1>
        <div className="app-header-actions">
          <button
            type="button"
            className="tutorial-trigger"
            onClick={onOpenTutorial}
            aria-haspopup="dialog"
            aria-expanded={tutorialOpen}
          >
            <IconHelp />
            {t('tutorial.open')}
          </button>
          <LanguageSwitch />
        </div>
      </div>
      <p className="disclaimer">{t('app.disclaimer')}</p>
    </header>
  )
}
