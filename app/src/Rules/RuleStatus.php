<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CaseFacts;
use Icd10Prototype\Model\CatalogueRecord;

/**
 * RULE-STATUS-01: prohibited `!` main-diagnosis use (PAT-STATUS-01).
 *
 * Source: SRC-AT-DOC-2026, printed pp. 10-11, 18. `!` is context-dependent
 * (p. 22 permits it in ordinary hospital-outpatient use), so a non-match
 * here does not by itself prove the response is otherwise correct.
 */
final class RuleStatus
{
    public const CRITERION = 'context_status_incompatibility';

    public static function matches(CaseFacts $case, CatalogueRecord $record): bool
    {
        if ($record->marker !== '!' || $case->diagnosisRole !== 'main') {
            return false;
        }

        if ($case->encounterSetting === 'inpatient') {
            return true;
        }

        return $case->encounterSetting === 'hospital_outpatient' && $case->inpatientLkfScored === true;
    }
}
