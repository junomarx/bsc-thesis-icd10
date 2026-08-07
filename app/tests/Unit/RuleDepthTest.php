<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleDepth;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-DEPTH-01: mandatory COPD coding depth. */
final class RuleDepthTest extends TestCase
{
    public function testDepthAInpatientFourCharacterMatches(): void
    {
        $case = Fixtures::copdCase('DEPTH-A', 'J44.0', 55.0, ['J44.0' => false]);

        self::assertTrue(RuleDepth::matches($case, 'J44.0'));
    }

    public function testDepthBInpatientFiveCharacterDoesNotMatch(): void
    {
        $case = Fixtures::copdCase('DEPTH-B', 'J44.0', 55.0, ['J44.02' => true]);

        self::assertFalse(RuleDepth::matches($case, 'J44.02'));
    }

    public function testDepthCHospitalOutpatientFourCharacterDoesNotMatchUnderInpatientOnlyRule(): void
    {
        $case = Fixtures::copdCase('DEPTH-C', 'J44.0', 55.0, ['J44.0' => false], encounterSetting: 'hospital_outpatient');

        self::assertFalse(RuleDepth::matches($case, 'J44.0'));
    }
}
