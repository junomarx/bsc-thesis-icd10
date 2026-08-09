<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;

/**
 * RULE-SPEC-01: known FEV1 left unspecified in a warning-listed main
 * diagnosis (PAT-SPEC-01). The source-specific `suboptimal` trigger.
 *
 * Source: SRC-AT-DOC-2026, printed p. 26 (`Unzureichend abgeklärte
 * Hauptdiagnose` warning for J44.09/J44.19/J44.89/J44.99) and p. 34 (FEV1
 * mapping). Only evaluated by the caller after every hard rule has cleared.
 * Unchanged predicate; facts now read from `question_fact` rather than
 * named `CaseFacts` properties.
 */
final class RuleSpec
{
    public const CRITERION = 'supported_specificity_not_used';
    private const WARNING_LISTED_UNSPECIFIED_FORMS = ['J44.09', 'J44.19', 'J44.89', 'J44.99'];

    public static function matches(CodingQuestion $question, string $submittedCode, MapResult $mapResult): bool
    {
        $encounterSetting = $question->facts->getEnum('encounter_setting');
        $diagnosisRole = $question->facts->getEnum('diagnosis_role');
        $copdBaseCode = $question->facts->getCode('copd_base_code');

        if ($encounterSetting !== 'inpatient' || $diagnosisRole !== 'main') {
            return false;
        }
        if ($copdBaseCode === null || !$mapResult->applicable) {
            return false;
        }
        if (!in_array($submittedCode, self::WARNING_LISTED_UNSPECIFIED_FORMS, true)) {
            return false;
        }

        return $submittedCode === $copdBaseCode . '9';
    }
}
