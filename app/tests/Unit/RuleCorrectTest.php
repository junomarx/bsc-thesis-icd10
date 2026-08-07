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
    public function testCase001WithJ4402MatchesDirectly(): void
    {
        $case = Fixtures::copdCase('CASE-001', 'J44.0', 55.0, ['J44.02' => true]);

        self::assertTrue(RuleCorrect::matches($case, 'J44.02'));
    }

    public function testCase003WithZ016MatchesDirectly(): void
    {
        $case = Fixtures::statusCase('CASE-003', 'hospital_outpatient', false, ['Z01.6' => true]);

        self::assertTrue(RuleCorrect::matches($case, 'Z01.6'));
    }

    public function testNonAcceptedCodeDoesNotMatchMerelyBecauseNoOtherRuleClassifiedIt(): void
    {
        $case = Fixtures::copdCase('CASE-001', 'J44.0', 55.0, ['J44.02' => true, 'J44.03' => false]);

        self::assertFalse(RuleCorrect::matches($case, 'J44.03'));
    }

    public function testAcceptedCodeCannotOverrideASimultaneousHardMatchThroughTheEvaluator(): void
    {
        // Contrived fixture: J44.0 (four-character, hard DEPTH match) is also
        // marked acceptable. RULE-PREC-01 must still classify it `incorrect`.
        $case = Fixtures::copdCase('PREC-OVERRIDE', 'J44.0', 55.0, ['J44.0' => true]);
        $record = Fixtures::record('J44.0');

        $result = (new Evaluator())->evaluate($case, $record, 'J44.0');

        self::assertSame('incorrect', $result->classification);
        self::assertSame('RULE-DEPTH-01', $result->determiningRule);
    }

    public function testAcceptedCodeCannotOverrideASimultaneousSpecMatchThroughTheEvaluator(): void
    {
        $case = Fixtures::copdCase('PREC-OVERRIDE-2', 'J44.0', 55.0, ['J44.09' => true]);
        $record = Fixtures::record('J44.09');

        $result = (new Evaluator())->evaluate($case, $record, 'J44.09');

        self::assertSame('suboptimal', $result->classification);
        self::assertSame('RULE-SPEC-01', $result->determiningRule);
    }
}
