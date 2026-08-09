<?php

declare(strict_types=1);

namespace Icd10Prototype\Rules;

use Icd10Prototype\Model\CatalogueRecord;
use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\ResponseInput;

/**
 * RULE-GATE-01: bounded question-response eligibility.
 *
 * A failed gate returns a validation/scope reason, never `incorrect`
 * (REQ-RUL-05). `APIBASE-0.1` §4 fixes the boundary explicitly: malformed
 * request shape and an unrecognised response type are HTTP/controller
 * errors (400, before this method ever runs, see
 * `EvaluationController::parseResponse()`), not gate reasons - by the time
 * a `ResponseInput` reaches here it is always syntactically valid, so this
 * method only ever produces the four semantic/configuration reasons in
 * `APIBASE-0.1` §4.2.
 *
 * Branches on response kind first (RULEBASE-0.2 §6 pseudocode). For a code
 * response, membership is checked against the question's evaluation domain
 * (`question_code_domain`), never against displayed options
 * (`question_option`) - a code may be evaluable without being displayed
 * (REQ-MOD-06, e.g. `M54.5` for `Q-004-05`).
 */
final class RuleGate
{
    public static function evaluate(CodingQuestion $question, ResponseInput $response, ?CatalogueRecord $record): GateResult
    {
        if ($response->isNoneOfAbove()) {
            if (!$question->hasNoneOfAboveOption()) {
                return GateResult::fail(GateResult::REASON_NONE_OPTION_NOT_DEFINED);
            }

            return GateResult::eligible();
        }

        $submittedCode = (string) $response->code;

        if ($record === null) {
            return GateResult::fail(GateResult::REASON_OUTSIDE_ACTIVE_SUBSET);
        }

        if (!$question->hasDefinedRelation($submittedCode)) {
            return GateResult::fail(GateResult::REASON_UNDEFINED_CASE_RELATION);
        }

        $copdBaseCode = $question->facts->getCode('copd_base_code');
        $fev1 = $question->facts->getDecimal('fev1_stable_pct_predicted');
        if ($copdBaseCode !== null && $fev1 === null) {
            return GateResult::fail(GateResult::REASON_MISSING_REQUIRED_CASE_FACT);
        }

        $diagnosisRole = $question->facts->getEnum('diagnosis_role');
        $encounterSetting = $question->facts->getEnum('encounter_setting');
        $inpatientLkfScored = $question->facts->getBool('inpatient_lkf_scored');

        $statusRuleApplies = $record->marker === '!' && $diagnosisRole === 'main';
        if ($statusRuleApplies && $encounterSetting === 'hospital_outpatient' && $inpatientLkfScored === null) {
            return GateResult::fail(GateResult::REASON_MISSING_REQUIRED_CASE_FACT);
        }

        return GateResult::eligible();
    }
}
