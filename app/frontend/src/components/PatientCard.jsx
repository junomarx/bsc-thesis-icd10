import { useLocale } from '../lib/i18n.jsx'
import { localizeText, PATIENT_SUMMARY_DE } from '../lib/contentTranslations.js'
import { IconCheck } from './icons.jsx'

const DIFFICULTY_LABEL_KEYS = {
  foundational: 'difficulty.foundational',
  involved: 'difficulty.involved',
}

const SEX_LABEL_KEYS = {
  female: 'sex.female',
  male: 'sex.male',
}

export default function PatientCard({ patient, completed, onSelect }) {
  const { t, locale } = useLocale()
  const summary = localizeText(PATIENT_SUMMARY_DE, patient.patient_id, patient.general_health_summary, locale)

  return (
    <button
      type="button"
      className="patient-card"
      data-patient-id={patient.patient_id}
      onClick={() => onSelect(patient.patient_id)}
    >
      <span className="patient-card-heading">
        {patient.display_name}
        {completed && (
          <span className="badge badge-completed">
            <IconCheck /> {t('patient.completed')}
          </span>
        )}
      </span>
      <span className="patient-card-badges">
        <span className="badge">{t('patient.yearsOld', { age: patient.age_years })}</span>
        <span className="badge">{t(SEX_LABEL_KEYS[patient.sex] ?? patient.sex)}</span>
        <span className="badge">{t(DIFFICULTY_LABEL_KEYS[patient.difficulty_role] ?? patient.difficulty_role)}</span>
        <span className="badge">{t('patient.questionCount', { count: patient.question_count })}</span>
      </span>
      <span className="patient-card-summary">{summary}</span>
    </button>
  )
}
