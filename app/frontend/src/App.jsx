import { useCallback, useEffect, useState } from 'react'
import { evaluate, getPatient, getQuestion, listPatients } from './api.js'
import Footer from './components/Footer.jsx'
import Header from './components/Header.jsx'
import PatientRoster from './components/PatientRoster.jsx'
import PatientReview from './components/PatientReview.jsx'
import QuestionView from './components/QuestionView.jsx'
import Tutorial from './components/Tutorial.jsx'
import { shuffledOrder } from './lib/playthrough.js'
import './App.css'

// REQ-UI-02: patient-level completion is shown on the roster, but is
// explicitly "session-local" - sessionStorage (cleared when the browser
// session ends), never a server-side attempt history (REQ-INT-05).
const COMPLETED_STORAGE_KEY = 'icd10-prototype:completed-patients'
const TUTORIAL_STORAGE_KEY = 'icd10-prototype:tutorial-seen-v1'

function readCompletedPatientIds() {
  try {
    const stored = sessionStorage.getItem(COMPLETED_STORAGE_KEY)
    const parsed = stored ? JSON.parse(stored) : []
    return new Set(Array.isArray(parsed) ? parsed : [])
  } catch {
    return new Set()
  }
}

function isFirstTutorialVisit() {
  try {
    return localStorage.getItem(TUTORIAL_STORAGE_KEY) !== 'true'
  } catch {
    return true
  }
}

export default function App() {
  const [patients, setPatients] = useState([])
  const [loadingPatients, setLoadingPatients] = useState(true)
  const [patientsError, setPatientsError] = useState(null)
  const [completedPatientIds, setCompletedPatientIds] = useState(() => readCompletedPatientIds())
  const [tutorialOpen, setTutorialOpen] = useState(() => isFirstTutorialVisit())

  const [view, setView] = useState('roster')
  const [activePatient, setActivePatient] = useState(null)
  const [orderedQuestionIds, setOrderedQuestionIds] = useState([])
  const [currentIndex, setCurrentIndex] = useState(0)
  const [questionsById, setQuestionsById] = useState({})
  const [results, setResults] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    listPatients()
      .then(({ status, body }) => {
        if (status !== 200) throw new Error(body?.error ?? `HTTP ${status}`)
        setPatients(body.patients)
      })
      .catch((error) => setPatientsError(error.message))
      .finally(() => setLoadingPatients(false))
  }, [])

  const currentQuestionId = orderedQuestionIds[currentIndex] ?? null
  const currentQuestion = currentQuestionId ? questionsById[currentQuestionId] : null

  useEffect(() => {
    if (view !== 'playthrough' || currentQuestionId === null || questionsById[currentQuestionId]) return

    getQuestion(currentQuestionId).then(({ status, body }) => {
      if (status === 200) {
        setQuestionsById((prev) => ({ ...prev, [currentQuestionId]: body }))
      }
    })
  }, [view, currentQuestionId, questionsById])

  function selectPatient(patientId) {
    getPatient(patientId).then(({ status, body }) => {
      if (status !== 200) return
      setActivePatient(body)
      setOrderedQuestionIds(shuffledOrder(body.questions.map((q) => q.question_id)))
      setCurrentIndex(0)
      setResults({})
      setView('playthrough')
    })
  }

  function submitAnswer(response) {
    setSubmitting(true)
    evaluate(currentQuestionId, response)
      .then(({ body }) => setResults((prev) => ({ ...prev, [currentQuestionId]: body })))
      .finally(() => setSubmitting(false))
  }

  function advance() {
    if (currentIndex + 1 >= orderedQuestionIds.length) {
      setView('review')
      setCompletedPatientIds((prev) => {
        if (prev.has(activePatient.patient_id)) return prev
        const next = new Set(prev)
        next.add(activePatient.patient_id)
        try {
          sessionStorage.setItem(COMPLETED_STORAGE_KEY, JSON.stringify([...next]))
        } catch {
          // private-browsing/quota: completion badge just won't survive this
        }
        return next
      })
      return
    }
    setCurrentIndex((i) => i + 1)
  }

  function replay() {
    setOrderedQuestionIds(shuffledOrder(activePatient.questions.map((q) => q.question_id)))
    setCurrentIndex(0)
    setResults({})
    setView('playthrough')
  }

  function chooseAnother() {
    setActivePatient(null)
    setOrderedQuestionIds([])
    setCurrentIndex(0)
    setResults({})
    setView('roster')
  }

  function resetProgress() {
    setCompletedPatientIds(new Set())
    try {
      sessionStorage.removeItem(COMPLETED_STORAGE_KEY)
    } catch {
      // private-browsing/quota: nothing to clean up in that case
    }
  }

  const closeTutorial = useCallback(() => {
    try {
      localStorage.setItem(TUTORIAL_STORAGE_KEY, 'true')
    } catch {
      // private-browsing/quota: the tutorial remains usable for this page
    }
    setTutorialOpen(false)
  }, [])

  return (
    <>
      <Header onOpenTutorial={() => setTutorialOpen(true)} tutorialOpen={tutorialOpen} />
      {view === 'roster' && (
        <PatientRoster
          patients={patients}
          completedPatientIds={completedPatientIds}
          onSelect={selectPatient}
          onResetProgress={resetProgress}
          loading={loadingPatients}
          error={patientsError}
        />
      )}
      {view === 'playthrough' && activePatient && currentQuestion && (
        <QuestionView
          patient={activePatient}
          question={currentQuestion}
          questionNumber={currentIndex + 1}
          totalQuestions={orderedQuestionIds.length}
          result={results[currentQuestionId] ?? null}
          submitting={submitting}
          onSubmit={submitAnswer}
          onAdvance={advance}
          onExit={chooseAnother}
          isLast={currentIndex + 1 >= orderedQuestionIds.length}
        />
      )}
      {view === 'review' && activePatient && (
        <PatientReview
          patient={activePatient}
          orderedQuestionIds={orderedQuestionIds}
          questionsById={questionsById}
          results={results}
          onReplay={replay}
          onChooseAnother={chooseAnother}
        />
      )}
      <Footer />
      {tutorialOpen && <Tutorial onClose={closeTutorial} />}
    </>
  )
}
