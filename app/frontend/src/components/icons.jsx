// Hand-authored inline SVGs, not an icon-font/library dependency (see
// docs/UX_UI_SPECIFICATION.md §2.5). Every icon here is a supporting third
// signal alongside colour and text, never the only signal - each usage site
// pairs it with visible text and/or a CSS colour class.

const common = {
  width: '1em',
  height: '1em',
  viewBox: '0 0 20 20',
  fill: 'none',
  'aria-hidden': 'true',
  focusable: 'false',
}

export function IconCheck(props) {
  return (
    <svg {...common} {...props}>
      <path
        d="M4 10.5l3.5 3.5L16 5.5"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

export function IconWarning(props) {
  return (
    <svg {...common} {...props}>
      <path
        d="M10 3l8 14H2z"
        stroke="currentColor"
        strokeWidth="1.6"
        strokeLinejoin="round"
      />
      <path d="M10 8.5v3.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
      <circle cx="10" cy="14.5" r="0.9" fill="currentColor" />
    </svg>
  )
}

export function IconCross(props) {
  return (
    <svg {...common} {...props}>
      <path
        d="M5 5l10 10M15 5L5 15"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  )
}

export function IconCircleDash(props) {
  return (
    <svg {...common} {...props}>
      <circle
        cx="10"
        cy="10"
        r="7.5"
        stroke="currentColor"
        strokeWidth="1.6"
        strokeDasharray="3.5 3.5"
      />
    </svg>
  )
}

export function IconBed(props) {
  return (
    <svg {...common} {...props}>
      <path
        d="M2 15V6M2 12h16v3M2 12V9a1 1 0 0 1 1-1h5v4M18 15v-3"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

export function IconClipboard(props) {
  return (
    <svg {...common} {...props}>
      <rect x="4.5" y="3.5" width="11" height="14" rx="1.5" stroke="currentColor" strokeWidth="1.5" />
      <path d="M7.5 3.5h5v2h-5z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
      <path d="M7 9.5h6M7 12.5h6" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
    </svg>
  )
}

export function IconHelp(props) {
  return (
    <svg {...common} {...props}>
      <circle cx="10" cy="10" r="7.5" stroke="currentColor" strokeWidth="1.6" />
      <path
        d="M7.8 8a2.2 2.2 0 1 1 3.4 1.8c-.6.4-1.2.9-1.2 1.9"
        stroke="currentColor"
        strokeWidth="1.5"
        strokeLinecap="round"
      />
      <circle cx="10" cy="14.3" r="0.9" fill="currentColor" />
    </svg>
  )
}

export function IconClose(props) {
  return (
    <svg {...common} {...props}>
      <path
        d="M5 5l10 10M15 5L5 15"
        stroke="currentColor"
        strokeWidth="1.8"
        strokeLinecap="round"
      />
    </svg>
  )
}
