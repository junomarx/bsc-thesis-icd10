<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CaseFacts;
use Icd10Prototype\Model\CatalogueRecord;

/**
 * RULE-GATE-01: bounded evaluation eligibility.
 *
 * A failed gate returns a validation/scope reason, never `incorrect`
 * (REQ-RUL-05). Malformed-input rejection happens one layer up, at the HTTP
 * request boundary, before a case/code lookup is even attempted.
 */
final class RuleGate
{
    public static function evaluate(CaseFacts $case, ?CatalogueRecord $record, string $submittedCode): GateResult
    {
        if ($record === null) {
            return GateResult::fail(GateResult::REASON_OUTSIDE_ACTIVE_SUBSET);
        }

        if (!$case->hasDefinedRelation($submittedCode)) {
            return GateResult::fail(GateResult::REASON_UNDEFINED_CASE_RELATION);
        }

        if ($case->copdBaseCode !== null && $case->fev1StablePctPredicted === null) {
            return GateResult::fail(GateResult::REASON_MISSING_REQUIRED_CASE_FACT);
        }

        $statusRuleApplies = $record->marker === '!' && $case->diagnosisRole === 'main';
        if ($statusRuleApplies && $case->encounterSetting === 'hospital_outpatient' && $case->inpatientLkfScored === null) {
            return GateResult::fail(GateResult::REASON_MISSING_REQUIRED_CASE_FACT);
        }

        return GateResult::eligible();
    }
}
