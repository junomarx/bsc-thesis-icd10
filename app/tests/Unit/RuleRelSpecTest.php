<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleRelSpec;
use Icd10Prototype\Rules\RuleSpec;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * RULE-REL-SPEC-01 (net new, RULEBASE-0.2 §4.2): the generic counterpart to
 * RULE-SPEC-01 - an explicit `less_specific_supported` relation, never a
 * code-shape/`.9`-suffix/designation-wording heuristic.
 */
final class RuleRelSpecTest extends TestCase
{
    public function testLessSpecificSupportedRelationMatches(): void
    {
        $question = Fixtures::question('RELSPEC-A', [], [
            'X99.9' => Fixtures::lessSpecificSupported('X99.9', 'X99.1'),
            'X99.1' => Fixtures::acceptedReference('X99.1'),
        ]);

        self::assertTrue(RuleRelSpec::matches($question, 'X99.9'));
    }

    public function testAcceptedReferenceDoesNotMatch(): void
    {
        $question = Fixtures::question('RELSPEC-B', [], [
            'X99.2' => Fixtures::acceptedReference('X99.2'),
        ]);

        self::assertFalse(RuleRelSpec::matches($question, 'X99.2'));
    }

    public function testFactConflictDoesNotMatch(): void
    {
        $question = Fixtures::question('RELSPEC-C', [], [
            'X99.3' => Fixtures::factConflict('X99.3'),
        ]);

        self::assertFalse(RuleRelSpec::matches($question, 'X99.3'));
    }

    public function testUndefinedRelationDoesNotMatch(): void
    {
        $question = Fixtures::question('RELSPEC-D');

        self::assertFalse(RuleRelSpec::matches($question, 'X99.4'));
    }

    public function testSharesCriterionStringWithSourceSpecificSpec(): void
    {
        // Deliberate: both rules represent "the same feedback class for the
        // same underlying reason" - callers must key off determining_rule,
        // never reverse-engineer which rule fired from the criterion alone.
        self::assertSame(RuleSpec::CRITERION, RuleRelSpec::CRITERION);
    }

    public function testNotTriggeredByDotNineSuffixAlone(): void
    {
        // REQ-RUL-02/07: a `.9` suffix or code shape must never independently
        // determine suboptimal - only an explicit relation_kind may.
        $question = Fixtures::question('RELSPEC-E', [], [
            'X99.9' => Fixtures::acceptedReference('X99.9'),
        ]);

        self::assertFalse(RuleRelSpec::matches($question, 'X99.9'));
    }
}
