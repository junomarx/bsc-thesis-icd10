<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\GateResult;
use Icd10Prototype\Rules\RuleGate;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-GATE-01: eligibility and scope boundaries. */
final class RuleGateTest extends TestCase
{
    private function case001(): \Icd10Prototype\Model\CaseFacts
    {
        return Fixtures::copdCase('CASE-001', 'J44.0', 55.00, [
            'J44.0' => false, 'J44.00' => false, 'J44.01' => false,
            'J44.02' => true, 'J44.03' => false, 'J44.09' => false,
        ]);
    }

    public function testGateAEligible(): void
    {
        $result = RuleGate::evaluate($this->case001(), Fixtures::record('J44.02'), 'J44.02');

        self::assertTrue($result->eligible);
        self::assertNull($result->reason);
    }

    public function testGateBOutsideActiveSubset(): void
    {
        $result = RuleGate::evaluate($this->case001(), null, 'Z01.8');

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_OUTSIDE_ACTIVE_SUBSET, $result->reason);
    }

    public function testGateCUndefinedCaseRelation(): void
    {
        $result = RuleGate::evaluate($this->case001(), Fixtures::record('J44.12'), 'J44.12');

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_UNDEFINED_CASE_RELATION, $result->reason);
    }

    public function testGateDMissingRequiredFevOneFact(): void
    {
        $case = Fixtures::copdCase('GATE-D', 'J44.0', null, ['J44.02' => true]);
        $result = RuleGate::evaluate($case, Fixtures::record('J44.02'), 'J44.02');

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_MISSING_REQUIRED_CASE_FACT, $result->reason);
    }

    public function testGateEMissingRequiredLkfScoringFlag(): void
    {
        $case = Fixtures::statusCase('GATE-E', 'hospital_outpatient', null, ['Z01.6' => true]);
        $result = RuleGate::evaluate($case, Fixtures::record('Z01.6', '!'), 'Z01.6');

        self::assertFalse($result->eligible);
        self::assertSame(GateResult::REASON_MISSING_REQUIRED_CASE_FACT, $result->reason);
    }

    public function testAbsentOptionalFactDoesNotFailGate(): void
    {
        // CASE-003: ordinary hospital-outpatient, lkf_scored=false; no COPD facts at all.
        $case = Fixtures::statusCase('CASE-003', 'hospital_outpatient', false, ['Z01.6' => true]);
        $result = RuleGate::evaluate($case, Fixtures::record('Z01.6', '!'), 'Z01.6');

        self::assertTrue($result->eligible);
    }
}
