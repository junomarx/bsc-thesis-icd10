<?php

declare(strict_types=1);

namespace Icd10Prototype\Evaluation;

use Icd10Prototype\Model\CatalogueRecord;
use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Model\QuestionCodeDomainRelation;
use Icd10Prototype\Model\ResponseInput;
use Icd10Prototype\Rules\MapResult;
use Icd10Prototype\Rules\Precedence;
use Icd10Prototype\Rules\RuleCorrect;
use Icd10Prototype\Rules\RuleDepth;
use Icd10Prototype\Rules\RuleEvid;
use Icd10Prototype\Rules\RuleGate;
use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Rules\RuleNoa;
use Icd10Prototype\Rules\RuleRelHard;
use Icd10Prototype\Rules\RuleRelSpec;
use Icd10Prototype\Rules\RuleSpec;
use Icd10Prototype\Rules\RuleStatus;

/**
 * RULE-PREC-01: deterministic precedence and terminal-result policy.
 *
 * Implements RULEBASE-0.2 Section 6 exactly:
 *   gate -> (none_of_above: RULE-NOA-01, terminal) | (code: map derivation ->
 *   hard rules (STATUS > DEPTH > EVID > REL_HARD) -> graded (SPEC >
 *   REL_SPEC) -> CORRECT -> specification gap).
 *
 * Pure/near-pure: it consumes only explicit question facts/domain relations
 * already resolved by the caller (REQ-MOD-01) and is testable without a
 * database or the React UI (REQ-ARC-01).
 */
final class Evaluator
{
    public function evaluate(CodingQuestion $question, ResponseInput $response, ?CatalogueRecord $record): EvaluationResult
    {
        $gate = RuleGate::evaluate($question, $response, $record);
        if (!$gate->eligible) {
            return EvaluationResult::notEvaluated($gate->reason);
        }

        if ($response->isNoneOfAbove()) {
            return $this->buildNoaResult($question);
        }

        $submittedCode = (string) $response->code;
        /** @var CatalogueRecord $record eligible implies a resolved record for a code response */
        $map = RuleMap::evaluate($question);
        $relation = $question->relationFor($submittedCode);

        $hardMatches = [];
        if (RuleStatus::matches($question, $record)) {
            $hardMatches[] = 'RULE-STATUS-01';
        }
        if (RuleDepth::matches($question, $submittedCode)) {
            $hardMatches[] = 'RULE-DEPTH-01';
        }
        if (RuleEvid::matches($question, $submittedCode, $map)) {
            $hardMatches[] = 'RULE-EVID-01';
        }
        if (RuleRelHard::matches($question, $submittedCode)) {
            $hardMatches[] = 'RULE-REL-HARD-01';
        }

        if ($hardMatches !== []) {
            return $this->buildHardResult($question, $record, $submittedCode, $map, $relation, $hardMatches);
        }

        $gradedMatches = [];
        if (RuleSpec::matches($question, $submittedCode, $map)) {
            $gradedMatches[] = 'RULE-SPEC-01';
        }
        if (RuleRelSpec::matches($question, $submittedCode)) {
            $gradedMatches[] = 'RULE-REL-SPEC-01';
        }

        if ($gradedMatches !== []) {
            return $this->buildGradedResult($question, $submittedCode, $map, $relation, $gradedMatches);
        }

        if (RuleCorrect::matches($question, $submittedCode)) {
            return EvaluationResult::classified(
                'correct',
                'RULE-CORRECT-01',
                RuleCorrect::CRITERION,
                sprintf('%s is supported by the documented information as an appropriate code for this question.', $submittedCode),
                sprintf('%s wird durch die dokumentierten Angaben als passende Kodierung unterstützt.', $submittedCode),
                ['accepted_code' => $submittedCode],
                ['RULE-CORRECT-01'],
                null,
            );
        }

        throw new SpecificationGapException(sprintf(
            'Question %s / code %s is eligible but reaches no terminal rule (RULE-PREC-01 specification gap).',
            $question->questionId,
            $submittedCode,
        ));
    }

    private function buildNoaResult(CodingQuestion $question): EvaluationResult
    {
        $accepted = RuleNoa::acceptedReferenceCode($question);
        $displayed = $accepted !== null && RuleNoa::isDisplayedCode($question, $accepted);
        $correct = RuleNoa::isCorrect($question);

        return EvaluationResult::classified(
            $correct ? 'correct' : 'incorrect',
            'RULE-NOA-01',
            $correct ? RuleNoa::CRITERION_NO_DISPLAYED_ACCEPTED : RuleNoa::CRITERION_DISPLAYED_ACCEPTED_EXISTS,
            $correct
                ? 'None of the displayed codes is supported by the documented information as an appropriate response. Therefore, “None of the above” is correct.'
                : 'The displayed codes include a response supported by the documented information. Therefore, “None of the above” is not correct here.',
            $correct
                ? 'Keiner der angezeigten Codes wird durch die dokumentierten Angaben als passende Kodierung unterstützt. Daher ist „Keine der genannten“ richtig.'
                : 'Unter den angezeigten Codes befindet sich eine durch die dokumentierten Angaben unterstützte Antwort. Daher ist „Keine der genannten“ hier nicht richtig.',
            [
                'displayed_accepted_response_exists' => $displayed,
                'reference_code' => $accepted,
            ],
            ['RULE-NOA-01'],
            null,
        );
    }

    /** @param list<string> $hardMatches */
    private function buildHardResult(
        CodingQuestion $question,
        CatalogueRecord $record,
        string $submittedCode,
        MapResult $map,
        ?QuestionCodeDomainRelation $relation,
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
                    (string) $question->facts->getEnum('diagnosis_role'),
                    str_replace('_', ' ', (string) $question->facts->getEnum('encounter_setting')),
                ),
                sprintf(
                    '%s trägt die Statuskennzeichnung „!“ und darf %s nicht als %sdiagnose verwendet werden.',
                    $submittedCode,
                    self::encounterSettingPhraseDe((string) $question->facts->getEnum('encounter_setting')),
                    self::diagnosisRoleDe((string) $question->facts->getEnum('diagnosis_role')),
                ),
                [
                    'submitted_code' => $submittedCode,
                    'marker' => $record->marker,
                    'diagnosis_role' => $question->facts->getEnum('diagnosis_role'),
                    'encounter_setting' => $question->facts->getEnum('encounter_setting'),
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
                sprintf(
                    '%s erreicht nicht die für diese Diagnosegruppe vorgeschriebene Kodiertiefe (%s).',
                    $submittedCode,
                    self::codingLevelDe(RuleDepth::REQUIRED_CODING_LEVEL),
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
                    self::formatDecimal($question->facts->getDecimal('fev1_stable_pct_predicted')),
                    $map->expectedSuffix,
                    RuleMap::suffixMeaning((int) $map->expectedSuffix),
                ),
                sprintf(
                    '%s widerspricht der angegebenen stabilen FEV1 von %s %%; die quellendefinierte Endziffer für diesen Wert ist %d (%s).',
                    $submittedCode,
                    self::formatDecimal($question->facts->getDecimal('fev1_stable_pct_predicted')),
                    $map->expectedSuffix,
                    self::suffixMeaningDe((int) $map->expectedSuffix),
                ),
                [
                    'submitted_code' => $submittedCode,
                    'fev1_stable_pct_predicted' => $question->facts->getDecimal('fev1_stable_pct_predicted'),
                    'submitted_suffix_meaning' => RuleMap::suffixMeaning((int) substr($submittedCode, -1)),
                    'expected_suffix' => $map->expectedSuffix,
                    'expected_code' => $map->expectedSpecificCode,
                ],
                $hardMatches,
                $map->expectedSpecificCode,
            ),
            'RULE-REL-HARD-01' => $this->buildRelHardResult($question, $submittedCode, $relation, $hardMatches),
            default => throw new SpecificationGapException('Hard match retained without a recognised primary rule.'),
        };
    }

    /** @param list<string> $hardMatches */
    private function buildRelHardResult(
        CodingQuestion $question,
        string $submittedCode,
        ?QuestionCodeDomainRelation $relation,
        array $hardMatches,
    ): EvaluationResult {
        /** @var QuestionCodeDomainRelation $relation a REL-HARD match implies a resolved relation */
        $criterion = RuleRelHard::criterionFor($relation);
        $fact = $this->localizedRelationFact($question, $submittedCode);
        $clauses = LocalizedFactFormatter::clauses($fact);

        $elementKey = $relation->relationKind === QuestionCodeDomainRelation::KIND_FACT_CONFLICT
            ? 'conflicting_fact'
            : 'temporal_fact';

        return EvaluationResult::classified(
            'incorrect',
            'RULE-REL-HARD-01',
            $criterion,
            sprintf('%s conflicts with the documented fact that %s.', $submittedCode, $clauses['en']),
            sprintf('%s widerspricht der dokumentierten Angabe, dass %s.', $submittedCode, $clauses['de']),
            [
                'submitted_code' => $submittedCode,
                'reason_key' => $relation->reasonKey,
                $elementKey => self::factElementValue($fact),
            ],
            $hardMatches,
            null,
        );
    }

    /** @param list<string> $gradedMatches */
    private function buildGradedResult(
        CodingQuestion $question,
        string $submittedCode,
        MapResult $map,
        ?QuestionCodeDomainRelation $relation,
        array $gradedMatches,
    ): EvaluationResult {
        $primary = Precedence::primaryGradedRule($gradedMatches);

        if ($primary === 'RULE-REL-SPEC-01') {
            /** @var QuestionCodeDomainRelation $relation a REL-SPEC match implies a resolved relation */
            $fact = $this->localizedRelationFact($question, $submittedCode);
            $clauses = LocalizedFactFormatter::clauses($fact);

            return EvaluationResult::classified(
                'suboptimal',
                'RULE-REL-SPEC-01',
                RuleRelSpec::CRITERION,
                sprintf(
                    '%s is supported; however, %s more precisely reflects that %s.',
                    $submittedCode,
                    (string) $relation->improvementCode,
                    $clauses['en'],
                ),
                sprintf(
                    '%s wird durch die Angaben unterstützt; %s bildet jedoch genauer ab, dass %s.',
                    $submittedCode,
                    (string) $relation->improvementCode,
                    $clauses['de'],
                ),
                [
                    'submitted_code' => $submittedCode,
                    'improvement_code' => $relation->improvementCode,
                    'supported_detail' => self::factElementValue($fact),
                ],
                $gradedMatches,
                $relation->improvementCode,
            );
        }

        return EvaluationResult::classified(
            'suboptimal',
            'RULE-SPEC-01',
            RuleSpec::CRITERION,
            sprintf(
                '%s leaves the FEV1 severity unspecified. The question already states a stable-phase FEV1 of %s%%, which supports the more specific code %s.',
                $submittedCode,
                self::formatDecimal($question->facts->getDecimal('fev1_stable_pct_predicted')),
                (string) $map->expectedSpecificCode,
            ),
            sprintf(
                '%s lässt den FEV1-Schweregrad unspezifiziert. Die Frage gibt für die stabile Phase bereits eine FEV1 von %s %% an, die den spezifischeren Code %s unterstützt.',
                $submittedCode,
                self::formatDecimal($question->facts->getDecimal('fev1_stable_pct_predicted')),
                (string) $map->expectedSpecificCode,
            ),
            [
                'submitted_code' => $submittedCode,
                'fev1_stable_pct_predicted' => $question->facts->getDecimal('fev1_stable_pct_predicted'),
                'expected_code' => $map->expectedSpecificCode,
                'improvement_direction' => sprintf('Use %s to reflect the documented FEV1 value.', (string) $map->expectedSpecificCode),
            ],
            $gradedMatches,
            $map->expectedSpecificCode,
        );
    }

    private static function formatDecimal(?float $value): string
    {
        if ($value === null) {
            return 'unspecified';
        }

        return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
    }

    private function localizedRelationFact(CodingQuestion $question, string $submittedCode): \Icd10Prototype\Model\QuestionFact
    {
        $relationFact = $question->relationFactsFor($submittedCode)[0] ?? null;
        if ($relationFact === null) {
            throw new SpecificationGapException(sprintf(
                'Question %s / code %s has no fact linked for learner feedback.',
                $question->questionId,
                $submittedCode,
            ));
        }

        $fact = $question->facts->get($relationFact->factKey);
        if ($fact === null) {
            throw new SpecificationGapException(sprintf(
                'Question %s / code %s links missing fact %s for learner feedback.',
                $question->questionId,
                $submittedCode,
                $relationFact->factKey,
            ));
        }

        return $fact;
    }

    private static function factElementValue(\Icd10Prototype\Model\QuestionFact $fact): string|int|float|bool
    {
        return $fact->value;
    }

    private static function diagnosisRoleDe(string $role): string
    {
        return match ($role) {
            'main' => 'Haupt',
            'additional' => 'Neben',
            default => throw new SpecificationGapException('No German diagnosis-role localization for ' . $role),
        };
    }

    private static function encounterSettingPhraseDe(string $setting): string
    {
        return match ($setting) {
            'inpatient' => 'bei einem stationären Aufenthalt',
            'hospital_outpatient' => 'bei einem ambulanten Spitalsbesuch im stationären LKF-Modell',
            default => throw new SpecificationGapException('No German encounter-setting localization for ' . $setting),
        };
    }

    private static function codingLevelDe(string $level): string
    {
        return match ($level) {
            'five-character' => 'fünfstellig',
            default => throw new SpecificationGapException('No German coding-level localization for ' . $level),
        };
    }

    private static function suffixMeaningDe(int $suffix): string
    {
        return match ($suffix) {
            0 => 'FEV1 < 35 % des Sollwertes',
            1 => 'FEV1 >= 35 % und < 50 % des Sollwertes',
            2 => 'FEV1 >= 50 % und < 70 % des Sollwertes',
            3 => 'FEV1 >= 70 % des Sollwertes',
            default => 'FEV1 nicht näher bezeichnet',
        };
    }
}
