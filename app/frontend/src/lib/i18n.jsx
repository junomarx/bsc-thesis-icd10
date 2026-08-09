import { createContext, useContext, useMemo, useState } from 'react'

// Minimal EN/DE UI-chrome translation. Patient/question *content* (prompts,
// context items, catalogue designations) comes from the runtime dataset as
// authored and is not translated here - there is no German-authored variant
// of that content yet, only of this static interface text.
const STORAGE_KEY = 'icd10-prototype:locale'

const STRINGS = {
  en: {
    'app.title': 'ICD-10 coding practice',
    'app.disclaimer':
      'Synthetic teaching patients only. This tool does not diagnose patients, does not provide clinical decision support, and is not used for official coding, reporting, or reimbursement.',
    'language.label': 'Language',
    'tutorial.open': 'How this works',
    'tutorial.close': 'Close tutorial',
    'tutorial.eyebrow': 'Interactive tutorial',
    'tutorial.title': 'Learn the workflow in four steps',
    'tutorial.stepCount': 'Step {current} of {total}',
    'tutorial.progressLabel': 'Tutorial progress',
    'tutorial.skip': 'Skip tutorial',
    'tutorial.back': 'Back',
    'tutorial.next': 'Next',
    'tutorial.finish': 'Choose a patient',
    'tutorial.step1.title': 'Choose a patient',
    'tutorial.step1.body':
      'Start from the patient cards behind this tutorial. Every patient contains several independent coding questions, and every card is available from the beginning.',
    'tutorial.step1.cue': 'The Foundational and Involved labels are guidance, not locked levels.',
    'tutorial.step2.title': 'Review the patient record',
    'tutorial.step2.body':
      'Inside a question, open “Show patient info” to review the summary and documented context. You can reopen this dossier at any time without losing your selection.',
    'tutorial.step2.cue': 'Pay attention to whether information comes from a record, a patient report, or a current examination.',
    'tutorial.step3.title': 'Answer one question at a time',
    'tutorial.step3.body':
      'Use the question counter, choose one ICD-10 code or “None of the above”, then submit. The answer is locked after evaluation so the feedback always refers to the choice you made.',
    'tutorial.step3.cue': 'Question order is reshuffled for each playthrough; the questions and answer options themselves do not change.',
    'tutorial.step4.title': 'Use the feedback, then continue',
    'tutorial.step4.body':
      'Read the result and explanation before moving on. After the final question, the review screen summarizes the patient without turning the three result classes into a score.',
    'tutorial.step4.cue': 'Technical details are available when you want to inspect the rule and criterion behind a result.',
    'tutorial.legend.correct': 'the selected response is explicitly supported.',
    'tutorial.legend.suboptimal': 'a more specific supported response is available.',
    'tutorial.legend.incorrect': 'the response conflicts with the documented facts or rules.',
    'roster.allCompleted': 'All 6 patients completed for this session.',
    'roster.resetProgress': 'Reset progress',
    'roster.resetProgressConfirm': 'Clear the completion marks for all patients in this session?',
    'roster.heading': 'Choose a patient',
    'roster.loading': 'Loading patients…',
    'roster.error': 'Could not load patients: {message}. If this persists, check that the backend and database containers are running.',
    'roster.progress': '{completed} of {total} patients completed',
    'patient.yearsOld': '{age} yrs',
    'patient.questionCount': '{count} questions',
    'patient.completed': 'Completed',
    'difficulty.foundational': 'Foundational',
    'difficulty.involved': 'Involved',
    'sex.female': 'female',
    'sex.male': 'male',
    'dossier.show': 'Show patient info',
    'dossier.hide': 'Hide patient info',
    'dossier.identityDetail': '— {age} yrs, {sex}',
    'itemType.documented_condition': 'Documented condition',
    'itemType.self_reported_history': 'Self-reported history',
    'itemType.current_exam_finding': 'Current exam finding',
    'itemType.social_context': 'Social context',
    'itemType.information_boundary': 'Information boundary',
    'itemType.other': 'Other',
    'question.progress': 'Question {number} of {total}',
    'question.exit': 'Exit to patient list',
    'question.exitConfirm': 'Leave this patient now? Your progress on this playthrough will not be saved.',
    'question.noneOfAbove': 'None of the above',
    'question.submit': 'Submit answer',
    'question.submitting': 'Submitting…',
    'question.notEvaluated': 'Not evaluated',
    'question.notEvaluatedBody': 'This submission could not be classified ({reason}).',
    'gateReason.outside_active_subset': 'the submitted code is outside the active catalogue subset for this session',
    'gateReason.undefined_case_relation': 'this question has no defined relationship to the submitted code',
    'gateReason.missing_required_case_fact': 'a fact required to evaluate this question is missing',
    'gateReason.none_option_not_defined': '"None of the above" is not defined as a response for this question',
    'gateReason.malformed_input': 'the submitted response was not in the expected format',
    'gateReason.unsupported_response_kind': 'the submitted response type is not supported',
    'question.next': 'Next question',
    'question.reviewPatient': 'Review patient',
    'question.improvement': 'Suggested improvement: {code}',
    'status.not_attempted': 'Not attempted',
    'status.correct': 'Correct',
    'status.suboptimal': 'Suboptimal',
    'status.incorrect': 'Incorrect',
    'review.heading': '{name} — patient completed',
    'review.counts': '{correct} correct · {suboptimal} suboptimal · {incorrect} incorrect',
    'review.playAgain': 'Play again',
    'review.chooseAnother': 'Choose another patient',
    'technicalDetails.toggle': 'Technical details',
    'technicalDetails.determiningRule': 'Determining rule',
    'technicalDetails.criterion': 'Criterion',
    'technicalDetails.matchedRules': 'Matched rules',
  },
  de: {
    'app.title': 'ICD-10-Kodierübung',
    'app.disclaimer':
      'Ausschließlich synthetische Lehrfälle. Dieses Werkzeug stellt keine Diagnosen, bietet keine klinische Entscheidungsunterstützung und wird nicht für die offizielle Kodierung, Meldung oder Abrechnung verwendet.',
    'language.label': 'Sprache',
    'tutorial.open': 'So funktioniert es',
    'tutorial.close': 'Tutorial schließen',
    'tutorial.eyebrow': 'Interaktives Tutorial',
    'tutorial.title': 'Den Ablauf in vier Schritten kennenlernen',
    'tutorial.stepCount': 'Schritt {current} von {total}',
    'tutorial.progressLabel': 'Tutorial-Fortschritt',
    'tutorial.skip': 'Tutorial überspringen',
    'tutorial.back': 'Zurück',
    'tutorial.next': 'Weiter',
    'tutorial.finish': 'Patient:in auswählen',
    'tutorial.step1.title': 'Patient:in auswählen',
    'tutorial.step1.body':
      'Beginnen Sie mit den Patient:innenkarten hinter diesem Tutorial. Jede Person enthält mehrere unabhängige Kodierfragen, und alle Karten sind von Anfang an verfügbar.',
    'tutorial.step1.cue': 'Die Hinweise „Grundlegend“ und „Anspruchsvoll“ dienen nur zur Orientierung und sperren nichts.',
    'tutorial.step2.title': 'Patient:innenakte prüfen',
    'tutorial.step2.body':
      'Öffnen Sie in einer Frage „Patient:inneninfo anzeigen“, um Zusammenfassung und dokumentierten Kontext zu prüfen. Die Akte lässt sich jederzeit erneut öffnen, ohne Ihre Auswahl zu verlieren.',
    'tutorial.step2.cue': 'Achten Sie darauf, ob Informationen aus einer Akte, einer Eigenangabe oder einer aktuellen Untersuchung stammen.',
    'tutorial.step3.title': 'Eine Frage nach der anderen beantworten',
    'tutorial.step3.body':
      'Orientieren Sie sich an der Fragenanzeige, wählen Sie einen ICD-10-Code oder „Keine der genannten“ und senden Sie die Antwort ab. Nach der Bewertung ist die Auswahl gesperrt, damit sich das Feedback eindeutig auf Ihre Entscheidung bezieht.',
    'tutorial.step3.cue': 'Die Reihenfolge wird bei jedem Durchlauf neu gemischt; Fragen und Antwortmöglichkeiten selbst bleiben unverändert.',
    'tutorial.step4.title': 'Feedback nutzen und fortfahren',
    'tutorial.step4.body':
      'Lesen Sie Ergebnis und Erklärung, bevor Sie fortfahren. Nach der letzten Frage fasst die Abschlussansicht den Fall zusammen, ohne aus den drei Ergebnisklassen eine Punktzahl zu bilden.',
    'tutorial.step4.cue': 'Bei Bedarf zeigen die technischen Details die entscheidende Regel und das Kriterium eines Ergebnisses.',
    'tutorial.legend.correct': 'die gewählte Antwort wird ausdrücklich unterstützt.',
    'tutorial.legend.suboptimal': 'eine spezifischere unterstützte Antwort ist verfügbar.',
    'tutorial.legend.incorrect': 'die Antwort widerspricht den dokumentierten Fakten oder Regeln.',
    'roster.allCompleted': 'Alle 6 Patient:innen in dieser Sitzung abgeschlossen.',
    'roster.resetProgress': 'Fortschritt zurücksetzen',
    'roster.resetProgressConfirm': 'Abschluss-Markierungen für alle Patient:innen in dieser Sitzung löschen?',
    'roster.heading': 'Patient:in auswählen',
    'roster.loading': 'Patient:innen werden geladen…',
    'roster.error': 'Patient:innen konnten nicht geladen werden: {message}. Falls dies bestehen bleibt, prüfen Sie, ob Backend und Datenbank-Container laufen.',
    'roster.progress': '{completed} von {total} Patient:innen abgeschlossen',
    'patient.yearsOld': '{age} Jahre',
    'patient.questionCount': '{count} Fragen',
    'patient.completed': 'Abgeschlossen',
    'difficulty.foundational': 'Grundlegend',
    'difficulty.involved': 'Anspruchsvoll',
    'sex.female': 'weiblich',
    'sex.male': 'männlich',
    'dossier.show': 'Patient:inneninfo anzeigen',
    'dossier.hide': 'Patient:inneninfo ausblenden',
    'dossier.identityDetail': '— {age} Jahre, {sex}',
    'itemType.documented_condition': 'Dokumentierte Erkrankung',
    'itemType.self_reported_history': 'Anamnestische Angabe',
    'itemType.current_exam_finding': 'Aktueller Untersuchungsbefund',
    'itemType.social_context': 'Sozialer Kontext',
    'itemType.information_boundary': 'Informationsgrenze',
    'itemType.other': 'Sonstiges',
    'question.progress': 'Frage {number} von {total}',
    'question.exit': 'Zur Patient:innenliste zurückkehren',
    'question.exitConfirm': 'Diesen Patient:in jetzt verlassen? Ihr Fortschritt in diesem Durchlauf wird nicht gespeichert.',
    'question.noneOfAbove': 'Keine der genannten',
    'question.submit': 'Antwort absenden',
    'question.submitting': 'Wird gesendet…',
    'question.notEvaluated': 'Nicht bewertet',
    'question.notEvaluatedBody': 'Diese Eingabe konnte nicht klassifiziert werden ({reason}).',
    'gateReason.outside_active_subset': 'der übermittelte Code liegt außerhalb der aktiven Katalog-Teilmenge für diese Sitzung',
    'gateReason.undefined_case_relation': 'für diese Frage ist keine Beziehung zum übermittelten Code definiert',
    'gateReason.missing_required_case_fact': 'ein für die Bewertung dieser Frage erforderlicher Fakt fehlt',
    'gateReason.none_option_not_defined': '„Keine der genannten" ist für diese Frage nicht als Antwort definiert',
    'gateReason.malformed_input': 'die übermittelte Antwort entsprach nicht dem erwarteten Format',
    'gateReason.unsupported_response_kind': 'der übermittelte Antworttyp wird nicht unterstützt',
    'question.next': 'Nächste Frage',
  'question.reviewPatient': 'Patient:in auswerten',
    'question.improvement': 'Verbesserungsvorschlag: {code}',
    'status.not_attempted': 'Nicht bearbeitet',
    'status.correct': 'Richtig',
    'status.suboptimal': 'Suboptimal',
    'status.incorrect': 'Falsch',
    'review.heading': '{name} — Patient:in abgeschlossen',
    'review.counts': '{correct} richtig · {suboptimal} suboptimal · {incorrect} falsch',
    'review.playAgain': 'Erneut spielen',
    'review.chooseAnother': 'Andere Patient:innen wählen',
    'technicalDetails.toggle': 'Technische Details',
    'technicalDetails.determiningRule': 'Entscheidende Regel',
    'technicalDetails.criterion': 'Kriterium',
    'technicalDetails.matchedRules': 'Zutreffende Regeln',
  },
}

function detectDefaultLocale() {
  if (typeof navigator === 'undefined') return 'en'
  const preferred = navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language]
  for (const lang of preferred) {
    if (typeof lang !== 'string') continue
    if (lang.toLowerCase().startsWith('de')) return 'de'
    if (lang.toLowerCase().startsWith('en')) return 'en'
  }
  return 'en'
}

function readStoredLocale() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'en' || stored === 'de' ? stored : null
  } catch {
    return null
  }
}

function format(text, vars) {
  if (!vars) return text
  return Object.entries(vars).reduce((acc, [name, value]) => acc.replaceAll(`{${name}}`, String(value)), text)
}

const LocaleContext = createContext(null)

export function LocaleProvider({ children }) {
  const [locale, setLocaleState] = useState(() => readStoredLocale() ?? detectDefaultLocale())

  function setLocale(next) {
    setLocaleState(next)
    try {
      localStorage.setItem(STORAGE_KEY, next)
    } catch {
      // private-browsing/quota: locale choice just won't survive a reload
    }
  }

  const t = useMemo(() => {
    const dict = STRINGS[locale] ?? STRINGS.en
    return (key, vars) => format(dict[key] ?? STRINGS.en[key] ?? key, vars)
  }, [locale])

  const value = useMemo(() => ({ locale, setLocale, t }), [locale, t])

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>
}

export function useLocale() {
  const context = useContext(LocaleContext)
  if (!context) throw new Error('useLocale() must be used within a LocaleProvider')
  return context
}
