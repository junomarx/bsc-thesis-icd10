const STORAGE_KEY = 'icd10-prototype:theme'
const THEMES = new Set(['light', 'dark'])

function preferredTheme() {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') return 'light'
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

export function readTheme() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return THEMES.has(stored) ? stored : preferredTheme()
  } catch {
    return preferredTheme()
  }
}

export function applyTheme(theme) {
  if (typeof document === 'undefined' || !THEMES.has(theme)) return
  document.documentElement.dataset.theme = theme
}

export function saveTheme(theme) {
  applyTheme(theme)
  try {
    localStorage.setItem(STORAGE_KEY, theme)
  } catch {
    // private-browsing/quota: the selected theme still applies to this page
  }
}

// Apply the stored/OS-derived value before React mounts, minimizing a flash
// of the wrong palette on a returning visit.
applyTheme(readTheme())
