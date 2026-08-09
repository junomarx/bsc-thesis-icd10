import Orientation from './Orientation.jsx'
import PatientCard from './PatientCard.jsx'
import { useLocale } from '../lib/i18n.jsx'

export default function PatientRoster({ patients, completedPatientIds, onSelect, onResetProgress, loading, error }) {
  const { t } = useLocale()
  const allCompleted = patients.length > 0 && completedPatientIds.size >= patients.length

  function handleResetProgress() {
    if (window.confirm(t('roster.resetProgressConfirm'))) {
      onResetProgress()
    }
  }

  return (
    <section>
      <Orientation />
      <h2>{t('roster.heading')}</h2>
      {loading && <p>{t('roster.loading')}</p>}
      {error && <p className="error">{t('roster.error', { message: error })}</p>}
      {!loading && !error && patients.length > 0 && (
        <div className="roster-progress-row">
          <p className="progress-summary">
            {allCompleted ? t('roster.allCompleted') : t('roster.progress', { completed: completedPatientIds.size, total: patients.length })}
          </p>
          {completedPatientIds.size > 0 && (
            <button type="button" className="link-button" onClick={handleResetProgress}>
              {t('roster.resetProgress')}
            </button>
          )}
        </div>
      )}
      <ul className="patient-list">
        {patients.map((patient) => (
          <li key={patient.patient_id}>
            <PatientCard
              patient={patient}
              completed={completedPatientIds.has(patient.patient_id)}
              onSelect={onSelect}
            />
          </li>
        ))}
      </ul>
    </section>
  )
}
