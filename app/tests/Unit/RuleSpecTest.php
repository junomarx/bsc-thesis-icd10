<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Rules\RuleSpec;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-SPEC-01: source-backed specificity (the sole `suboptimal` trigger). */
final class RuleSpecTest extends TestCase
{
    public function testSpecACase001WithJ4409Matches(): void
    {
        $case = Fixtures::copdCase('CASE-001', 'J44.0', 55.0, ['J44.09' => false]);
        $map = RuleMap::evaluate($case);

        self::assertTrue(RuleSpec::matches($case, 'J44.09', $map));
    }

    public function testSpecBCase002WithJ4419Matches(): void
    {
        $case = Fixtures::copdCase('CASE-002', 'J44.1', 50.0, ['J44.19' => false]);
        $map = RuleMap::evaluate($case);

        self::assertTrue(RuleSpec::matches($case, 'J44.19', $map));
    }

    public function testSpecCSpecificResponseDoesNotMatch(): void
    {
        $case = Fixtures::copdCase('CASE-001', 'J44.0', 55.0, ['J44.02' => true]);
        $map = RuleMap::evaluate($case);

        self::assertFalse(RuleSpec::matches($case, 'J44.02', $map));
    }

    public function testSpecDMissingFev1NeverMatches(): void
    {
        $case = Fixtures::copdCase('SPEC-D', 'J44.0', null, ['J44.09' => false]);
        $map = RuleMap::evaluate($case);

        self::assertFalse(RuleSpec::matches($case, 'J44.09', $map));
    }

    public function testSpecEAdditionalDiagnosisRoleDoesNotMatch(): void
    {
        $case = Fixtures::copdCase('SPEC-E', 'J44.0', 55.0, ['J44.09' => false], diagnosisRole: 'additional');
        $map = RuleMap::evaluate($case);

        self::assertFalse(RuleSpec::matches($case, 'J44.09', $map));
    }
}
