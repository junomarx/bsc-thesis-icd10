<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleMap;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** TEST-MAP-01: FEV1 mapping and applicability, including exact boundaries. */
final class RuleMapTest extends TestCase
{
    #[DataProvider('boundaryVectors')]
    public function testBoundaryVectors(float $fev1, int $expectedSuffix, string $expectedTarget): void
    {
        $question = Fixtures::copdQuestion('MAP-TEST', 'J44.0', $fev1, ['J44.0' => false]);
        $result = RuleMap::evaluate($question);

        self::assertTrue($result->applicable);
        self::assertSame($expectedSuffix, $result->expectedSuffix);
        self::assertSame($expectedTarget, $result->expectedSpecificCode);
    }

    public static function boundaryVectors(): array
    {
        return [
            'MAP-A 34.99' => [34.99, 0, 'J44.00'],
            'MAP-B 35.00' => [35.00, 1, 'J44.01'],
            'MAP-C 49.99' => [49.99, 1, 'J44.01'],
            'MAP-D 50.00' => [50.00, 2, 'J44.02'],
            'MAP-E 69.99' => [69.99, 2, 'J44.02'],
            'MAP-F 70.00' => [70.00, 3, 'J44.03'],
            'Q-001-01 55.00' => [55.00, 2, 'J44.02'],
        ];
    }

    public function testUsesItsOwnBase(): void
    {
        $question = Fixtures::copdQuestion('MAP-OWN-BASE', 'J44.1', 50.00, ['J44.1' => false]);
        $result = RuleMap::evaluate($question);

        self::assertSame(2, $result->expectedSuffix);
        self::assertSame('J44.12', $result->expectedSpecificCode);
    }

    public function testAbsentFev1ProducesNoDerivedSuffix(): void
    {
        $question = Fixtures::copdQuestion('MAP-TEST', 'J44.0', null, ['J44.0' => false]);

        self::assertFalse(RuleMap::evaluate($question)->applicable);
    }

    public function testHospitalOutpatientContextDoesNotActivateThisRule(): void
    {
        $question = Fixtures::copdQuestion('MAP-TEST', 'J44.0', 55.0, ['J44.0' => false], encounterSetting: 'hospital_outpatient');

        self::assertFalse(RuleMap::evaluate($question)->applicable);
    }
}
