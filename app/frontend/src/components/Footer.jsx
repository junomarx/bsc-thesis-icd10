import { useLocale } from '../lib/i18n.jsx'

// __APP_VERSION__/__BUILD_DATE__ are injected at build time via
// vite.config.js's `define` (from package.json and the build timestamp,
// respectively) - not runtime values, so they stay fixed until the next
// `npm run build`. The copyright year, unlike those, is computed live so
// it never goes stale after a build.
export default function Footer() {
  const { t } = useLocale()

  return (
    <footer className="app-footer">
      <p>
        {t('footer.build', {
          version: __APP_VERSION__,
          date: __BUILD_DATE__,
          year: new Date().getFullYear(),
          name: 'Juno Anna Marx',
        })}
      </p>
    </footer>
  )
}
