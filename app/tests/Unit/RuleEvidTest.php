<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Model\CodingQuestion;
use Icd10Prototype\Rules\RuleEvid;
use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** TEST-EVID-01: explicit FEV1/code conflict, using a FEV1=55% fixture (expected suffix 2). */
final class RuleEvidTest extends TestCase
{
    private function question001(): CodingQuestion
    {
        return Fixtures::copdQuestion('Q-TEST-001', 'J44.0', 55.0, [
            'J44.0' => false, 'J44.00' => false, 'J44.01' => false,
            'J44.02' => true, 'J44.03' => false, 'J44.09' => false,
        ]);
    }

    #[DataProvider('mismatchingBands')]
    public function testMismatchingBandsMatch(string $code): void
    {
        $question = $this->question001();
        $map = RuleMap::evaluate($question);

        self::assertTrue(RuleEvid::matches($question, $code, $map));
    }

    public static function mismatchingBands(): array
    {
        return [['J44.00'], ['J44.01'], ['J44.03']];
    }

    public function testMatchingBandDoesNotTrigger(): void
    {
        $question = $this->question001();
        $map = RuleMap::evaluate($question);

        self::assertFalse(RuleEvid::matches($question, 'J44.02', $map));
    }

    public function testUnspecifiedSuffixIsNotAContradiction(): void
    {
        $question = $this->question001();
        $map = RuleMap::evaluate($question);

        self::assertFalse(RuleEvid::matches($question, 'J44.09', $map));
    }

    public function testExactFiftyPercentBoundaryConflictsWithSuffixOne(): void
    {
        // FEV1 exactly 50% maps to suffix 2; J44.11 (suffix 1, <50%) conflicts.
        $question = Fixtures::copdQuestion('Q-TEST-002', 'J44.1', 50.0, [
            'J44.1' => false, 'J44.10' => false, 'J44.11' => false,
            'J44.12' => true, 'J44.13' => false, 'J44.19' => false,
        ]);
        $map = RuleMap::evaluate($question);

        self::assertTrue(RuleEvid::matches($question, 'J44.11', $map));
        self::assertFalse(RuleEvid::matches($question, 'J44.12', $map));
    }
}
