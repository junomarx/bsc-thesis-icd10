import { useState } from 'react'
import { useLocale } from '../lib/i18n.jsx'
import { readTheme, saveTheme } from '../lib/theme.js'
import { IconMoon, IconSun } from './icons.jsx'

export default function ThemeSwitch() {
  const { t } = useLocale()
  const [theme, setTheme] = useState(() => readTheme())
  const dark = theme === 'dark'
  const actionLabel = t(dark ? 'theme.switchToLight' : 'theme.switchToDark')

  function toggleTheme() {
    const next = dark ? 'light' : 'dark'
    saveTheme(next)
    setTheme(next)
  }

  return (
    <button
      type="button"
      className="theme-switch"
      onClick={toggleTheme}
      aria-label={t('theme.label')}
      aria-pressed={dark}
      title={actionLabel}
      data-testid="theme-toggle"
    >
      {dark ? <IconMoon /> : <IconSun />}
      <span className="visually-hidden">{actionLabel}</span>
    </button>
  )
}
