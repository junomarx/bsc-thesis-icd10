<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CaseFacts;

/**
 * RULE-SPEC-01: known FEV1 left unspecified in a warning-listed main
 * diagnosis (PAT-SPEC-01). The sole `suboptimal` trigger in RULEBASE-0.1.
 *
 * Source: SRC-AT-DOC-2026, printed p. 26 (`Unzureichend abgeklärte
 * Hauptdiagnose` warning for J44.09/J44.19/J44.89/J44.99) and p. 34 (FEV1
 * mapping). Only evaluated by the caller after every hard rule has cleared.
 */
final class RuleSpec
{
    public const CRITERION = 'supported_specificity_not_used';
    private const WARNING_LISTED_UNSPECIFIED_FORMS = ['J44.09', 'J44.19', 'J44.89', 'J44.99'];

    public static function matches(CaseFacts $case, string $submittedCode, MapResult $mapResult): bool
    {
        if ($case->encounterSetting !== 'inpatient' || $case->diagnosisRole !== 'main') {
            return false;
        }
        if ($case->copdBaseCode === null || !$mapResult->applicable) {
            return false;
        }
        if (!in_array($submittedCode, self::WARNING_LISTED_UNSPECIFIED_FORMS, true)) {
            return false;
        }

        return $submittedCode === $case->copdBaseCode . '9';
    }
}
