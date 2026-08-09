import { useLocale } from '../lib/i18n.jsx'

// Minimal EN|DE switch, available on every screen (rendered once, in
// Header, which is always mounted regardless of the active view).
export default function LanguageSwitch() {
  const { locale, setLocale, t } = useLocale()

  return (
    <div className="language-switch" role="group" aria-label={t('language.label')}>
      <button
        type="button"
        className={locale === 'en' ? 'language-switch-option language-switch-option-active' : 'language-switch-option'}
        aria-pressed={locale === 'en'}
        onClick={() => setLocale('en')}
      >
        EN
      </button>
      <span aria-hidden="true">|</span>
      <button
        type="button"
        className={locale === 'de' ? 'language-switch-option language-switch-option-active' : 'language-switch-option'}
        aria-pressed={locale === 'de'}
        onClick={() => setLocale('de')}
      >
        DE
      </button>
    </div>
  )
}
