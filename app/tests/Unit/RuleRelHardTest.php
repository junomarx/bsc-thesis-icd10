<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleRelHard;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * RULE-REL-HARD-01 (net new, RULEBASE-0.2 §4.1): explicit, source-audited
 * hard incompatibility - never a bare `submitted != accepted` heuristic.
 */
final class RuleRelHardTest extends TestCase
{
    public function testFactConflictRelationMatches(): void
    {
        $question = Fixtures::question('RELHARD-A', [], [
            'X99.0' => Fixtures::factConflict('X99.0', 'wrong_condition_state'),
        ]);

        self::assertTrue(RuleRelHard::matches($question, 'X99.0'));
    }

    public function testTemporalContextConflictRelationMatches(): void
    {
        $question = Fixtures::question('RELHARD-B', [], [
            'X99.1' => Fixtures::temporalContextConflict('X99.1', 'wrong_temporal_context'),
        ]);

        self::assertTrue(RuleRelHard::matches($question, 'X99.1'));
    }

    public function testAcceptedReferenceDoesNotMatch(): void
    {
        $question = Fixtures::question('RELHARD-C', [], [
            'X99.2' => Fixtures::acceptedReference('X99.2'),
        ]);

        self::assertFalse(RuleRelHard::matches($question, 'X99.2'));
    }

    public function testLessSpecificSupportedDoesNotMatch(): void
    {
        $question = Fixtures::question('RELHARD-D', [], [
            'X99.3' => Fixtures::lessSpecificSupported('X99.3', 'X99.9'),
            'X99.9' => Fixtures::acceptedReference('X99.9'),
        ]);

        self::assertFalse(RuleRelHard::matches($question, 'X99.3'));
    }

    public function testSourceRuleResolvedDoesNotMatch(): void
    {
        // A defined-but-not-generically-terminal relation (e.g. a COPD code
        // handled by RULE-DEPTH-01/EVID-01/SPEC-01) must never surface here.
        $question = Fixtures::question('RELHARD-E', [], [
            'X99.4' => Fixtures::sourceRuleResolved('X99.4'),
        ]);

        self::assertFalse(RuleRelHard::matches($question, 'X99.4'));
    }

    public function testUndefinedRelationDoesNotMatch(): void
    {
        $question = Fixtures::question('RELHARD-F');

        self::assertFalse(RuleRelHard::matches($question, 'X99.5'));
    }

    public function testCriterionForDistinguishesFactConflictFromTemporalContextConflict(): void
    {
        $question = Fixtures::question('RELHARD-G', [], [
            'X99.6' => Fixtures::factConflict('X99.6'),
            'X99.7' => Fixtures::temporalContextConflict('X99.7'),
        ]);

        self::assertSame(
            RuleRelHard::CRITERION_FACT_CONFLICT,
            RuleRelHard::criterionFor($question->relationFor('X99.6')),
        );
        self::assertSame(
            RuleRelHard::CRITERION_TEMPORAL_CONTEXT_CONFLICT,
            RuleRelHard::criterionFor($question->relationFor('X99.7')),
        );
    }
}
