<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

use Icd10Prototype\Model\CaseFacts;
use Icd10Prototype\Model\CatalogueRecord;
use Icd10Prototype\Rules\RuleCorrect;
use Icd10Prototype\Rules\RuleDepth;
use Icd10Prototype\Rules\RuleEvid;
use Icd10Prototype\Rules\RuleGate;
use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Rules\RuleSpec;
use Icd10Prototype\Rules\RuleStatus;
use Icd10Prototype\Rules\Precedence;

/**
 * RULE-PREC-01: deterministic precedence and terminal-result policy.
 *
 * Implements RULEBASE-0.1 Section 6 exactly:
 *   gate -> map derivation -> hard rules (STATUS > DEPTH > EVID) -> SPEC ->
 *   CORRECT -> specification gap.
 *
 * Pure/near-pure: it consumes only explicit case/catalogue facts already
 * resolved by the caller (REQ-MOD-01) and is testable without a database or
 * the React UI (REQ-ARC-01).
 */
final class Evaluator
{
    public function evaluate(CaseFacts $case, ?CatalogueRecord $record, string $submittedCode): EvaluationResult
    {
        $gate = RuleGate::evaluate($case, $record, $submittedCode);
        if (!$gate->eligible) {
            return EvaluationResult::notEvaluated($gate->reason);
        }

        /** @var CatalogueRecord $record eligible implies a resolved record */
        $map = RuleMap::evaluate($case);

        $hardMatches = [];
        if (RuleStatus::matches($case, $record)) {
            $hardMatches[] = 'RULE-STATUS-01';
        }
        if (RuleDepth::matches($case, $submittedCode)) {
            $hardMatches[] = 'RULE-DEPTH-01';
        }
        if (RuleEvid::matches($case, $submittedCode, $map)) {
            $hardMatches[] = 'RULE-EVID-01';
        }

        if ($hardMatches !== []) {
            return $this->buildHardResult($case, $record, $submittedCode, $map, $hardMatches);
        }

        if (RuleSpec::matches($case, $submittedCode, $map)) {
            return $this->buildSpecResult($case, $submittedCode, $map);
        }

        if (RuleCorrect::matches($case, $submittedCode)) {
            return EvaluationResult::classified(
                'correct',
                'RULE-CORRECT-01',
                RuleCorrect::CRITERION,
                sprintf('%s is a declared acceptable response for this case.', $submittedCode),
                ['accepted_code' => $submittedCode],
                ['RULE-CORRECT-01'],
                null,
            );
        }

        throw new SpecificationGapException(sprintf(
            'Case %s / code %s is eligible but reaches no terminal rule (RULE-PREC-01 specification gap).',
            $case->caseId,
            $submittedCode,
        ));
    }

    /** @param list<string> $hardMatches */
    private function buildHardResult(
        CaseFacts $case,
        CatalogueRecord $record,
        string $submittedCode,
        \Icd10Prototype\Rules\MapResult $map,
        array $hardMatches,
    ): EvaluationResult {
        $primary = Precedence::primaryHardRule($hardMatches);

        return match ($primary) {
            'RULE-STATUS-01' => EvaluationResult::classified(
                'incorrect',
                'RULE-STATUS-01',
                RuleStatus::CRITERION,
                sprintf(
                    '%s carries the "!" status marker and cannot be used as the %s diagnosis in this %s context.',
                    $submittedCode,
                    $case->diagnosisRole,
                    str_replace('_', ' ', $case->encounterSetting),
                ),
                [
                    'submitted_code' => $submittedCode,
                    'marker' => $record->marker,
                    'diagnosis_role' => $case->diagnosisRole,
                    'encounter_setting' => $case->encounterSetting,
                    'restriction' => '"!" codes may not be used as a main diagnosis for inpatient stays or for hospital-outpatient visits scored under the inpatient LKF model.',
                ],
                $hardMatches,
                null,
            ),
            'RULE-DEPTH-01' => EvaluationResult::classified(
                'incorrect',
                'RULE-DEPTH-01',
                RuleDepth::CRITERION,
                sprintf(
                    '%s does not meet the mandatory %s coding depth required for this diagnosis family.',
                    $submittedCode,
                    RuleDepth::REQUIRED_CODING_LEVEL,
                ),
                [
                    'submitted_code' => $submittedCode,
                    'required_coding_level' => RuleDepth::REQUIRED_CODING_LEVEL,
                    'mapped_target' => $map->expectedSpecificCode,
                ],
                $hardMatches,
                $map->expectedSpecificCode,
            ),
            'RULE-EVID-01' => EvaluationResult::classified(
                'incorrect',
                'RULE-EVID-01',
                RuleEvid::CRITERION,
                sprintf(
                    '%s conflicts with the represented stable-phase FEV1 of %s%%; the source-defined suffix for that value is %d (%s).',
                    $submittedCode,
                    self::formatFev1($case->fev1StablePctPredicted),
                    $map->expectedSuffix,
                    RuleMap::suffixMeaning($map->expectedSuffix),
                ),
                [
                    'submitted_code' => $submittedCode,
                    'fev1_stable_pct_predicted' => $case->fev1StablePctPredicted,
                    'submitted_suffix_meaning' => RuleMap::suffixMeaning((int) substr($submittedCode, -1)),
                    'expected_suffix' => $map->expectedSuffix,
                    'expected_code' => $map->expectedSpecificCode,
                ],
                $hardMatches,
                $map->expectedSpecificCode,
            ),
            default => throw new SpecificationGapException('Hard match retained without a recognised primary rule.'),
        };
    }

    private function buildSpecResult(CaseFacts $case, string $submittedCode, \Icd10Prototype\Rules\MapResult $map): EvaluationResult
    {
        return EvaluationResult::classified(
            'suboptimal',
            'RULE-SPEC-01',
            RuleSpec::CRITERION,
            sprintf(
                '%s leaves the FEV1 severity unspecified. The case already states a stable-phase FEV1 of %s%%, which supports the more specific code %s.',
                $submittedCode,
                self::formatFev1($case->fev1StablePctPredicted),
                $map->expectedSpecificCode,
            ),
            [
                'submitted_code' => $submittedCode,
                'fev1_stable_pct_predicted' => $case->fev1StablePctPredicted,
                'expected_code' => $map->expectedSpecificCode,
                'improvement_direction' => sprintf('Use %s to reflect the documented FEV1 value.', $map->expectedSpecificCode),
            ],
            ['RULE-SPEC-01'],
            $map->expectedSpecificCode,
        );
    }

    private static function formatFev1(?float $fev1): string
    {
        if ($fev1 === null) {
            return 'unspecified';
        }

        return rtrim(rtrim(sprintf('%.2f', $fev1), '0'), '.');
    }
}
