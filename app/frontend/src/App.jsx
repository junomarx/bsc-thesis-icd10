import { useEffect, useMemo, useState } from 'react'
import { evaluate, getCase, listCases } from './api.js'
import './App.css'

const CLASS_LABELS = {
  correct: 'Correct',
  suboptimal: 'Suboptimal',
  incorrect: 'Incorrect',
}

function CaseList({ cases, onSelect, loading, error }) {
  return (
    <section>
      <h1>Austrian ICD-10 coding practice (educational prototype)</h1>
      <p className="disclaimer">
        Synthetic teaching cases only. This tool does not diagnose patients, does not
        provide clinical decision support, and is not used for official coding,
        reporting, or reimbursement.
      </p>
      {loading && <p>Loading cases…</p>}
      {error && <p className="error">Could not load cases: {error}</p>}
      <ul className="case-list">
        {cases.map((c) => (
          <li key={c.case_id}>
            <button type="button" onClick={() => onSelect(c.case_id)}>
              {c.case_id} — {c.encounter_setting.replace('_', ' ')}, {c.diagnosis_role} diagnosis
            </button>
          </li>
        ))}
      </ul>
    </section>
  )
}

function CaseDetail({ caseData, onSubmit, submitting, onBack }) {
  const [search, setSearch] = useState('')
  const [selectedCode, setSelectedCode] = useState(null)

  const visibleCodes = useMemo(() => {
    const term = search.trim().toLowerCase()
    if (term === '') return caseData.supported_codes
    return caseData.supported_codes.filter(
      (code) =>
        code.code.toLowerCase().includes(term) ||
        code.designation.toLowerCase().includes(term) ||
        code.short_designation.toLowerCase().includes(term),
    )
  }, [search, caseData.supported_codes])

  return (
    <section>
      <button type="button" className="link-button" onClick={onBack}>
        ← Back to cases
      </button>
      <h2>{caseData.case_id}</h2>
      <dl className="case-facts">
        <dt>Setting</dt>
        <dd>{caseData.encounter_setting.replace('_', ' ')}</dd>
        <dt>Diagnosis role</dt>
        <dd>{caseData.diagnosis_role}</dd>
        {caseData.fev1_stable_pct_predicted !== null && (
          <>
            <dt>Stable-phase FEV1</dt>
            <dd>{caseData.fev1_stable_pct_predicted}% of predicted</dd>
          </>
        )}
      </dl>

      <label htmlFor="code-search">Search supported codes</label>
      <input
        id="code-search"
        type="text"
        value={search}
        onChange={(event) => setSearch(event.target.value)}
        placeholder="Search by code or designation…"
      />

      <ul className="code-list">
        {visibleCodes.map((code) => (
          <li key={code.code}>
            <label>
              <input
                type="radio"
                name="submitted_code"
                value={code.code}
                checked={selectedCode === code.code}
                onChange={() => setSelectedCode(code.code)}
              />
              <strong>{code.code}</strong> — {code.short_designation}
            </label>
          </li>
        ))}
      </ul>

      <button
        type="button"
        disabled={selectedCode === null || submitting}
        onClick={() => onSubmit(selectedCode)}
      >
        {submitting ? 'Submitting…' : 'Submit code'}
      </button>
    </section>
  )
}

function ResultView({ result, onRetry, onBack }) {
  if (result.evaluation_status === 'not_evaluated') {
    return (
      <section>
        <h2>Not evaluated</h2>
        <p>This submission could not be classified ({result.reason}).</p>
        <button type="button" onClick={onRetry}>Try another code</button>
        <button type="button" className="link-button" onClick={onBack}>Back to cases</button>
      </section>
    )
  }

  return (
    <section>
      <h2 className={`result-heading result-${result.classification}`}>
        {CLASS_LABELS[result.classification] ?? result.classification}
      </h2>
      <p>{result.explanation}</p>
      {result.improvement_code && (
        <p className="improvement">Suggested improvement: <strong>{result.improvement_code}</strong></p>
      )}
      <button type="button" onClick={onRetry}>Try another code</button>
      <button type="button" className="link-button" onClick={onBack}>Back to cases</button>
    </section>
  )
}

export default function App() {
  const [cases, setCases] = useState([])
  const [loadingCases, setLoadingCases] = useState(true)
  const [listError, setListError] = useState(null)

  const [activeCase, setActiveCase] = useState(null)
  const [result, setResult] = useState(null)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    listCases()
      .then(({ status, body }) => {
        if (status !== 200) throw new Error(body?.error ?? `HTTP ${status}`)
        setCases(body.cases)
      })
      .catch((error) => setListError(error.message))
      .finally(() => setLoadingCases(false))
  }, [])

  function openCase(caseId) {
    setResult(null)
    getCase(caseId).then(({ status, body }) => {
      if (status === 200) setActiveCase(body)
    })
  }

  function backToList() {
    setActiveCase(null)
    setResult(null)
  }

  function submitCode(code) {
    setSubmitting(true)
    evaluate(activeCase.case_id, code)
      .then(({ body }) => setResult(body))
      .finally(() => setSubmitting(false))
  }

  if (result !== null) {
    return <ResultView result={result} onRetry={() => setResult(null)} onBack={backToList} />
  }

  if (activeCase !== null) {
    return (
      <CaseDetail
        caseData={activeCase}
        onSubmit={submitCode}
        submitting={submitting}
        onBack={backToList}
      />
    )
  }

  return (
    <CaseList cases={cases} onSelect={openCase} loading={loadingCases} error={listError} />
  )
}
