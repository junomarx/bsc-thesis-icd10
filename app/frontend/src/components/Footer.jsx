// __APP_VERSION__/__BUILD_DATE__ are injected at build time via
// vite.config.js's `define` (from package.json and the build timestamp,
// respectively) - not runtime values, so they stay fixed until the next
// `npm run build`. The copyright year, unlike those, is computed live so
// it never goes stale after a build.
export default function Footer() {
  return (
    <footer className="app-footer">
      <p>
        v{__APP_VERSION__} · build {__BUILD_DATE__} · © {new Date().getFullYear()} Juno Anna Marx
      </p>
    </footer>
  )
}
