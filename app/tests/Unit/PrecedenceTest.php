<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\Precedence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * TEST-PREC-01: precedence and terminal policy over synthetic rule-match
 * state. These vectors intentionally include combinations (e.g. all three
 * hard rules matching at once) that need not occur in SUBSET-0.1; this is a
 * controller test, not a claim that such a coding combination exists.
 */
final class PrecedenceTest extends TestCase
{
    public function testPrecAHardWinsOverSpecAndAccept(): void
    {
        self::assertSame('incorrect', Precedence::terminalClass(['RULE-EVID-01'], true, true));
        self::assertSame('RULE-EVID-01', Precedence::primaryHardRule(['RULE-EVID-01']));
    }

    public function testPrecBSpecWinsOverAcceptWhenNoHardMatch(): void
    {
        self::assertSame('suboptimal', Precedence::terminalClass([], true, true));
    }

    public function testPrecCAcceptOnlyWhenNoHardAndNoSpec(): void
    {
        self::assertSame('correct', Precedence::terminalClass([], false, true));
    }

    public function testPrecDNoTerminalRuleIsNotIncorrectByDefault(): void
    {
        self::assertNull(Precedence::terminalClass([], false, false));
    }

    #[DataProvider('orderingsOfAllThreeHardRules')]
    public function testPrecEStatusIsPrimaryRegardlessOfIterationOrder(array $hardMatches): void
    {
        self::assertSame('incorrect', Precedence::terminalClass($hardMatches, true, true));
        self::assertSame('RULE-STATUS-01', Precedence::primaryHardRule($hardMatches));
        self::assertCount(3, $hardMatches, 'all three hard matches must remain retained in the trace');
    }

    public static function orderingsOfAllThreeHardRules(): array
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
}
