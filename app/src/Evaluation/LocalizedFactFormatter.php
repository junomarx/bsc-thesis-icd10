<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

use Icd10Prototype\Model\QuestionFact;

/**
 * Turns evaluator facts into value-aware learner prose in both supported
 * locales. The formatter deliberately keys only on semantic fact key/value
 * pairs: it contains no question IDs or ICD codes, and it never consumes the
 * English-only authoring label stored in `question_fact.learner_label`.
 *
 * @phpstan-type LocalizedClause array{en: string, de: string}
 */
final class LocalizedFactFormatter
{
    /** @return array{en: string, de: string} */
    public static function clauses(QuestionFact $fact): array
    {
        $identity = $fact->factKey . '=' . self::normalizedValue($fact);

        return match ($identity) {
            'anaemia_cause=chronic_blood_loss' => self::pair(
                'the anaemia is caused by chronic blood loss',
                'die Anämie durch chronischen Blutverlust verursacht wird',
            ),
            'anaemia_type=iron_deficiency' => self::pair(
                'iron-deficiency anaemia is documented',
                'eine Eisenmangelanämie dokumentiert ist',
            ),
            'aura_present=true' => self::pair(
                'an aura is documented',
                'eine Aura dokumentiert ist',
            ),
            'cerebrovascular_causal_link_documented=false' => self::pair(
                'no causal link to the cerebrovascular disease is documented',
                'kein ursächlicher Zusammenhang mit der zerebrovaskulären Erkrankung dokumentiert ist',
            ),
            'ckd_stage=3' => self::pair(
                'chronic kidney disease stage 3 is documented',
                'eine chronische Nierenerkrankung im Stadium 3 dokumentiert ist',
            ),
            'codable_disease_cause_established=false' => self::pair(
                'no codable disease cause has been established',
                'keine kodierbare Krankheitsursache festgestellt wurde',
            ),
            'consciousness_state=unconscious' => self::pair(
                'the patient remains unconscious',
                'der Patient weiterhin bewusstlos ist',
            ),
            'current_episode_severity=moderate' => self::pair(
                'the current episode is moderate',
                'die aktuelle Episode mittelgradig ist',
            ),
            'delirium_documented=false' => self::pair(
                'no delirium is documented',
                'kein Delir dokumentiert ist',
            ),
            'dementia_etiology_documented=false' => self::pair(
                'no aetiology for the dementia is documented',
                'keine Ätiologie der Demenz dokumentiert ist',
            ),
            'depressive_course=recurrent' => self::pair(
                'the depressive disorder is recurrent',
                'eine rezidivierende depressive Störung dokumentiert ist',
            ),
            'diabetic_complications_documented=false' => self::pair(
                'no diabetic complication is documented',
                'keine diabetische Komplikation dokumentiert ist',
            ),
            'diabetic_eye_causality_documented=false' => self::pair(
                'no causal link between diabetes and the eye condition is documented',
                'kein ursächlicher Zusammenhang zwischen Diabetes und Augenerkrankung dokumentiert ist',
            ),
            'documented_diagnosis=benign_prostatic_hyperplasia' => self::pair(
                'benign prostatic hyperplasia is documented',
                'eine benigne Prostatahyperplasie dokumentiert ist',
            ),
            'documented_diagnosis=generalized_anxiety_disorder' => self::pair(
                'generalised anxiety disorder is documented',
                'eine generalisierte Angststörung dokumentiert ist',
            ),
            'documented_diagnosis=panic_disorder' => self::pair(
                'panic disorder is documented',
                'eine Panikstörung dokumentiert ist',
            ),
            'epilepsy_as_current_cause_established=false' => self::pair(
                'epilepsy is not established as the cause of the current episode',
                'die Epilepsie nicht als Ursache der aktuellen Episode festgestellt wurde',
            ),
            'epilepsy_type=generalized_idiopathic' => self::pair(
                'generalised idiopathic epilepsy is documented',
                'eine generalisierte idiopathische Epilepsie dokumentiert ist',
            ),
            'etiology=postinfectious' => self::pair(
                'the aetiology is postinfectious',
                'eine postinfektiöse Ätiologie vorliegt',
            ),
            'gfr=45' => self::pair(
                'the recorded GFR is 45 mL/min/1.73 m²',
                'die dokumentierte GFR 45 mL/min/1,73 m² beträgt',
            ),
            'glaucoma_subtype=primary_open_angle' => self::pair(
                'primary open-angle glaucoma is documented',
                'ein primäres Offenwinkelglaukom dokumentiert ist',
            ),
            'hypertension_type=essential_primary' => self::pair(
                'essential primary hypertension is documented',
                'eine essentielle primäre Hypertonie dokumentiert ist',
            ),
            'hypertensive_heart_disease_documented=false' => self::pair(
                'no hypertensive heart disease is documented',
                'keine hypertensive Herzkrankheit dokumentiert ist',
            ),
            'ischemic_cardiomyopathy_as_target=false' => self::pair(
                'ischaemic cardiomyopathy is not the current coding target',
                'eine ischämische Kardiomyopathie nicht das aktuelle Kodierziel ist',
            ),
            'leg_radiation=false' => self::pair(
                'the pain does not radiate into the leg',
                'der Schmerz nicht ins Bein ausstrahlt',
            ),
            'lipid_disorder=mixed_hyperlipidaemia' => self::pair(
                'mixed hyperlipidaemia is documented',
                'eine gemischte Hyperlipidämie dokumentiert ist',
            ),
            'movement_disorder=tardive_dyskinesia' => self::pair(
                'tardive dyskinesia is documented',
                'eine tardive Dyskinesie dokumentiert ist',
            ),
            'old_mi_as_target=false' => self::pair(
                'an old myocardial infarction is not the current coding target',
                'ein alter Myokardinfarkt nicht das aktuelle Kodierziel ist',
            ),
            'osteoarthritis_etiology=primary' => self::pair(
                'the osteoarthritis is primary',
                'eine primäre Arthrose dokumentiert ist',
            ),
            'pain_location=low_back_lumbar' => self::pair(
                'the pain is located in the lower back',
                'der Schmerz im Kreuzbereich lokalisiert ist',
            ),
            'prior_event=cerebral_infarction' => self::pair(
                'the prior event was a cerebral infarction',
                'das frühere Ereignis ein Hirninfarkt war',
            ),
            'psoriasis_subtype=vulgaris' => self::pair(
                'psoriasis vulgaris is documented',
                'eine Psoriasis vulgaris dokumentiert ist',
            ),
            'rhythm_diagnosis=atrial_fibrillation' => self::pair(
                'atrial fibrillation is documented',
                'Vorhofflimmern dokumentiert ist',
            ),
            'rhythm_pattern=paroxysmal' => self::pair(
                'the atrial fibrillation is paroxysmal',
                'das Vorhofflimmern paroxysmal ist',
            ),
            'schizophrenia_subtype=paranoid' => self::pair(
                'paranoid schizophrenia is documented',
                'eine paranoide Schizophrenie dokumentiert ist',
            ),
            'secondary_cause_documented=false' => self::pair(
                'no secondary cause is documented',
                'keine sekundäre Ursache dokumentiert ist',
            ),
            'status_epilepticus=false' => self::pair(
                'no status epilepticus is documented',
                'kein Status epilepticus dokumentiert ist',
            ),
            'status_migrainosus=false' => self::pair(
                'no status migrainosus is documented',
                'kein Status migrainosus dokumentiert ist',
            ),
            'chronic_ischemic_condition=atherosclerotic_heart_disease' => self::pair(
                'atherosclerotic heart disease is documented',
                'eine atherosklerotische Herzkrankheit dokumentiert ist',
            ),
            'laterality=bilateral' => self::pair(
                'both sides are affected',
                'beide Seiten betroffen sind',
            ),
            'documented_as_sequela=true' => self::pair(
                'the current deficit is documented as a sequela',
                'das aktuelle Defizit als Folgezustand dokumentiert ist',
            ),
            'time_since_event_years=2' => self::pair(
                'the cerebral infarction occurred two years ago',
                'der Hirninfarkt zwei Jahre zurückliegt',
            ),
            default => throw new SpecificationGapException(sprintf(
                'No learner-facing localization is defined for fact %s.',
                $identity,
            )),
        };
    }

    private static function normalizedValue(QuestionFact $fact): string
    {
        if (is_bool($fact->value)) {
            return $fact->value ? 'true' : 'false';
        }
        if (is_float($fact->value)) {
            return rtrim(rtrim(sprintf('%.6F', $fact->value), '0'), '.');
        }

        return (string) $fact->value;
    }

    /** @return array{en: string, de: string} */
    private static function pair(string $en, string $de): array
    {
        return ['en' => $en, 'de' => $de];
    }
}
