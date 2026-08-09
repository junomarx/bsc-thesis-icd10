<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CodingQuestion;

/**
 * RULE-MAP-01: stable-phase FEV1 -> COPD fifth-character suffix.
 *
 * Source: SRC-AT-DOC-2026, printed p. 34.
 * <35 -> 0; >=35 and <50 -> 1; >=50 and <70 -> 2; >=70 -> 3.
 *
 * Applicability: inpatient setting, a four-character COPD base J44.0-J44.9,
 * and an explicit stable-phase FEV1 value - all read as `question_fact`
 * rows now rather than named `CaseFacts` properties. Unchanged predicate.
 * It derives data; it never itself returns a feedback class. Applicable to
 * only one of the 25 forward learner questions (`Q-001-01`) plus the
 * COPD-related legacy verification fixtures.
 */
final class RuleMap
{
    public static function evaluate(CodingQuestion $question): MapResult
    {
        $encounterSetting = $question->facts->getEnum('encounter_setting');
        $copdBaseCode = $question->facts->getCode('copd_base_code');
        $fev1 = $question->facts->getDecimal('fev1_stable_pct_predicted');

        if ($encounterSetting !== 'inpatient') {
            return MapResult::notApplicable();
        }
        if ($copdBaseCode === null || !preg_match('/^J44\.[0-9]$/', $copdBaseCode)) {
            return MapResult::notApplicable();
        }
        if ($fev1 === null) {
            return MapResult::notApplicable();
        }

        $suffix = match (true) {
            $fev1 < 35.0 => 0,
            $fev1 < 50.0 => 1,
            $fev1 < 70.0 => 2,
            default => 3,
        };

        return MapResult::derived($suffix, $copdBaseCode . $suffix);
    }

    public static function suffixMeaning(int $suffix): string
    {
        return match ($suffix) {
            0 => 'FEV1 < 35% of predicted',
            1 => 'FEV1 >= 35% and < 50% of predicted',
            2 => 'FEV1 >= 50% and < 70% of predicted',
            3 => 'FEV1 >= 70% of predicted',
            default => 'FEV1 not further specified',
        };
    }
}
