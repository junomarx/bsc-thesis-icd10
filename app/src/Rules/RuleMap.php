<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CaseFacts;

/**
 * RULE-MAP-01: stable-phase FEV1 -> COPD fifth-character suffix.
 *
 * Source: SRC-AT-DOC-2026, printed p. 34.
 * <35 -> 0; >=35 and <50 -> 1; >=50 and <70 -> 2; >=70 -> 3.
 *
 * Applicability: inpatient setting, a four-character COPD base J44.0-J44.9,
 * and an explicit stable-phase FEV1 value. It derives data; it never itself
 * returns a feedback class.
 */
final class RuleMap
{
    public static function evaluate(CaseFacts $case): MapResult
    {
        if ($case->encounterSetting !== 'inpatient') {
            return MapResult::notApplicable();
        }
        if ($case->copdBaseCode === null || !preg_match('/^J44\.[0-9]$/', $case->copdBaseCode)) {
            return MapResult::notApplicable();
        }
        if ($case->fev1StablePctPredicted === null) {
            return MapResult::notApplicable();
        }

        $fev1 = $case->fev1StablePctPredicted;
        $suffix = match (true) {
            $fev1 < 35.0 => 0,
            $fev1 < 50.0 => 1,
            $fev1 < 70.0 => 2,
            default => 3,
        };

        return MapResult::derived($suffix, $case->copdBaseCode . $suffix);
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
