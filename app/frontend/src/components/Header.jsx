import LanguageSwitch from './LanguageSwitch.jsx'
import { useLocale } from '../lib/i18n.jsx'

export default function Header() {
  const { t } = useLocale()

  return (
    <header className="app-header">
      <div className="app-header-row">
        <h1 className="app-title">{t('app.title')}</h1>
        <LanguageSwitch />
      </div>
      <p className="disclaimer">{t('app.disclaimer')}</p>
    </header>
  )
}
