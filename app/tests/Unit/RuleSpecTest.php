<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Rules\RuleSpec;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-SPEC-01: source-backed specificity (the source-specific `suboptimal` trigger). */
final class RuleSpecTest extends TestCase
{
    public function testSpecAJ4409Matches(): void
    {
        $question = Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.0, ['J44.09' => false]);
        $map = RuleMap::evaluate($question);

        self::assertTrue(RuleSpec::matches($question, 'J44.09', $map));
    }

    public function testSpecBJ4419Matches(): void
    {
        $question = Fixtures::copdQuestion('Q-TEST-002', 'J44.1', 50.0, ['J44.19' => false]);
        $map = RuleMap::evaluate($question);

        self::assertTrue(RuleSpec::matches($question, 'J44.19', $map));
    }

    public function testSpecCSpecificResponseDoesNotMatch(): void
    {
        $question = Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.0, ['J44.02' => true]);
        $map = RuleMap::evaluate($question);

        self::assertFalse(RuleSpec::matches($question, 'J44.02', $map));
    }

    public function testSpecDMissingFev1NeverMatches(): void
    {
        $question = Fixtures::copdQuestion('SPEC-D', 'J44.0', null, ['J44.09' => false]);
        $map = RuleMap::evaluate($question);

        self::assertFalse(RuleSpec::matches($question, 'J44.09', $map));
    }

    public function testSpecEAdditionalDiagnosisRoleDoesNotMatch(): void
    {
        $question = Fixtures::copdQuestion('SPEC-E', 'J44.0', 55.0, ['J44.09' => false], diagnosisRole: 'additional');
        $map = RuleMap::evaluate($question);

        self::assertFalse(RuleSpec::matches($question, 'J44.09', $map));
    }
}
