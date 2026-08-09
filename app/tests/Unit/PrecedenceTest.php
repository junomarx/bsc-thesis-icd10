<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\Precedence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * TEST-PREC-01: precedence and terminal policy over synthetic rule-match
 * state. These vectors intentionally include combinations (e.g. all three
 * source-specific hard rules matching at once, or both graded rules
 * matching at once) that need not occur in SUBSET-0.2; this is a controller
 * test, not a claim that such a coding combination exists. Extended for
 * RULEBASE-0.2: `terminalClass()`'s second argument is now the list of
 * graded matches (was a single `$specMatches` bool), and hard priority
 * gained a fourth slot (`RULE-REL-HARD-01`).
 */
final class PrecedenceTest extends TestCase
{
    public function testPrecAHardWinsOverSpecAndAccept(): void
    {
        self::assertSame('incorrect', Precedence::terminalClass(['RULE-EVID-01'], ['RULE-SPEC-01'], true));
        self::assertSame('RULE-EVID-01', Precedence::primaryHardRule(['RULE-EVID-01']));
    }

    public function testPrecBSpecWinsOverAcceptWhenNoHardMatch(): void
    {
        self::assertSame('suboptimal', Precedence::terminalClass([], ['RULE-SPEC-01'], true));
    }

    public function testPrecCAcceptOnlyWhenNoHardAndNoSpec(): void
    {
        self::assertSame('correct', Precedence::terminalClass([], [], true));
    }

    public function testPrecDNoTerminalRuleIsNotIncorrectByDefault(): void
    {
        self::assertNull(Precedence::terminalClass([], [], false));
    }

    #[DataProvider('orderingsOfAllThreeSourceSpecificHardRules')]
    public function testPrecEStatusIsPrimaryRegardlessOfIterationOrder(array $hardMatches): void
    {
        self::assertSame('incorrect', Precedence::terminalClass($hardMatches, ['RULE-SPEC-01'], true));
        self::assertSame('RULE-STATUS-01', Precedence::primaryHardRule($hardMatches));
        self::assertCount(3, $hardMatches, 'all three hard matches must remain retained in the trace');
    }

    public static function orderingsOfAllThreeSourceSpecificHardRules(): array
    {
        return [
            [['RULE-STATUS-01', 'RULE-DEPTH-01', 'RULE-EVID-01']],
            [['RULE-EVID-01', 'RULE-DEPTH-01', 'RULE-STATUS-01']],
            [['RULE-DEPTH-01', 'RULE-EVID-01', 'RULE-STATUS-01']],
        ];
    }

    #[DataProvider('orderingsOfDepthAndEvid')]
    public function testPrecFDepthIsPrimaryOverEvidRegardlessOfIterationOrder(array $hardMatches): void
    {
        self::assertSame('RULE-DEPTH-01', Precedence::primaryHardRule($hardMatches));
        self::assertCount(2, $hardMatches);
    }

    public static function orderingsOfDepthAndEvid(): array
    {
        return [
            [['RULE-DEPTH-01', 'RULE-EVID-01']],
            [['RULE-EVID-01', 'RULE-DEPTH-01']],
        ];
    }

    public function testPrecGRelHardAloneIsHardPrimary(): void
    {
        self::assertSame('incorrect', Precedence::terminalClass(['RULE-REL-HARD-01'], [], true));
        self::assertSame('RULE-REL-HARD-01', Precedence::primaryHardRule(['RULE-REL-HARD-01']));
    }

    #[DataProvider('orderingsOfRelHardAndSourceSpecific')]
    public function testPrecHRelHardYieldsToEverySourceSpecificHardRule(array $hardMatches, string $expectedPrimary): void
    {
        self::assertSame($expectedPrimary, Precedence::primaryHardRule($hardMatches));
    }

    public static function orderingsOfRelHardAndSourceSpecific(): array
    {
        return [
            [['RULE-REL-HARD-01', 'RULE-STATUS-01'], 'RULE-STATUS-01'],
            [['RULE-STATUS-01', 'RULE-REL-HARD-01'], 'RULE-STATUS-01'],
            [['RULE-REL-HARD-01', 'RULE-DEPTH-01'], 'RULE-DEPTH-01'],
            [['RULE-REL-HARD-01', 'RULE-EVID-01'], 'RULE-EVID-01'],
        ];
    }

    public function testPrecIRelSpecAloneIsGradedPrimary(): void
    {
        self::assertSame('suboptimal', Precedence::terminalClass([], ['RULE-REL-SPEC-01'], true));
        self::assertSame('RULE-REL-SPEC-01', Precedence::primaryGradedRule(['RULE-REL-SPEC-01']));
    }

    #[DataProvider('orderingsOfSpecAndRelSpec')]
    public function testPrecJSpecIsPrimaryOverRelSpecRegardlessOfIterationOrder(array $gradedMatches): void
    {
        self::assertSame('RULE-SPEC-01', Precedence::primaryGradedRule($gradedMatches));
    }

    public static function orderingsOfSpecAndRelSpec(): array
    {
        return [
            [['RULE-SPEC-01', 'RULE-REL-SPEC-01']],
            [['RULE-REL-SPEC-01', 'RULE-SPEC-01']],
        ];
    }

    public function testPrecKNoGradedMatchHasNoPrimary(): void
    {
        self::assertNull(Precedence::primaryGradedRule([]));
    }
}
