<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleEvid;
use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** TEST-EVID-01: explicit FEV1/code conflict, using CASE-001 (FEV1 55%, expected suffix 2). */
final class RuleEvidTest extends TestCase
{
    private function case001(): \Icd10Prototype\Model\CaseFacts
    {
        return Fixtures::copdCase('CASE-001', 'J44.0', 55.0, [
            'J44.0' => false, 'J44.00' => false, 'J44.01' => false,
            'J44.02' => true, 'J44.03' => false, 'J44.09' => false,
        ]);
    }

    #[DataProvider('mismatchingBands')]
    public function testMismatchingBandsMatch(string $code): void
    {
        $case = $this->case001();
        $map = RuleMap::evaluate($case);

        self::assertTrue(RuleEvid::matches($case, $code, $map));
    }

    public static function mismatchingBands(): array
    {
        return [['J44.00'], ['J44.01'], ['J44.03']];
    }

    public function testMatchingBandDoesNotTrigger(): void
    {
        $case = $this->case001();
        $map = RuleMap::evaluate($case);

        self::assertFalse(RuleEvid::matches($case, 'J44.02', $map));
    }

    public function testUnspecifiedSuffixIsNotAContradiction(): void
    {
        $case = $this->case001();
        $map = RuleMap::evaluate($case);

        self::assertFalse(RuleEvid::matches($case, 'J44.09', $map));
    }

    public function testExactFiftyPercentBoundaryConflictsWithSuffixOne(): void
    {
        // CASE-002: FEV1 exactly 50% maps to suffix 2; J44.11 (suffix 1, <50%) conflicts.
        $case = Fixtures::copdCase('CASE-002', 'J44.1', 50.0, [
            'J44.1' => false, 'J44.10' => false, 'J44.11' => false,
            'J44.12' => true, 'J44.13' => false, 'J44.19' => false,
        ]);
        $map = RuleMap::evaluate($case);

        self::assertTrue(RuleEvid::matches($case, 'J44.11', $map));
        self::assertFalse(RuleEvid::matches($case, 'J44.12', $map));
    }
}
