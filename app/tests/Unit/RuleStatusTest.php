<?php

declare(strict_types=1);

namespace Icd10Prototype\Tests\Unit;

use Icd10Prototype\Rules\RuleStatus;
use Icd10Prototype\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

/** TEST-STATUS-01: "!" status predicate, isolated from acceptance logic. */
final class RuleStatusTest extends TestCase
{
    public function testStatusAInpatientMainMarkedMatches(): void
    {
        $case = Fixtures::statusCase('STATUS-A', 'inpatient', null, ['Z01.6' => false]);

        self::assertTrue(RuleStatus::matches($case, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusBHospitalOutpatientLkfScoredMatches(): void
    {
        $case = Fixtures::statusCase('STATUS-B', 'hospital_outpatient', true, ['Z01.6' => false]);

        self::assertTrue(RuleStatus::matches($case, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusCHospitalOutpatientNotLkfScoredDoesNotMatch(): void
    {
        $case = Fixtures::statusCase('STATUS-C', 'hospital_outpatient', false, ['Z01.6' => true]);

        self::assertFalse(RuleStatus::matches($case, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusDAdditionalDiagnosisInpatientDoesNotMatch(): void
    {
        $case = Fixtures::statusCase('STATUS-D', 'inpatient', null, ['Z01.6' => false], diagnosisRole: 'additional');

        self::assertFalse(RuleStatus::matches($case, Fixtures::record('Z01.6', '!')));
    }

    public function testStatusENoMarkerInpatientDoesNotMatch(): void
    {
        $case = Fixtures::statusCase('STATUS-E', 'inpatient', null, ['Z00.0' => false]);

        self::assertFalse(RuleStatus::matches($case, Fixtures::record('Z00.0', null)));
    }
}
