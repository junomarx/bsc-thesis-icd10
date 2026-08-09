<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CatalogueRecord;
use Icd10Prototype\Model\CodingQuestion;

/**
 * RULE-STATUS-01: prohibited `!` main-diagnosis use (PAT-STATUS-01).
 *
 * Source: SRC-AT-DOC-2026, printed pp. 10-11, 18. `!` is context-dependent
 * (p. 22 permits it in ordinary hospital-outpatient use), so a non-match
 * here does not by itself prove the response is otherwise correct.
 * Unchanged predicate; facts now read from `question_fact` rather than
 * named `CaseFacts` properties.
 */
final class RuleStatus
{
    public const CRITERION = 'context_status_incompatibility';

    public static function matches(CodingQuestion $question, CatalogueRecord $record): bool
    {
        $diagnosisRole = $question->facts->getEnum('diagnosis_role');
        $encounterSetting = $question->facts->getEnum('encounter_setting');

        if ($record->marker !== '!' || $diagnosisRole !== 'main') {
            return false;
        }

        if ($encounterSetting === 'inpatient') {
            return true;
        }

        return $encounterSetting === 'hospital_outpatient' && $question->facts->getBool('inpatient_lkf_scored') === true;
    }
}
