<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleNoa;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * RULE-NOA-01 (net new, RULEBASE-0.2 §4.3): `none_of_above` is `correct`
 * iff the displayed code set D(q) contains no `accepted_reference` code -
 * i.e. D(q) ∩ A(q) = ∅. A pure set-membership check over option/domain
 * membership, never itself an Austrian coding claim.
 */
final class RuleNoaTest extends TestCase
{
    public function testCorrectWhenAcceptedReferenceIsNotDisplayed(): void
    {
        // Mirrors Q-004-05/M54.5: accepted but never a displayed option.
        $question = Fixtures::question('NOA-A', [], [
            'M54.5' => Fixtures::acceptedReference('M54.5'),
            'M54.2' => Fixtures::sourceRuleResolved('M54.2'),
        ], [], [
            Fixtures::codeOption('NOA-A-O1', 'M54.2', 1),
            Fixtures::noneOfAboveOption('NOA-A-O2', 2),
        ]);

        self::assertTrue(RuleNoa::isCorrect($question));
        self::assertSame('M54.5', RuleNoa::acceptedReferenceCode($question));
        self::assertFalse(RuleNoa::isDisplayedCode($question, 'M54.5'));
    }

    public function testIncorrectWhenAcceptedReferenceIsDisplayed(): void
    {
        $question = Fixtures::question('NOA-B', [], [
            'I48.0' => Fixtures::acceptedReference('I48.0'),
        ], [], [
            Fixtures::codeOption('NOA-B-O1', 'I48.0', 1),
            Fixtures::noneOfAboveOption('NOA-B-O2', 2),
        ]);

        self::assertFalse(RuleNoa::isCorrect($question));
        self::assertTrue(RuleNoa::isDisplayedCode($question, 'I48.0'));
    }

    public function testCorrectWhenNoAcceptedReferenceExistsAtAll(): void
    {
        $question = Fixtures::question('NOA-C', [], [
            'X00.0' => Fixtures::sourceRuleResolved('X00.0'),
        ], [], [
            Fixtures::codeOption('NOA-C-O1', 'X00.0', 1),
            Fixtures::noneOfAboveOption('NOA-C-O2', 2),
        ]);

        self::assertTrue(RuleNoa::isCorrect($question));
        self::assertNull(RuleNoa::acceptedReferenceCode($question));
    }

    public function testDisplayedCodeCheckIgnoresNoneOfAboveOptions(): void
    {
        $question = Fixtures::question('NOA-D', [], [], [], [
            Fixtures::noneOfAboveOption('NOA-D-O1', 1),
        ]);

        self::assertFalse(RuleNoa::isDisplayedCode($question, 'anything'));
    }
}
