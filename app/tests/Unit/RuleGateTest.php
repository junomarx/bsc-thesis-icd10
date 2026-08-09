<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Rules\GateResult;
use Icd10Prototype\Rules\RuleGate;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-GATE-01: eligibility and scope boundaries. */
final class RuleGateTest extends TestCase
{
    private function question001(): CodingQuestion
    {
        return Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.00, [
            'J44.0' => false, 'J44.00' => false, 'J44.01' => false,
            'J44.02' => true, 'J44.03' => false, 'J44.09' => false,
        ]);
    }

    public function testGateAEligible(): void
    {
        $result = RuleGate::evaluate($this->question001(), Fixtures::code('J44.02'), Fixtures::record('J44.02'));

        self::assertTrue($result->eligible);
        self::assertNull($result->reason);
    }

    public function testGateBOutsideActiveSubset(): void
    {
        $result = RuleGate::evaluate($this->question001(), Fixtures::code('Z01.8'), null);

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_OUTSIDE_ACTIVE_SUBSET, $result->reason);
    }

    public function testGateCUndefinedCaseRelation(): void
    {
        $result = RuleGate::evaluate($this->question001(), Fixtures::code('J44.12'), Fixtures::record('J44.12'));

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_UNDEFINED_CASE_RELATION, $result->reason);
    }

    public function testGateDMissingRequiredFevOneFact(): void
    {
        $question = Fixtures::copdQuestion('GATE-D', 'J44.0', null, ['J44.02' => true]);
        $result = RuleGate::evaluate($question, Fixtures::code('J44.02'), Fixtures::record('J44.02'));

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_MISSING_REQUIRED_CASE_FACT, $result->reason);
    }

    public function testGateEMissingRequiredLkfScoringFlag(): void
    {
        $question = Fixtures::statusQuestion('GATE-E', 'hospital_outpatient', null, ['Z01.6' => true]);
        $result = RuleGate::evaluate($question, Fixtures::code('Z01.6'), Fixtures::record('Z01.6', '!'));

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_MISSING_REQUIRED_CASE_FACT, $result->reason);
    }

    public function testAbsentOptionalFactDoesNotFailGate(): void
    {
        // Ordinary hospital-outpatient, lkf_scored=false; no COPD facts at all.
        $question = Fixtures::statusQuestion('GATE-F', 'hospital_outpatient', false, ['Z01.6' => true]);
        $result = RuleGate::evaluate($question, Fixtures::code('Z01.6'), Fixtures::record('Z01.6', '!'));

        self::assertTrue($result->eligible);
    }

    public function testNoneOfAboveIsEligibleWhenOptionIsDefined(): void
    {
        $question = Fixtures::question('GATE-NOA-A', [], ['Z00.0' => Fixtures::acceptedReference('Z00.0')], [], [
            Fixtures::noneOfAboveOption('GATE-NOA-A-O1', 1),
        ]);

        $result = RuleGate::evaluate($question, Fixtures::noneOfAbove(), null);

        self::assertTrue($result->eligible);
    }

    public function testNoneOfAboveIsNotEligibleWhenOptionIsNotDefined(): void
    {
        $question = Fixtures::question('GATE-NOA-B', [], ['Z00.0' => Fixtures::acceptedReference('Z00.0')], [], [
            Fixtures::codeOption('GATE-NOA-B-O1', 'Z00.0', 1),
        ]);

        $result = RuleGate::evaluate($question, Fixtures::noneOfAbove(), null);

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_NONE_OPTION_NOT_DEFINED, $result->reason);
    }
}
