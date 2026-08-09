// German translations of runtime *content* (patient summaries, context
// items, question titles/prompts) for the German UI locale. Keyed by the
// same ids the API returns, so a lookup miss (e.g. a hidden
// verification_only question, which is never rendered anyway) falls back
// to the English text the backend sent rather than showing a blank.
//
// This is presentation-only: it does not change QUESTIONBASE-0.1/
// PATIENTBASE-0.1 runtime data, the evaluator, or anything the backend
// returns - REQ-ARC-01's separation of reference data from presentation.
// Kept in one file, reviewed for exact clinical/logical equivalence to the
// English source (these prompts state facts the rule engine's evaluation
// depends on the learner reading correctly).

export const PATIENT_SUMMARY_DE = {
  'PATIENT-001':
    'Es liegen etablierte hausärztliche und stationäre Aufzeichnungen vor. Mehrere chronische Erkrankungen sind dokumentiert; die aktuelle respiratorische Episode ist gesondert dargestellt.',
  'PATIENT-002':
    'Longitudinale Aufzeichnungen dokumentieren kardiovaskuläre, renale und psychiatrische Erkrankungen. Die Lernenden sollen den dokumentierten Subtyp bzw. das dokumentierte Stadium beibehalten, statt eine neue Erkrankung abzuleiten.',
  'PATIENT-003':
    'Die Aufzeichnung enthält neurologische, endokrinologische und psychische Diagnosen sowie nicht zusammenhängende Erkrankungen der Vorgeschichte.',
  'PATIENT-004':
    'Es liegt ein übermittelter Befund vor, mehrere Diagnosen wurden jedoch von unterschiedlichen Abteilungen dokumentiert. Manche Fragen erfordern die Unterscheidung zwischen bloßem Nebeneinanderbestehen und einem ausdrücklich dokumentierten kausalen Zusammenhang.',
  'PATIENT-005':
    'Fach- und hausärztliche Aufzeichnungen enthalten psychiatrische, bewegungsstörungsbezogene, dermatologische, metabolische und kardiovaskuläre Diagnosen. Die Aufgaben erstrecken sich bewusst über nicht zusammenhängende ICD-Gruppen.',
  'PATIENT-006':
    'Der Patient ist bewusstlos und kann keine Anamnese angeben. Es dürfen nur zugängliche Vorbefunde sowie aktuelle Untersuchungs- bzw. Kontaktbefunde verwendet werden.',
}

export const CONTEXT_ITEM_DE = {
  'CTX-001-01':
    'COPD ist dokumentiert; der aktuelle respiratorische Befund dokumentiert zusätzlich eine akute Infektion der unteren Atemwege.',
  'CTX-001-02': 'Diabetes mellitus Typ 2 ist im dargestellten Befund ohne diabetische Komplikation dokumentiert.',
  'CTX-001-03': 'Primäre beidseitige Kniegelenksarthrose ist dokumentiert.',
  'CTX-001-04':
    'Essentielle Hypertonie ist Teil der Vorgeschichte, ist jedoch in diesem Patientendurchlauf kein Kodierziel.',
  'CTX-001-05':
    'Für die dargestellte COPD-Aufgabe ist die FEV1 in der stabilen Phase mit 55 % des Sollwerts dokumentiert.',
  'CTX-002-01': 'Paroxysmales Vorhofflimmern ist in der longitudinalen Aufzeichnung dokumentiert.',
  'CTX-002-02': 'Chronische Nierenerkrankung Stadium 3 ist dokumentiert.',
  'CTX-002-03': 'Panikstörung ist dokumentiert.',
  'CTX-002-04': 'Essentielle Hypertonie ist Teil der Vorgeschichte und derzeit kein Kodierziel.',
  'CTX-002-05': 'Gicht ist Teil der Vorgeschichte und derzeit kein Kodierziel.',
  'CTX-003-01': 'Migräne mit Aura ist dokumentiert; die dargestellte Episode ist keine Status migrainosus.',
  'CTX-003-02': 'Postinfektiöse Hypothyreose ist dokumentiert.',
  'CTX-003-03': 'Generalisierte Angststörung ist dokumentiert.',
  'CTX-003-04': 'Endometriose ist in der Vorgeschichte vermerkt und derzeit kein Kodierziel.',
  'CTX-003-05':
    'Eine verheilte Handgelenksfraktur ist in der lange zurückliegenden Vorgeschichte vermerkt und derzeit kein Kodierziel.',
  'CTX-004-01': 'Diabetes mellitus Typ 2 ist im übermittelten Befund ohne diabetische Komplikation dokumentiert.',
  'CTX-004-02':
    'Primäres Offenwinkelglaukom ist gesondert dokumentiert; der Befund führt es nicht auf den Diabetes zurück.',
  'CTX-004-03': 'Rezidivierende depressive Störung mit einer aktuell mittelgradigen Episode ist dokumentiert.',
  'CTX-004-04': 'Eisenmangelanämie durch chronischen Blutverlust ist dokumentiert.',
  'CTX-004-05':
    'Lokalisierte Kreuzschmerzen ohne Ausstrahlung ins Bein sind vermerkt; für diesen Befund wurde noch keine zugrunde liegende Diagnose festgestellt.',
  'CTX-005-01': 'Paranoide Schizophrenie ist fachärztlich dokumentiert.',
  'CTX-005-02': 'Tardive Dyskinesie im Zusammenhang mit langfristiger neuroleptischer Behandlung ist dokumentiert.',
  'CTX-005-03': 'Psoriasis vulgaris ist dokumentiert.',
  'CTX-005-04': 'Gemischte Hyperlipidämie ist dokumentiert.',
  'CTX-005-05':
    'Essentielle primäre Hypertonie ist ohne hypertensive Herzkrankheit oder sekundäre Ursache dokumentiert.',
  'CTX-006-01': 'Der Patient ist bewusstlos und kann bei dem dargestellten Kontakt keine Anamnese angeben.',
  'CTX-006-02': 'Zugängliche Vorbefunde dokumentieren eine atherosklerotische Herzkrankheit.',
  'CTX-006-03':
    'Ein zwei Jahre zurückliegender Hirninfarkt ist gemeinsam mit einem aktuellen Defizit dokumentiert, das ausdrücklich als Folgezustand beschrieben wird.',
  'CTX-006-04':
    'Generalisierte idiopathische Epilepsie ist dokumentiert; der Befund stellt sie nicht als Ursache der aktuellen Bewusstlosigkeit fest.',
  'CTX-006-05':
    'Demenz ist ohne dokumentierte Ätiologie oder dokumentierten kausalen Zusammenhang mit der vorbestehenden zerebrovaskulären Erkrankung dokumentiert.',
  'CTX-006-06': 'Benigne Prostatahyperplasie ist dokumentiert.',
  'CTX-006-07':
    'Am dargestellten Ende des Kontakts bleibt der Patient bewusstlos; die diagnostische Abklärung ist nicht abgeschlossen, und es wurde keine kodierbare Krankheitsursache festgestellt.',
}

export const QUESTION_CONTENT_DE = {
  'Q-001-01': {
    title: 'Kodieraufgabe Atmungssystem',
    prompt:
      'Der dargestellte stationäre Befund dokumentiert eine COPD mit akuter Infektion der unteren Atemwege. Die FEV1 in der stabilen Phase beträgt 55 % des Sollwerts. Wählen Sie die am besten begründete österreichische ICD-10-Antwort.',
  },
  'Q-001-02': {
    title: 'Kodieraufgabe Stoffwechsel',
    prompt:
      'Der Befund gibt ausdrücklich einen Diabetes mellitus Typ 2 ohne dokumentierte diabetische Komplikationen an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-001-03': {
    title: 'Kodieraufgabe Bewegungsapparat',
    prompt:
      'Der Befund dokumentiert ausdrücklich eine primäre beidseitige Kniegelenksarthrose, ohne dokumentierte posttraumatische oder sekundäre Ätiologie. Wählen Sie den am besten begründeten Code.',
  },
  'Q-002-01': {
    title: 'Kodieraufgabe Herzrhythmus',
    prompt:
      'Die longitudinale Aufzeichnung bezeichnet das Vorhofflimmern ausdrücklich als paroxysmal. Wählen Sie den am besten begründeten Code.',
  },
  'Q-002-02': {
    title: 'Kodieraufgabe Niere',
    prompt:
      'Der Befund dokumentiert eine chronische Nierenerkrankung Stadium 3; die dokumentierte GFR für den dargestellten Zustand beträgt 45 ml/min/1,73 m². Wählen Sie den am besten begründeten Code.',
  },
  'Q-002-03': {
    title: 'Kodieraufgabe Psychische Gesundheit',
    prompt:
      'Der Befund gibt ausdrücklich eine Panikstörung an. Keine aktuelle depressive Störung wird als Grundlage dieser Diagnose dargestellt. Wählen Sie den am besten begründeten Code.',
  },
  'Q-003-01': {
    title: 'Kodieraufgabe Neurologie',
    prompt:
      'Der Befund gibt ausdrücklich eine Migräne mit Aura an. Die dargestellte Episode ist keine Status migrainosus. Wählen Sie den am besten begründeten Code.',
  },
  'Q-003-02': {
    title: 'Kodieraufgabe Endokrinologie',
    prompt: 'Der Befund gibt ausdrücklich eine postinfektiöse Hypothyreose an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-003-03': {
    title: 'Kodieraufgabe Angststörung',
    prompt: 'Der Befund gibt ausdrücklich eine generalisierte Angststörung an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-004-01': {
    title: 'Kodieraufgabe Begleiterkrankung',
    prompt:
      'Der übermittelte Befund führt Diabetes mellitus Typ 2 und gesondert ein primäres Offenwinkelglaukom an. Es ist weder eine diabetische Augenkomplikation noch ein kausaler Zusammenhang zwischen beiden dokumentiert. Welcher Code stellt den Diabetes für diese Aufgabe am besten dar?',
  },
  'Q-004-02': {
    title: 'Kodieraufgabe Augenheilkunde',
    prompt:
      'Der übermittelte augenärztliche Befund gibt ausdrücklich ein primäres Offenwinkelglaukom an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-004-03': {
    title: 'Kodieraufgabe Affektive Störung',
    prompt:
      'Der Befund gibt ausdrücklich eine rezidivierende depressive Störung mit einer aktuell mittelgradigen Episode an. Keine manische Episode ist dokumentiert. Wählen Sie den am besten begründeten Code.',
  },
  'Q-004-04': {
    title: 'Kodieraufgabe Hämatologie',
    prompt:
      'Der Befund gibt ausdrücklich eine Eisenmangelanämie durch chronischen Blutverlust an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-004-05': {
    title: 'Kodieraufgabe Aktueller Befund',
    prompt:
      'Im Rahmen der Untersuchung gibt der Patient lokalisierte Kreuzschmerzen ohne Ausstrahlung ins Bein an; eine zugrunde liegende Diagnose wurde nicht festgestellt. Welche der angezeigten Auswahlmöglichkeiten stellt den dokumentierten Befund am besten dar?',
  },
  'Q-005-01': {
    title: 'Kodieraufgabe Psychiatrie',
    prompt:
      'Der fachärztliche Befund gibt ausdrücklich eine paranoide Schizophrenie an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-005-02': {
    title: 'Kodieraufgabe Bewegungsstörung',
    prompt:
      'Die Behandlerin bzw. der Behandler dokumentiert ausdrücklich eine tardive Dyskinesie im Zusammenhang mit langfristiger neuroleptischer Behandlung. Diese Aufgabe verlangt ausschließlich den Diagnosecode, nicht einen Zusatzcode für die äußere Ursache. Wählen Sie den am besten begründeten Diagnosecode.',
  },
  'Q-005-03': {
    title: 'Kodieraufgabe Dermatologie',
    prompt:
      'Der Befund gibt ausdrücklich eine Psoriasis vulgaris an. Keine pustulöse oder guttata-Form ist dokumentiert. Wählen Sie den am besten begründeten Code.',
  },
  'Q-005-04': {
    title: 'Kodieraufgabe Fettstoffwechselstörung',
    prompt: 'Der Befund gibt ausdrücklich eine gemischte Hyperlipidämie an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-005-05': {
    title: 'Kodieraufgabe Blutdruck',
    prompt:
      'Der Befund weist eine essentielle primäre Hypertonie ohne hypertensive Herzkrankheit oder sekundäre Ursache aus. Welche der angezeigten Auswahlmöglichkeiten stellt diese Diagnose dar?',
  },
  'Q-006-01': {
    title: 'Kodieraufgabe Kardiologischer Vorbefund',
    prompt:
      'Der zugängliche Vorbefund gibt ausdrücklich eine atherosklerotische Herzkrankheit an. Er benennt weder einen alten Myokardinfarkt noch eine ischämische Kardiomyopathie als dieses Kodierziel. Wählen Sie den am besten begründeten Code.',
  },
  'Q-006-02': {
    title: 'Kodieraufgabe Folgezustand',
    prompt:
      'Der zugängliche Befund dokumentiert einen zwei Jahre zurückliegenden Hirninfarkt und ein aktuelles Restdefizit, das ausdrücklich als dessen Folgezustand beschrieben wird. Wählen Sie den Code, der den dokumentierten Folgezustand am besten darstellt.',
  },
  'Q-006-03': {
    title: 'Kodieraufgabe Epilepsie-Vorbefund',
    prompt:
      'Der zugängliche Vorbefund gibt ausdrücklich eine generalisierte idiopathische Epilepsie an. Kein Status epilepticus ist dargestellt. Wählen Sie den am besten begründeten Code für diese chronische Diagnose.',
  },
  'Q-006-04': {
    title: 'Kodieraufgabe Kognitiver Vorbefund',
    prompt:
      'Der Vorbefund gibt eine Demenz an, jedoch ist keine Ätiologie dokumentiert. Eine zerebrovaskuläre Erkrankung besteht gesondert und ist nicht als ursächlich dokumentiert; ein Delir ist nicht dokumentiert. Wählen Sie den am besten begründeten Code.',
  },
  'Q-006-05': {
    title: 'Kodieraufgabe Urologischer Vorbefund',
    prompt:
      'Der zugängliche Vorbefund gibt ausdrücklich eine benigne Prostatahyperplasie an. Wählen Sie den am besten begründeten Code.',
  },
  'Q-006-06': {
    title: 'Kodieraufgabe Aktuelle Bewusstlosigkeit',
    prompt:
      'Am dargestellten Ende des Kontakts bleibt der Patient bewusstlos. Die diagnostische Abklärung ist nicht abgeschlossen, und es wurde keine kodierbare Krankheitsursache festgestellt. Die vorbestehende Epilepsiediagnose ist nicht als Ursache dieser Episode dokumentiert. Wählen Sie die am besten begründete Antwort für den aktuell dokumentierten Zustand.',
  },
}

export function localizeText(dict, id, fallback, locale) {
  if (locale !== 'de') return fallback
  return dict[id] ?? fallback
}
