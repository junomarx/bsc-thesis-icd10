import { useLocale } from '../lib/i18n.jsx'
import { CONTEXT_ITEM_DE, localizeText, PATIENT_SUMMARY_DE } from '../lib/contentTranslations.js'

const ITEM_TYPE_LABEL_KEYS = {
  documented_condition: 'itemType.documented_condition',
  self_reported_history: 'itemType.self_reported_history',
  current_exam_finding: 'itemType.current_exam_finding',
  social_context: 'itemType.social_context',
  information_boundary: 'itemType.information_boundary',
  other: 'itemType.other',
}

const SEX_LABEL_KEYS = {
  female: 'sex.female',
  male: 'sex.male',
}

// REQ-INT-02: patient identity/context stays reachable throughout a
// playthrough without losing the active question's state - this renders
// inline (collapsible), it never navigates away from the question view.
export default function PatientDossier({ patient, open, onToggle }) {
  const { t, locale } = useLocale()
  const summary = localizeText(PATIENT_SUMMARY_DE, patient.patient_id, patient.general_health_summary, locale)

  return (
    <div className="patient-dossier">
      <button type="button" className="patient-dossier-toggle" onClick={onToggle}>
        {open ? t('dossier.hide') : t('dossier.show')}
      </button>
      {open && (
        <div className="patient-dossier-panel">
          <p className="patient-dossier-identity">
            <strong>{patient.display_name}</strong>{' '}
            {t('dossier.identityDetail', {
              age: patient.age_years,
              sex: t(SEX_LABEL_KEYS[patient.sex] ?? patient.sex),
            })}
          </p>
          <p>{summary}</p>
          <ul className="patient-context-list">
            {patient.context_items.map((item) => (
              <li key={item.context_item_id}>
                <span className="badge">{t(ITEM_TYPE_LABEL_KEYS[item.item_type] ?? item.item_type)}</span>{' '}
                {localizeText(CONTEXT_ITEM_DE, item.context_item_id, item.display_text, locale)}
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}
