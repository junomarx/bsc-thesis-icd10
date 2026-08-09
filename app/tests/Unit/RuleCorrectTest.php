<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Evaluation\Evaluator;
use Icd10Prototype\Rules\RuleCorrect;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-CORRECT-01: declared acceptance, reached only after gate/hard/graded rules clear. */
final class RuleCorrectTest extends TestCase
{
    public function testJ4402MatchesDirectly(): void
    {
        $question = Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.0, ['J44.02' => true]);

        self::assertTrue(RuleCorrect::matches($question, 'J44.02'));
    }

    public function testZ016MatchesDirectly(): void
    {
        $question = Fixtures::statusQuestion('Q-TEST-003', 'hospital_outpatient', false, ['Z01.6' => true]);

        self::assertTrue(RuleCorrect::matches($question, 'Z01.6'));
    }

    public function testNonAcceptedCodeDoesNotMatchMerelyBecauseNoOtherRuleClassifiedIt(): void
    {
        $question = Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.0, ['J44.02' => true, 'J44.03' => false]);

        self::assertFalse(RuleCorrect::matches($question, 'J44.03'));
    }

    public function testAcceptedCodeCannotOverrideASimultaneousHardMatchThroughTheEvaluator(): void
    {
        // Contrived fixture: J44.0 (four-character, hard DEPTH match) is also
        // marked acceptable. RULE-PREC-01 must still classify it `incorrect`.
        $question = Fixtures::copdQuestion('PREC-OVERRIDE', 'J44.0', 55.0, ['J44.0' => true]);
        $record = Fixtures::record('J44.0');

        $result = (new Evaluator())->evaluate($question, Fixtures::code('J44.0'), $record);

        self::assertSame('incorrect', $result->classification);
        self::assertSame('RULE-DEPTH-01', $result->determiningRule);
    }

    public function testAcceptedCodeCannotOverrideASimultaneousSpecMatchThroughTheEvaluator(): void
    {
        $question = Fixtures::copdQuestion('PREC-OVERRIDE-2', 'J44.0', 55.0, ['J44.09' => true]);
        $record = Fixtures::record('J44.09');

        $result = (new Evaluator())->evaluate($question, Fixtures::code('J44.09'), $record);

        self::assertSame('suboptimal', $result->classification);
        self::assertSame('RULE-SPEC-01', $result->determiningRule);
    }
}
